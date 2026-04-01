<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireAuth();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: ' . url('orders.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT o.*, u.name as courier_name FROM orders o LEFT JOIN users u ON u.id = o.courier_id WHERE o.id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ' . url('orders.php'));
    exit;
}

$role = $_SESSION['user_role'] ?? '';
if ($role === 'courier' && (int)$order['courier_id'] !== (int)$_SESSION['user_id']) {
    header('Location: ' . url('orders.php'));
    exit;
}

$couriers = [];
if ($role === 'admin') {
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'courier' ORDER BY name");
    $couriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare('
    SELECT oi.quantity, oi.price, p.name as product_name, pv.color, pv.size
    FROM order_items oi
    JOIN product_variations pv ON pv.id = oi.variation_id
    JOIN products p ON p.id = pv.product_id
    WHERE oi.order_id = ?
');
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = ['new' => 'Новый', 'assigned' => 'Назначен', 'completed' => 'Выполнен', 'canceled' => 'Отменён'];

$canChangeStatus = false;
$allowedNewStatuses = [];
if ($role === 'admin') {
    $canChangeStatus = true;
    $allowedNewStatuses = ['new', 'assigned', 'completed', 'canceled'];
} elseif ($role === 'courier' && $order['status'] === 'assigned') {
    $canChangeStatus = true;
    $allowedNewStatuses = ['completed', 'canceled'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Заказ #<?= $orderId ?> — CRM</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 16px; line-height: 1.5; padding: 1rem; background: #f5f5f5; max-width: 640px; margin: 0 auto; }
        .back { display: inline-block; margin-bottom: 1rem; color: #333; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; margin-bottom: 1rem; }
        .row { margin-bottom: 0.5rem; }
        .label { font-size: 0.85rem; color: #666; }
        .value { font-weight: 500; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        .items-table th, .items-table td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #eee; }
        .items-table th { font-size: 0.85rem; color: #666; }
        .status-form { margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .status-form button { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }
        .btn-completed { background: #2e7d32; color: #fff; }
        .btn-canceled { background: #c62828; color: #fff; }
        .btn-assigned { background: #e65100; color: #fff; }
        .btn-new { background: #1565c0; color: #fff; }
        .message { padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
        .message.success { background: #e8f5e9; color: #2e7d32; }
        .message.error { background: #ffebee; color: #c62828; }
        .accept-form { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .accept-form input[type="number"] { width: 120px; margin-bottom: 0; }
        .status-form button { min-height: 44px; }
    </style>
</head>
<body>
    <div class="page">
    <?php $pageTitle = 'Заказ #' . $orderId; include __DIR__ . '/includes/header.php'; ?>

    <?php if (!empty($_GET['updated'])): ?>
        <div class="message success">Статус обновлён</div>
    <?php endif; ?>
    <?php if (!empty($_GET['accepted'])): ?>
        <div class="message success">Деньги приняты в кассу</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="card">
        <h1>Заказ #<?= $orderId ?></h1>
        <div class="row"><span class="label">Клиент:</span> <span class="value"><?= htmlspecialchars($order['customer_name']) ?></span></div>
        <div class="row"><span class="label">Телефон:</span> <span class="value"><?= htmlspecialchars($order['phone']) ?></span></div>
        <div class="row"><span class="label">Адрес:</span> <span class="value"><?= htmlspecialchars($order['address']) ?></span></div>
        <div class="row"><span class="label">Статус:</span> <span class="value"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span></div>
        <?php if ($order['courier_name']): ?>
        <div class="row"><span class="label">Курьер:</span> <span class="value"><?= htmlspecialchars($order['courier_name']) ?></span></div>
        <?php endif; ?>
        <div class="row"><span class="label">Сумма заказа:</span> <span class="value"><?= number_format($order['total_price'], 2, '.', ' ') ?> ₽</span></div>
        <?php if ((int)$order['cash_added'] === 1): ?>
        <div class="row"><span class="label">Деньги в кассу:</span> <span class="value" style="color:#2e7d32;">✓ Принято</span></div>
        <?php endif; ?>
    </div>

    <?php if ($role === 'admin' && $order['status'] === 'completed' && (int)$order['cash_added'] === 0): ?>
    <div class="card">
        <h2 style="font-size:1.1rem; margin-bottom:0.75rem;">Принять деньги</h2>
        <p style="font-size:0.9rem; color:#666; margin-bottom:0.75rem;">Курьер доставил заказ. Введите сумму, которую получили (с учётом расходов курьера):</p>
        <form id="acceptForm" class="status-form accept-form">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <input type="number" name="amount" step="0.01" min="0.01" value="<?= number_format($order['total_price'], 2, '.', '') ?>" required
                style="padding:0.5rem; border-radius:6px; border:1px solid #ddd; width:120px;">
            <span>₽</span>
            <button type="submit" class="btn-completed">Получил товар</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2 style="font-size:1.1rem; margin-bottom:0.75rem;">Товары</h2>
        <table class="items-table">
            <thead>
                <tr><th>Товар</th><th>Кол-во</th><th>Цена</th><th>Сумма</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    $varLabel = array_filter([$item['product_name'], $item['color'], $item['size']]);
                    $itemLabel = implode(' — ', $varLabel);
                    $sum = $item['quantity'] * $item['price'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($itemLabel) ?></td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 2, '.', ' ') ?> ₽</td>
                    <td><?= number_format($sum, 2, '.', ' ') ?> ₽</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($canChangeStatus && $order['status'] !== 'completed' && $order['status'] !== 'canceled'): ?>
    <div class="card">
        <h2 style="font-size:1.1rem; margin-bottom:0.75rem;">Изменить статус</h2>
        <div class="status-form">
            <?php if (in_array('completed', $allowedNewStatuses)): ?>
            <form method="POST" action="<?= url('api/order_status.php') ?>" class="status-form-inline" data-status="completed">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="status" value="completed">
                <button type="submit" class="btn-completed">Доставлен</button>
            </form>
            <?php endif; ?>
            <?php if (in_array('canceled', $allowedNewStatuses)): ?>
            <form method="POST" action="<?= url('api/order_status.php') ?>" class="status-form-inline" data-status="canceled">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="status" value="canceled">
                <button type="submit" class="btn-canceled">Отменить</button>
            </form>
            <?php endif; ?>
            <?php if ($role === 'admin' && in_array('assigned', $allowedNewStatuses) && !empty($couriers)): ?>
            <form method="POST" action="<?= url('api/order_status.php') ?>" class="status-form-inline assign-form">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="status" value="assigned">
                <select name="courier_id" required style="padding:0.5rem; border-radius:6px; border:1px solid #ddd;">
                    <option value="">— Выберите курьера —</option>
                    <?php foreach ($couriers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-assigned">Назначить</button>
            </form>
            <?php endif; ?>
            <?php if ($role === 'admin' && in_array('new', $allowedNewStatuses)): ?>
            <form method="POST" action="<?= url('api/order_status.php') ?>" class="status-form-inline">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="status" value="new">
                <button type="submit" class="btn-new">В новый</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <script>
    document.querySelectorAll('.status-form-inline').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(form);
            fetch(form.action, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) location.href = '<?= url('order_view.php?id=' . $orderId) ?>&updated=1' + (window.location.search.includes('from=requests') ? '&from=requests' : '');
                    else alert(d.error || 'Ошибка');
                })
                .catch(function() { alert('Ошибка сети'); });
        });
    });
    </script>
    <?php endif; ?>
    <?php if ($role === 'admin' && $order['status'] === 'completed' && (int)$order['cash_added'] === 0): ?>
    <script>
    document.getElementById('acceptForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        var fromParam = '<?= (!empty($_GET['from']) && $_GET['from'] === 'requests') ? '&from=requests' : '' ?>';
        fetch('<?= url('api/order_accept.php') ?>', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) location.href = '<?= url('order_view.php?id=' . $orderId) ?>&accepted=1' + fromParam;
                else alert(d.error || 'Ошибка');
            })
            .catch(function() { alert('Ошибка сети'); });
    });
    </script>
    <?php endif; ?>
    </div>
</body>
</html>

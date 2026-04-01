<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireAuth();

$role = $_SESSION['user_role'] ?? '';
$isAdmin = ($role === 'admin');
$stats = [];
$courierStats = [];
$courierOrders = [];

if ($isAdmin) {
    $stats = [
        'products_count' => 0,
        'variations_count' => 0,
        'total_stock' => 0,
        'orders_count' => 0,
        'cashbox_total' => 0,
        'pending_accept' => 0,
        'orders_today' => 0,
        'best_product' => null,
        'canceled_count' => 0,
    ];
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM products');
        $stats['products_count'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->query('SELECT COUNT(*) FROM product_variations');
        $stats['variations_count'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM product_variations');
        $stats['total_stock'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->query('SELECT COUNT(*) FROM orders');
        $stats['orders_count'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM cashbox');
        $stats['cashbox_total'] = (float)$stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'completed' AND cash_added = 0");
        $stats['pending_accept'] = (float)$stmt->fetchColumn();
        $stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()');
        $stats['orders_today'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'canceled'");
        $stats['canceled_count'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->query("
            SELECT p.name, SUM(oi.quantity) AS total_qty
            FROM order_items oi
            JOIN product_variations pv ON pv.id = oi.variation_id
            JOIN products p ON p.id = pv.product_id
            JOIN orders o ON o.id = oi.order_id AND o.status = 'completed'
            GROUP BY p.id, p.name
            ORDER BY total_qty DESC
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['best_product'] = $row ? $row['name'] . ' (' . (int)$row['total_qty'] . ' шт.)' : '—';
    } catch (PDOException $e) {
        $stats['error'] = 'Ошибка загрузки данных';
    }
} else {
    $courierId = (int)$_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE courier_id = ? AND status = 'completed'");
        $stmt->execute([$courierId]);
        $courierStats['delivered'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE courier_id = ? AND status = 'canceled'");
        $stmt->execute([$courierId]);
        $courierStats['canceled'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE courier_id = ? AND status = 'assigned'");
        $stmt->execute([$courierId]);
        $courierStats['assigned'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT id, customer_name, address, status, total_price, created_at FROM orders WHERE courier_id = ? ORDER BY created_at DESC LIMIT 10');
        $stmt->execute([$courierId]);
        $courierOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $courierStats['error'] = 'Ошибка загрузки данных';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Dashboard — CRM</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            padding: 1rem;
            background: #f5f5f5;
            max-width: 640px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        h1 { font-size: 1.5rem; color: #333; }
        .logout { color: #666; text-decoration: none; font-size: 0.9rem; }
        .logout:hover { color: #333; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 1.25rem;
        }
        .card.full { grid-column: 1 / -1; }
        .card-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        .card-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        .card-value.small { font-size: 1.1rem; }
        .nav {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .nav a {
            display: block;
            padding: 0.875rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }
        .nav a:hover { background: #f0f0f0; }
        .order-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .order-card { display: block; padding: 0.75rem; background: #f9f9f9; border-radius: 6px; text-decoration: none; color: inherit; }
        .order-card:hover { background: #eee; }
        .order-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.25rem; }
        .order-id { font-weight: 600; font-size: 0.95rem; }
        .order-status { font-size: 0.8rem; padding: 0.15rem 0.4rem; border-radius: 4px; }
        .order-status.assigned { background: #fff3e0; color: #e65100; }
        .order-status.completed { background: #e8f5e9; color: #2e7d32; }
        .order-status.canceled { background: #ffebee; color: #c62828; }
        .order-info { font-size: 0.85rem; color: #666; }
        .order-total { font-weight: 600; margin-top: 0.25rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="page">
    <?php $pageTitle = $isAdmin ? 'Главная' : 'Мои заказы'; include __DIR__ . '/includes/header.php'; ?>
    <?php if ($isAdmin): ?>
        <?php if (!empty($stats['error'])): ?>
            <div class="card full"><?= htmlspecialchars($stats['error']) ?></div>
        <?php else: ?>
        <div class="grid">
            <div class="card">
                <div class="card-label">Наименований</div>
                <div class="card-value"><?= $stats['products_count'] ?></div>
            </div>
            <div class="card">
                <div class="card-label">Позиций (вариаций)</div>
                <div class="card-value"><?= $stats['variations_count'] ?></div>
            </div>
            <div class="card">
                <div class="card-label">Остаток на складе</div>
                <div class="card-value"><?= $stats['total_stock'] ?> шт.</div>
            </div>
            <div class="card">
                <div class="card-label">Заказов</div>
                <div class="card-value"><?= $stats['orders_count'] ?></div>
            </div>
        <div class="card full">
            <div class="card-label">В кассе</div>
            <div class="card-value"><?= number_format($stats['cashbox_total'], 2, '.', ' ') ?> ₽</div>
        </div>
        <div class="card full">
            <div class="card-label">Ожидает приёма</div>
            <div class="card-value"><?= number_format($stats['pending_accept'], 2, '.', ' ') ?> ₽</div>
        </div>
            <div class="card">
                <div class="card-label">Заказов сегодня</div>
                <div class="card-value"><?= $stats['orders_today'] ?></div>
            </div>
            <div class="card">
                <div class="card-label">Отказов</div>
                <div class="card-value"><?= $stats['canceled_count'] ?></div>
            </div>
            <div class="card full">
                <div class="card-label">Самый продаваемый товар</div>
                <div class="card-value small"><?= htmlspecialchars($stats['best_product']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (!empty($courierStats['error'])): ?>
            <div class="card full"><?= htmlspecialchars($courierStats['error']) ?></div>
        <?php else: ?>
        <div class="grid">
            <div class="card">
                <div class="card-label">К назначению</div>
                <div class="card-value"><?= $courierStats['assigned'] ?? 0 ?></div>
            </div>
            <div class="card">
                <div class="card-label">Доставлено</div>
                <div class="card-value"><?= $courierStats['delivered'] ?? 0 ?></div>
            </div>
            <div class="card">
                <div class="card-label">Отказов</div>
                <div class="card-value"><?= $courierStats['canceled'] ?? 0 ?></div>
            </div>
        </div>
        <div class="card full" style="margin-top:1rem;">
            <h2 style="font-size:1.1rem; margin-bottom:0.75rem;">История заказов</h2>
            <?php if (empty($courierOrders)): ?>
                <p style="color:#666;">Нет заказов</p>
            <?php else: ?>
            <div class="order-list">
                <?php
                $statusLabels = ['new' => 'Новый', 'assigned' => 'Назначен', 'completed' => 'Доставлен', 'canceled' => 'Отменён'];
                foreach ($courierOrders as $o):
                ?>
                <a href="<?= url('order_view.php?id=' . $o['id']) ?>" class="order-card">
                    <div class="order-header">
                        <span class="order-id">#<?= (int)$o['id'] ?> <?= htmlspecialchars($o['customer_name']) ?></span>
                        <span class="order-status <?= $o['status'] ?>"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span>
                    </div>
                    <div class="order-info"><?= htmlspecialchars($o['address']) ?></div>
                    <div class="order-total"><?= number_format($o['total_price'], 2, '.', ' ') ?> ₽</div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</body>
</html>

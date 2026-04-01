<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireAuth();

$statusFilter = $_GET['status'] ?? '';
$role = $_SESSION['user_role'] ?? '';

$where = [];
$params = [];
if ($statusFilter && in_array($statusFilter, ['new', 'assigned', 'completed', 'canceled'])) {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($role === 'courier') {
    $where[] = 'o.courier_id = ?';
    $params[] = $_SESSION['user_id'];
}

$sql = 'SELECT o.id, o.customer_name, o.phone, o.address, o.status, o.total_price, o.created_at
        FROM orders o
        ' . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . '
        ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'new' => 'Новый',
    'assigned' => 'Назначен',
    'completed' => 'Выполнен',
    'canceled' => 'Отменён',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Заказы — CRM</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 16px; line-height: 1.5; padding: 1rem; background: #f5f5f5; max-width: 800px; margin: 0 auto; }
        .back { display: inline-block; margin-bottom: 1rem; color: #333; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; color: #333; }
        .filters { margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .filters a { padding: 0.5rem 0.75rem; background: #fff; border-radius: 6px; text-decoration: none; color: #333; font-size: 0.9rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .filters a:hover, .filters a.active { background: #333; color: #fff; }
        .order-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .order-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 1rem;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .order-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.12); }
        .order-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem; }
        .order-id { font-weight: 600; color: #333; }
        .order-status { font-size: 0.85rem; padding: 0.2rem 0.5rem; border-radius: 4px; }
        .order-status.new { background: #e3f2fd; color: #1565c0; }
        .order-status.assigned { background: #fff3e0; color: #e65100; }
        .order-status.completed { background: #e8f5e9; color: #2e7d32; }
        .order-status.canceled { background: #ffebee; color: #c62828; }
        .order-info { font-size: 0.9rem; color: #666; }
        .order-total { font-weight: 600; margin-top: 0.5rem; }
        .order-date-group { font-size: 0.9rem; font-weight: 600; color: #666; margin: 1rem 0 0.5rem; padding-bottom: 0.25rem; }
        .order-date-group:first-child { margin-top: 0; }
        .order-date { font-size: 0.8rem; color: #999; }
    </style>
</head>
<body>
    <div class="page">
    <?php $pageTitle = $role === 'admin' ? 'Заказы' : 'Мои заказы'; include __DIR__ . '/includes/header.php'; ?>
    <div class="filters">
        <a href="<?= url('orders.php') ?>" class="<?= !$statusFilter ? 'active' : '' ?>">Все</a>
        <a href="<?= url('orders.php?status=new') ?>" class="<?= $statusFilter === 'new' ? 'active' : '' ?>">Новые</a>
        <a href="<?= url('orders.php?status=assigned') ?>" class="<?= $statusFilter === 'assigned' ? 'active' : '' ?>">Назначенные</a>
        <a href="<?= url('orders.php?status=completed') ?>" class="<?= $statusFilter === 'completed' ? 'active' : '' ?>">Выполненные</a>
        <a href="<?= url('orders.php?status=canceled') ?>" class="<?= $statusFilter === 'canceled' ? 'active' : '' ?>">Отменённые</a>
    </div>
    <div class="order-list">
        <?php
        $lastDate = '';
        foreach ($orders as $o):
            $orderDate = date('Y-m-d', strtotime($o['created_at']));
            $dateLabel = $orderDate === date('Y-m-d') ? 'Сегодня' : ($orderDate === date('Y-m-d', strtotime('-1 day')) ? 'Вчера' : date('d.m.Y', strtotime($o['created_at'])));
            if ($dateLabel !== $lastDate):
                $lastDate = $dateLabel;
        ?>
        <div class="order-date-group"><?= $dateLabel ?></div>
        <?php endif; ?>
        <a href="<?= url('order_view.php?id=' . $o['id']) ?>" class="order-card">
            <div class="order-header">
                <span class="order-id">#<?= (int)$o['id'] ?> <?= htmlspecialchars($o['customer_name']) ?></span>
                <span class="order-status <?= $o['status'] ?>"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span>
            </div>
            <div class="order-info order-date"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></div>
            <div class="order-info"><?= htmlspecialchars($o['address']) ?></div>
            <div class="order-info"><?= htmlspecialchars($o['phone']) ?></div>
            <div class="order-total"><?= number_format($o['total_price'], 2, '.', ' ') ?> ₽</div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php if (empty($orders)): ?>
        <p class="empty">Нет заказов</p>
    <?php endif; ?>
    </div>
</body>
</html>

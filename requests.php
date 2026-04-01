<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireRole('admin');

$stmt = $pdo->query("
    SELECT o.id, o.customer_name, o.phone, o.address, o.total_price, o.created_at, u.name as courier_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.courier_id
    WHERE o.status = 'completed' AND o.cash_added = 0
    ORDER BY o.created_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Запросы — CRM</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
    <div class="page">
        <?php $pageTitle = 'Запросы'; include __DIR__ . '/includes/header.php'; ?>
        <p class="subtitle">Доставленные заказы, ожидающие приёма денег</p>
        <div class="card-list">
            <?php foreach ($requests as $r): ?>
            <a href="<?= url('order_view.php?id=' . $r['id'] . '&from=requests') ?>" class="card card-link">
                <div class="card-header">
                    <span class="card-title">#<?= (int)$r['id'] ?> <?= htmlspecialchars($r['customer_name']) ?></span>
                    <span class="badge badge-warning">Ожидает</span>
                </div>
                <div class="card-meta"><?= htmlspecialchars($r['address']) ?></div>
                <?php if ($r['courier_name']): ?>
                <div class="card-meta">Курьер: <?= htmlspecialchars($r['courier_name']) ?></div>
                <?php endif; ?>
                <div class="card-value"><?= number_format($r['total_price'], 2, '.', ' ') ?> ₽</div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if (empty($requests)): ?>
        <p class="empty">Нет заказов, ожидающих приёма</p>
        <?php endif; ?>
    </div>
</body>
</html>

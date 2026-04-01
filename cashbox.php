<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireRole('admin');

$stmt = $pdo->query('
    SELECT c.id, c.order_id, c.amount, c.created_at, o.customer_name, o.total_price
    FROM cashbox c
    JOIN orders o ON o.id = c.order_id
    ORDER BY c.created_at DESC
');
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM cashbox');
$total = (float)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>История кассы — CRM</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <style>
        .total-card { background: #e8f5e9; border-left: 4px solid #2e7d32; }
        .history-list { margin: -1rem -1.25rem; }
        .history-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #eee; gap: 0.5rem; flex-wrap: wrap; -webkit-tap-highlight-color: transparent; }
        .history-item:hover, .history-item:active { background: #f9f9f9; }
        .history-item:last-child { border-bottom: none; }
        .history-info { flex: 1; min-width: 0; }
        .history-amount { font-weight: 600; color: #2e7d32; white-space: nowrap; }
        .history-date { font-size: 0.85rem; color: #666; }
    </style>
</head>
<body>
    <div class="page">
        <?php $pageTitle = 'История кассы'; include __DIR__ . '/includes/header.php'; ?>
        <div class="card total-card" style="margin-bottom: 1rem;">
            <div class="card-label">Всего в кассе</div>
            <div class="card-value"><?= number_format($total, 2, '.', ' ') ?> ₽</div>
        </div>
        <div class="card">
            <h2 style="font-size: 1.1rem; margin-bottom: 0.75rem;">Пополнения</h2>
            <?php if (empty($history)): ?>
                <p class="empty">Нет записей</p>
            <?php else: ?>
            <div class="history-list">
                <?php foreach ($history as $h): ?>
                <a href="<?= url('order_view.php?id=' . $h['order_id']) ?>" class="history-item" style="text-decoration:none; color:inherit; display:flex;">
                    <div class="history-info">
                        <div>Заказ #<?= (int)$h['order_id'] ?> — <?= htmlspecialchars($h['customer_name']) ?></div>
                        <div class="history-date"><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></div>
                    </div>
                    <div class="history-amount">+<?= number_format($h['amount'], 2, '.', ' ') ?> ₽</div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireRole('admin');

$stmt = $pdo->query('
    SELECT p.id, p.name,
        (SELECT COUNT(*) FROM product_variations pv WHERE pv.product_id = p.id) as variations_count,
        (SELECT COALESCE(SUM(pv.quantity), 0) FROM product_variations pv WHERE pv.product_id = p.id) as total_stock
    FROM products p
    ORDER BY p.name
');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as &$p) {
    $stmt = $pdo->prepare('SELECT id, color, size, quantity, price FROM product_variations WHERE product_id = ? ORDER BY color, size');
    $stmt->execute([$p['id']]);
    $p['variations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($p);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Товары — CRM</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 16px; line-height: 1.5; padding: 1rem; background: #f5f5f5; max-width: 800px; margin: 0 auto; }
        .back { display: inline-block; margin-bottom: 1rem; color: #333; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; color: #333; }
        .product-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 1rem; margin-bottom: 1rem; }
        .product-name { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; }
        .product-meta { font-size: 0.85rem; color: #666; margin-bottom: 0.75rem; }
        .var-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .var-table th, .var-table td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #eee; }
        .var-table th { color: #666; font-weight: 500; }
    </style>
</head>
<body>
    <div class="page">
    <?php $pageTitle = 'Товары'; include __DIR__ . '/includes/header.php'; ?>
    <?php foreach ($products as $p): ?>
    <div class="product-card">
        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="product-meta">Позиций: <?= (int)$p['variations_count'] ?> · На складе: <?= (int)$p['total_stock'] ?> шт.</div>
        <table class="var-table">
            <thead><tr><th>Цвет</th><th>Размер</th><th>Остаток</th><th>Цена</th></tr></thead>
            <tbody>
                <?php foreach ($p['variations'] as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['color'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($v['size'] ?: '—') ?></td>
                    <td><?= (int)$v['quantity'] ?></td>
                    <td><?= number_format($v['price'], 2, '.', ' ') ?> ₽</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    <?php if (empty($products)): ?>
        <p style="color:#666;">Нет товаров</p>
    <?php endif; ?>
    </div>
</body>
</html>

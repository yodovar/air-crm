<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireRole('admin');

$error = '';
$success = '';
$variations = [];

try {
    $stmt = $pdo->query('
        SELECT pv.id, p.name as product_name, pv.color, pv.size, pv.quantity, pv.price
        FROM product_variations pv
        JOIN products p ON p.id = pv.product_id
        ORDER BY p.name, pv.color, pv.size
    ');
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Ошибка загрузки товаров';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $cashAdded = 0;
    $variationIds = $_POST['variation_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];

    if (empty($customerName) || empty($phone) || empty($address)) {
        $error = 'Заполните данные клиента';
    } else {
        $items = [];
        $totalPrice = 0;
        for ($i = 0; $i < count($variationIds); $i++) {
            $vid = (int)$variationIds[$i];
            $qty = (int)($quantities[$i] ?? 0);
            $price = (float)str_replace(',', '.', $prices[$i] ?? 0);
            if ($vid > 0 && $qty > 0 && $price > 0) {
                $items[] = ['variation_id' => $vid, 'quantity' => $qty, 'price' => $price];
                $totalPrice += $qty * $price;
            }
        }

        if (empty($items)) {
            $error = 'Добавьте хотя бы один товар';
        } else {
            try {
                $pdo->beginTransaction();

                foreach ($items as $item) {
                    $stmt = $pdo->prepare('SELECT quantity FROM product_variations WHERE id = ? FOR UPDATE');
                    $stmt->execute([$item['variation_id']]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$row || $row['quantity'] < $item['quantity']) {
                        throw new Exception('Недостаточно товара на складе (вариация ID ' . $item['variation_id'] . ')');
                    }
                }

                $stmt = $pdo->prepare('INSERT INTO orders (customer_name, phone, address, total_price, cash_added) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$customerName, $phone, $address, $totalPrice, $cashAdded]);
                $orderId = $pdo->lastInsertId();

                $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, variation_id, quantity, price) VALUES (?, ?, ?, ?)');
                foreach ($items as $item) {
                    $stmtItem->execute([$orderId, $item['variation_id'], $item['quantity'], $item['price']]);
                }

                $pdo->commit();
                $success = 'Заказ #' . $orderId . ' создан';
                $_POST = [];
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Ошибка при сохранении заказа';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Создать заказ — CRM</title>
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
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; color: #333; }
        .message { padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
        .message.error { background: #fee; color: #c00; }
        .message.success { background: #efe; color: #060; }
        label { display: block; margin-bottom: 0.25rem; font-weight: 500; color: #444; }
        input, select {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        input:focus, select:focus { outline: none; border-color: #333; }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .back { display: inline-block; margin-bottom: 1rem; color: #333; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        .variation-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 0.5rem;
            align-items: end;
            margin-bottom: 0.75rem;
        }
        .variation-item .field { min-width: 0; }
        .variation-item .field input { margin-bottom: 0; }
        .variation-item .stock { font-size: 0.85rem; color: #666; }
        .btn-add { padding: 0.5rem 1rem; background: #333; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }
        .btn-add:hover { background: #555; }
        .btn-remove { padding: 0.5rem 0.75rem; background: #c00; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        .btn-remove:hover { background: #a00; }
        .btn-submit { width: 100%; padding: 0.875rem; background: #333; color: #fff; border: none; border-radius: 6px; font-size: 1rem; font-weight: 500; cursor: pointer; }
        .btn-submit:hover { background: #555; }
        .total { font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; }
        .checkbox-wrap { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .checkbox-wrap input { width: auto; margin: 0; }
        #variationsContainer { margin-top: 1rem; }
        .loading { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="page">
    <?php $pageTitle = 'Создать заказ'; include __DIR__ . '/includes/header.php'; ?>
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST" action="" id="orderForm">
        <div class="card">
            <label for="customer_name">Имя клиента</label>
            <input type="text" id="customer_name" name="customer_name" required
                value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone" required
                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            <label for="address">Адрес</label>
            <input type="text" id="address" name="address" required
                value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        </div>
        <div class="card">
            <label for="variation_id">Товар (цвет / размер)</label>
            <select id="variation_id" name="variation_id">
                <option value="">— Выберите позицию —</option>
                <?php foreach ($variations as $v): 
                    $label = $v['product_name'];
                    if ($v['color'] || $v['size']) $label .= ' — ' . trim(($v['color'] ?? '') . ' ' . ($v['size'] ?? ''));
                    $label .= ' (остаток: ' . (int)$v['quantity'] . ', ' . number_format($v['price'], 0, '.', ' ') . ' ₽)';
                ?>
                    <option value="<?= (int)$v['id'] ?>" data-quantity="<?= (int)$v['quantity'] ?>" data-price="<?= (float)$v['price'] ?>" data-label="<?= htmlspecialchars($v['product_name'] . ($v['color'] || $v['size'] ? ' — ' . trim(($v['color'] ?? '') . ' ' . ($v['size'] ?? '')) : '')) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="item_quantity" style="margin-top:0.75rem;">Количество</label>
            <input type="number" id="item_quantity" min="1" value="1" placeholder="Кол-во">
            <button type="button" class="btn-add" id="addToOrder" style="margin-top:0.5rem;">Добавить в заказ</button>
        </div>
        <div class="card" id="orderItemsCard" style="display:none;">
            <h2 style="font-size:1.1rem; margin-bottom:1rem;">Товары в заказе</h2>
            <div id="orderItems"></div>
            <div class="total" id="totalPrice">Итого: 0 ₽</div>
        </div>
        <button type="submit" class="btn-submit" id="submitBtn" disabled>Создать заказ</button>
    </form>
    <script src="<?= url('js/orders_create.js') ?>"></script>
    </div>
</body>
</html>

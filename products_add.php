<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';
requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'Введите название товара';
    } else {
        $variations = [];
        $colors = $_POST['color'] ?? [];
        $sizes = $_POST['size'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];

        for ($i = 0; $i < max(1, count($colors)); $i++) {
            $qty = (int)($quantities[$i] ?? 0);
            $price = (float)str_replace(',', '.', $prices[$i] ?? 0);
            if ($qty > 0 && $price > 0) {
                $variations[] = [
                    'color' => trim($colors[$i] ?? '') ?: null,
                    'size' => trim($sizes[$i] ?? '') ?: null,
                    'quantity' => $qty,
                    'price' => $price,
                ];
            }
        }

        if (empty($variations)) {
            $error = 'Добавьте хотя бы одну вариацию с количеством и ценой';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('INSERT INTO products (name) VALUES (?)');
                $stmt->execute([$name]);
                $productId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO product_variations (product_id, color, size, quantity, price) VALUES (?, ?, ?, ?, ?)');
                foreach ($variations as $v) {
                    $stmt->execute([$productId, $v['color'], $v['size'], $v['quantity'], $v['price']]);
                }

                $pdo->commit();
                $success = 'Товар успешно добавлен';
                $_POST = [];
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Ошибка при сохранении';
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
    <title>Добавить товар — CRM</title>
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
        .message {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
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
        input:focus { outline: none; border-color: #333; }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .variations-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .variations-header h2 { font-size: 1.1rem; }
        .variation-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 0.5rem;
            align-items: end;
            margin-bottom: 0.75rem;
        }
        .variation-row .field { min-width: 0; }
        .variation-row .field input { margin-bottom: 0; }
        .btn-remove {
            padding: 0.5rem 0.75rem;
            background: #c00;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-remove:hover { background: #a00; }
        .btn-add {
            padding: 0.5rem 1rem;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-add:hover { background: #555; }
        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-submit:hover { background: #555; }
        .back { display: inline-block; margin-bottom: 1rem; color: #333; text-decoration: none; }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page">
    <?php $pageTitle = 'Добавить товар'; include __DIR__ . '/includes/header.php'; ?>
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="card">
            <label for="name">Название товара</label>
            <input type="text" id="name" name="name" required
                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="card">
            <div class="variations-header">
                <h2>Вариации</h2>
                <button type="button" class="btn-add" id="addVariation">+ Добавить</button>
            </div>
            <div id="variations">
                <div class="variation-row" data-index="0">
                    <div class="field">
                        <label>Цвет</label>
                        <input type="text" name="color[]" placeholder="Черный">
                    </div>
                    <div class="field">
                        <label>Размер</label>
                        <input type="text" name="size[]" placeholder="M">
                    </div>
                    <div class="field">
                        <button type="button" class="btn-remove btn-remove-row" disabled>×</button>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label>Количество</label>
                        <input type="number" name="quantity[]" min="0" value="0" required>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label>Цена</label>
                        <input type="text" name="price[]" placeholder="599.00" required>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn-submit">Сохранить товар</button>
    </form>
    <script src="<?= url('js/products_add.js') ?>"></script>
    </div>
</body>
</html>

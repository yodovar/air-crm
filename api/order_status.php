<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? $_GET['status'] ?? '');

if (!$orderId || !in_array($newStatus, ['new', 'assigned', 'completed', 'canceled'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$role = $_SESSION['user_role'] ?? '';
$isAdmin = ($role === 'admin');
$isCourier = ($role === 'courier');

try {
    $stmt = $pdo->prepare('SELECT id, status, cash_added, total_price FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    $currentStatus = $order['status'];

    if ($isCourier) {
        $allowed = [
            'assigned' => ['completed', 'canceled'],
        ];
        if (!isset($allowed[$currentStatus]) || !in_array($newStatus, $allowed[$currentStatus])) {
            echo json_encode(['success' => false, 'error' => 'Courier can only change assigned → completed or canceled']);
            exit;
        }
    } elseif (!$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    if ($currentStatus === $newStatus) {
        echo json_encode(['success' => true, 'message' => 'Status unchanged']);
        exit;
    }

    if ($newStatus === 'assigned') {
        $courierId = (int)($_POST['courier_id'] ?? $_GET['courier_id'] ?? 0);
        if (!$courierId) {
            echo json_encode(['success' => false, 'error' => 'Выберите курьера']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = ?');
        $stmt->execute([$courierId, 'courier']);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Курьер не найден']);
            exit;
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT variation_id, quantity FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            $stmt = $pdo->prepare('SELECT quantity FROM product_variations WHERE id = ? FOR UPDATE');
            $stmt->execute([$item['variation_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['quantity'] < $item['quantity']) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Недостаточно товара на складе']);
                exit;
            }
        }
        foreach ($items as $item) {
            $stmt = $pdo->prepare('UPDATE product_variations SET quantity = quantity - ? WHERE id = ?');
            $stmt->execute([$item['quantity'], $item['variation_id']]);
        }
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, courier_id = ? WHERE id = ?');
        $stmt->execute([$newStatus, $courierId, $orderId]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Заказ назначен']);
        exit;
    }

    if ($newStatus === 'completed') {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        echo json_encode(['success' => true, 'message' => 'Заказ доставлен']);
        exit;
    }

    if ($newStatus === 'canceled') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT variation_id, quantity FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            $stmt = $pdo->prepare('UPDATE product_variations SET quantity = quantity + ? WHERE id = ?');
            $stmt->execute([$item['quantity'], $item['variation_id']]);
        }
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Заказ отменён, товар возвращён на склад']);
        exit;
    }

    if ($newStatus === 'new' && $currentStatus === 'assigned') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT variation_id, quantity FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            $stmt = $pdo->prepare('UPDATE product_variations SET quantity = quantity + ? WHERE id = ?');
            $stmt->execute([$item['quantity'], $item['variation_id']]);
        }
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, courier_id = NULL WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Товар возвращён на склад']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE orders SET status = ?, courier_id = NULL WHERE id = ?');
    $stmt->execute([$newStatus, $orderId]);
    echo json_encode(['success' => true, 'message' => 'Status updated']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

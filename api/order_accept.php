<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$amount = (float)str_replace(',', '.', $_POST['amount'] ?? 0);

if (!$orderId || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Укажите сумму']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, status, cash_added FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Заказ не найден']);
        exit;
    }

    if ($order['status'] !== 'completed') {
        echo json_encode(['success' => false, 'error' => 'Только для доставленных заказов']);
        exit;
    }

    if ((int)$order['cash_added'] === 1) {
        echo json_encode(['success' => false, 'error' => 'Деньги уже приняты']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO cashbox (order_id, amount) VALUES (?, ?)');
    $stmt->execute([$orderId, $amount]);

    $stmt = $pdo->prepare('UPDATE orders SET cash_added = 1 WHERE id = ?');
    $stmt->execute([$orderId]);

    echo json_encode(['success' => true, 'message' => 'Деньги приняты в кассу']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'error' => 'Деньги уже приняты по этому заказу']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка сохранения']);
    }
}

<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$productId = (int)($_GET['product_id'] ?? 0);
if (!$productId) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT id, color, size, quantity, price
        FROM product_variations
        WHERE product_id = ?
        ORDER BY color, size
    ');
    $stmt->execute([$productId]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['variations' => $variations]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

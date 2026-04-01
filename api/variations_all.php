<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query('
        SELECT pv.id, p.name as product_name, pv.color, pv.size, pv.quantity, pv.price
        FROM product_variations pv
        JOIN products p ON p.id = pv.product_id
        ORDER BY p.name, pv.color, pv.size
    ');
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['variations' => $variations]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

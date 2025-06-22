<?php
include '../cors.php';
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $data = json_decode(file_get_contents("php://input"), true);
    $productId = $data['id'] ?? null;

    if ($productId === null) {
        echo json_encode(['success' => false, 'error' => 'Missing product ID']);
        exit;
    }

    // set the available field to 0 (soft delete)
    try {
        $stmt = $pdo->prepare("UPDATE products SET available = 0 WHERE id = ?");
        $stmt->execute([$productId]);

        echo json_encode(['success' => true, 'message' => 'Product soft-deleted']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

<?php
include './cors.php';
include './config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $stmt = $pdo->query("SELECT id, name FROM sizes ORDER BY id");
    $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($sizes);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

<?php
include './cors.php';
include './config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $data = json_decode(file_get_contents("php://input"), true);

    $order_id = intval($data['order_id']);
    $new_status = $data['new_status'];

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error", "message" => $e->getMessage()]);
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access"]);
}

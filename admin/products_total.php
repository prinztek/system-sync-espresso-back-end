<?php
// Include CORS headers to allow cross-origin requests
include '../cors.php';
// Include database configuration and connection
include '../config/db.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] === "GET" && $_SESSION['role'] === "admin") {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM Products");
        $totalNumberofProducts = $stmt->fetchColumn();
        header('Content-Type: application/json');
        echo json_encode($totalNumberofProducts, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        // Log the error
        error_log("Error fetching products: " . $e->getMessage());

        // Send a 500 Internal Server Error response
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
    }
}

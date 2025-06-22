<?php
include '../cors.php';
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("
        SELECT 
            p.id AS product_id,
            p.name,
            p.description,
            p.type,
            p.ingredients,
            p.image_url,
            p.available,
            p.stock_quantity,
            s.id AS size_id,
            s.name AS size_name,
            ps.price
        FROM products p
        JOIN product_sizes ps ON p.id = ps.product_id
        JOIN sizes s ON ps.size_id = s.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        http_response_code(404);
        echo json_encode(["error" => "Product not found"]);
        exit;
    }

    $product = [
        'id' => (int)$rows[0]['product_id'],
        'name' => $rows[0]['name'],
        'description' => $rows[0]['description'],
        'type' => $rows[0]['type'],
        'ingredients' => array_map('trim', explode(',', $rows[0]['ingredients'])),
        'image_url' => $rows[0]['image_url'],
        'available' => (bool)$rows[0]['available'],
        'stock_quantity' => (int)$rows[0]['stock_quantity'],
        'sizes' => [],
    ];

    foreach ($rows as $row) {
        $product['sizes'][] = [
            'id' => (int)$row['size_id'],
            'name' => $row['size_name'],
            'price' => (float)$row['price']
        ];
    }

    header("Content-Type: application/json");
    echo json_encode($product);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
}

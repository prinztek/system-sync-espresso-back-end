<?php
include './cors.php';
include './config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $data = json_decode(file_get_contents("php://input"), true);

    $product_id = intval($data['id']);
    $name = $data['name'];
    $description = $data['description'];
    $type = $data['type'];
    $ingredients = implode(",", array_map('trim', $data['ingredients']));
    $image_url = $data['image_url'];
    $available = intval($data['available']);
    $stock_quantity = intval($data['stock_quantity']);

    // Update product info
    $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, type=?, ingredients=?, image_url=?, available=?, stock_quantity=? WHERE id=?");
    $stmt->execute([$name, $description, $type, $ingredients, $image_url, $available, $stock_quantity, $product_id]);

    // Update each size price
    foreach ($data['sizes'] as $size) {
        $size_id = intval($size['id']);
        $price = floatval($size['price']);

        $priceStmt = $pdo->prepare("UPDATE product_sizes SET price = ? WHERE product_id = ? AND size_id = ?");
        $priceStmt->execute([$price, $product_id, $size_id]);
    }

    echo json_encode(["success" => true]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}

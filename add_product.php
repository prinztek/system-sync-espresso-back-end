<?php
include './cors.php';
include './config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $ingredients = $_POST['ingredients'] ?? '';
    $available = isset($_POST['available']) ? (int)$_POST['available'] : 0; // 0 = false, 1 = true
    $stock_quantity = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 0;

    // JSON string Sizes 
    $sizes = isset($_POST['sizes']) ? json_decode($_POST['sizes'], true) : [];

    try {
        $pdo->beginTransaction();

        // Handle image upload
        $imagePath = "";
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "uploads/";
            $filename = basename($_FILES['image']['name']);
            $targetPath = $uploadDir . uniqid() . "_" . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = "http://localhost/php-backend/" . $targetPath;
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "error" => "Failed to move uploaded file"]);
                exit;
            }
        }

        // Insert into products
        $stmt = $pdo->prepare("
            INSERT INTO products (name, description, type, ingredients, image_url, available, stock_quantity)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $type, $ingredients, $imagePath, $available, $stock_quantity]);
        $productId = $pdo->lastInsertId();

        // Insert into product_sizes
        $sizeStmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size_id, price) VALUES (?, ?, ?)");
        foreach ($sizes as $size) {
            $sizeStmt->execute([$productId, $size['id'], $size['price']]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Product added']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

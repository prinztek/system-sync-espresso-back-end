<?php
include '../cors.php';
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $ingredients = $_POST['ingredients'] ?? '';
    $available = isset($_POST['available']) ? (int)$_POST['available'] : 0; // 0 = false, 1 = true
    if ($type === 'Food') {
        $stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== ''
            ? (int)$_POST['stock_quantity']
            : null;

        if ($stock_quantity === null) {
            throw new Exception("Stock quantity is required for Food items.");
        }
    } else {
        $stock_quantity = null;
    }

    // JSON string Sizes 
    $sizes = isset($_POST['sizes']) ? json_decode($_POST['sizes'], true) : [];

    try {
        $pdo->beginTransaction();

        // Handle image upload
        $imagePath = "";
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['image']['name']);
            $newFileName = uniqid() . "_" . $filename;

            // Save to the parent /uploads/ directory from /admin/
            $relativeUploadDir = "../uploads/";
            $absolutePath = $relativeUploadDir . $newFileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $absolutePath)) {
                // Save correct public URL
                $imagePath = "http://localhost/php-backend/uploads/" . $newFileName;
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

<?php

session_start();
include './cors.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        echo json_encode($_SESSION['cart']);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $productId = $data['product_id'];
        $sizeId = $data['size_id'];
        $quantity = $data['quantity'];

        if (!$productId || !$sizeId || !$quantity) {
            http_response_code(400);
            echo json_encode(["error" => "Missing product_id, size_id or quantity"]);
            exit;
        }

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $productId && $item['size_id'] === $sizeId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $productId,
                'size_id' => $sizeId,
                'quantity' => $quantity
            ];
        }

        echo json_encode(["success" => true]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['product_id'], $data['size_id'], $data['quantity'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing product_id, size_id, or quantity"]);
            exit;
        }

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $data['product_id'] && $item['size_id'] === $data['size_id']) {
                $item['quantity'] = (int)$data['quantity'];
                break;
            }
        }

        echo json_encode(["success" => true, "cart" => $_SESSION['cart']]);
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $data);

        // Check if product_id and size_id are provided (for removing a specific item)
        if (isset($data['product_id'], $data['size_id'])) {
            // Remove specific item from the cart
            $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($item) use ($data) {
                return !(
                    $item['product_id'] === (int)$data['product_id'] &&
                    $item['size_id'] === (int)$data['size_id']
                );
            }));

            echo json_encode(["success" => true, "cart" => $_SESSION['cart']]);
            break;
        }

        // If no product_id and size_id are provided, clear the entire cart
        if (!isset($data['product_id'], $data['size_id'])) {
            // Clear the cart by resetting the session cart
            $_SESSION['cart'] = [];

            echo json_encode(["success" => true, "cart" => $_SESSION['cart']]);
            break;
        }

        break;
}

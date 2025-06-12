<?php

session_start();

include '../cors.php';
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        echo json_encode($_SESSION['cart']);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];
        $quantity = $data['quantity'];
        $product = $data['product'];

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = array_merge($product, ['quantity' => $quantity]);
        }
        echo json_encode(["success" => true]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $data['id']) {
                $item['quantity'] = $data['quantity'];
                break;
            }
        }
        echo json_encode(["success" => true]);
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $data);
        $id = $data['id'];
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($item) use ($id) {
            return $item['id'] !== $id;
        });
        echo json_encode(["success" => true]);
        break;
}

<?php
include './cors.php';
include './config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $data = json_decode(file_get_contents('php://input'), true);

    $formData = $data['formData'] ?? [];
    $name = $formData['name'] ?? '';
    $address = $formData['address'] ?? '';
    $cart = $data['cartItems'] ?? [];

    $pdo->beginTransaction(); // use transactions to ensure data integrity

    try {
        $totalPrice = 0;
        $detailedCart = [];

        // insert into orders
        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_address, total_price) VALUES (?, ?, ?, ?)");

        // insert total_price after calculating it

        // First calculate total and gather detailed info
        foreach ($cart as $item) {
            $product_id = $item['product_id'];
            $size_id = $item['size_id'];
            $quantity = $item['quantity'];

            // Get product name, size name, and price in one query
            $stmt = $pdo->prepare("
                SELECT p.name AS product_name, p.type, s.name AS size_name, p.stock_quantity, ps.price
                FROM product_sizes ps
                JOIN products p ON ps.product_id = p.id
                JOIN sizes s ON ps.size_id = s.id
                WHERE ps.product_id = ? AND ps.size_id = ?
            ");
            $stmt->execute([$product_id, $size_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception("Invalid product or size");
            }

            $itemTotal = $result['price'] * $quantity;
            $totalPrice += $itemTotal;

            $detailedCart[] = [
                'product_id' => $product_id,
                'product_name' => $result['product_name'],
                'size_id' => $size_id,
                'size_name' => $result['size_name'],
                'quantity' => $quantity,
                'stock_quantity' => $result['stock_quantity'],
                'price' => $result['price'],
                'type' => $result['type'],
                'total' => $itemTotal
            ];
        }

        // Insert the order
        $orderStmt->execute([$_SESSION['user_id'], $name, $address, $totalPrice]);
        if ($orderStmt->rowCount() === 0) {
            throw new Exception("Failed to insert order.");
        }

        $orderId = $pdo->lastInsertId();

        // Insert into order_items
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, size_id, quantity, price) VALUES (?, ?, ?, ?, ?)");

        foreach ($detailedCart as $item) {
            $itemStmt->execute([
                $orderId,
                $item['product_id'],
                $item['size_id'],
                $item['quantity'],
                $item['price']
            ]);
        }

        // Update stock quantities
        $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");

        foreach ($detailedCart as $item) {
            if ($item['type'] === "Food" && is_numeric($item['stock_quantity'])) {
                $stockStmt->execute([
                    (int) $item['quantity'],
                    $item['product_id'],
                    (int) $item['quantity']
                ]);

                if ($stockStmt->rowCount() === 0) {
                    throw new Exception("Not enough stock for product ID {$item['product_id']}");
                }
            }
        }



        $pdo->commit();

        // Get the order time
        $timeStmt = $pdo->prepare("SELECT created_at FROM orders WHERE id = ?");
        $timeStmt->execute([$orderId]);
        $createdAt = $timeStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully.',
            'order' => [
                'id' => $orderId,
                'name' => $name,
                'address' => $address,
                'total_price' => $totalPrice,
                'created_at' => $createdAt,
                'cartItems' => $detailedCart
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// response from submit order
// {
//     "success": true,
//     "message": "Order placed successfully.",
//     "order": {
//         "id": "12",
//         "name": "Prince James",
//         "address": "b",
//         "total_price": 2.5,
//         "created_at": "2025-06-15 23:26:57",
//         "cartItems": [
//             {
//                 "product_id": 1,
//                 "product_name": "Espresso",
//                 "size_id": 1,
//                 "size_name": "Single Shot",
//                 "quantity": 1,
//                 "price": "2.50",
//                 "total": 2.5
//             }
//         ]
//     }
// }
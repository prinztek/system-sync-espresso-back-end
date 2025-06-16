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

    $pdo->beginTransaction();

    try {
        $totalPrice = 0;
        $detailedCart = [];

        // insert into orders
        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_address, total_price) VALUES (?, ?, ?, ?)");

        // We'll insert total_price after calculating it, so delay this for now

        // First calculate total and gather detailed info
        foreach ($cart as $item) {
            $product_id = $item['product_id'];
            $size_id = $item['size_id'];
            $quantity = $item['quantity'];

            // Get product name, size name, and price in one query
            $stmt = $pdo->prepare("
                SELECT p.name AS product_name, s.name AS size_name, ps.price
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
                'price' => $result['price'],
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

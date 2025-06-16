<?php
include './cors.php';
include './config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['user_id'])) {
    try {
        $userId = $_SESSION['user_id'];
        $ordersStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $ordersStmt->execute([$userId]);
        $orders = [];

        while ($order = $ordersStmt->fetch(PDO::FETCH_ASSOC)) {
            // Get items for this order
            $itemsStmt = $pdo->prepare("
                SELECT 
                    oi.product_id,
                    oi.size_id,
                    oi.quantity,
                    oi.price,
                    (oi.quantity * oi.price) AS subtotal,
                    p.name AS product_name,
                    s.name AS size_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN sizes s ON oi.size_id = s.id
                WHERE oi.order_id = ?
            ");
            $itemsStmt->execute([$order['id']]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $orders[] = [
                'id' => $order['id'],
                'created_at' => $order['created_at'],
                'status' => $order['status'] ?? 'Pending',
                'total_price' => (float)$order['total_price'],
                'items' => array_map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'size_id' => $item['size_id'],
                        'product_name' => $item['product_name'],
                        'size_name' => $item['size_name'],
                        'quantity' => (int)$item['quantity'],
                        'price' => (float)$item['price'],
                        'subtotal' => (float)$item['subtotal']
                    ];
                }, $items)
            ];
        }

        echo json_encode([
            'success' => true,
            'orders' => $orders
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch orders.',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
}

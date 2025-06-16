<?php
include './cors.php';
include './config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === "GET" && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    try {
        // Get all orders
        $orderStmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
        $ordersRaw = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];

        foreach ($ordersRaw as $order) {
            $orderId = $order['id'];

            // Get items for each order with product name, size, quantity, and price
            $itemsStmt = $pdo->prepare("
            SELECT 
                oi.product_id,
                oi.size_id,
                oi.quantity,
                oi.price,
                p.name AS product_name,
                s.name AS size_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN sizes s ON oi.size_id = s.id
            WHERE oi.order_id = ?
        ");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $orders[] = [
                'order_id' => $order['id'],
                'created_at' => $order['created_at'],
                'status' => $order['status'] ?? 'Pending', // Default to "Pending" if null
                'total_price' => $order['total_price'],
                'customer' => [
                    'name' => $order['customer_name'],
                    'address' => $order['customer_address']
                ],
                'items' => array_map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'size_name' => $item['size_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ];
                }, $items)
            ];
        }

        echo json_encode($orders, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch orders', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized or invalid request']);
}

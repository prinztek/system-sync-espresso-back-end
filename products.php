<?php
include './cors.php';
include './config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // get products for users
    try {
        $stmt = $pdo->query("
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
            WHERE p.available = 1
                AND (p.stock_quantity > 0 OR p.stock_quantity IS NULL)
            ORDER BY p.id, s.id
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $products = [];

        foreach ($rows as $row) {
            $id = $row['product_id'];

            if (!isset($products[$id])) {
                $products[$id] = [
                    'id' => (int)$row['product_id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'type' => $row['type'],
                    'ingredients' => array_map('trim', explode(',', $row['ingredients'])),
                    'image_url' => $row['image_url'],
                    'available' => (bool)$row['available'],
                    'stock_quantity' => (int)$row['stock_quantity'],
                    'sizes' => [],
                ];
            }

            $products[$id]['sizes'][] = [
                'id' => (int)$row['size_id'],
                'name' => $row['size_name'],
                'price' => (float)$row['price']
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(array_values($products), JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}

// [
//   {
//     "id": 1,
//     "name": "Espresso",
//     "description": "A strong, black coffee...",
//     "type": "Drink",
//     "ingredients": ["Espresso beans", "Water"],
//     "image_url": "/src/assets/product_images/espresso.png",
//     "available": true,
//     "stock_quantity" : 0 or null
//     "sizes": [
//       {
//         "id": 1,
//         "name": "Single Shot",
//         "price": 2.5
//       },
//       {
//         "id": 2,
//         "name": "Double Shot",
//         "price": 3.5
//       }
//     ]
//   }
// ]
<?php
// Include CORS headers to allow cross-origin requests
include '../cors.php';
// Include database configuration and connection
include '../config/db.php';

class ProductController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function addProduct($name, $description, $price, $imagePath)
    {
        $stmt = $this->pdo->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $imagePath]);
    }

    public function updateProduct($id, $name, $description, $price, $imagePath)
    {
        $stmt = $this->pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $imagePath, $id]);
    }

    public function deleteProduct($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function getProduct($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTotalNumberofProducts()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM Products");
        return $stmt->fetchColumn();
    }

    public function getAllProducts()
    {
        $stmt = $this->pdo->query("SELECT * FROM products");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllProductsDetailed()
    {
        $stmt = $this->pdo->query("
        SELECT 
            p.id AS product_id,
            p.name,
            p.description,
            p.type,
            p.ingredients,
            p.image_url,
            p.available,
            s.id AS size_id,                      
            s.name AS size_name,
            ps.price
        FROM products p
        JOIN product_sizes ps ON p.id = ps.product_id
        JOIN sizes s ON ps.size_id = s.id
        ORDER BY p.id, s.id
    ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Step 2: Group the flat result into structured format
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
                    'sizes' => [],
                ];
            }

            $products[$id]['sizes'][] = [
                'id' => (int)$row['size_id'],
                'name' => $row['size_name'],
                'price' => (float)$row['price']
            ];
        }

        return array_values($products); // return as indexed array
    }
}


$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);  // Only get the path without query string
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle GET requests to "/products"
if ($requestMethod == 'GET') {
    try {
        $productController = new ProductController($pdo);
        $products = $productController->getAllProductsDetailed();
        header('Content-Type: application/json');
        echo json_encode($products, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        // Log the error
        error_log("Error fetching products: " . $e->getMessage());

        // Send a 500 Internal Server Error response
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
    }
} else {
    // Handle unknown routes (optional)
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}

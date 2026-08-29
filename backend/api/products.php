<?php
/**
 * ============================================================
 *  PRODUCTS API
 *  Endpoint: /backend/api/products.php
 *  Returns the product catalog as JSON.
 *  Optional: ?category=fertilizer to filter.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';

$products = require __DIR__ . '/../config/products.php';

// Optional category filter
$category = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : '';
if ($category !== '') {
    $products = array_values(array_filter($products, function ($p) use ($category) {
        return $p['category'] === $category;
    }));
}

// Optional single product by id
$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id !== '') {
    $match = null;
    foreach ($products as $p) {
        if ($p['id'] === $id) { $match = $p; break; }
    }
    if (!$match) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    echo json_encode(['success' => true, 'product' => $match]);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'count' => count($products),
    'products' => $products
]);

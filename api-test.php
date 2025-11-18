<?php
include 'config/config.inc.php';
include 'classes/ApiResponse.php';

// Headers
header('Content-Type: application/json');

// Validar API Key
$apiKey = $_GET['api_key'] ?? '';
$validKey = Configuration::get('MYAPI_PROD_KEY');

if ($apiKey !== $validKey) {
  http_response_code(401);
  echo json_encode(['error' => 'API Key inválida']);
  exit;
}

// Obtener productos
$products = Product::getProducts(Context::getContext()->language->id, 0, 10);
$formatted = [];

foreach ($products as $p) {
  $product = new Product($p['id_product'], true, Context::getContext()->language->id);
  $formatted[] = [
    'id' => $product->id,
    'name' => $product->name,
    'price' => $product->price,
    'active' => (bool)$product->active
  ];
}

echo json_encode([
  'status' => 'success',
  'data' => $formatted
]);

<?php
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiBaseController.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiResponse.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiLogger.php';

class MyApiApigatewayModuleFrontController extends ModuleFrontController
{
  private $apiBase;

  public function __construct()
  {
    parent::__construct();
    // ✅ FORMA CORRECTA EN PRESTASHOP
    $this->ajax = true;
    $this->content_only = true;
    $this->apiBase = new ApiBaseController($this->context);
  }

  public function init()
  {
    header('Content-Type: application/json');

    // ✅ CORS MEJORADO
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // ✅ DOMINIOS PERMITIDOS EXPLÍCITAMENTE
    $allowedDomains = [
      'https://www.salamandraluz.net',
      'https://test6.salamandraluz.net',
      Tools::getShopDomain(true)
    ];

    // ✅ SI EL ORIGEN ESTÁ EN LA LISTA PERMITIDA, PERMITIRLO
    if (in_array($origin, $allowedDomains)) {
      header('Access-Control-Allow-Origin: ' . $origin);
    } else {
      // ✅ CORS NORMAL PARA CLIENTES DE API
      $apiKey = $this->apiBase->getApiKey();
      $client = $this->apiBase->getClientByApiKey($apiKey);

      $shopDomain = Tools::getShopDomain(true);
      if ($origin === $shopDomain) {
        header('Access-Control-Allow-Origin: ' . $origin);
      } else if ($client && !empty($client['allowed_origins'])) {
        $allowedOrigins = json_decode($client['allowed_origins'], true);
        foreach ($allowedOrigins as $allowedOrigin) {
          $pattern = '/^' . str_replace('\*', '.*', preg_quote($allowedOrigin, '/')) . '$/';
          if ($origin === $allowedOrigin || preg_match($pattern, $origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            break;
          }
        }
      }
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      exit;
    }

    parent::init();
  }

  public function initContent()
  {
    try {
      $route = Tools::getValue('route');
      $method = $_SERVER['REQUEST_METHOD'];

      ApiLogger::log("apigateway request", [
        'route' => $route,
        'method' => $method
      ]);

      // ✅ ROUTING CENTRALIZADO Y LIMPIO
      switch (true) {
        case $route === 'products' && $method === 'GET':
          $this->handleProductsList();
          break;

        case preg_match('/^products\/(\d+)$/', $route, $matches) && $method === 'GET':
          $this->handleProductDetail($matches[1]);
          break;

        case $route === 'products/featured' && $method === 'GET':
          $this->handleFeaturedProducts();
          break;

        case preg_match('/^products\/(\d+)\/images$/', $route, $matches) && $method === 'GET':
          $this->handleProductImages($matches[1]);
          break;

        // ✅ NUEVAS RUTAS CRUD
        case $route === 'products' && $method === 'POST':
          $this->handleCreateProduct();
          break;

        case preg_match('/^products\/(\d+)$/', $route, $matches) && $method === 'PUT':
          $this->handleUpdateProduct($matches[1]);
          break;

        case preg_match('/^products\/(\d+)$/', $route, $matches) && $method === 'DELETE':
          $this->handleDeleteProduct($matches[1]);
          break;

        case $route === 'categories' && $method === 'GET':
          $this->handleCategoriesList();
          break;

        case preg_match('/^categories\/(\d+)\/products$/', $route, $matches) && $method === 'GET':
          $this->handleCategoryProducts($matches[1]);
          break;

        case $route === 'docs' && $method === 'GET':
          $this->handleApiDocs();
          break;

        default:
          ApiResponse::create()
            ->error('Ruta no encontrada: ' . $route)
            ->send(404);
      }
    } catch (Exception $e) {
      ApiLogger::logError("Error en apigateway", $e);
      ApiResponse::create()
        ->error($e->getMessage())
        ->send(500);
    }
  }

  // ========== HANDLERS ==========

  private function handleProductsList()
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apiproducts.php';
    $controller = new MyApiApiproductsModuleFrontController();
    $controller->init();
    $controller->handleListProducts();
  }

  private function handleProductDetail($id)
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apiproducts.php';
    $controller = new MyApiApiproductsModuleFrontController();
    $controller->init();
    $controller->handleGetProduct($id);
  }

  private function handleFeaturedProducts()
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apiproducts.php';
    $controller = new MyApiApiproductsModuleFrontController();
    $controller->init();
    $controller->handleFeatured();
  }

  private function handleProductImages($productId)
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apiproducts.php';
    $controller = new MyApiApiproductsModuleFrontController();
    $controller->init();
    $controller->handleProductImages($productId);
  }

  // ✅ NUEVOS HANDLERS CRUD
  private function handleCreateProduct()
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apiproducts.php';
    $controller = new MyApiApiproductsModuleFrontController();
    $controller->init();
    $controller->handleCreateProduct();
  }

  private function handleUpdateProduct($id)
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apiproducts.php';
    $controller = new MyApiApiproductsModuleFrontController();
    $controller->init();
    $controller->handleUpdateProduct($id);
  }

  private function handleDeleteProduct($id)
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apiproducts.php';
    $controller = new MyApiApiproductsModuleFrontController();
    $controller->init();
    $controller->handleDeleteProduct($id);
  }

  private function handleCategoriesList()
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apicategories.php';
    $controller = new MyApiApicategoriesModuleFrontController();
    $controller->init();
    $controller->handleListCategories();
  }

  private function handleCategoryProducts($categoryId)
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apicategories.php';
    $controller = new MyApiApicategoriesModuleFrontController();
    $controller->init();
    $controller->handleCategoryProducts($categoryId);
  }

  private function handleApiDocs()
  {
    require_once _PS_MODULE_DIR_ . 'myapi/controllers/front/apidocs.php';
    $controller = new MyApiApidocsModuleFrontController();
    $controller->init();
    $controller->initContent();
  }
}

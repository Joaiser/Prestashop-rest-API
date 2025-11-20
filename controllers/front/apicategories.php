<?php
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiBaseController.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiResponse.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiLogger.php';

class MyApiApicategoriesModuleFrontController extends ModuleFrontController
{
  private $apiBase;

  public function __construct()
  {
    parent::__construct();
    $this->auth = false;
    $this->guestAllowed = true;
    $this->apiBase = new ApiBaseController($this->context);
  }

  public function init()
  {
    $this->auth = false;
    $this->guestAllowed = true;

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      exit;
    }

    // Validación API Key
    $apiKey = $this->apiBase->getApiKey();
    if (!$this->apiBase->validateApiKey($apiKey)) {
      ApiResponse::create()
        ->error('API Key inválida')
        ->send(401);
    }

    if (!$this->apiBase->validateEndpointAccess('categories')) {
      ApiResponse::create()
        ->error('Endpoint no permitido para este cliente')
        ->send(401);
    }

    parent::init();
  }

  public function initContent()
  {
    try {
      $id = Tools::getValue('id');
      $method = $_SERVER['REQUEST_METHOD'];

      ApiLogger::log("apicategories request", [
        'method' => $method,
        'id' => $id
      ]);

      if ($method === 'GET') {
        if ($id) {
          // GET /api/v1/categories/123/products
          $this->handleCategoryProducts($id);
        } else {
          // GET /api/v1/categories
          $this->handleListCategories();
        }
      } else {
        ApiResponse::create()
          ->error('Método no permitido')
          ->send(405);
      }
    } catch (Exception $e) {
      ApiResponse::create()
        ->error($e->getMessage())
        ->send(500);
    }
  }

  private function handleListCategories()
  {
    try {
      $data = $this->getCategories();

      if (isset($data['error'])) {
        ApiResponse::create()->error($data['error'])->send(400);
      } else {
        // ✅ AÑADIR EL FILTRO
        $filteredData = $this->apiBase->filterResponseArray('categories', $data);
        ApiResponse::create()->success($filteredData)->send();
      }
    } catch (Exception $e) {
      ApiResponse::create()
        ->error('Error interno del servidor: ' . $e->getMessage())
        ->send(500);
    }
  }

  private function handleCategoryProducts($categoryId)
  {
    try {
      $data = $this->getCategoryProducts($categoryId);

      if (isset($data['debug_error'])) {
        ApiResponse::create()
          ->error('Error cargando productos: ' . $data['debug_error'])
          ->send(400);
      } else {
        // ✅ AÑADIR FILTRO PARA PRODUCTOS
        if (isset($data['products'])) {
          $data['products'] = $this->apiBase->filterResponseArray('products', $data['products']);
        }

        ApiResponse::create()->success($data)->send();
      }
    } catch (Exception $e) {
      ApiResponse::create()
        ->error('Error interno: ' . $e->getMessage())
        ->send(500);
    }
  }

  private function getCategories()
  {
    try {

      $categories = Category::getSimpleCategories($this->apiBase->getLanguageId());

      $formattedCategories = [];
      $languageId = $this->apiBase->getLanguageId();

      foreach ($categories as $category) {
        $categoryId = (int)$category['id_category'];

        // Excluir categorías root (1) y home (2)
        if ($categoryId <= 2) {
          continue;
        }

        $catObj = new Category($categoryId, $languageId);

        if (!Validate::isLoadedObject($catObj) || !$catObj->active) {
          continue;
        }

        // ✅ ACCESO SEGURO a propiedades multi-idioma
        $name = is_array($catObj->name) ?
          ($catObj->name[$languageId] ?? $catObj->name[1] ?? 'Sin nombre') :
          $catObj->name;

        $formattedCategories[] = [
          'id' => $categoryId,
          'name' => $name,
          'description' => is_array($catObj->description) ?
            ($catObj->description[$languageId] ?? $catObj->description[1] ?? '') :
            $catObj->description,
          'link_rewrite' => is_array($catObj->link_rewrite) ?
            ($catObj->link_rewrite[$languageId] ?? $catObj->link_rewrite[1] ?? '') :
            $catObj->link_rewrite,
          'active' => (bool)$catObj->active,
          'position' => (int)$catObj->position,
          'parent_id' => (int)$catObj->id_parent,
          'products_count' => (int)$catObj->getProducts(null, null, null, null, null, true)
        ];
      }

      return $formattedCategories;
    } catch (Exception $e) {
      return ['error' => 'Error cargando categorías: ' . $e->getMessage()];
    }
  }

  private function getCategoryProducts($categoryId)
  {
    try {

      $category = new Category($categoryId, $this->apiBase->getLanguageId());

      if (!Validate::isLoadedObject($category)) {
        throw new Exception('Categoría no encontrada');
      }

      // ✅ MÉTODO MÁS SIMPLE Y DIRECTO
      $products = Product::getProducts(
        $this->apiBase->getLanguageId(), // id_lang
        0,                               // start
        100,                             // limit
        'name',                          // order by
        'ASC',                           // order way
        $categoryId,                     // id_category - IMPORTANTE!
        false                            // only_active
      );


      // Formatear categoría
      $languageId = $this->apiBase->getLanguageId();
      $categoryData = [
        'id' => $category->id,
        'name' => is_array($category->name) ?
          ($category->name[$languageId] ?? $category->name[1] ?? 'Sin nombre') :
          $category->name,
        'description' => is_array($category->description) ?
          ($category->description[$languageId] ?? $category->description[1] ?? '') :
          $category->description,
        'products_count' => (int)$category->getProducts(null, null, null, null, null, true)
      ];

      return [
        'category' => $categoryData,
        'products' => array_map([$this, 'formatProduct'], $products)
      ];
    } catch (Exception $e) {
      return [
        'category' => ['id' => $categoryId, 'error' => 'Error loading category'],
        'products' => [],
        'debug_error' => $e->getMessage()
      ];
    }
  }

  // ✅ MÉTODO formatProduct 
  private function formatProduct($product)
  {
    if (is_array($product)) {
      $productObj = new Product($product['id_product'], true, $this->apiBase->getLanguageId());
    } else {
      $productObj = $product;
    }

    return [
      'id' => $productObj->id,
      'name' => $productObj->name,
      'description' => $productObj->description,
      'price' => $productObj->price,
      'reference' => $productObj->reference,
      'active' => (bool)$productObj->active,
      'stock' => StockAvailable::getQuantityAvailableByProduct($productObj->id)
    ];
  }
}

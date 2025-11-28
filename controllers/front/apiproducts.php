<?php
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiBaseController.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiResponse.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiLogger.php';

class MyApiApiproductsModuleFrontController extends ModuleFrontController
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

    // header('Content-Type: application/json');
    // header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    // header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

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

    if (!$this->apiBase->validateEndpointAccess('products')) {
      ApiResponse::create()
        ->error('Endpoint no permitido para este cliente')
        ->send(403);
    }

    parent::init();
  }

  public function initContent()
  {
    try {
      $action = Tools::getValue('action');
      $id = Tools::getValue('id');
      $method = $_SERVER['REQUEST_METHOD'];

      ApiLogger::log("apiproducts request", [
        'method' => $method,
        'action' => $action,
        'id' => $id
      ]);

      // ✅ DETECTAR AUTOMÁTICAMENTE EL TIPO DE PETICIÓN CON LAS NUEVAS RUTAS
      if ($method === 'GET') {
        if ($action === 'featured') {
          $this->handleFeatured();
        } else if ($action === 'search') {
          $this->handleSearch();
        } else if ($action === 'categories' && $id) {
          $this->handleProductCategories($id);
        } else if ($action === 'images' && $id) {
          // ✅ NUEVO ENDPOINT: GET /api/v1/products/123/images
          $this->handleProductImages($id);
        } else if ($id) {
          // GET /api/v1/products/123
          $this->handleGetProduct($id);
        } else {
          // GET /api/v1/products
          $this->handleListProducts();
        }
      } else if ($method === 'POST' && !$id) {
        // POST /api/v1/products
        $this->handleCreateProduct();
      } else if ($method === 'PUT' && $id) {
        // PUT /api/v1/products/123  
        $this->handleUpdateProduct($id);
      } else if ($method === 'DELETE' && $id) {
        // DELETE /api/v1/products/123
        $this->handleDeleteProduct($id);
      } else {
        ApiResponse::create()
          ->error('Método o ruta no válidos')
          ->send(405);
      }
    } catch (Exception $e) {
      ApiLogger::logError("Error en apiproducts", $e);
      ApiResponse::create()
        ->error($e->getMessage())
        ->send(500);
    }
  }

  // ✅ MÉTODOS ESPECÍFICOS PARA CADA ACCIÓN

  public function handleListProducts()
  {
    $data = $this->getProducts();
    //✅FILTROOO
    if (isset($data['products'])) {
      $data['products'] = $this->apiBase->filterResponseArray('products', $data['products']);
    }
    ApiResponse::create()->success($data)->send();
  }

  public function handleGetProduct($id)
  {
    $data = $this->getProductById($id);
    // ✅ -_-
    $filteredData = $this->apiBase->filterResponseFields('products', $data);

    ApiResponse::create()->success($filteredData)->send();
  }

  public function handleFeatured()
  {
    $data = $this->getFeaturedProducts();

    if (isset($data['error'])) {
      ApiResponse::create()
        ->error($data['error'])
        ->send(400);
    } else {
      // ✅ -_-
      $filteredData = $this->apiBase->filterResponseArray('products', $data);
      ApiResponse::create()->success($filteredData)->send();
    }
  }

  private function handleSearch()
  {
    $data = $this->searchProducts();
    ApiResponse::create()->success($data)->send();
  }

  private function handleProductCategories($productId)
  {
    $data = $this->getProductCategories($productId);
    ApiResponse::create()->success($data)->send();
  }

  // ✅ NUEVO MÉTODO PARA ENDPOINT DE IMÁGENES
  public function handleProductImages($productId)
  {
    $data = $this->getProductImages($productId);
    ApiResponse::create()->success($data)->send();
  }

  public function handleCreateProduct()
  {
    // ✅ VERIFICAR PERMISO DE CREACIÓN
    if (!$this->apiBase->canPerformOperation('create')) {
      ApiResponse::create()
        ->error('No tienes permisos para crear productos')
        ->send(403);
    }

    $input = $this->getInputData();
    $data = $this->createProduct($input);
    ApiResponse::create()->success($data, 'Producto creado')->send(201);
  }

  public function handleUpdateProduct($id)
  {
    // ✅ VERIFICAR PERMISO DE ACTUALIZACIÓN
    if (!$this->apiBase->canPerformOperation('update')) {
      ApiResponse::create()
        ->error('No tienes permisos para actualizar productos')
        ->send(403);
    }

    $input = $this->getInputData();
    $data = $this->updateProduct($id, $input);
    ApiResponse::create()->success($data, 'Producto actualizado')->send(200);
  }

  public function handleDeleteProduct($id)
  {
    // ✅ VERIFICAR PERMISO DE ELIMINACIÓN
    if (!$this->apiBase->canPerformOperation('delete')) {
      ApiResponse::create()
        ->error('No tienes permisos para eliminar productos')
        ->send(403);
    }

    $data = $this->deleteProduct($id);
    ApiResponse::create()->success($data, 'Producto eliminado')->send(200);
  }

  // ========== MÉTODOS CRUD COMPLETOS ==========

  private function getProducts()
  {
    try {
      // ✅ PAGINACIÓN MEJORADA
      $page = max(1, (int)Tools::getValue('page', 1));
      $limit = max(1, min((int)Tools::getValue('limit', 50), 730)); // Máximo 730 por página
      $start = ($page - 1) * $limit;

      ApiLogger::log("Loading paginated products", [
        'page' => $page,
        'limit' => $limit,
        'start' => $start
      ]);

      // Obtener productos paginados
      $products = Product::getProducts(
        $this->apiBase->getLanguageId(),
        $start,
        $limit,
        'id_product',
        'ASC',
        false,
        false
      );

      // Total de productos
      $allProducts = Product::getProducts(
        $this->apiBase->getLanguageId(),
        0,
        0,
        'id_product',
        'ASC',
        false,
        false
      );
      $totalProducts = count($allProducts);
      $totalPages = ceil($totalProducts / $limit);

      ApiLogger::log("Products retrieved", [
        'count' => count($products),
        'total_products' => $totalProducts,
        'total_pages' => $totalPages
      ]);

      return [
        'products' => array_map([$this, 'formatProduct'], $products),
        'pagination' => [
          'current_page' => $page,
          'per_page' => $limit,
          'total_products' => $totalProducts,
          'total_pages' => $totalPages,
          'has_next' => $page < $totalPages,
          'has_prev' => $page > 1
        ]
      ];
    } catch (Exception $e) {
      ApiLogger::logError("Error in getProducts", $e);
      throw $e;
    }
  }

  private function getProductById($id)
  {
    ApiLogger::log("Loading single product", ['product_id' => $id]);
    $product = new Product($id, true, $this->apiBase->getLanguageId());

    if (!Validate::isLoadedObject($product)) {
      ApiLogger::logError("Product not found", new Exception("Producto ID $id no encontrado"));
      throw new Exception('Producto no encontrado');
    }

    ApiLogger::log("Product loaded successfully", ['id' => $product->id, 'name' => $product->name]);
    return $this->formatProduct($product, true); // true = formato detallado
  }

  // ✅ CREAR PRODUCTO
  private function createProduct($data)
  {
    try {
      $product = new Product();

      // Datos básicos obligatorios
      $product->name = $this->createMultiLangField($data['name'] ?? 'Nuevo Producto');
      $product->reference = $data['reference'] ?? 'REF-' . uniqid();
      $product->price = floatval($data['price'] ?? 0);
      $product->id_category_default = intval($data['category_id'] ?? 2); // Categoría por defecto
      $product->active = boolval($data['active'] ?? true);

      // Descripción (opcional)
      if (isset($data['description'])) {
        $product->description = $this->createMultiLangField($data['description']);
      }

      // Guardar producto
      if (!$product->save()) {
        throw new Exception('Error al guardar el producto');
      }

      // ✅ Asignar categorías
      if (isset($data['categories']) && is_array($data['categories'])) {
        $product->updateCategories($data['categories']);
      } else {
        // Categoría por defecto
        $product->updateCategories([$product->id_category_default]);
      }

      // ✅ Gestión de stock
      if (isset($data['stock'])) {
        StockAvailable::setQuantity($product->id, 0, intval($data['stock']));
      }

      ApiLogger::log("Product created successfully", ['id' => $product->id]);

      return [
        'id' => $product->id,
        'name' => $product->name,
        'reference' => $product->reference,
        'message' => 'Producto creado correctamente'
      ];
    } catch (Exception $e) {
      ApiLogger::logError("Error creating product", $e);
      throw new Exception('Error al crear el producto: ' . $e->getMessage());
    }
  }

  // ✅ ACTUALIZAR PRODUCTO
  private function updateProduct($id, $data)
  {
    try {
      $product = new Product($id);

      if (!Validate::isLoadedObject($product)) {
        throw new Exception('Producto no encontrado');
      }

      // Campos actualizables
      if (isset($data['name'])) {
        $product->name = $this->createMultiLangField($data['name']);
      }
      if (isset($data['reference'])) {
        $product->reference = $data['reference'];
      }
      if (isset($data['price'])) {
        $product->price = floatval($data['price']);
      }
      if (isset($data['description'])) {
        $product->description = $this->createMultiLangField($data['description']);
      }
      if (isset($data['active'])) {
        $product->active = boolval($data['active']);
      }
      if (isset($data['category_id'])) {
        $product->id_category_default = intval($data['category_id']);
      }

      // Guardar cambios
      if (!$product->save()) {
        throw new Exception('Error al actualizar el producto');
      }

      // ✅ Actualizar categorías
      if (isset($data['categories']) && is_array($data['categories'])) {
        $product->updateCategories($data['categories']);
      }

      // ✅ Actualizar stock
      if (isset($data['stock'])) {
        StockAvailable::setQuantity($product->id, 0, intval($data['stock']));
      }

      ApiLogger::log("Product updated successfully", ['id' => $product->id]);

      return [
        'id' => $product->id,
        'name' => $product->name,
        'reference' => $product->reference,
        'message' => 'Producto actualizado correctamente'
      ];
    } catch (Exception $e) {
      ApiLogger::logError("Error updating product", $e);
      throw new Exception('Error al actualizar el producto: ' . $e->getMessage());
    }
  }

  // ✅ ELIMINAR PRODUCTO
  private function deleteProduct($id)
  {
    try {
      $product = new Product($id);

      if (!Validate::isLoadedObject($product)) {
        throw new Exception('Producto no encontrado');
      }

      // Eliminar producto
      if (!$product->delete()) {
        throw new Exception('Error al eliminar el producto');
      }

      ApiLogger::log("Product deleted successfully", ['id' => $id]);

      return [
        'id' => $id,
        'message' => 'Producto eliminado correctamente'
      ];
    } catch (Exception $e) {
      ApiLogger::logError("Error deleting product", $e);
      throw new Exception('Error al eliminar el producto: ' . $e->getMessage());
    }
  }

  // ✅ HELPER para campos multi-idioma
  private function createMultiLangField($value)
  {
    $languages = Language::getLanguages();
    $field = [];

    foreach ($languages as $language) {
      $field[$language['id_lang']] = $value;
    }

    return $field;
  }

  // ✅ OBTENER DATOS DEL BODY
  private function getInputData()
  {
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception('JSON inválido en el cuerpo de la petición');
    }

    return $input ?? [];
  }

  private function getFeaturedProducts()
  {
    try {
      // Primero intentar productos destacados manuales
      $category = new Category(Configuration::get('PS_HOME_CATEGORY'), $this->apiBase->getLanguageId());
      $products = $category->getProducts($this->apiBase->getLanguageId(), 1, 10);

      // Si no hay destacados, mostrar ofertas
      if (empty($products)) {
        $products = Product::getPricesDrop($this->apiBase->getLanguageId(), 0, 10);
      }

      // Si tampoco hay ofertas, productos nuevos
      if (empty($products)) {
        $products = Product::getNewProducts($this->apiBase->getLanguageId(), 0, 10);
      }

      ApiLogger::log("Featured products loaded", [
        'count' => count($products),
        'type' => 'featured'
      ]);

      return array_map([$this, 'formatProduct'], $products);
    } catch (Exception $e) {
      ApiLogger::logError("Error in getFeaturedProducts", $e);
      return [
        'error' => 'No se pudieron cargar los productos destacados',
        'message' => $e->getMessage()
      ];
    }
  }

  private function searchProducts()
  {
    $query = Tools::getValue('q');
    // Implementar búsqueda de productos
    return ['results' => [], 'query' => $query];
  }

  private function getProductCategories($productId)
  {
    if (!$productId) {
      throw new Exception('ID de producto requerido');
    }

    $product = new Product($productId);
    $categories = $product->getCategories();
    return $categories;
  }

  // ✅ FORMATO MEJORADO con más datos
  private function formatProduct($product, $detailed = false)
  {
    if (is_array($product)) {
      $productObj = new Product($product['id_product'], true, $this->apiBase->getLanguageId());
    } else {
      $productObj = $product;
    }

    $formatted = [
      'id' => $productObj->id,
      'name' => $productObj->name,
      'description' => $productObj->description,
      'price' => $productObj->price,
      'reference' => $productObj->reference,
      'active' => (bool)$productObj->active,
      'stock' => StockAvailable::getQuantityAvailableByProduct($productObj->id),
      'has_combinations' => (bool)$productObj->hasAttributes(),
    ];

    // ✅ DATOS EXTRA para producto individual
    if ($detailed) {
      $formatted['images'] = $this->getProductImages($productObj->id);
      $formatted['categories'] = $productObj->getCategories();
      $formatted['features'] = $productObj->getFeatures();
      $formatted['manufacturer'] = $productObj->manufacturer_name;

      if ($productObj->hasAttributes()) {
        $formatted['combinations'] = $this->getProductCombinationsBasic($productObj->id);
      }
    }

    return $formatted;
  }

  private function getProductCombinationsBasic($productId)
  {
    try {
      ApiLogger::log("🔄 Starting getProductCombinationsBasic", ['product_id' => $productId]);

      // ✅ VERSIÓN MÍNIMA Y SEGURA - SIN IMÁGENES POR AHORA
      $product = new Product($productId);

      // if (!Validate::isLoadedObject($product)) {
      //   ApiLogger::log("❌ Product not found", ['product_id' => $productId]);
      //   return [];
      // }

      // ApiLogger::log("✅ Product loaded", [
      //   'id' => $product->id,
      //   'name' => $product->name,
      //   'has_attributes' => $product->hasAttributes()
      // ]);

      if (!$product->hasAttributes()) {
        // ApiLogger::log("ℹ️ Product has no attributes", ['product_id' => $productId]);
        return [];
      }

      $combinations = $product->getAttributesResume($this->apiBase->getLanguageId());

      // ApiLogger::log("📊 Raw combinations data", [
      //   'count' => count($combinations),
      //   'combinations' => $combinations
      // ]);

      if (empty($combinations)) {
        // ApiLogger::log("ℹ️ No combinations found", ['product_id' => $productId]);
        return [];
      }

      $basicCombinations = [];
      foreach ($combinations as $comb) {
        $combinationId = $comb['id_product_attribute'];

        // ✅ VERIFICACIÓN EXTRA DE SEGURIDAD
        if (empty($combinationId)) {
          // ApiLogger::log("⚠️ Empty combination ID", ['combination_data' => $comb]);
          continue;
        }

        // ApiLogger::log("🔄 Processing combination", [
        //   'combination_id' => $combinationId,
        //   'reference' => $comb['reference'] ?? 'NO_REFERENCE'
        // ]);

        // ✅ PRECIO SIMPLIFICADO - usar precio del producto como fallback
        $price = $product->price;
        try {
          $calculatedPrice = Product::getPriceStatic(
            $productId,
            false,
            $combinationId,
            6,
            null,
            null,
            $this->apiBase->getLanguageId()
          );
          if ($calculatedPrice > 0) {
            $price = $calculatedPrice;
          }
        } catch (Exception $e) {
          ApiLogger::logError("⚠️ Error calculating combination price, using product price", $e);
          // Mantener el precio del producto como fallback
        }

        $basicCombinations[] = [
          'id' => (int)$combinationId,
          'reference' => $comb['reference'] ?? '',
          'quantity' => StockAvailable::getQuantityAvailableByProduct($productId, $combinationId),
          'price' => (float)$price,
          'attributes' => $comb['attribute_designation'] ?? '',
          'default_on' => (bool)($comb['default_on'] ?? false),
          'images' => [] // ✅ TEMPORALMENTE VACÍO - lo añadiremos después
        ];
      }

      // ApiLogger::log("✅ Combinations processed successfully", [
      //   'product_id' => $productId,
      //   'combinations_count' => count($basicCombinations)
      // ]);

      return $basicCombinations;
    } catch (Exception $e) {
      // ApiLogger::logError("❌ CRITICAL ERROR in getProductCombinationsBasic", $e);
      return []; // ✅ SIEMPRE retornar array vacío en caso de error
    }
  }


  // ✅ ENDPOINT SEPARADO PARA COMBINACIONES COMPLETAS
  private function getProductCombinationsDetailed($productId)
  {
    try {
      $product = new Product($productId);

      if (!Validate::isLoadedObject($product) || !$product->hasAttributes()) {
        return [];
      }

      $combinations = $product->getAttributesResume($this->apiBase->getLanguageId());

      if (empty($combinations)) {
        return [];
      }

      $detailedCombinations = [];
      foreach ($combinations as $comb) {
        $combinationId = $comb['id_product_attribute'];
        $combinationObj = new Combination($combinationId);

        if (!Validate::isLoadedObject($combinationObj)) {
          continue;
        }

        // Obtener atributos detallados
        $attributeNames = $combinationObj->getAttributesName($this->apiBase->getLanguageId());
        $attributes = [];
        foreach ($attributeNames as $attribute) {
          $attributes[] = [
            'group' => $attribute['group_name'],
            'name' => $attribute['attribute_name']
          ];
        }

        // Obtener precio específico
        $price = Product::getPriceStatic(
          $productId,
          false,
          $combinationId,
          6,
          null,
          null,
          $this->apiBase->getLanguageId()
        );

        $detailedCombinations[] = [
          'id' => (int)$combinationId,
          'reference' => $combinationObj->reference ?: '',
          'ean13' => $combinationObj->ean13 ?: '',
          'upc' => $combinationObj->upc ?: '',
          'price' => (float)$price,
          'quantity' => StockAvailable::getQuantityAvailableByProduct($productId, $combinationId),
          'attributes' => $attributes,
          'images' => $this->getCombinationImages($productId, $combinationId),
          'default_on' => (bool)$comb['default_on'],
          'minimal_quantity' => (int)$combinationObj->minimal_quantity,
          'available_date' => $combinationObj->available_date ?: ''
        ];
      }

      return $detailedCombinations;
    } catch (Exception $e) {
      ApiLogger::logError("Error getting detailed combinations for product: $productId", $e);
      return ['error' => $e->getMessage()];
    }
  }

  // ✅ MÉTODO MEJORADO PARA OBTENER TODAS LAS IMÁGENES Y TAMAÑOS
  private function getProductImages($productId)
  {
    try {
      $product = new Product($productId, false, $this->apiBase->getLanguageId());

      if (!Validate::isLoadedObject($product)) {
        throw new Exception("Producto ID $productId no encontrado");
      }

      $images = $product->getImages($this->apiBase->getLanguageId());
      $imageTypes = ImageType::getImagesTypes('products');
      $formattedImages = [];

      foreach ($images as $image) {
        $imageData = [
          'id' => (int)$image['id_image'],
          'position' => (int)$image['position'],
          'cover' => (bool)$image['cover'],
          'legend' => $image['legend'],
          'sizes' => []
        ];

        // Generar URLs para todos los tipos de imagen
        foreach ($imageTypes as $type) {
          $imageUrl = $this->context->link->getImageLink(
            $product->link_rewrite[$this->apiBase->getLanguageId()],
            $image['id_image'],
            $type['name']
          );

          // Convertir a URL absoluta
          $absoluteUrl = $this->getAbsoluteImageUrl($imageUrl);

          $imageData['sizes'][$type['name']] = [
            'url' => $absoluteUrl,
            'width' => (int)$type['width'],
            'height' => (int)$type['height']
          ];
        }

        // Añadir también la URL original/base
        $originalUrl = $this->context->link->getImageLink(
          $product->link_rewrite[$this->apiBase->getLanguageId()],
          $image['id_image']
        );
        $imageData['sizes']['original'] = [
          'url' => $this->getAbsoluteImageUrl($originalUrl),
          'width' => null,
          'height' => null
        ];

        $formattedImages[] = $imageData;
      }

      return $formattedImages;
    } catch (Exception $e) {
      ApiLogger::logError("Error getting product images for ID: $productId", $e);
      return ['error' => $e->getMessage()];
    }
  }

  // ✅ CONVERTIR URL RELATIVA A ABSOLUTA
  private function getAbsoluteImageUrl($relativeUrl)
  {
    if (empty($relativeUrl)) {
      return $relativeUrl;
    }

    // Si ya es una URL absoluta, devolver tal cual
    if (strpos($relativeUrl, 'http') === 0) {
      return $relativeUrl;
    }

    // Construir URL absoluta
    $shopDomain = Tools::getShopDomain(true);
    return $shopDomain . $relativeUrl;
  }
}

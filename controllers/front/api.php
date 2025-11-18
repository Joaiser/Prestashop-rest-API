<?php
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiBaseController.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiResponse.php';
require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiLogger.php';

class MyApiApiModuleFrontController extends ModuleFrontController
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
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

    parent::init();
  }

  public function initContent()
  {
    $entity = Tools::getValue('entity');

    switch ($entity) {
      case 'products':
        // Redirigir al controlador de productos
        Tools::redirect($this->context->link->getModuleLink('myapi', 'apiproducts', $_GET));
        break;
      case 'categories':
        $data = $this->getCategories();
        break;
      case 'customers':
        $data = $this->getCustomers();
        break;
      case 'orders':
        $data = $this->getOrders();
        break;
      default:
        ApiResponse::create()
          ->error('Entidad no soportada: ' . $entity)
          ->send(404);
    }

    ApiResponse::create()
      ->success($data)
      ->send();
  }

  private function getCustomers()
  {
    return ['message' => 'Endpoint en desarrollo'];
  }

  private function getOrders()
  {
    return ['message' => 'Endpoint en desarrollo'];
  }
}

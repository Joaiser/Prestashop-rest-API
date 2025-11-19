<?php
class ApiBaseController
{
  public $context;
  private $currentClient = null;

  public function __construct($context)
  {
    $this->context = $context;
  }

  // ✅ VALIDACIÓN MULTI-CLIENTE
  public function validateApiKey($apiKey)
  {
    if (!$apiKey) return false;

    // Primero buscar en clientes externos
    $client = $this->getClientByApiKey($apiKey);
    if ($client && $client['is_active']) {
      $this->currentClient = $client;
      $this->logRequest($client['id_client']);
      return true;
    }

    // Fallback a clave legacy (para compatibilidad)
    $validProdKey = Configuration::get('MYAPI_PROD_KEY');
    if ($apiKey === $validProdKey) {
      return true;
    }

    // Fallback a claves de testing
    $testKeys = ['API_KEY_PRUEBA', 'mykey', 'test', 'testing', 'demo'];
    return in_array($apiKey, $testKeys);
  }

  private function getClientByApiKey($apiKey)
  {
    $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'external_api_clients 
                WHERE api_key = "' . pSQL($apiKey) . '"';

    return Db::getInstance()->getRow($sql);
  }

  private function logRequest($clientId)
  {
    $sql = 'UPDATE ' . _DB_PREFIX_ . 'external_api_clients 
                SET requests_count = requests_count + 1,
                    last_request = NOW()
                WHERE id_client = ' . (int)$clientId;
    Db::getInstance()->execute($sql);
  }

  public function getCurrentClient()
  {
    return $this->currentClient;
  }

  public function getApiKey()
  {
    $headers = getallheaders();
    foreach ($headers as $key => $value) {
      if (strtolower($key) === 'x-api-key') {
        return $value;
      }
    }
    return Tools::getValue('api_key');
  }

  public function getLanguageId()
  {
    return $this->context->language->id;
  }

  public function getShopId()
  {
    return $this->context->shop->id;
  }
}

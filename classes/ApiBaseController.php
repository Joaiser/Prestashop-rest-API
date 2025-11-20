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

  //✅ VALIDAR ACCESO A ENDPOINT
  public function validateEndpointAccess($endpoint)
  {
    //Cliente legacy/testing acceso completo
    if (!$this->currentClient) {
      return true;
    }

    // Sin restricciones permitir todo
    if (empty($this->currentClient['allowed_endpoints']) || $this->currentClient['allowed_endpoints'] == '[]') {
      return true;
    }


    $allowedEndpoints = json_decode($this->currentClient['allowed_endpoints'], true);
    return in_array($endpoint, $allowedEndpoints);
  }

  //✅ FILTRAR CAMPOS DE RESPUESTA
  public function filterResponseFields($endpoint, $data)
  {


    // Si es cliente legacy/testing, devolver todos los campos
    if (!$this->currentClient) {
      return $data;
    }

    // DEBUG: Log permisos del cliente


    // Si no hay restricciones, devolvemos todos los campos
    if (empty($this->currentClient['allowed_fields']) || $this->currentClient['allowed_fields'] == '[]') {
      return $data;
    }

    $allowedFields = json_decode($this->currentClient['allowed_fields'], true);

    // DEBUG: Log campos permitidos para este endpoint

    // Si no hay campos específicos devolver todo
    if (!isset($allowedFields[$endpoint]) || empty($allowedFields[$endpoint])) {
      return $data;
    }

    // DEBUG: Log datos originales

    //Filtrar solo los campos permitidos
    $filteredData = [];
    foreach ($allowedFields[$endpoint] as $field) {
      if (array_key_exists($field, $data)) {
        $filteredData[$field] = $data[$field];
      }
    }

    return $filteredData;
  }

  //✅ FILTRAR ARRAY DE DATOS (para listas)
  public function filterResponseArray($endpoint, $dataArray)
  {
    $filteredArray = [];
    foreach ($dataArray as $item) {
      $filteredArray[] = $this->filterResponseFields($endpoint, $item);
    }
    return $filteredArray;
  }

  //✅ OBTENER CLIENTE COMO OBJETO
  public function getClientObject()
  {
    if (!$this->currentClient) {
      return null;
    }

    $client = new ApiClient();
    foreach ($this->currentClient as $key => $value) {
      if (property_exists($client, $key)) {
        $client->$key = $value;
      }
    }
    return $client;
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

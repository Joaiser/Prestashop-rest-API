<?php

class ApiBaseController
{
  public $context;

  public function __construct($context)
  {
    $this->context = $context;
  }

  // ✅ SOLO autenticación
  public function validateApiKey($apiKey)
  {
    if (!$apiKey) return false;
    $validKey = Configuration::get('MYAPI_PROD_KEY');
    return $apiKey === $validKey;
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

  // ✅ SOLO helpers de contexto
  public function getLanguageId()
  {
    return $this->context->language->id;
  }

  public function getShopId()
  {
    return $this->context->shop->id;
  }
}

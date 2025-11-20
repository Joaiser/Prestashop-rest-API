<?php
class ApiClient extends ObjectModel
{
  public $id_client;
  public $client_name;
  public $company;
  public $email;
  public $api_key;
  public $secret_key;
  public $is_active;
  public $rate_limit;
  public $requests_count;
  public $last_request;
  public $webhook_url;
  public $allowed_fields;
  public $allowed_endpoints;
  public $allowed_origins;
  public $created_at;
  public $updated_at;

  public static $definition = [
    'table' => 'external_api_clients',
    'primary' => 'id_client',
    'fields' => [
      'client_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
      'company' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
      'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255],
      'api_key' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
      'secret_key' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
      'is_active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
      'rate_limit' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
      'requests_count' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
      'last_request' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
      'webhook_url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 500],
      'allowed_fields' => ['type' => self::TYPE_STRING, 'validate' => 'isJson'],
      'allowed_endpoints' => ['type' => self::TYPE_STRING, 'validate' => 'isJson'],
      'allowed_origins' => ['type' => self::TYPE_STRING, 'validate' => 'isJson'],  // ✅ NUEVO
      'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
      'updated_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
    ],
  ];

  public function __construct($id = null, $id_lang = null, $id_shop = null)
  {
    parent::__construct($id, $id_lang, $id_shop);

    // ✅ INICIALIZAR LOS NUEVOS CAMPOS SI ESTÁN VACÍOS
    if ($this->id && empty($this->allowed_endpoints)) {
      $this->allowed_endpoints = '[]';
    }
    if ($this->id && empty($this->allowed_fields)) {
      $this->allowed_fields = '[]';
    }
    if ($this->id && empty($this->allowed_origins)) {
      $this->allowed_origins = '[]';
    }
  }
}

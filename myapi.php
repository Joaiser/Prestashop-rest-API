<?php
if (!defined('_PS_VERSION_')) {
  exit;
}

class MyApi extends Module
{
  public function __construct()
  {
    $this->name = 'myapi';
    $this->tab = 'administration';
    $this->version = '1.1.0';
    $this->author = 'Aitor';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = [
      'min' => '8.0.0',
      'max' => _PS_VERSION_
    ];
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('My Custom API - Multi Cliente');
    $this->description = $this->l('API extensible multi-cliente para PrestaShop 8');
    $this->loadModuleClasses();
  }

  private function loadModuleClasses()
  {
    $classes = ['ApiClient', 'ApiBaseController', 'ApiResponse', 'ApiLogger'];
    foreach ($classes as $class) {
      if (!class_exists($class)) {
        $file = _PS_MODULE_DIR_ . $this->name . '/classes/' . $class . '.php';
        if (file_exists($file)) require_once $file;
      }
    }
  }

  public function install()
  {
    if (!parent::install()) return false;
    if (!$this->createClientsTable()) return false;
    return $this->installTabs() && Configuration::updateValue('MYAPI_PROD_KEY', 'TEST_KEY_PARA_DESARROLLO');
  }

  private function createClientsTable()
  {
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'external_api_clients` (
            `id_client` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_name` VARCHAR(255) NOT NULL,
            `company` VARCHAR(255) NULL,
            `email` VARCHAR(255) NULL,
            `api_key` VARCHAR(255) NOT NULL UNIQUE,
            `secret_key` VARCHAR(255) NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `rate_limit` INT(11) DEFAULT 1000,
            `requests_count` INT(11) DEFAULT 0,
            `last_request` DATETIME NULL,
            `webhook_url` VARCHAR(500) NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_client`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
    return Db::getInstance()->execute($sql);
  }

  private function installTabs()
  {
    $success = true;
    $configureId = (int)Tab::getIdFromClassName('CONFIGURE');

    // ✅ TAB PADRE: "Mi API" con logo MECORRO
    $tabParent = new Tab();
    $tabParent->class_name = 'AdminMyApiParent';
    $tabParent->module = $this->name;
    $tabParent->id_parent = $configureId;
    $tabParent->icon = 'rocket';
    foreach (Language::getLanguages(true) as $lang) {
      $tabParent->name[$lang['id_lang']] = ' Mi API';
    }
    if (!$tabParent->add()) {
      PrestaShopLogger::addLog('MyApi: Error creando tab padre Mi API', 3);
      $success = false;
    } else {
      $parentId = $tabParent->id;

      // ✅ TAB HIJO 1: Configuración API con icono
      $tabConfig = new Tab();
      $tabConfig->class_name = 'AdminMyApi';
      $tabConfig->module = $this->name;
      $tabConfig->id_parent = $parentId;
      $tabConfig->icon = 'cogs'; // ⚙️
      foreach (Language::getLanguages(true) as $lang) {
        $tabConfig->name[$lang['id_lang']] = '⚙️ Config API';
      }
      if (!$tabConfig->add()) {
        PrestaShopLogger::addLog('MyApi: Error creando Configuración API', 3);
        $success = false;
      }

      // ✅ TAB HIJO 2: Clientes API con icono
      $tabClients = new Tab();
      $tabClients->class_name = 'AdminMyApiClients';
      $tabClients->module = $this->name;
      $tabClients->id_parent = $parentId;
      $tabClients->icon = 'users'; // 👥
      foreach (Language::getLanguages(true) as $lang) {
        $tabClients->name[$lang['id_lang']] = '👥 Clientes API';
      }
      if (!$tabClients->add()) {
        PrestaShopLogger::addLog('MyApi: Error creando Clientes API', 3);
        $success = false;
      }
    }

    return $success;
  }

  private function uninstallTabs()
  {
    $tabs = ['AdminMyApiParent', 'AdminMyApi', 'AdminMyApiClients'];
    $success = true;
    foreach ($tabs as $tabClass) {
      $id_tab = (int)Tab::getIdFromClassName($tabClass);
      if ($id_tab) {
        $tab = new Tab($id_tab);
        $success &= $tab->delete();
      }
    }
    return $success;
  }

  public function uninstall()
  {
    $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'external_api_clients`';
    Db::getInstance()->execute($sql);
    return $this->uninstallTabs() && parent::uninstall();
  }

  public function getContent()
  {
    Tools::redirectAdmin($this->context->link->getAdminLink('AdminMyApi'));
  }
}

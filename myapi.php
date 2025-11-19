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
    $this->version = '1.1.0'; // ✅ Incrementa versión
    $this->author = 'Tu Nombre';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = [
      'min' => '8.0.0',
      'max' => _PS_VERSION_
    ];
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('My Custom API - Multi Cliente');
    $this->description = $this->l('API extensible multi-cliente para PrestaShop 8');

    // ✅ AUTOLOAD MANUAL de clases
    $this->loadModuleClasses();
  }

  private function loadModuleClasses()
  {
    $classes = [
      'ApiClient',
      'ApiBaseController',
      'ApiResponse',
      'ApiLogger'
    ];

    foreach ($classes as $class) {
      if (!class_exists($class)) {
        $file = _PS_MODULE_DIR_ . $this->name . '/classes/' . $class . '.php';
        if (file_exists($file)) {
          require_once $file;
        }
      }
    }
  }

  public function install()
  {
    if (!parent::install()) {
      return false;
    }

    // ✅ CREAR TABLA DE CLIENTES
    if (!$this->createClientsTable()) {
      return false;
    }

    return $this->installTabs() &&
      Configuration::updateValue('MYAPI_PROD_KEY', 'TEST_KEY_PARA_DESARROLLO');
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
    // ✅ TAB PRINCIPAL (Configuración)
    $tabMain = new Tab();
    $tabMain->class_name = 'AdminMyApi';
    $tabMain->module = $this->name;
    $tabMain->id_parent = (int)Tab::getIdFromClassName('CONFIGURE');
    $tabMain->name = array();
    foreach (Language::getLanguages(true) as $lang) {
      $tabMain->name[$lang['id_lang']] = 'My API Config';
    }
    $tabMain->add();

    // ✅ NUEVA TAB PARA GESTIÓN DE CLIENTES
    $tabClients = new Tab();
    $tabClients->class_name = 'AdminMyApiClients';
    $tabClients->module = $this->name;
    $tabClients->id_parent = (int)Tab::getIdFromClassName('AdminMyApi'); // Submenú de la principal
    $tabClients->name = array();
    foreach (Language::getLanguages(true) as $lang) {
      $tabClients->name[$lang['id_lang']] = 'Gestión de Clientes API';
    }

    return $tabClients->add();
  }

  private function uninstallTabs()
  {
    $tabs = ['AdminMyApi', 'AdminMyApiClients'];
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
    // ✅ ELIMINAR TABLA AL DESINSTALAR (opcional)
    $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'external_api_clients`';
    Db::getInstance()->execute($sql);

    return $this->uninstallTabs() &&
      parent::uninstall();
  }

  public function getContent()
  {
    Tools::redirectAdmin($this->context->link->getAdminLink('AdminMyApi'));
  }
}

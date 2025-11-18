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
    $this->version = '1.0.0';
    $this->author = 'Tu Nombre';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = [
      'min' => '8.0.0',
      'max' => _PS_VERSION_
    ];
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('My Custom API');
    $this->description = $this->l('API extensible para PrestaShop 8');
  }

  public function install()
  {
    if (!parent::install()) {
      return false;
    }

    // Registrar el hook manualmente si falla
    if (!$this->registerHook('moduleRoutes')) {
      // Forzar registro en la base de datos
      $id_hook = Hook::getIdByName('moduleRoutes');
      $this->registerHook('moduleRoutes');
    }

    return $this->installTab() &&
      Configuration::updateValue('MYAPI_PROD_KEY', 'TEST_KEY_PARA_DESARROLLO');
  }

  private function installTab()
  {
    $tab = new Tab();
    $tab->class_name = 'AdminMyApi';
    $tab->module = $this->name;
    $tab->id_parent = (int)Tab::getIdFromClassName('CONFIGURE');
    $tab->name = array();

    foreach (Language::getLanguages(true) as $lang) {
      $tab->name[$lang['id_lang']] = 'My API Config';
    }

    return $tab->add();
  }

  private function uninstallTab()
  {
    $id_tab = (int)Tab::getIdFromClassName('AdminMyApi');
    if ($id_tab) {
      $tab = new Tab($id_tab);
      return $tab->delete();
    }
    return true;
  }


  public function uninstall()
  {
    return $this->uninstallTab() &&
      parent::uninstall();
  }
  public function getContent()
  {
    // Redirigir al controlador admin CON NUEVO NOMBRE
    Tools::redirectAdmin(
      $this->context->link->getAdminLink('AdminMyApi') // CAMBIADO
    );
  }

  public function hookModuleRoutes($params)
  {
    // Debug para ver si se ejecuta el hook
    error_log("=== MYAPI hookModuleRoutes EJECUTADO ===");

    $routes = include $this->getLocalPath() . 'config/api_routes.php';

    error_log("Routes loaded: " . print_r($routes, true));

    return $routes;
  }
}

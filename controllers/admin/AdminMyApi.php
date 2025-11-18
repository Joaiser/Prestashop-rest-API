<?php

class AdminMyApiController extends ModuleAdminController // CAMBIADO el nombre
{
  public function __construct()
  {
    $this->bootstrap = true;
    $this->display = 'view';
    parent::__construct();

    if (!$this->module->active) {
      Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
    }
  }

  public function initContent()
  {
    parent::initContent();

    // ✅ SOLO generar nueva key si se envía el formulario
    if (Tools::isSubmit('generate_key')) {
      $newKey = 'SALAMANDRA_' . bin2hex(random_bytes(16));
      Configuration::updateValue('MYAPI_PROD_KEY', $newKey);
      $this->confirmations[] = $this->l('Nueva API Key generada: ') . $newKey;
    }

    $apiKey = Configuration::get('MYAPI_PROD_KEY');

    $this->context->smarty->assign([
      'api_key' => $apiKey,
      'api_url' => $this->context->shop->getBaseURL() . 'api/v1/products',
      'token' => Tools::getAdminTokenLite('AdminMyApi'),
    ]);

    $this->setTemplate('configure.tpl');
  }
}

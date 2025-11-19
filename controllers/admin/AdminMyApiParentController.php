<?php
class AdminMyApiParentController extends ModuleAdminController
{
  public function __construct()
  {
    $this->bootstrap = true;
    parent::__construct();

    // Redirigir al primer hijo (Configuración API)
    Tools::redirectAdmin($this->context->link->getAdminLink('AdminMyApi'));
  }
}

<?php
class AdminMyApiClientsController extends ModuleAdminController
{
  public function __construct()
  {

    if (!class_exists('ApiClient')) {
      require_once _PS_MODULE_DIR_ . 'myapi/classes/ApiClient.php';
    }

    $this->bootstrap = true;
    $this->table = 'external_api_clients';
    $this->className = 'ApiClient';
    $this->identifier = 'id_client';
    $this->lang = false;

    parent::__construct();

    $this->fields_list = [
      'id_client' => [
        'title' => 'ID',
        'align' => 'center',
        'class' => 'fixed-width-xs'
      ],
      'client_name' => [
        'title' => 'Cliente',
        'width' => 'auto',
        'filter_key' => 'a!client_name'
      ],
      'company' => [
        'title' => 'Empresa',
        'width' => 'auto'
      ],
      'api_key' => [
        'title' => 'API Key',
        'width' => 'auto',
        'callback' => 'maskApiKey',
        'search' => false
      ],
      'is_active' => [
        'title' => 'Activo',
        'active' => 'status',
        'type' => 'bool',
        'align' => 'center',
        'orderby' => false
      ],
      'requests_count' => [
        'title' => 'Peticiones',
        'align' => 'center',
        'class' => 'fixed-width-sm'
      ],
      'last_request' => [
        'title' => 'Última petición',
        'type' => 'datetime',
        'align' => 'center'
      ],
      'created_at' => [
        'title' => 'Creado',
        'type' => 'datetime',
        'align' => 'center'
      ]
    ];

    $this->bulk_actions = [
      'enable' => [
        'text' => $this->l('Activar seleccionados'),
        'icon' => 'icon-power-off text-success'
      ],
      'disable' => [
        'text' => $this->l('Desactivar seleccionados'),
        'icon' => 'icon-power-off text-danger'
      ],
      'delete' => [
        'text' => $this->l('Eliminar seleccionados'),
        'confirm' => $this->l('¿Estás seguro de eliminar los clientes seleccionados?')
      ]
    ];
  }

  public function renderForm()
  {
    $this->fields_form = [
      'legend' => [
        'title' => $this->l('Gestión de Cliente API'),
        'icon' => 'icon-key'
      ],
      'input' => [
        [
          'type' => 'text',
          'label' => $this->l('Nombre del cliente'),
          'name' => 'client_name',
          'required' => true,
          'col' => 6,
          'hint' => $this->l('Nombre identificativo del cliente')
        ],
        [
          'type' => 'text',
          'label' => $this->l('Empresa'),
          'name' => 'company',
          'col' => 6,
          'hint' => $this->l('Nombre de la empresa del cliente')
        ],
        [
          'type' => 'text',
          'label' => $this->l('Email de contacto'),
          'name' => 'email',
          'col' => 6,
          'hint' => $this->l('Email para notificaciones')
        ],
        [
          'type' => 'textarea',
          'label' => $this->l('Webhook URL'),
          'name' => 'webhook_url',
          'col' => 6,
          'rows' => 3,
          'hint' => $this->l('URL para notificar cambios de productos (opcional)')
        ],
        [
          'type' => 'text',
          'label' => $this->l('Límite de peticiones/hora'),
          'name' => 'rate_limit',
          'col' => 3,
          'suffix' => $this->l('peticiones/hora'),
          'default' => 1000,
          'hint' => $this->l('Máximo número de peticiones por hora')
        ],
        [
          'type' => 'switch',
          'label' => $this->l('Activo'),
          'name' => 'is_active',
          'required' => false,
          'is_bool' => true,
          'values' => [
            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sí')],
            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')]
          ]
        ]
      ],
      'submit' => [
        'title' => $this->l('Guardar'),
        'class' => 'btn btn-default pull-right'
      ]
    ];

    return parent::renderForm();
  }

  public function processAdd()
  {
    // Generar API Key automáticamente
    $_POST['api_key'] = 'SALAMANDRA_' . bin2hex(random_bytes(16));
    $_POST['secret_key'] = bin2hex(random_bytes(32));
    $_POST['created_at'] = date('Y-m-d H:i:s');
    $_POST['updated_at'] = date('Y-m-d H:i:s');

    return parent::processAdd();
  }

  public function processUpdate()
  {
    $_POST['updated_at'] = date('Y-m-d H:i:s');
    return parent::processUpdate();
  }

  public function maskApiKey($key, $row)
  {
    if (empty($key)) return '-';
    return substr($key, 0, 12) . '...' . substr($key, -8);
  }

  public function initPageHeaderToolbar()
  {
    parent::initPageHeaderToolbar();

    if (empty($this->display) || $this->display == 'list') {
      $this->page_header_toolbar_btn['new_api_client'] = [
        'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
        'desc' => $this->l('Añadir nuevo cliente'),
        'icon' => 'process-icon-new'
      ];
    }
  }
}

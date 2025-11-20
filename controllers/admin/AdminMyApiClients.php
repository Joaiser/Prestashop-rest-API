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

    if (!$this->module) {
      $this->module = Module::getInstanceByName('myapi');
    }

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
      'allowed_endpoints' => [
        'title' => 'Endpoints',
        'width' => 'auto',
        'callback' => 'formatEndpoints',
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

    // ✅ AÑADIR ACCIONES INDIVIDUALES
    $this->actions = ['edit', 'delete', 'copy'];
  }

  // ✅ BOTÓN PERSONALIZADO "COPIAR" - LIMPIO
  public function displayCopyLink($token, $id, $name = null)
  {
    $client = new ApiClient($id);
    if (!Validate::isLoadedObject($client)) {
      return '';
    }

    $apiKeyEscaped = htmlspecialchars($client->api_key, ENT_QUOTES, 'UTF-8');

    // ✅ JAVASCRIPT INLINE LIMPIO
    $javascript = '
        <script>
        function copyApiKeyToClipboard(apiKey) {
            var tempInput = document.createElement("input");
            tempInput.value = apiKey;
            document.body.appendChild(tempInput);
            tempInput.select();
            tempInput.setSelectionRange(0, 99999);
            
            try {
                var successful = document.execCommand("copy");
                if (successful) {
                    alert("' . $this->l('API Key copiada al portapapeles') . '");
                } else {
                    alert("' . $this->l('Error al copiar la API Key') . '");
                }
            } catch (err) {
                alert("' . $this->l('Error al copiar la API Key') . '");
            }
            document.body.removeChild(tempInput);
        }
        </script>
        ';

    return $javascript . '
            <a class="btn btn-default" href="javascript:void(0)" 
               onclick="copyApiKeyToClipboard(\'' . $apiKeyEscaped . '\')" 
               title="' . $this->l('Copiar API Key') . '">
                <i class="icon-clipboard"></i> ' . $this->l('Copiar') . '
            </a>
        ';
  }

  public function renderForm()
  {
    $availableEndpoints = $this->module->getAvailableEndpoints();

    // CONSTRUIR INPUTS DINÁMICAMENTE
    $inputs = [
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
      // ENDPOINTS PERMITIDOS
      [
        'type' => 'checkbox',
        'label' => $this->l('Endpoints permitidos'),
        'name' => 'allowed_endpoints',
        'values' => [
          'query' => $this->getEndpointsForCheckboxes(),
          'id' => 'id',
          'name' => 'name'
        ],
        'expand' => [
          'print_total' => count($availableEndpoints),
          'default' => 'show',
          'show' => ['text' => $this->l('mostrar'), 'icon' => 'plus-sign-alt'],
          'hide' => ['text' => $this->l('ocultar'), 'icon' => 'minus-sign-alt']
        ],
        'hint' => $this->l('Selecciona los endpoints a los que puede acceder este cliente')
      ],
      // SEPARADOR
      [
        'type' => 'free',
        'name' => 'fields_separator',
        'col' => 12,
        'html_content' => '<hr><h4>' . $this->l('Campos permitidos por endpoint') . '</h4><p class="help-block">' . $this->l('Selecciona los campos específicos para cada endpoint') . '</p>'
      ]
    ];

    // AÑADIR CAMPOS POR CADA ENDPOINT
    foreach ($availableEndpoints as $endpointKey => $endpointData) {
      $inputs[] = [
        'type' => 'checkbox',
        'label' => $endpointData['label'],
        'name' => 'allowed_fields_' . $endpointKey,
        'values' => [
          'query' => $this->getFieldsForCheckboxes($endpointKey),
          'id' => 'id',
          'name' => 'name'
        ],
        'expand' => [
          'print_total' => count($endpointData['fields']),
          'default' => 'hide',
          'show' => ['text' => $this->l('mostrar campos'), 'icon' => 'plus-sign-alt'],
          'hide' => ['text' => $this->l('ocultar campos'), 'icon' => 'minus-sign-alt']
        ]
      ];
    }

    // SWITCH ACTIVO
    $inputs[] = [
      'type' => 'switch',
      'label' => $this->l('Activo'),
      'name' => 'is_active',
      'required' => false,
      'is_bool' => true,
      'values' => [
        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sí')],
        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')]
      ]
    ];

    $this->fields_form = [
      'legend' => [
        'title' => $this->l('Gestión de Cliente API'),
        'icon' => 'icon-key'
      ],
      'input' => $inputs,
      'submit' => [
        'title' => $this->l('Guardar'),
        'class' => 'btn btn-default pull-right'
      ],
      'buttons' => [
        'regenerate_keys' => [
          'title' => $this->l('Regenerar Keys'),
          'name' => 'regenerateKeys',
          'type' => 'submit',
          'class' => 'btn btn-warning pull-left',
          'icon' => 'process-icon-refresh'
        ]
      ]
    ];

    return parent::renderForm();
  }

  protected function getEndpointsForCheckboxes()
  {
    $endpoints = $this->module->getAvailableEndpoints();
    $checkboxData = [];

    foreach ($endpoints as $key => $data) {
      $checkboxData[] = [
        'id' => $key,
        'name' => $data['label'],
        'val' => $key
      ];
    }

    return $checkboxData;
  }

  protected function getFieldsForCheckboxes($endpoint)
  {
    $endpoints = $this->module->getAvailableEndpoints();
    if (!isset($endpoints[$endpoint])) {
      return [];
    }

    $checkboxData = [];
    foreach ($endpoints[$endpoint]['fields'] as $key => $label) {
      $checkboxData[] = [
        'id' => $key,
        'name' => $label,
        'val' => $key
      ];
    }

    return $checkboxData;
  }

  public function getFieldsValue($obj)
  {
    $values = parent::getFieldsValue($obj);

    if ($obj && $obj->id) {
      // Cargar endpoints permitidos
      $allowedEndpoints = json_decode($obj->allowed_endpoints, true) ?: [];
      foreach ($allowedEndpoints as $endpoint) {
        $values['allowed_endpoints_' . $endpoint] = true;
      }

      // Cargar campos permitidos
      $allowedFields = json_decode($obj->allowed_fields, true) ?: [];
      foreach ($allowedFields as $endpoint => $fields) {
        foreach ($fields as $field) {
          $values['allowed_fields_' . $endpoint . '_' . $field] = true;
        }
      }
    }

    return $values;
  }

  protected function processPermissions()
  {
    // Procesar endpoints
    $allowedEndpoints = [];
    $availableEndpoints = $this->module->getAvailableEndpoints();

    foreach (array_keys($availableEndpoints) as $endpoint) {
      if (Tools::getValue('allowed_endpoints_' . $endpoint)) {
        $allowedEndpoints[] = $endpoint;
      }
    }

    $_POST['allowed_endpoints'] = json_encode($allowedEndpoints);

    // Procesar campos
    $allowedFields = [];
    foreach ($availableEndpoints as $endpoint => $endpointData) {
      $fieldsForEndpoint = [];
      foreach (array_keys($endpointData['fields']) as $field) {
        if (Tools::getValue('allowed_fields_' . $endpoint . '_' . $field)) {
          $fieldsForEndpoint[] = $field;
        }
      }
      if (!empty($fieldsForEndpoint)) {
        $allowedFields[$endpoint] = $fieldsForEndpoint;
      }
    }

    $_POST['allowed_fields'] = json_encode($allowedFields);
  }

  // ✅ RENDERIZAR CAMPO API KEY CON BOTÓN COPIAR EN EL FORMULARIO - LIMPIO
  public function renderOptions()
  {
    if ($this->object && $this->object->id) {
      $apiKey = $this->object->api_key;

      // ✅ JAVASCRIPT INLINE LIMPIO
      $javascript = '
            <script>
            function copyApiKeyFromForm() {
                var apiKey = "' . $apiKey . '";
                var tempInput = document.createElement("input");
                tempInput.value = apiKey;
                document.body.appendChild(tempInput);
                tempInput.select();
                tempInput.setSelectionRange(0, 99999);
                
                try {
                    var successful = document.execCommand("copy");
                    if (successful) {
                        alert("' . $this->l('API Key copiada al portapapeles') . '");
                    } else {
                        alert("' . $this->l('Error al copiar la API Key') . '");
                    }
                } catch (err) {
                    alert("' . $this->l('Error al copiar la API Key') . '");
                }
                document.body.removeChild(tempInput);
            }
            </script>
            ';

      $copyButton = $javascript . '
                <div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('API Key') . '</label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="text" id="api_key_field" class="form-control" value="' . $apiKey . '" readonly>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="copyApiKeyFromForm()">
                                    <i class="icon-clipboard"></i> ' . $this->l('Copiar') . '
                                </button>
                            </span>
                        </div>
                        <p class="help-block">' . $this->l('Copia esta clave y envíala al cliente') . '</p>
                    </div>
                </div>
            ';

      $this->fields_value['api_key_display'] = $copyButton;
    }

    return parent::renderOptions();
  }

  // ✅ PROCESAR REGENERACIÓN DE KEYS - VERSIÓN MEJORADA
  public function processRegenerateKeys()
  {
    if (Tools::isSubmit('regenerateKeys')) {
      $clientId = Tools::getValue('id_client');
      if ($clientId) {
        $client = new ApiClient($clientId);
        if (Validate::isLoadedObject($client)) {
          $client->api_key = 'SALAMANDRA_' . bin2hex(random_bytes(16));
          $client->secret_key = bin2hex(random_bytes(32));
          $client->updated_at = date('Y-m-d');

          if ($client->save()) {
            $this->confirmations[] = $this->l('Keys regeneradas correctamente');
            // ✅ REDIRIGIR PARA EVITAR REENVÍO DEL FORMULARIO
            Tools::redirectAdmin(self::$currentIndex . '&id_client=' . $clientId . '&update' . $this->table . '&conf=4&token=' . $this->token);
          } else {
            $this->errors[] = $this->l('Error al regenerar keys');
          }
        }
      }
    }
  }

  public function processUpdate()
  {
    // ✅ PROCESAR REGENERACIÓN PRIMERO
    $this->processRegenerateKeys();

    // ✅ PROCESAR PERMISOS
    $this->processPermissions();

    // ✅ SI NO SE REGENERÓ, ACTUALIZAR NORMAL
    if (!Tools::isSubmit('regenerateKeys')) {
      $_POST['updated_at'] = date('Y-m-d');
      return parent::processUpdate();
    }

    return true;
  }

  public function processAdd()
  {
    // ✅ PROCESAR PERMISOS
    $this->processPermissions();

    // Generar API Key automáticamente
    $_POST['api_key'] = 'SALAMANDRA_' . bin2hex(random_bytes(16));
    $_POST['secret_key'] = bin2hex(random_bytes(32));
    $_POST['created_at'] = date('Y-m-d');
    $_POST['updated_at'] = date('Y-m-d');

    return parent::processAdd();
  }

  public function maskApiKey($key, $row)
  {
    if (empty($key)) return '-';
    return substr($key, 0, 12) . '...' . substr($key, -8);
  }

  /**
   * Formatear endpoints para mostrar en la lista
   */
  public function formatEndpoints($endpoints, $row)
  {
    if (empty($endpoints)) {
      return '<span class="badge badge-warning">' . $this->l('Ninguno') . '</span>';
    }

    $endpoints_array = json_decode($endpoints, true);
    if (empty($endpoints_array)) {
      return '<span class="badge badge-warning">' . $this->l('Ninguno') . '</span>';
    }

    $availableEndpoints = $this->module->getAvailableEndpoints();
    $endpointLabels = [];

    foreach ($endpoints_array as $endpoint) {
      if (isset($availableEndpoints[$endpoint])) {
        $endpointLabels[] = $availableEndpoints[$endpoint]['label'];
      } else {
        $endpointLabels[] = $endpoint;
      }
    }

    $count = count($endpointLabels);
    $preview = implode(', ', array_slice($endpointLabels, 0, 2));
    if ($count > 2) {
      $preview .= '... (+' . ($count - 2) . ')';
    }

    return '<span class="badge badge-success" title="' . implode(', ', $endpointLabels) . '">' . $preview . '</span>';
  }

  public function initPageHeaderToolbar()
  {
    parent::initPageHeaderToolbar();

    // ✅ BOTÓN PARA IR A CONFIGURACIÓN PRINCIPAL
    $this->page_header_toolbar_btn['main_config'] = [
      'href' => $this->context->link->getAdminLink('AdminMyApi'),
      'desc' => $this->l('Configuración Principal'),
      'icon' => 'process-icon-cogs'
    ];

    if (empty($this->display) || $this->display == 'list') {
      $this->page_header_toolbar_btn['new_api_client'] = [
        'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
        'desc' => $this->l('Añadir nuevo cliente'),
        'icon' => 'process-icon-new'
      ];
    }
  }
}

<?php
class MyApiApidocsModuleFrontController extends ModuleFrontController
{
  public function __construct()
  {
    parent::__construct();
    $this->auth = false;
    $this->guestAllowed = true;
  }

  public function init()
  {
    $this->auth = false;
    $this->guestAllowed = true;
    parent::init();
  }

  public function initContent()
  {
    // ✅ HEADERS para HTML
    header('Content-Type: text/html');
    header('Access-Control-Allow-Origin: *');

    $this->generateDocumentation();
  }

  private function generateDocumentation()
  {
    if (isset($_GET['json'])) {
      header('Content-Type: application/json');
      echo json_encode($this->getOpenAPISpec(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      exit;
    }

    $this->renderSwaggerUI($this->getOpenAPISpec());
    exit; // ← IMPORTANTE: evitar que PrestaShop continúe
  }
  private function getOpenAPISpec()
  {
    $apiUrl = _PS_BASE_URL_ . __PS_BASE_URI__;

    return [
      'openapi' => '3.0.0',
      'info' => [
        'title' => 'Salamandra Luz API',
        'description' => 'API completa para gestión de productos y categorías',
        'version' => '1.0.0'
      ],
      'servers' => [
        ['url' => $apiUrl, 'description' => 'Servidor principal']
      ],
      'paths' => $this->getPaths(),
      'components' => [
        'securitySchemes' => [
          'ApiKeyAuth' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key'
          ]
        ],
        'schemas' => $this->getSchemas()
      ]
    ];
  }

  private function getPaths()
  {
    return [
      '/api/v1/products' => [
        'get' => [
          'summary' => 'Listar productos',
          'description' => 'Obtiene lista paginada de productos',
          'parameters' => [
            [
              'name' => 'page',
              'in' => 'query',
              'schema' => ['type' => 'integer', 'default' => 1]
            ],
            [
              'name' => 'limit',
              'in' => 'query',
              'schema' => ['type' => 'integer', 'default' => 50]
            ]
          ],
          'responses' => [
            '200' => [
              'description' => 'Lista de productos',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string'],
                      'data' => [
                        'type' => 'object',
                        'properties' => [
                          'products' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/Product']
                          ]
                        ]
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ],

      '/api/v1/products/{id}' => [
        'get' => [
          'summary' => 'Obtener producto específico',
          'parameters' => [
            [
              'name' => 'id',
              'in' => 'path',
              'required' => true,
              'schema' => ['type' => 'integer']
            ]
          ],
          'responses' => [
            '200' => [
              'description' => 'Detalles del producto',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string'],
                      'data' => ['$ref' => '#/components/schemas/ProductDetailed']
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ],

      '/api/v1/products/{id}/images' => [
        'get' => [
          'summary' => 'Obtener imágenes del producto',
          'parameters' => [
            [
              'name' => 'id',
              'in' => 'path',
              'required' => true,
              'schema' => ['type' => 'integer']
            ]
          ],
          'responses' => [
            '200' => [
              'description' => 'Imágenes del producto',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string'],
                      'data' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/ProductImage']
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ];
  }

  private function getSchemas()
  {
    return [
      'Product' => [
        'type' => 'object',
        'properties' => [
          'id' => ['type' => 'integer'],
          'name' => ['type' => 'string'],
          'price' => ['type' => 'number'],
          'reference' => ['type' => 'string'],
          'active' => ['type' => 'boolean'],
          'stock' => ['type' => 'integer']
        ]
      ],
      'ProductDetailed' => [
        'type' => 'object',
        'properties' => [
          'id' => ['type' => 'integer'],
          'name' => ['type' => 'string'],
          'price' => ['type' => 'number'],
          'images' => [
            'type' => 'array',
            'items' => ['$ref' => '#/components/schemas/ProductImage']
          ],
          'categories' => ['type' => 'array', 'items' => ['type' => 'integer']]
        ]
      ],
      'ProductImage' => [
        'type' => 'object',
        'properties' => [
          'id' => ['type' => 'integer'],
          'cover' => ['type' => 'boolean'],
          'sizes' => [
            'type' => 'object',
            'additionalProperties' => [
              'type' => 'object',
              'properties' => [
                'url' => ['type' => 'string'],
                'width' => ['type' => 'integer'],
                'height' => ['type' => 'integer']
              ]
            ]
          ]
        ]
      ]
    ];
  }

  private function renderSwaggerUI($spec)
  {
    // ✅ JSON válido para JavaScript
    $specJson = json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Salamandra Luz API - Documentación</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        #swagger-ui { padding: 20px; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            // ✅ CONFIGURACIÓN SIMPLE - sin layouts complejos
            SwaggerUIBundle({
                spec: $specJson,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis
                ],
                layout: "BaseLayout" // ✅ Layout por defecto que SÍ existe
            });
        }
    </script>
</body>
</html>
HTML;
  }
}

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
    exit;
  }

  private function getOpenAPISpec()
  {
    $apiUrl = _PS_BASE_URL_ . __PS_BASE_URI__;

    return [
      'openapi' => '3.0.0',
      'info' => [
        'title' => 'Salamandra Luz API',
        'description' => 'API completa para gestión de productos y categorías',
        'version' => '1.0.0',
        'contact' => [
          'name' => 'Soporte API',
          'email' => 'soporte@salamandraluz.net'
        ]
      ],
      'servers' => [
        ['url' => 'https://test6.salamandraluz.net', 'description' => 'Servidor principal HTTPS']
      ],
      'paths' => $this->getPaths(),
      'components' => [
        'securitySchemes' => [
          'ApiKeyAuth' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
            'description' => 'Claves de testing: API_KEY_PRUEBA, mykey, test, testing, demo'
          ]
        ],
        'schemas' => $this->getSchemas(),
        // ✅ AÑADE ESTOS RESPONSES QUE FALTAN:
        'responses' => [
          'Unauthorized' => [
            'description' => 'API Key inválida o faltante',
            'content' => [
              'application/json' => [
                'schema' => [
                  'type' => 'object',
                  'properties' => [
                    'status' => ['type' => 'string', 'example' => 'error'],
                    'error' => ['type' => 'string', 'example' => 'API Key inválida']
                  ]
                ]
              ]
            ]
          ],
          'NotFound' => [
            'description' => 'Recurso no encontrado',
            'content' => [
              'application/json' => [
                'schema' => [
                  'type' => 'object',
                  'properties' => [
                    'status' => ['type' => 'string', 'example' => 'error'],
                    'error' => ['type' => 'string', 'example' => 'Recurso no encontrado']
                  ]
                ]
              ]
            ]
          ],
          'ServerError' => [
            'description' => 'Error interno del servidor',
            'content' => [
              'application/json' => [
                'schema' => [
                  'type' => 'object',
                  'properties' => [
                    'status' => ['type' => 'string', 'example' => 'error'],
                    'error' => ['type' => 'string', 'example' => 'Error interno del servidor']
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ];
  }

  private function getPaths()
  {
    return [
      // ========== PRODUCTOS ==========
      '/api/v1/products' => [
        'get' => [
          'summary' => 'Listar productos paginados',
          'description' => 'Obtiene lista paginada de productos con stock, imágenes y categorías',
          'security' => [['ApiKeyAuth' => []]],
          'parameters' => [
            [
              'name' => 'page',
              'in' => 'query',
              'schema' => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
              'description' => 'Número de página'
            ],
            [
              'name' => 'limit',
              'in' => 'query',
              'schema' => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100],
              'description' => 'Productos por página (máx. 100)'
            ]
          ],
          'responses' => [
            '200' => [
              'description' => 'Lista de productos paginada',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string', 'example' => 'success'],
                      'data' => [
                        'type' => 'object',
                        'properties' => [
                          'products' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/Product']
                          ],
                          'pagination' => ['$ref' => '#/components/schemas/Pagination']
                        ]
                      ]
                    ]
                  ]
                ]
              ]
            ],
            '401' => ['$ref' => '#/components/responses/Unauthorized'],
            '500' => ['$ref' => '#/components/responses/ServerError']
          ]
        ],
        'post' => [
          'summary' => 'Crear nuevo producto',
          'description' => 'Crea un nuevo producto en el catálogo',
          'security' => [['ApiKeyAuth' => []]],
          'requestBody' => [
            'required' => true,
            'content' => [
              'application/json' => [
                'schema' => [
                  'type' => 'object',
                  'required' => ['name', 'reference', 'price'],
                  'properties' => [
                    'name' => ['type' => 'string', 'example' => 'Mi Producto'],
                    'reference' => ['type' => 'string', 'example' => 'REF-001'],
                    'price' => ['type' => 'number', 'example' => 29.99],
                    'description' => ['type' => 'string'],
                    'active' => ['type' => 'boolean', 'default' => true],
                    'stock' => ['type' => 'integer', 'default' => 0],
                    'categories' => [
                      'type' => 'array',
                      'items' => ['type' => 'integer'],
                      'example' => [2, 5]
                    ]
                  ]
                ]
              ]
            ]
          ],
          'responses' => [
            '201' => [
              'description' => 'Producto creado exitosamente',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string'],
                      'data' => [
                        'type' => 'object',
                        'properties' => [
                          'id' => ['type' => 'integer'],
                          'name' => ['type' => 'string'],
                          'reference' => ['type' => 'string'],
                          'message' => ['type' => 'string']
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
          'description' => 'Obtiene todos los detalles de un producto específico incluyendo imágenes y categorías',
          'security' => [['ApiKeyAuth' => []]],
          'parameters' => [
            [
              'name' => 'id',
              'in' => 'path',
              'required' => true,
              'schema' => ['type' => 'integer'],
              'description' => 'ID del producto'
            ]
          ],
          'responses' => [
            '200' => [
              'description' => 'Detalles completos del producto',
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
            ],
            '404' => ['$ref' => '#/components/responses/NotFound']
          ]
        ],
        'put' => [
          'summary' => 'Actualizar producto',
          'security' => [['ApiKeyAuth' => []]],
          'parameters' => [
            [
              'name' => 'id',
              'in' => 'path',
              'required' => true,
              'schema' => ['type' => 'integer']
            ]
          ],
          'requestBody' => [
            'content' => [
              'application/json' => [
                'schema' => [
                  'type' => 'object',
                  'properties' => [
                    'name' => ['type' => 'string'],
                    'price' => ['type' => 'number'],
                    'reference' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'active' => ['type' => 'boolean'],
                    'stock' => ['type' => 'integer']
                  ]
                ]
              ]
            ]
          ],
          'responses' => [
            '200' => ['description' => 'Producto actualizado']
          ]
        ],
        'delete' => [
          'summary' => 'Eliminar producto',
          'security' => [['ApiKeyAuth' => []]],
          'parameters' => [
            [
              'name' => 'id',
              'in' => 'path',
              'required' => true,
              'schema' => ['type' => 'integer']
            ]
          ],
          'responses' => [
            '200' => ['description' => 'Producto eliminado']
          ]
        ]
      ],

      '/api/v1/products/{id}/images' => [
        'get' => [
          'summary' => 'Obtener imágenes del producto',
          'description' => 'Obtiene todas las imágenes del producto en diferentes tamaños',
          'security' => [['ApiKeyAuth' => []]],
          'parameters' => [
            [
              'name' => 'id',
              'in' => 'path',
              'required' => true,
              'schema' => ['type' => 'integer'],
              'description' => 'ID del producto'
            ]
          ],
          'responses' => [
            '200' => [
              'description' => 'Lista de imágenes del producto',
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
      ],

      '/api/v1/products/featured' => [
        'get' => [
          'summary' => 'Productos destacados',
          'description' => 'Obtiene productos destacados, en oferta o nuevos',
          'security' => [['ApiKeyAuth' => []]],
          'responses' => [
            '200' => [
              'description' => 'Lista de productos destacados',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string'],
                      'data' => [
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
      ],

      // ========== CATEGORÍAS ==========
      '/api/v1/categories' => [
        'get' => [
          'summary' => 'Listar todas las categorías',
          'description' => 'Obtiene todas las categorías activas con conteo de productos',
          'security' => [['ApiKeyAuth' => []]],
          'responses' => [
            '200' => [
              'description' => 'Lista de categorías',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string'],
                      'data' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Category']
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ],

      '/api/v1/categories/{id}/products' => [
        'get' => [
          'summary' => 'Productos por categoría',
          'description' => 'Obtiene todos los productos de una categoría específica',
          'security' => [['ApiKeyAuth' => []]],
          'parameters' => [
            [
              'name' => 'id',
              'in' => 'path',
              'required' => true,
              'schema' => ['type' => 'integer'],
              'description' => 'ID de la categoría'
            ]
          ],
          'responses' => [
            '200' => [
              'description' => 'Categoría y sus productos',
              'content' => [
                'application/json' => [
                  'schema' => [
                    'type' => 'object',
                    'properties' => [
                      'status' => ['type' => 'string'],
                      'data' => [
                        'type' => 'object',
                        'properties' => [
                          'category' => ['$ref' => '#/components/schemas/Category'],
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
      ]
    ];
  }

  private function getSchemas()
  {
    return [
      'Product' => [
        'type' => 'object',
        'properties' => [
          'id' => ['type' => 'integer', 'example' => 1],
          'name' => ['type' => 'string', 'example' => 'Lámpara de Mesa Moderna'],
          'price' => ['type' => 'number', 'example' => 89.99],
          'reference' => ['type' => 'string', 'example' => 'LAMP-001'],
          'active' => ['type' => 'boolean', 'example' => true],
          'stock' => ['type' => 'integer', 'example' => 25],
          'description' => ['type' => 'string']
        ]
      ],
      'ProductDetailed' => [
        'type' => 'object',
        'properties' => [
          'id' => ['type' => 'integer'],
          'name' => ['type' => 'string'],
          'price' => ['type' => 'number'],
          'reference' => ['type' => 'string'],
          'active' => ['type' => 'boolean'],
          'stock' => ['type' => 'integer'],
          'description' => ['type' => 'string'],
          'images' => [
            'type' => 'array',
            'items' => ['$ref' => '#/components/schemas/ProductImage']
          ],
          'categories' => [
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'example' => [2, 5, 8]
          ],
          'manufacturer' => ['type' => 'string'],
          'features' => [
            'type' => 'array',
            'items' => ['type' => 'object']
          ]
        ]
      ],
      'ProductImage' => [
        'type' => 'object',
        'properties' => [
          'id' => ['type' => 'integer', 'example' => 123],
          'position' => ['type' => 'integer', 'example' => 1],
          'cover' => ['type' => 'boolean', 'example' => true],
          'legend' => ['type' => 'string', 'example' => 'Lámpara vista frontal'],
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
      ],
      'Category' => [
        'type' => 'object',
        'properties' => [
          'id' => ['type' => 'integer', 'example' => 5],
          'name' => ['type' => 'string', 'example' => 'Lámparas de Interior'],
          'description' => ['type' => 'string'],
          'link_rewrite' => ['type' => 'string', 'example' => 'lamparas-interior'],
          'active' => ['type' => 'boolean', 'example' => true],
          'position' => ['type' => 'integer', 'example' => 2],
          'parent_id' => ['type' => 'integer', 'example' => 3],
          'products_count' => ['type' => 'integer', 'example' => 45]
        ]
      ],
      'Pagination' => [
        'type' => 'object',
        'properties' => [
          'current_page' => ['type' => 'integer', 'example' => 1],
          'per_page' => ['type' => 'integer', 'example' => 50],
          'total_products' => ['type' => 'integer', 'example' => 125],
          'total_pages' => ['type' => 'integer', 'example' => 3],
          'has_next' => ['type' => 'boolean', 'example' => true],
          'has_prev' => ['type' => 'boolean', 'example' => false]
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
    <title>Salamandra Luz API - Documentación Completa</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        #swagger-ui { padding: 20px; }
        .swagger-ui .info .title { color: #e74c3c; }
        .swagger-ui .btn.authorize { background-color: #e74c3c; border-color: #e74c3c; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                spec: $specJson,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis
                ],
                layout: "BaseLayout",
                requestInterceptor: function(request) {
                    // Auto-add API key for testing
                    if (!request.headers['X-API-Key']) {
                        request.headers['X-API-Key'] = 'API_KEY_PRUEBA';
                    }
                    return request;
                }
            });
        }
    </script>
</body>
</html>
HTML;
  }
}

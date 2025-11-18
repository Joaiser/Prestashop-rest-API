<div class="panel">
  <div class="panel-heading">
    <i class="icon icon-key"></i> Configuración API - Producción
  </div>

  <div class="alert alert-info">
    <strong>URL Base de la API:</strong> {$api_url}
  </div>

  <div class="alert alert-warning">
    <strong>API Key Actual:</strong> {$api_key|default:'No generada aún'}
  </div>

  <form method="post">
    <button type="submit" name="generate_key" class="btn btn-primary">
      <i class="icon icon-refresh"></i> Generar Nueva API Key
    </button>
  </form>

  <hr>

  <h4>Cómo usar la API:</h4>
  <pre>
// Con header
curl -H "X-API-Key: {$api_key}" "{$api_url}"

// Con parámetro
curl "{$api_url}?api_key={$api_key}"
    </pre>

  <h4>📦 Endpoints de Productos:</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Método</th>
          <th>Endpoint</th>
          <th>Descripción</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><span class="label label-success">GET</span></td>
          <td><code>/api/v1/products</code></td>
          <td>Listar productos (con paginación)</td>
        </tr>
        <tr>
          <td><span class="label label-success">GET</span></td>
          <td><code>/api/v1/products/123</code></td>
          <td>Obtener producto específico (detalles completos)</td>
        </tr>
        <tr>
          <td><span class="label label-success">GET</span></td>
          <td><code>/api/v1/products/123/images</code></td>
          <td><strong>NUEVO:</strong> Obtener todas las imágenes del producto en todos los tamaños</td>
        </tr>
        <tr>
          <td><span class="label label-success">GET</span></td>
          <td><code>/api/v1/products/featured</code></td>
          <td>Productos destacados (ofertas)</td>
        </tr>
        <tr>
          <td><span class="label label-success">GET</span></td>
          <td><code>/api/v1/products/search</code></td>
          <td>Buscar productos (próximamente)</td>
        </tr>
        <tr>
          <td><span class="label label-success">GET</span></td>
          <td><code>/api/v1/products/categories/123</code></td>
          <td>Obtener categorías de un producto</td>
        </tr>
        <tr>
          <td><span class="label label-primary">POST</span></td>
          <td><code>/api/v1/products</code></td>
          <td>Crear nuevo producto</td>
        </tr>
        <tr>
          <td><span class="label label-warning">PUT</span></td>
          <td><code>/api/v1/products/123</code></td>
          <td>Actualizar producto existente</td>
        </tr>
        <tr>
          <td><span class="label label-danger">DELETE</span></td>
          <td><code>/api/v1/products/123</code></td>
          <td>Eliminar producto</td>
        </tr>
      </tbody>
    </table>
  </div>

  <h4>📁 Endpoints de Categorías:</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Método</th>
          <th>Endpoint</th>
          <th>Descripción</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><span class="label label-success">GET</span></td>
          <td><code>/api/v1/categories</code></td>
          <td>Listar todas las categorías</td>
        </tr>
      </tbody>
    </table>
  </div>

  <h4>🔧 Parámetros de Consulta (Query Parameters):</h4>
  <div class="alert alert-info">
    <strong>Para GET /api/v1/products:</strong>
    <ul>
      <li><code>?page=2</code> - Página específica (default: 1)</li>
      <li><code>?limit=20</code> - Productos por página (max: 100, default: 50)</li>
      <li><code>?api_key=tu_api_key</code> - Autenticación vía query parameter</li>
    </ul>
  </div>

  <h4>🖼️ Ejemplo de Respuesta de Imágenes:</h4>
  <pre>
{ldelim}
  "success": true,
  "data": [
    {ldelim}
      "id": 123,
      "position": 1,
      "cover": true,
      "legend": "Product main image",
      "sizes": {ldelim}
        "small_default": {ldelim}
          "url": "https://tutienda.com/img/p/1/2/3-small_default.jpg",
          "width": 125,
          "height": 125
        {rdelim},
        "medium_default": {ldelim}
          "url": "https://tutienda.com/img/p/1/2/3-medium_default.jpg",
          "width": 450,
          "height": 450
        {rdelim},
        "large_default": {ldelim}
          "url": "https://tutienda.com/img/p/1/2/3-large_default.jpg",
          "width": 800,
          "height": 800
        {rdelim},
        "home_default": {ldelim}
          "url": "https://tutienda.com/img/p/1/2/3-home_default.jpg",
          "width": 250,
          "height": 250
        {rdelim},
        "original": {ldelim}
          "url": "https://tutienda.com/img/p/1/2/3.jpg",
          "width": null,
          "height": null
        {rdelim}
      {rdelim}
    {rdelim}
  ]
{rdelim}
  </pre>

  <h4>📝 Ejemplo de Uso con cURL:</h4>
  <pre>
# Obtener productos paginados
curl -H "X-API-Key: {$api_key}" "{$api_url}/api/v1/products?page=1&limit=10"

# Obtener producto específico
curl -H "X-API-Key: {$api_key}" "{$api_url}/api/v1/products/123"

# Obtener imágenes de producto
curl -H "X-API-Key: {$api_key}" "{$api_url}/api/v1/products/123/images"

# Crear producto
curl -X POST -H "X-API-Key: {$api_key}" -H "Content-Type: application/json" \
  -d '{ldelim}"name":"Nuevo Producto","price":29.99,"stock":50{rdelim}' \
  "{$api_url}/api/v1/products"
  </pre>

  <div class="alert alert-success">
    <strong>💡 Tip:</strong> Todos los endpoints soportan CORS y pueden ser consumidos desde aplicaciones web frontend.
  </div>
</div>
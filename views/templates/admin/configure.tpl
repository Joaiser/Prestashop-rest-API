<div class="panel">
  <div class="panel-heading">
    <i class="icon icon-key"></i> Configuración API - Salamandra Luz
  </div>

  <!-- ✅ SECCIÓN SISTEMA MULTI-CLIENTE -->
  <div class="alert alert-success">
    <h4><i class="icon icon-rocket"></i> Sistema Multi-Cliente Activo</h4>
    <p>Gestiona múltiples clientes API desde el nuevo panel:</p>
    <a href="{$link->getAdminLink('AdminMyApiClients')}" class="btn btn-success">
      <i class="icon icon-users"></i> Gestionar Clientes API
    </a>
    <a href="{$api_url}/../docs" target="_blank" class="btn btn-info">
      <i class="icon icon-book"></i> Ver Documentación Completa
    </a>
  </div>

  <!-- ✅ INFORMACIÓN BÁSICA -->
  <div class="alert alert-info">
    <strong>🌐 URL Base de la API:</strong> {$api_url}
  </div>

  <div class="alert alert-warning">
    <strong>🔑 API Key Legacy:</strong> {$api_key|default:'No generada aún'}
    <br><small><i class="icon icon-info"></i> Esta clave es para compatibilidad. Usa el sistema multi-cliente para
      nuevos desarrollos.</small>
  </div>

  <!-- ✅ GENERAR KEY LEGACY -->
  <form method="post" class="form-inline">
    <button type="submit" name="generate_key" class="btn btn-primary">
      <i class="icon icon-refresh"></i> Generar Nueva API Key Legacy
    </button>
    <span class="help-block"><small>Solo si necesitas compatibilidad con sistemas antiguos</small></span>
  </form>

  <hr>

  <!-- ✅ ACCESO RÁPIDO -->
  <h4><i class="icon icon-bolt"></i> Acceso Rápido</h4>
  <div class="row">
    <div class="col-md-4">
      <div class="panel panel-default">
        <div class="panel-body text-center">
          <i class="icon icon-users icon-3x text-success"></i>
          <h4>Clientes API</h4>
          <p>Gestiona empresas externas</p>
          <a href="{$link->getAdminLink('AdminMyApiClients')}" class="btn btn-success btn-sm">
            Administrar
          </a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="panel panel-default">
        <div class="panel-body text-center">
          <i class="icon icon-book icon-3x text-info"></i>
          <h4>Documentación</h4>
          <p>API completa interactiva</p>
          <a href="{$api_url}/../docs" target="_blank" class="btn btn-info btn-sm">
            Ver Docs
          </a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="panel panel-default">
        <div class="panel-body text-center">
          <i class="icon icon-code icon-3x text-warning"></i>
          <h4>Probar API</h4>
          <p>Testing inmediato</p>
          <a href="{$api_url}?page=1&limit=5" target="_blank" class="btn btn-warning btn-sm">
            Test Endpoint
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- ✅ USO BÁSICO -->
  <h4><i class="icon icon-terminal"></i> Uso Básico</h4>
  <div class="well">
    <strong>Con header:</strong>
    <pre>curl -H "X-API-Key: {$api_key}" "{$api_url}?page=1&limit=10"</pre>

    <strong>Con parámetro:</strong>
    <pre>curl "{$api_url}?api_key={$api_key}&page=1&limit=10"</pre>
  </div>

  <!-- ✅ ESTADO DEL SISTEMA -->
  <h4><i class="icon icon-cogs"></i> Estado del Sistema</h4>
  <div class="alert alert-info">
    <p><strong>✅ API Multi-Cliente:</strong> <span class="label label-success">Activo</span></p>
    <p><strong>✅ Documentación Swagger:</strong> <span class="label label-success">Disponible</span></p>
    <p><strong>✅ Endpoints CRUD:</strong> <span class="label label-success">Completos</span></p>
    <p><strong>✅ Soporte CORS:</strong> <span class="label label-success">Habilitado</span></p>
  </div>

  <!-- ✅ ENLACES ÚTILES -->
  <h4><i class="icon icon-link"></i> Enlaces Útiles</h4>
  <ul class="list-group">
    <li class="list-group-item">
      <i class="icon icon-external-link"></i>
      <a href="{$api_url}/../docs" target="_blank">Documentación Interactiva API</a>
    </li>
    <li class="list-group-item">
      <i class="icon icon-external-link"></i>
      <a href="{$link->getAdminLink('AdminMyApiClients')}">Panel de Gestión de Clientes</a>
    </li>
    <li class="list-group-item">
      <i class="icon icon-external-link"></i>
      <a href="{$api_url}?page=1&limit=5" target="_blank">Probar Endpoint de Productos</a>
    </li>
  </ul>
</div>
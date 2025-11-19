<div class="panel">
  <div class="panel-heading">
    <i class="icon icon-key"></i> Configuración API - Salamandra Luz
  </div>

  <!-- ✅ INFORMACIÓN BÁSICA -->
  <div class="alert alert-info">
    <strong>🌐 URL Base de la API:</strong> {$api_url}
  </div>

  <!-- ✅ API KEY LEGACY CON TOOLTIP -->
  <div class="alert alert-warning">
    <strong>🔑 API Key Legacy:</strong>
    <span id="api-key-value">{$api_key|default:'No generada aún'}</span>
    <br>
    <small>
      <i class="icon icon-info"></i>
      <span
        title="Esta clave es para compatibilidad con sistemas antiguos y testing interno. Para clientes nuevos usa el sistema multi-cliente.">
        Clave maestra para compatibilidad y testing
      </span>
    </small>
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

</div>
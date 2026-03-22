<?php
// =============================================================================
// includes/layout.php -- Layout con Bootstrap 5
// =============================================================================

function renderHead(string $title, string $extra = ''): void {
    $t = htmlspecialchars($title); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $t ?> - TecnoRural</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.navbar-brand { font-weight: 700; }
.badge-nueva      { background-color: #0d6efd; }
.badge-asignada   { background-color: #6f42c1; }
.badge-en_proceso { background-color: #fd7e14; }
.badge-resuelta   { background-color: #198754; }
.badge-cerrada    { background-color: #6c757d; }
.badge-baja    { background-color: #6c757d; }
.badge-media   { background-color: #0d6efd; }
.badge-alta    { background-color: #fd7e14; }
.badge-critica { background-color: #dc3545; }
<?= $extra ?>
</style>
</head>
<body>
<?php }

function renderNav(string $current = ''): void {
    $esSupervisor = !empty($_SESSION['supervisor_id']);
    $esUsuario    = !empty($_SESSION['usuario_id']);

    $links = ['index.php' => 'Inicio'];
    if ($esSupervisor) {
        $links['inventario.php']       = 'Inventario';
        $links['supervisor.php']       = 'Supervisor';
        $links['registro_tecnico.php'] = 'Tecnicos';
        $links['tecnicos.php']         = 'Dashboard';
    } else {
        $links['incidencias.php'] = 'Incidencias';
    }
    ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="/index.php">TecnoRural</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto">
        <?php foreach ($links as $url => $label): ?>
        <li class="nav-item">
          <a class="nav-link <?= basename($current)===$url?'active':'' ?>" href="/<?= $url ?>"><?= $label ?></a>
        </li>
        <?php endforeach; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($esSupervisor): ?>
          <li class="nav-item"><span class="nav-link text-warning"><?= htmlspecialchars($_SESSION['supervisor_nombre']) ?> (Supervisor)</span></li>
          <li class="nav-item"><a class="nav-link" href="/logout.php">Salir</a></li>
        <?php elseif ($esUsuario): ?>
          <li class="nav-item"><span class="nav-link text-light"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span></li>
          <li class="nav-item"><a class="nav-link" href="/logout.php">Salir</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/registro.php">Registrarse</a></li>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm nav-link" href="/login.php">Iniciar sesion</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<?php }

function renderFlash(): void {
    $f = getFlash();
    if ($f) {
        $type = $f['type'] === 'ok' ? 'success' : ($f['type'] === 'err' ? 'danger' : 'warning');
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($f['msg']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

function renderFoot(): void { ?>
<footer class="bg-dark text-secondary text-center py-3 mt-5">
  <small>&copy; <?= date('Y') ?> TecnoRural &mdash; Servidor: <?= gethostname() ?></small>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php }

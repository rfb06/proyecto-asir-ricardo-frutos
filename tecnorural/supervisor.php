<?php
// panel del supervisor para gestionar incidencias
include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

// comprobar que es el supervisor
if (empty($_SESSION['supervisor_id'])) {
    if (!empty($_SESSION['usuario_id'])) {
        flash('No tienes permiso para acceder a esta seccion.', 'err');
        header('Location: /incidencias.php'); exit;
    }
    flash('Debes iniciar sesion como supervisor.', 'err');
    header('Location: /login.php?rol=supervisor'); exit;
}

// sacar tecnicos para el select
$tecnicos = $conn->query("SELECT * FROM tecnico WHERE activo=1 ORDER BY nombre")->fetchAll();

// cuando el supervisor asigna una incidencia a un tecnico
if(isset($_POST['asignar'])) {
    $id_incidencia = $_POST['id_incidencia'];
    $id_tecnico = $_POST['id_tecnico'];
    $prioridad = $_POST['prioridad'];
    $notas = $_POST['notas_supervisor'];
    
    // si no asigno tecnico la dejo como nueva, si asigno la pongo asignada
    if($id_tecnico == '') {
        $id_tecnico = null;
        $estado_nuevo = 'nueva';
        $fecha_asig = null;
    } else {
        $estado_nuevo = 'asignada';
        $fecha_asig = date('Y-m-d H:i:s');
    }
    
    $sql = "UPDATE incidencia SET id_tecnico=?, prioridad=?, notas_supervisor=?, estado=?, fecha_asignacion=? WHERE id=?";
    $conn->prepare($sql)->execute([$id_tecnico, $prioridad, $notas ?: null, $estado_nuevo, $fecha_asig, $id_incidencia]);
    
    flash('Incidencia actualizada correctamente');
    header('Location: supervisor.php');
    exit;
}

// cambiar solo el estado rapido
if(isset($_POST['cambiar_estado'])) {
    $id_incidencia = $_POST['id_incidencia'];
    $estado = $_POST['estado'];
    $fecha_res = null;
    if($estado == 'resuelta') {
        $fecha_res = date('Y-m-d H:i:s');
    }
    $conn->prepare("UPDATE incidencia SET estado=?, fecha_resolucion=? WHERE id=?")->execute([$estado, $fecha_res, $id_incidencia]);
    flash('Estado cambiado');
    header('Location: supervisor.php');
    exit;
}

// filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_prioridad = isset($_GET['prioridad']) ? $_GET['prioridad'] : '';
$filtro_tecnico = isset($_GET['tecnico']) ? $_GET['tecnico'] : '';
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// construir la consulta con los filtros que haya
$sql = "SELECT i.*, t.nombre as tienda_nombre, tc.nombre as tecnico_nombre
        FROM incidencia i
        LEFT JOIN tienda t ON t.id = i.id_tienda
        LEFT JOIN tecnico tc ON tc.id = i.id_tecnico
        WHERE 1=1";
$params = array();

if($filtro_estado != '') {
    $sql .= " AND i.estado = ?";
    $params[] = $filtro_estado;
}
if($filtro_prioridad != '') {
    $sql .= " AND i.prioridad = ?";
    $params[] = $filtro_prioridad;
}
if($filtro_tecnico != '') {
    $sql .= " AND i.id_tecnico = ?";
    $params[] = $filtro_tecnico;
}
if($buscar != '') {
    $sql .= " AND (i.titulo LIKE ? OR i.nombre_cliente LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

// ordeno por prioridad, las criticas primero
$sql .= " ORDER BY FIELD(i.prioridad, 'critica', 'alta', 'media', 'baja'), i.fecha_creacion DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$incidencias = $stmt->fetchAll();

// estadisticas
$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(estado='nueva') as nuevas,
    SUM(estado='asignada') as asignadas,
    SUM(estado='en_proceso') as en_proceso,
    SUM(prioridad='critica' AND estado NOT IN ('resuelta','cerrada')) as criticas
    FROM incidencia")->fetch();

// listas para los selects de los filtros
$lista_estados = array('nueva'=>'Nueva', 'asignada'=>'Asignada', 'en_proceso'=>'En proceso', 'resuelta'=>'Resuelta', 'cerrada'=>'Cerrada');
$lista_prioridades = array('baja', 'media', 'alta', 'critica');

renderHead('Supervisor');
renderNav('supervisor.php');
?>

<div class="container-fluid px-4">

<?php renderFlash(); ?>

<!-- numeros de resumen -->
<div class="row mb-3">
    <div class="col"><div class="card text-center p-2"><big><?php echo $stats['total'] ?></big><br><small class="text-muted">Total</small></div></div>
    <div class="col"><div class="card text-center p-2"><big class="text-primary"><?php echo $stats['nuevas'] ?></big><br><small class="text-muted">Nuevas</small></div></div>
    <div class="col"><div class="card text-center p-2"><big class="text-purple"><?php echo $stats['asignadas'] ?></big><br><small class="text-muted">Asignadas</small></div></div>
    <div class="col"><div class="card text-center p-2"><big class="text-warning"><?php echo $stats['en_proceso'] ?></big><br><small class="text-muted">En proceso</small></div></div>
    <div class="col"><div class="card text-center p-2 border-danger"><big class="text-danger"><?php echo $stats['criticas'] ?></big><br><small class="text-muted">Criticas</small></div></div>
</div>

<!-- filtros de busqueda -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <input type="text" name="buscar" class="form-control" placeholder="Buscar..." value="<?php echo $buscar ?>">
    </div>
    <div class="col-md-2">
        <select name="estado" class="form-select">
            <option value="">Todos los estados</option>
            <?php foreach($lista_estados as $key => $val) { ?>
            <option value="<?php echo $key ?>" <?php if($filtro_estado==$key) echo 'selected' ?>><?php echo $val ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="prioridad" class="form-select">
            <option value="">Todas prioridades</option>
            <?php foreach($lista_prioridades as $p) { ?>
            <option value="<?php echo $p ?>" <?php if($filtro_prioridad==$p) echo 'selected' ?>><?php echo ucfirst($p) ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="tecnico" class="form-select">
            <option value="">Todos los tecnicos</option>
            <?php foreach($tecnicos as $t) { ?>
            <option value="<?php echo $t['id'] ?>" <?php if($filtro_tecnico==$t['id']) echo 'selected' ?>><?php echo $t['nombre'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </div>
    <div class="col-auto">
        <a href="supervisor.php" class="btn btn-outline-secondary">Limpiar</a>
    </div>
</form>

<!-- tabla incidencias -->
<div class="card">
<div class="table-responsive">
<table class="table table-hover mb-0">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Prioridad</th>
            <th>Titulo</th>
            <th>Cliente</th>
            <th>Estado</th>
            <th>Tecnico</th>
            <th>Fecha</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if(count($incidencias) == 0) { ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No hay incidencias</td></tr>
        <?php } ?>
        
        <?php foreach($incidencias as $inc) { ?>
        <tr>
            <td class="text-muted">#<?php echo $inc['id'] ?></td>
            <td><span class="badge badge-<?php echo $inc['prioridad'] ?>"><?php echo ucfirst($inc['prioridad']) ?></span></td>
            <td>
                <strong><?php echo htmlspecialchars($inc['titulo']) ?></strong>
                <?php if($inc['notas_supervisor'] != '') { ?>
                <br><small class="text-muted"><?php echo htmlspecialchars(substr($inc['notas_supervisor'], 0, 50)) ?>...</small>
                <?php } ?>
            </td>
            <td>
                <?php echo htmlspecialchars($inc['nombre_cliente']) ?>
                <?php if($inc['email_cliente'] != '') { ?>
                <br><small class="text-muted"><?php echo $inc['email_cliente'] ?></small>
                <?php } ?>
            </td>
            <td><span class="badge badge-<?php echo $inc['estado'] ?>"><?php echo $lista_estados[$inc['estado']] ?></span></td>
            <td><?php echo $inc['tecnico_nombre'] ? $inc['tecnico_nombre'] : '-' ?></td>
            <td><small><?php echo date('d/m/Y H:i', strtotime($inc['fecha_creacion'])) ?></small></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalGestionar"
                        onclick='abrirModal(<?php echo json_encode($inc) ?>)'>
                    Gestionar
                </button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</div>
</div>

<!-- modal para gestionar la incidencia -->
<div class="modal fade" id="modalGestionar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar incidencia <span id="m_id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <!-- formulario de asignacion -->
            <form method="POST">
                <input type="hidden" name="id_incidencia" id="m_id_inc">
                <div class="modal-body">
                    <!-- resumen de la incidencia -->
                    <div class="bg-light p-3 rounded mb-3">
                        <div id="m_titulo" class="fw-bold"></div>
                        <div id="m_cliente" class="text-muted small"></div>
                        <div id="m_desc" class="small mt-1"></div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Prioridad</label>
                            <select name="prioridad" id="m_prioridad" class="form-select">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                                <option value="critica">CRITICA</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Asignar a tecnico</label>
                            <select name="id_tecnico" id="m_tecnico" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php foreach($tecnicos as $t) { ?>
                                <option value="<?php echo $t['id'] ?>"><?php echo htmlspecialchars($t['nombre']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Notas para el tecnico</label>
                            <textarea name="notas_supervisor" id="m_notas" class="form-control" rows="3"
                                      placeholder="Instrucciones para el tecnico..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <!-- botones de cambio de estado rapido -->
                    <form method="POST" class="d-flex gap-1 flex-wrap">
                        <input type="hidden" name="id_incidencia" id="m_id_estado">
                        <?php foreach($lista_estados as $key => $val) { ?>
                        <button type="submit" name="cambiar_estado" class="btn btn-sm btn-outline-secondary">
                            <?php echo $val ?>
                        </button>
                        <!-- necesito el value del estado en el submit -->
                        <?php } ?>
                    </form>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="asignar" class="btn btn-success">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<script>
function abrirModal(inc) {
    document.getElementById('m_id').textContent = '#' + inc.id;
    document.getElementById('m_id_inc').value = inc.id;
    document.getElementById('m_id_estado').value = inc.id;
    document.getElementById('m_titulo').textContent = inc.titulo;
    document.getElementById('m_cliente').textContent = inc.nombre_cliente;
    document.getElementById('m_desc').textContent = inc.descripcion;
    document.getElementById('m_prioridad').value = inc.prioridad;
    document.getElementById('m_tecnico').value = inc.id_tecnico || '';
    document.getElementById('m_notas').value = inc.notas_supervisor || '';
}
</script>

<?php renderFoot(); ?>

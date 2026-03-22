<?php
// dashboard para los tecnicos - muestra las incidencias ordenadas por prioridad
include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

// solo puede entrar el supervisor por ahora
if (empty($_SESSION['supervisor_id'])) {
    if (!empty($_SESSION['usuario_id'])) {
        flash('No tienes permiso para acceder a esta seccion.', 'err');
        header('Location: /incidencias.php'); exit;
    }
    flash('Debes iniciar sesion como supervisor.', 'err');
    header('Location: /login.php?rol=supervisor'); exit;
}

// cuando el tecnico actualiza una incidencia
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $notas = $_POST['notas_tecnico'];
    $estado = $_POST['estado'];
    
    $fecha_res = null;
    if($estado == 'resuelta') {
        $fecha_res = date('Y-m-d H:i:s'); // pongo la fecha de cuando se resolvio
    }
    
    $conn->prepare("UPDATE incidencia SET notas_tecnico=?, estado=?, fecha_resolucion=IFNULL(?, fecha_resolucion) WHERE id=?")
         ->execute([$notas ?: null, $estado, $fecha_res, $id]);
    
    flash('Incidencia actualizada');
    header('Location: tecnicos.php');
    exit;
}

// sacar todas las incidencias que estan asignadas o en proceso
// las ordeno por prioridad (critica primero) y luego por fecha de asignacion
$incidencias = $conn->query("
    SELECT i.*, t.nombre as tienda_nombre, tc.nombre as tecnico_nombre
    FROM incidencia i
    LEFT JOIN tienda t ON t.id = i.id_tienda
    LEFT JOIN tecnico tc ON tc.id = i.id_tecnico
    WHERE i.estado IN ('asignada', 'en_proceso')
    ORDER BY FIELD(i.prioridad, 'critica', 'alta', 'media', 'baja'), i.fecha_asignacion ASC
")->fetchAll();

// los tecnicos para el sidebar
$tecnicos = $conn->query("
    SELECT tc.*,
        (SELECT COUNT(*) FROM incidencia i WHERE i.id_tecnico=tc.id AND i.estado IN ('asignada','en_proceso')) as num_incidencias
    FROM tecnico tc
    WHERE tc.activo=1
    ORDER BY num_incidencias DESC
")->fetchAll();

// agrupo las incidencias por prioridad para mostrarlas en secciones
$por_prioridad = array(
    'critica' => array(),
    'alta'    => array(),
    'media'   => array(),
    'baja'    => array()
);
foreach($incidencias as $inc) {
    $por_prioridad[$inc['prioridad']][] = $inc;
}

$estados_texto = array('nueva'=>'Nueva', 'asignada'=>'Asignada', 'en_proceso'=>'En proceso', 'resuelta'=>'Resuelta', 'cerrada'=>'Cerrada');
$colores_prioridad = array('critica'=>'danger', 'alta'=>'warning', 'media'=>'primary', 'baja'=>'secondary');

renderHead('Dashboard Tecnicos');
renderNav('tecnicos.php');
?>

<div class="container-fluid px-4">

<?php renderFlash(); ?>

<div class="row g-4">

    <!-- sidebar con lista de tecnicos -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header fw-bold">Tecnicos</div>
            <div class="list-group list-group-flush" id="lista-tecnicos">
                <!-- opcion ver todos -->
                <a href="#" class="list-group-item list-group-item-action active"
                   onclick="filtrarTecnico('todos', this); return false;">
                    Todos
                    <span class="badge bg-secondary float-end"><?php echo count($incidencias) ?></span>
                </a>
                
                <!-- un elemento por cada tecnico -->
                <?php foreach($tecnicos as $t) { ?>
                <a href="#" class="list-group-item list-group-item-action"
                   onclick="filtrarTecnico('<?php echo $t['id'] ?>', this); return false;">
                    <?php echo htmlspecialchars($t['nombre']) ?>
                    <span class="badge bg-secondary float-end"><?php echo $t['num_incidencias'] ?></span>
                </a>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- panel principal con las incidencias -->
    <div class="col-md-9">
    
        <?php if(count($incidencias) == 0) { ?>
        <div class="alert alert-success">No hay incidencias activas ahora mismo, todo en orden!</div>
        <?php } ?>

        <!-- recorro cada grupo de prioridad -->
        <?php foreach($por_prioridad as $prioridad => $lista) {
            if(count($lista) == 0) continue; // si no hay de este tipo lo salto
            $color = $colores_prioridad[$prioridad];
        ?>
        <div class="grupo-prioridad mb-3" data-prioridad="<?php echo $prioridad ?>">
            <h6 class="text-uppercase text-muted mb-2">
                <span class="badge bg-<?php echo $color ?>"><?php echo ucfirst($prioridad) ?></span>
                - <?php echo count($lista) ?> incidencia<?php echo count($lista) != 1 ? 's' : '' ?>
            </h6>
            
            <?php foreach($lista as $inc) { ?>
            <div class="card mb-2 inc-item border-<?php echo $color ?>"
                 data-tecnico="<?php echo $inc['id_tecnico'] ?? 0 ?>"
                 style="border-left-width:4px; cursor:pointer"
                 data-bs-toggle="modal" data-bs-target="#modalTecnico"
                 onclick='abrirModal(<?php echo json_encode($inc) ?>)'>
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between">
                        <strong class="small"><?php echo htmlspecialchars($inc['titulo']) ?></strong>
                        <span class="badge badge-<?php echo $inc['estado'] ?>"><?php echo $estados_texto[$inc['estado']] ?></span>
                    </div>
                    <!-- corto la descripcion si es muy larga -->
                    <div class="text-muted small">
                        <?php echo htmlspecialchars(substr($inc['descripcion'], 0, 100)) ?>
                        <?php if(strlen($inc['descripcion']) > 100) echo '...' ?>
                    </div>
                    <?php if($inc['notas_supervisor'] != '') { ?>
                    <div class="small text-primary">
                        Notas supervisor: <?php echo htmlspecialchars(substr($inc['notas_supervisor'], 0, 80)) ?>
                    </div>
                    <?php } ?>
                    <div class="text-muted small mt-1">
                        <?php echo htmlspecialchars($inc['nombre_cliente']) ?>
                        <?php if($inc['tienda_nombre']) echo ' - ' . htmlspecialchars($inc['tienda_nombre']) ?>
                        <?php if($inc['tecnico_nombre']) echo ' - ' . htmlspecialchars($inc['tecnico_nombre']) ?>
                    </div>
                </div>
            </div>
            <?php } // fin foreach incidencias del grupo ?>
            
        </div>
        <?php } // fin foreach prioridades ?>
        
    </div><!-- fin col -->
    
</div><!-- fin row -->

<!-- modal para que el tecnico actualice la incidencia -->
<div class="modal fade" id="modalTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Incidencia <span id="m_id" class="text-success"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="m_id_inc">
                <div class="modal-body">
                    <div id="m_badge" class="mb-2"></div>
                    <div id="m_titulo" class="fw-bold mb-1"></div>
                    <div id="m_desc" class="text-muted small mb-3"></div>
                    
                    <!-- notas del supervisor si las hay -->
                    <div id="m_notas_sup_div" class="alert alert-info small d-none">
                        <strong>El supervisor dice:</strong><br>
                        <span id="m_notas_sup"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label>Mis notas / lo que he hecho</label>
                        <textarea name="notas_tecnico" id="m_notas_tec" class="form-control" rows="4"
                                  placeholder="Escribe aqui lo que has hecho, el diagnostico, la solucion..."></textarea>
                    </div>
                    
                    <div class="mb-2">
                        <label>Estado actual</label>
                        <select name="estado" id="m_estado" class="form-select">
                            <option value="asignada">Asignada (sin empezar)</option>
                            <option value="en_proceso">En proceso (trabajando en ello)</option>
                            <option value="resuelta">Resuelta (ya esta arreglado!)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div><!-- fin container -->

<script>
function abrirModal(inc) {
    document.getElementById('m_id').textContent = '#' + inc.id;
    document.getElementById('m_id_inc').value = inc.id;
    document.getElementById('m_titulo').textContent = inc.titulo;
    document.getElementById('m_desc').textContent = inc.descripcion;
    document.getElementById('m_notas_tec').value = inc.notas_tecnico || '';
    document.getElementById('m_estado').value = inc.estado;
    
    // poner el badge de prioridad
    var colores = {critica:'danger', alta:'warning', media:'primary', baja:'secondary'};
    document.getElementById('m_badge').innerHTML = '<span class="badge bg-' + colores[inc.prioridad] + '">' + inc.prioridad.toUpperCase() + '</span>';
    
    // mostrar notas del supervisor si hay
    if(inc.notas_supervisor && inc.notas_supervisor != '') {
        document.getElementById('m_notas_sup_div').classList.remove('d-none');
        document.getElementById('m_notas_sup').textContent = inc.notas_supervisor;
    } else {
        document.getElementById('m_notas_sup_div').classList.add('d-none');
    }
}

// filtrar por tecnico al hacer clic en el sidebar
function filtrarTecnico(tecId, elemento) {
    // quito el active de todos y se lo pongo al clicado
    document.querySelectorAll('#lista-tecnicos a').forEach(function(a) {
        a.classList.remove('active');
    });
    elemento.classList.add('active');
    
    // muestro/oculto las incidencias segun el tecnico
    document.querySelectorAll('.inc-item').forEach(function(item) {
        var card = item.closest('.card');
        if(tecId === 'todos' || item.dataset.tecnico === tecId) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // oculto los grupos que queden vacios
    document.querySelectorAll('.grupo-prioridad').forEach(function(grupo) {
        var hayVisible = false;
        grupo.querySelectorAll('.inc-item').forEach(function(item) {
            if(item.closest('.card').style.display !== 'none') hayVisible = true;
        });
        grupo.style.display = hayVisible ? '' : 'none';
    });
}
</script>

<?php renderFoot(); ?>

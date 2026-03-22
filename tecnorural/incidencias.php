<?php
// pagina para que los clientes pongan sus problemas
include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

// saco las tiendas para el desplegable
$tiendas = $conn->query("SELECT * FROM tienda WHERE activa=1")->fetchAll();

// si el usuario esta logueado cargo sus datos para rellenar el formulario solo
$usuario_logueado = null;
if(isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];
    $query = $conn->prepare("SELECT * FROM usuario WHERE id = ?");
    $query->execute([$id_usuario]);
    $usuario_logueado = $query->fetch();
}

// cuando envian el formulario
if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $nombre = $_POST['nombre_cliente'];
    $email = $_POST['email_cliente'];
    $telefono = $_POST['telefono_cliente'];
    $tienda = $_POST['id_tienda'];
    
    // si no pusieron tienda que sea null
    if($tienda == '') {
        $tienda = null;
    }
    
    // el id del usuario si esta logueado
    $uid = null;
    if(isset($_SESSION['usuario_id'])) {
        $uid = $_SESSION['usuario_id'];
    }

    // comprobar que han rellenado lo obligatorio
    if($titulo == '' || $descripcion == '' || $nombre == '') {
        flash('Tienes que rellenar el titulo, descripcion y nombre', 'err');
    } else {
        // meter en la base de datos
        $insertar = $conn->prepare("INSERT INTO incidencia (titulo, descripcion, nombre_cliente, email_cliente, telefono_cliente, id_tienda, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertar->execute([$titulo, $descripcion, $nombre, $email, $telefono, $tienda, $uid]);
        
        $nuevo_id = $conn->lastInsertId();
        flash("Incidencia creada con el numero $nuevo_id. Te llamaremos pronto!");
        header("Location: incidencias.php");
        exit;
    }
}

// buscar incidencias por email si pone el email
$mis_incidencias = array(); // array vacio por defecto
$email_buscado = '';
if(isset($_GET['email']) && $_GET['email'] != '') {
    $email_buscado = $_GET['email'];
    $buscar = $conn->prepare("SELECT * FROM incidencia WHERE email_cliente = ? ORDER BY fecha_creacion DESC");
    $buscar->execute([$email_buscado]);
    $mis_incidencias = $buscar->fetchAll();
}

// los textos de los estados
$estados_texto = array(
    'nueva' => 'Nueva',
    'asignada' => 'Asignada',
    'en_proceso' => 'En proceso',
    'resuelta' => 'Resuelta',
    'cerrada' => 'Cerrada'
);

renderHead('Incidencias');
renderNav('incidencias.php');
?>

<div class="container">

<?php renderFlash(); ?>

<div class="row">
    <!-- formulario para poner una incidencia nueva -->
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-header">Abre una incidencia</div>
            <div class="card-body">
                
                <?php if($usuario_logueado == null) { ?>
                <div class="alert alert-info">
                    Si tienes cuenta <a href="/login.php">inicia sesion</a> para que se rellene solo
                </div>
                <?php } ?>

                <form method="POST" action="">
                
                    <div class="mb-3">
                        <label>Titulo del problema</label>
                        <!-- el required es para que no lo dejen vacio -->
                        <input type="text" name="titulo" class="form-control" required
                               value="<?php echo isset($_POST['titulo']) ? $_POST['titulo'] : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Explicame que pasa</label>
                        <textarea name="descripcion" class="form-control" rows="4" required><?php echo isset($_POST['descripcion']) ? $_POST['descripcion'] : '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label>Tu nombre</label>
                        <input type="text" name="nombre_cliente" class="form-control" required
                               value="<?php
                               // si esta logueado pongo su nombre automaticamente
                               if($usuario_logueado != null) {
                                   echo $usuario_logueado['nombre'] . ' ' . $usuario_logueado['apellidos'];
                               } else if(isset($_POST['nombre_cliente'])) {
                                   echo $_POST['nombre_cliente'];
                               }
                               ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email_cliente" class="form-control"
                               <?php if($usuario_logueado != null) echo 'readonly'; ?>
                               value="<?php echo $usuario_logueado != null ? $usuario_logueado['email'] : (isset($_POST['email_cliente']) ? $_POST['email_cliente'] : '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Telefono (opcional)</label>
                        <input type="text" name="telefono_cliente" class="form-control"
                               value="<?php echo isset($_POST['telefono_cliente']) ? $_POST['telefono_cliente'] : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Tienda mas cercana (opcional)</label>
                        <select name="id_tienda" class="form-select">
                            <option value="">No se / Da igual</option>
                            <?php foreach($tiendas as $t) { ?>
                            <option value="<?php echo $t['id'] ?>"><?php echo $t['nombre'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Enviar</button>
                    
                </form>
            </div>
        </div>
    </div>

    <!-- buscador de mis incidencias y pasos -->
    <div class="col-md-5">
    
        <div class="card mb-3">
            <div class="card-header">Ver mis incidencias</div>
            <div class="card-body">
                <p>Pon tu email para ver como van tus incidencias</p>
                <form method="GET">
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="tu@email.com"
                               value="<?php echo $email_buscado ?>">
                        <button type="submit" class="btn btn-secondary">Buscar</button>
                    </div>
                </form>
                
                <?php if($email_buscado != '' && count($mis_incidencias) == 0) { ?>
                <p class="text-muted mt-2">No encontre ninguna incidencia con ese email</p>
                <?php } ?>
                
                <!-- muestro las que encontre -->
                <?php foreach($mis_incidencias as $inc) { ?>
                <div class="border rounded p-2 mt-2">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo $inc['titulo'] ?></strong>
                        <!-- el badge del estado -->
                        <span class="badge badge-<?php echo $inc['estado'] ?>">
                            <?php echo $estados_texto[$inc['estado']] ?>
                        </span>
                    </div>
                    <small class="text-muted">
                        Incidencia #<?php echo $inc['id'] ?> -
                        <?php echo date('d/m/Y', strtotime($inc['fecha_creacion'])) ?>
                    </small>
                    <?php if($inc['notas_tecnico'] != '') { ?>
                    <p class="small mt-1"><?php echo $inc['notas_tecnico'] ?></p>
                    <?php } ?>
                </div>
                <?php } ?>
                
            </div>
        </div>

        <div class="card">
            <div class="card-header">Como funciona</div>
            <div class="card-body">
                <ol>
                    <li>Rellenas el formulario</li>
                    <li>Ricardo o el supervisor lo mira</li>
                    <li>Lo asigna a un tecnico</li>
                    <li>El tecnico te ayuda</li>
                </ol>
            </div>
        </div>
        
    </div>
</div>

</div>

<?php renderFoot(); ?>

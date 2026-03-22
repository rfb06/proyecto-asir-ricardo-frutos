<?php
// para que el supervisor registre tecnicos nuevos
include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

// solo el supervisor puede entrar aqui
if (empty($_SESSION['supervisor_id'])) {
    if (!empty($_SESSION['usuario_id'])) {
        flash('No tienes permiso para acceder a esta seccion.', 'err');
        header('Location: /incidencias.php'); exit;
    }
    flash('Debes iniciar sesion como supervisor.', 'err');
    header('Location: /login.php?rol=supervisor'); exit;
}

$nombre_supervisor = $_SESSION['supervisor_nombre'];

// registrar tecnico nuevo
if(isset($_POST['crear_tecnico'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $especialidad = $_POST['especialidad'];
    $pass1 = $_POST['password'];
    $pass2 = $_POST['password2'];
    
    $errores = array();
    
    if($nombre == '') $errores[] = 'El nombre es obligatorio';
    if($email == '') $errores[] = 'El email es obligatorio';
    if(strlen($pass1) < 8) $errores[] = 'La contrasena tiene que tener minimo 8 caracteres';
    if($pass1 != $pass2) $errores[] = 'Las contrasenas no son iguales';
    
    // comprobar que no existe ya ese email
    if(count($errores) == 0) {
        $check = $conn->prepare("SELECT id FROM tecnico WHERE email=?");
        $check->execute([$email]);
        if($check->fetch()) {
            $errores[] = 'Ya hay un tecnico con ese email';
        }
    }
    
    if(count($errores) == 0) {
        // si la tabla no tiene columna password la creo
        // esto es porque la tabla se creo antes sin esa columna
        try {
            $conn->query("SELECT password FROM tecnico LIMIT 1");
        } catch(Exception $e) {
            $conn->exec("ALTER TABLE tecnico ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER especialidad");
        }
        
        $hash = password_hash($pass1, PASSWORD_BCRYPT);
        $conn->prepare("INSERT INTO tecnico (nombre, email, telefono, especialidad, password) VALUES (?,?,?,?,?)")
             ->execute([$nombre, $email, $telefono ?: null, $especialidad ?: null, $hash]);
        
        flash("Tecnico $nombre registrado!");
        header('Location: /registro_tecnico.php');
        exit;
    } else {
        flash(implode('. ', $errores), 'err');
    }
}

// editar un tecnico existente
if(isset($_POST['editar_tecnico'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $especialidad = $_POST['especialidad'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $pass1 = $_POST['password'];
    $pass2 = $_POST['password2'];
    
    if($pass1 != '' && $pass1 != $pass2) {
        flash('Las contrasenas no coinciden', 'err');
    } else {
        if($pass1 != '') {
            // si puso contrasena nueva la cambio
            try { $conn->query("SELECT password FROM tecnico LIMIT 1"); }
            catch(Exception $e) { $conn->exec("ALTER TABLE tecnico ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER especialidad"); }
            
            $hash = password_hash($pass1, PASSWORD_BCRYPT);
            $conn->prepare("UPDATE tecnico SET nombre=?, email=?, telefono=?, especialidad=?, activo=?, password=? WHERE id=?")
                 ->execute([$nombre, $email, $telefono ?: null, $especialidad ?: null, $activo, $hash, $id]);
        } else {
            // sin cambiar la contrasena
            $conn->prepare("UPDATE tecnico SET nombre=?, email=?, telefono=?, especialidad=?, activo=? WHERE id=?")
                 ->execute([$nombre, $email, $telefono ?: null, $especialidad ?: null, $activo, $id]);
        }
        flash('Tecnico actualizado');
        header('Location: /registro_tecnico.php');
        exit;
    }
}

// desactivar o activar tecnico
if(isset($_GET['desactivar'])) {
    $conn->prepare("UPDATE tecnico SET activo=0 WHERE id=?")->execute([$_GET['desactivar']]);
    flash('Tecnico desactivado');
    header('Location: /registro_tecnico.php');
    exit;
}
if(isset($_GET['activar'])) {
    $conn->prepare("UPDATE tecnico SET activo=1 WHERE id=?")->execute([$_GET['activar']]);
    flash('Tecnico activado de nuevo');
    header('Location: /registro_tecnico.php');
    exit;
}

// sacar lista de todos los tecnicos con cuantas incidencias tienen
$tecnicos = $conn->query("
    SELECT t.*,
        (SELECT COUNT(*) FROM incidencia i WHERE i.id_tecnico=t.id AND i.estado IN ('asignada','en_proceso')) as incidencias_activas
    FROM tecnico t
    ORDER BY t.activo DESC, t.nombre
")->fetchAll();

// si me piden editar uno cargo sus datos
$editando = null;
if(isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM tecnico WHERE id=?");
    $stmt->execute([$_GET['editar']]);
    $editando = $stmt->fetch();
}

renderHead('Tecnicos');
renderNav('registro_tecnico.php');
?>

<div class="container">

<?php renderFlash(); ?>

<!-- cabecera con quien esta logueado -->
<div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
    <span>Conectado como supervisor: <strong><?php echo htmlspecialchars($nombre_supervisor) ?></strong></span>
    <a href="/logout.php" class="btn btn-sm btn-outline-dark">Salir</a>
</div>

<div class="row g-4">

    <!-- lista de tecnicos -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Tecnicos registrados (<?php echo count($tecnicos) ?>)</span>
                <a href="/registro_tecnico.php" class="btn btn-sm btn-success">+ Nuevo tecnico</a>
            </div>
            
            <?php if(count($tecnicos) == 0) { ?>
            <div class="card-body text-muted">Todavia no hay tecnicos</div>
            <?php } ?>
            
            <ul class="list-group list-group-flush">
            <?php foreach($tecnicos as $t) { ?>
                <li class="list-group-item <?php echo !$t['activo'] ? 'text-muted' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($t['nombre']) ?></strong>
                            <?php if(!$t['activo']) { ?>
                            <span class="badge bg-secondary ms-1">Inactivo</span>
                            <?php } ?>
                            <br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($t['email']) ?>
                                <?php if($t['especialidad']) echo ' - ' . htmlspecialchars($t['especialidad']) ?>
                                <?php if($t['telefono']) echo ' - ' . htmlspecialchars($t['telefono']) ?>
                            </small>
                            <br>
                            <small class="text-muted"><?php echo $t['incidencias_activas'] ?> incidencias activas</small>
                        </div>
                        <div class="d-flex gap-1 mt-1">
                            <a href="?editar=<?php echo $t['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <?php if($t['activo']) { ?>
                            <a href="?desactivar=<?php echo $t['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Desactivar a <?php echo addslashes($t['nombre']) ?>?')">
                                Desactivar
                            </a>
                            <?php } else { ?>
                            <a href="?activar=<?php echo $t['id'] ?>" class="btn btn-sm btn-outline-success">Activar</a>
                            <?php } ?>
                        </div>
                    </div>
                </li>
            <?php } // fin foreach ?>
            </ul>
        </div>
    </div>

    <!-- formulario nuevo o editar -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header fw-bold">
                <?php echo $editando ? 'Editar tecnico' : 'Registrar tecnico nuevo' ?>
            </div>
            <div class="card-body">
            
                <?php if($editando) { ?>
                <!-- formulario editar -->
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $editando['id'] ?>">
                    <div class="mb-3">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required
                               value="<?php echo htmlspecialchars($editando['nombre']) ?>">
                    </div>
                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($editando['email']) ?>">
                    </div>
                    <div class="mb-3">
                        <label>Telefono</label>
                        <input type="text" name="telefono" class="form-control"
                               value="<?php echo htmlspecialchars($editando['telefono'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Especialidad</label>
                        <input type="text" name="especialidad" class="form-control"
                               value="<?php echo htmlspecialchars($editando['especialidad'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Nueva contrasena</label>
                        <input type="password" name="password" class="form-control">
                        <div class="form-text">Dejalo vacio si no quieres cambiarla</div>
                    </div>
                    <div class="mb-3">
                        <label>Repetir contrasena</label>
                        <input type="password" name="password2" class="form-control">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="activo" class="form-check-input" id="cb_activo" value="1"
                               <?php echo $editando['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="cb_activo">Tecnico activo</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="editar_tecnico" class="btn btn-success">Guardar cambios</button>
                        <a href="/registro_tecnico.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
                
                <?php } else { ?>
                <!-- formulario nuevo -->
                <form method="POST">
                    <div class="mb-3">
                        <label>Nombre completo *</label>
                        <input type="text" name="nombre" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required
                               placeholder="nombre@tecnorural.es">
                    </div>
                    <div class="mb-3">
                        <label>Telefono</label>
                        <input type="text" name="telefono" class="form-control" placeholder="600 000 000">
                    </div>
                    <div class="mb-3">
                        <label>Especialidad</label>
                        <input type="text" name="especialidad" class="form-control"
                               placeholder="ej: hardware, redes, soporte...">
                    </div>
                    <div class="mb-3">
                        <label>Contrasena *</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">Minimo 8 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label>Repetir contrasena *</label>
                        <input type="password" name="password2" class="form-control" required>
                    </div>
                    <button type="submit" name="crear_tecnico" class="btn btn-success w-100">
                        Registrar tecnico
                    </button>
                </form>
                <?php } ?>
                
            </div>
        </div>
    </div>

</div><!-- fin row -->

</div><!-- fin container -->

<?php renderFoot(); ?>

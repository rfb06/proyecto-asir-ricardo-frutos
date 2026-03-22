<?php
// registro de usuarios nuevos
include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

// sacar las tiendas para el selector
$tiendas = $conn->query("SELECT * FROM tienda WHERE activa=1 ORDER BY nombre")->fetchAll();

$errores = array();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $id_tienda = $_POST['id_tienda'] != '' ? $_POST['id_tienda'] : null;
    $pass1 = $_POST['password'];
    $pass2 = $_POST['password2'];
    
    // validar
    if($nombre == '') $errores[] = 'El nombre es obligatorio';
    if($email == '') $errores[] = 'El email es obligatorio';
    if(strlen($pass1) < 8) $errores[] = 'La contrasena debe tener al menos 8 caracteres';
    if($pass1 != $pass2) $errores[] = 'Las dos contrasenas no son iguales';
    
    // ver si ya existe ese email
    if(count($errores) == 0) {
        $check = $conn->prepare("SELECT id FROM usuario WHERE email=?");
        $check->execute([$email]);
        if($check->fetch()) {
            $errores[] = 'Ya existe una cuenta con ese email, prueba a iniciar sesion';
        }
    }
    
    // si no hay errores registro al usuario
    if(count($errores) == 0) {
        $hash = password_hash($pass1, PASSWORD_BCRYPT);
        
        $conn->prepare("INSERT INTO usuario (nombre, apellidos, email, password, telefono, id_tienda) VALUES (?,?,?,?,?,?)")
             ->execute([$nombre, $apellidos ?: null, $email, $hash, $telefono ?: null, $id_tienda]);
        
        $nuevo_id = $conn->lastInsertId();
        
        // lo meto en la sesion para que ya este logueado
        $_SESSION['usuario_id'] = $nuevo_id;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_email'] = $email;
        
        flash('Cuenta creada! Bienvenido ' . $nombre);
        header('Location: /incidencias.php');
        exit;
    }
}

renderHead('Crear cuenta');
renderNav('registro.php');
?>

<div class="container">
    <div class="row justify-content-center mt-4">
        <div class="col-md-6">
        
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Crear cuenta nueva</div>
                <div class="card-body">
                
                    <!-- mostrar errores si hay -->
                    <?php if(count($errores) > 0) { ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach($errores as $e) { ?>
                            <li><?php echo $e ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php } ?>
                    
                    <form method="POST" action="">
                        <div class="row g-3">
                        
                            <div class="col-md-6">
                                <label>Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required
                                       value="<?php echo isset($_POST['nombre']) ? $_POST['nombre'] : '' ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label>Apellidos</label>
                                <input type="text" name="apellidos" class="form-control"
                                       value="<?php echo isset($_POST['apellidos']) ? $_POST['apellidos'] : '' ?>">
                            </div>
                            
                            <div class="col-12">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo isset($_POST['email']) ? $_POST['email'] : '' ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label>Contrasena *</label>
                                <input type="password" name="password" class="form-control" required>
                                <small class="text-muted">Minimo 8 caracteres</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label>Repite la contrasena *</label>
                                <input type="password" name="password2" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label>Telefono</label>
                                <input type="text" name="telefono" class="form-control"
                                       value="<?php echo isset($_POST['telefono']) ? $_POST['telefono'] : '' ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label>Tu tienda mas cercana</label>
                                <select name="id_tienda" class="form-select">
                                    <option value="">No se</option>
                                    <?php foreach($tiendas as $t) { ?>
                                    <option value="<?php echo $t['id'] ?>"><?php echo $t['nombre'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100">Crear mi cuenta</button>
                            </div>
                            
                        </div>
                    </form>
                    
                    <hr>
                    <p class="text-center mb-0">
                        Ya tienes cuenta? <a href="/login.php">Inicia sesion</a>
                    </p>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php renderFoot(); ?>

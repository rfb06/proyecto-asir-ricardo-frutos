<?php
// pagina de login
include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

$rol = isset($_GET['rol']) ? $_GET['rol'] : 'usuario';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $rol = $_POST['rol'];
    
    if($email == '' || $pass == '') {
        $error = 'Rellena el email y la contrasena';
    } else {
    
        if($rol == 'supervisor') {
            // busco el supervisor
            $stmt = $conn->prepare("SELECT * FROM supervisor WHERE email=? AND activo=1");
            $stmt->execute([$email]);
            $sup = $stmt->fetch();
            
            if($sup && password_verify($pass, $sup['password'])) {
                $_SESSION['supervisor_id'] = $sup['id'];
                $_SESSION['supervisor_nombre'] = $sup['nombre'];
                flash('Bienvenido ' . $sup['nombre']);
                header('Location: /supervisor.php');
                exit;
            } else {
                $error = 'El email o contrasena del supervisor no es correcto';
            }
            
        } else {
            // busco el usuario normal
            $stmt = $conn->prepare("SELECT * FROM usuario WHERE email=? AND activo=1");
            $stmt->execute([$email]);
            $usr = $stmt->fetch();
            
            if($usr && password_verify($pass, $usr['password'])) {
                $_SESSION['usuario_id'] = $usr['id'];
                $_SESSION['usuario_nombre'] = $usr['nombre'];
                $_SESSION['usuario_email'] = $usr['email'];
                
                // actualizo cuando entro por ultima vez
                $conn->prepare("UPDATE usuario SET ultimo_acc=NOW() WHERE id=?")->execute([$usr['id']]);
                
                flash('Bienvenido ' . $usr['nombre'] . '!');
                header('Location: /incidencias.php');
                exit;
            } else {
                $error = 'El email o la contrasena no son correctos';
            }
        }
    }
}

renderHead('Entrar');
renderNav('login.php');
?>

<div class="container">
    <div class="row justify-content-center mt-4">
        <div class="col-md-5">
        
            <?php renderFlash(); ?>
        
            <div class="card shadow-sm">
                <div class="card-header">
                    <!-- tabs para elegir si eres usuario o supervisor -->
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $rol=='usuario' ? 'active' : '' ?>" href="?rol=usuario">
                                Soy cliente
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $rol=='supervisor' ? 'active' : '' ?>" href="?rol=supervisor">
                                Soy supervisor
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                
                    <?php if($error != '') { ?>
                    <div class="alert alert-danger"><?php echo $error ?></div>
                    <?php } ?>
                    
                    <?php if($rol == 'supervisor') { ?>
                    <div class="alert alert-warning">Solo para el supervisor del sistema</div>
                    <?php } ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="rol" value="<?php echo $rol ?>">
                        
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required autofocus
                                   value="<?php echo isset($_POST['email']) ? $_POST['email'] : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label>Contrasena</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn <?php echo $rol=='supervisor' ? 'btn-warning' : 'btn-success' ?> w-100">
                            Entrar
                        </button>
                    </form>
                    
                    <?php if($rol == 'usuario') { ?>
                    <hr>
                    <p class="text-center mb-0">
                        No tienes cuenta? <a href="/registro.php">Registrate aqui</a>
                    </p>
                    <?php } ?>
                    
                </div>
            </div>
            
            <p class="text-center mt-3">
                <a href="/index.php">Volver al inicio</a>
            </p>
            
        </div>
    </div>
</div>

<?php renderFoot(); ?>

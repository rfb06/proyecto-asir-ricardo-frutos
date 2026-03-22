<?php
// gestion del inventario - solo para el supervisor
include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

// Guard: solo supervisor
if (empty($_SESSION['supervisor_id'])) {
    if (!empty($_SESSION['usuario_id'])) {
        flash('No tienes permiso para acceder a esta seccion.', 'err');
        header('Location: /incidencias.php'); exit;
    }
    flash('Debes iniciar sesion como supervisor.', 'err');
    header('Location: /login.php?rol=supervisor'); exit;
}

// cuando le dan a guardar producto (nuevo o editar)
if(isset($_POST['guardar'])) {

    $nombre = $_POST['nombre'];
    $referencia = $_POST['referencia'];
    $id_categoria = $_POST['id_categoria'];
    $id_tienda = $_POST['id_tienda'];
    $stock = $_POST['stock'];
    $precio_compra = $_POST['precio_compra'];
    $precio_venta = $_POST['precio_venta'];
    $descripcion = $_POST['descripcion'];
    $proveedor = $_POST['proveedor'];
    $stock_minimo = $_POST['stock_minimo'];
    $id_producto = $_POST['id_producto']; // si es 0 es nuevo

    if($nombre == '') {
        flash('El nombre no puede estar vacio', 'err');
    } else {
        
        if($id_producto == 0) {
            // es nuevo
            try {
                $sql = "INSERT INTO inventario (nombre, referencia, id_categoria, id_tienda, stock, precio_compra, precio_venta, descripcion, proveedor, stock_minimo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nombre, $referencia ?: null, $id_categoria ?: null, $id_tienda ?: null, $stock, $precio_compra, $precio_venta, $descripcion ?: null, $proveedor ?: null, $stock_minimo]);
                flash('Producto anadido!');
            } catch(Exception $e) {
                flash('Error al anadir: ' . $e->getMessage(), 'err');
            }
        } else {
            // actualizar el que ya existe
            $sql = "UPDATE inventario SET nombre=?, referencia=?, id_categoria=?, id_tienda=?, stock=?, precio_compra=?, precio_venta=?, descripcion=?, proveedor=?, stock_minimo=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $referencia ?: null, $id_categoria ?: null, $id_tienda ?: null, $stock, $precio_compra, $precio_venta, $descripcion ?: null, $proveedor ?: null, $stock_minimo, $id_producto]);
            flash('Producto actualizado!');
        }
        header('Location: inventario.php');
        exit;
    }
}

// borrar producto (poner activo=0)
if(isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    $conn->prepare("UPDATE inventario SET activo=0 WHERE id=?")->execute([$id_borrar]);
    flash('Producto eliminado');
    header('Location: inventario.php');
    exit;
}

// si me piden editar uno cargo sus datos
$producto_editar = null;
if(isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM inventario WHERE id=?");
    $stmt->execute([$_GET['editar']]);
    $producto_editar = $stmt->fetch();
}

// filtros de busqueda
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$filtro_cat = isset($_GET['cat']) ? $_GET['cat'] : '';
$filtro_tienda = isset($_GET['tienda']) ? $_GET['tienda'] : '';
$filtro_stock = isset($_GET['stock']) ? $_GET['stock'] : '';

// construyo la consulta con los filtros
// esto fue lo mas dificil de hacer
$sql = "SELECT i.*, c.nombre as cat_nombre, t.nombre as tienda_nombre
        FROM inventario i
        LEFT JOIN categoria c ON c.id = i.id_categoria
        LEFT JOIN tienda t ON t.id = i.id_tienda
        WHERE i.activo = 1";

$params = array();

if($buscar != '') {
    $sql .= " AND (i.nombre LIKE ? OR i.referencia LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}
if($filtro_cat != '') {
    $sql .= " AND i.id_categoria = ?";
    $params[] = $filtro_cat;
}
if($filtro_tienda != '') {
    $sql .= " AND i.id_tienda = ?";
    $params[] = $filtro_tienda;
}
if($filtro_stock == 'bajo') {
    $sql .= " AND i.stock <= i.stock_minimo AND i.stock > 0";
}
if($filtro_stock == 'cero') {
    $sql .= " AND i.stock = 0";
}

$sql .= " ORDER BY i.nombre";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// saco categorias y tiendas para los selects
$categorias = $conn->query("SELECT * FROM categoria ORDER BY nombre")->fetchAll();
$tiendas = $conn->query("SELECT * FROM tienda WHERE activa=1 ORDER BY nombre")->fetchAll();

// estadisticas rapidas
$total_stock = $conn->query("SELECT SUM(stock) FROM inventario WHERE activo=1")->fetchColumn();
$valor_total = $conn->query("SELECT SUM(stock*precio_venta) FROM inventario WHERE activo=1")->fetchColumn();
$bajo_stock = $conn->query("SELECT COUNT(*) FROM inventario WHERE activo=1 AND stock<=stock_minimo AND stock>0")->fetchColumn();
$sin_stock = $conn->query("SELECT COUNT(*) FROM inventario WHERE activo=1 AND stock=0")->fetchColumn();

renderHead('Inventario');
renderNav('inventario.php');
?>

<div class="container-fluid px-4">

<?php renderFlash(); ?>

<!-- numeros arriba -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center p-2">
            <big><?php echo number_format($total_stock) ?></big>
            <small class="text-muted">unidades totales</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-2">
            <big class="text-success">EUR <?php echo number_format($valor_total, 0) ?></big>
            <small class="text-muted">valor total</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-2">
            <big class="text-warning"><?php echo $bajo_stock ?></big>
            <small class="text-muted">stock bajo</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-2">
            <big class="text-danger"><?php echo $sin_stock ?></big>
            <small class="text-muted">sin stock</small>
        </div>
    </div>
</div>

<!-- filtros -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <input type="text" name="buscar" class="form-control" placeholder="Buscar producto..."
               value="<?php echo $buscar ?>">
    </div>
    <div class="col-md-2">
        <select name="cat" class="form-select">
            <option value="">Todas las categorias</option>
            <?php foreach($categorias as $c) { ?>
            <option value="<?php echo $c['id'] ?>" <?php if($filtro_cat == $c['id']) echo 'selected' ?>>
                <?php echo $c['nombre'] ?>
            </option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="tienda" class="form-select">
            <option value="">Todas las tiendas</option>
            <?php foreach($tiendas as $t) { ?>
            <option value="<?php echo $t['id'] ?>" <?php if($filtro_tienda == $t['id']) echo 'selected' ?>>
                <?php echo $t['nombre'] ?>
            </option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="stock" class="form-select">
            <option value="">Todo</option>
            <option value="bajo" <?php if($filtro_stock=='bajo') echo 'selected' ?>>Stock bajo</option>
            <option value="cero" <?php if($filtro_stock=='cero') echo 'selected' ?>>Sin stock</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </div>
    <div class="col-auto">
        <a href="inventario.php" class="btn btn-outline-secondary">Quitar filtros</a>
    </div>
    <div class="col-auto ms-auto">
        <!-- boton para abrir el modal de anadir -->
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalProducto"
                onclick="limpiarModal()">+ Anadir producto</button>
    </div>
</form>

<!-- tabla de productos -->
<div class="card">
<div class="table-responsive">
<table class="table table-hover mb-0">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Categoria</th>
            <th>Tienda</th>
            <th>Stock</th>
            <th>Precio compra</th>
            <th>Precio venta</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if(count($productos) == 0) { ?>
        <tr>
            <td colspan="8" class="text-center text-muted py-3">No hay productos</td>
        </tr>
        <?php } ?>
        
        <?php foreach($productos as $p) {
            // color del badge segun el stock
            if($p['stock'] == 0) {
                $color_stock = 'danger';
            } else if($p['stock'] <= $p['stock_minimo']) {
                $color_stock = 'warning';
            } else {
                $color_stock = 'success';
            }
        ?>
        <tr>
            <td><?php echo $p['id'] ?></td>
            <td>
                <strong><?php echo htmlspecialchars($p['nombre']) ?></strong>
                <?php if($p['referencia'] != '') { ?>
                <br><small class="text-muted"><?php echo $p['referencia'] ?></small>
                <?php } ?>
            </td>
            <td><?php echo $p['cat_nombre'] ? $p['cat_nombre'] : '-' ?></td>
            <td><?php echo $p['tienda_nombre'] ? $p['tienda_nombre'] : '-' ?></td>
            <td>
                <span class="badge bg-<?php echo $color_stock ?>">
                    <?php echo $p['stock'] ?> uds
                </span>
            </td>
            <td><?php echo $p['precio_compra'] ? 'EUR ' . number_format($p['precio_compra'], 2) : '-' ?></td>
            <td><?php echo $p['precio_venta'] ? 'EUR ' . number_format($p['precio_venta'], 2) : '-' ?></td>
            <td>
                <!-- boton editar abre el modal con los datos del producto -->
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalProducto"
                        onclick='cargarProducto(<?php echo json_encode($p) ?>)'>
                    Editar
                </button>
                <a href="?borrar=<?php echo $p['id'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Seguro que quieres eliminar este producto?')">
                    Eliminar
                </a>
            </td>
        </tr>
        <?php } // fin foreach productos ?>
    </tbody>
</table>
</div>
</div>

<!-- el modal para anadir o editar productos -->
<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModal">Anadir producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- id oculto, si es 0 es nuevo -->
                    <input type="hidden" name="id_producto" id="id_producto" value="0">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label>Nombre del producto *</label>
                            <input type="text" name="nombre" id="f_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Referencia</label>
                            <input type="text" name="referencia" id="f_ref" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Proveedor</label>
                            <input type="text" name="proveedor" id="f_proveedor" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Categoria</label>
                            <select name="id_categoria" id="f_categoria" class="form-select">
                                <option value="">Sin categoria</option>
                                <?php foreach($categorias as $c) { ?>
                                <option value="<?php echo $c['id'] ?>"><?php echo $c['nombre'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Tienda</label>
                            <select name="id_tienda" id="f_tienda" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php foreach($tiendas as $t) { ?>
                                <option value="<?php echo $t['id'] ?>"><?php echo $t['nombre'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Stock actual</label>
                            <input type="number" name="stock" id="f_stock" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label>Stock minimo</label>
                            <input type="number" name="stock_minimo" id="f_stock_min" class="form-control" value="3" min="0">
                        </div>
                        <div class="col-md-4">
                            <label>Precio compra</label>
                            <input type="number" name="precio_compra" id="f_precio_compra" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label>Precio venta</label>
                            <input type="number" name="precio_venta" id="f_precio_venta" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="col-12">
                            <label>Descripcion</label>
                            <textarea name="descripcion" id="f_descripcion" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <!-- el name guardar es lo que compruebo arriba en el PHP -->
                    <button type="submit" name="guardar" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div><!-- fin container -->

<script>
// limpiar el modal cuando es para anadir nuevo
function limpiarModal() {
    document.getElementById('tituloModal').textContent = 'Anadir producto nuevo';
    document.getElementById('id_producto').value = '0';
    document.querySelector('#modalProducto form').reset();
}

// cargar los datos del producto en el modal para editar
function cargarProducto(p) {
    document.getElementById('tituloModal').textContent = 'Editar producto';
    document.getElementById('id_producto').value = p.id;
    document.getElementById('f_nombre').value = p.nombre;
    document.getElementById('f_ref').value = p.referencia || '';
    document.getElementById('f_proveedor').value = p.proveedor || '';
    document.getElementById('f_categoria').value = p.id_categoria || '';
    document.getElementById('f_tienda').value = p.id_tienda || '';
    document.getElementById('f_stock').value = p.stock;
    document.getElementById('f_stock_min').value = p.stock_minimo;
    document.getElementById('f_precio_compra').value = p.precio_compra || 0;
    document.getElementById('f_precio_venta').value = p.precio_venta || 0;
    document.getElementById('f_descripcion').value = p.descripcion || '';
}

<?php if($producto_editar) { ?>
// si vengo de ?editar=X abro el modal automaticamente
window.onload = function() {
    cargarProducto(<?php echo json_encode($producto_editar) ?>);
    new bootstrap.Modal(document.getElementById('modalProducto')).show();
}
<?php } ?>
</script>

<?php renderFoot(); ?>

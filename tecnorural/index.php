<?php
// pagina principal de tecnorural
// hecha por mi

include 'includes/config.php';
include 'includes/layout.php';

$conn = getDB();

// sacar las tiendas de la base de datos
$resultado = $conn->query("SELECT * FROM tienda WHERE activa=1");
$tiendas = $resultado->fetchAll();

// contar productos
$res2 = $conn->query("SELECT COUNT(*) FROM inventario WHERE activo=1");
$numproductos = $res2->fetchColumn();

// contar incidencias que no esten cerradas ni resueltas
$res3 = $conn->query("SELECT COUNT(*) FROM incidencia WHERE estado != 'resuelta' AND estado != 'cerrada'");
$incidencias_abiertas = $res3->fetchColumn();

renderHead('Inicio');
renderNav('index.php');
?>

<div class="container">

<?php renderFlash(); ?>

<!-- aqui va el titulo principal -->
<div class="card mt-3 mb-4">
    <div class="card-body text-center">
        <h1>Bienvenido a TecnoRural</h1>
        <p>Somos una empresa de informatica en Extremadura</p>
        <p>Tenemos <?php echo count($tiendas); ?> tiendas abiertas</p>
        <p>Productos disponibles: <?php echo $numproductos ?></p>
        <p>Incidencias abiertas ahora mismo: <?php echo $incidencias_abiertas ?></p>
    </div>
</div>

<!-- historia de la empresa -->
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">Historia</div>
            <div class="card-body">
                <p>TecnoRural la fundo Ricardo cuando tenia 19 anos. Ricardo vivia en un pueblo de Caceres y no habia ninguna tienda de informatica cerca, la mas cercana estaba a como 60km o asi.</p>
                <p>Un dia Ricardo necesitaba comprar un raton para el ordenador y tuvo que coger el autobus y gastar todo el dia para ir a comprarlo. Cuando volvio penso "pues voy a abrir yo una tienda".</p>

                <!-- esto lo copie de un tutorial -->
                <blockquote class="blockquote">
                    <p>"En mi pueblo no habia tiendas de informatica y decidi abrir una"</p>
                    <footer class="blockquote-footer">Ricardo, el dueno</footer>
                </blockquote>

                <p>Ricardo ahorro dinero trabajando en verano y con ayuda de su familia abrio la primera tienda en Plasencia en 2021, el dia de su cumpleanos que cumplia 19. Al principio estaba solo pero le fue muy bien y abrio mas tiendas.</p>
                <p>En 2022 abrio en Caceres y en Navalmoral. Luego en 2023 abrio en Merida y en Badajoz. Ahora tiene <?php echo count($tiendas) ?> tiendas en total por toda Extremadura.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- info del dueno -->
        <div class="card mb-3">
            <div class="card-header">El fundador</div>
            <div class="card-body">
                <h5>Ricardo</h5>
                <p class="text-muted">Fundador y jefe de TecnoRural</p>
                <p>Tiene 19 anos (bueno ya tendra mas). Estudio informatica. Le gustan los ordenadores y los pueblos.</p>
                <p>En 2023 le dieron un premio de joven emprendedor en Caceres.</p>
            </div>
        </div>

        <!-- cuando paso cada cosa -->
        <div class="card">
            <div class="card-header">Cuando abrimos cada tienda</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">2020 - Ricardo tuvo la idea</li>
                <li class="list-group-item">2021 - Primera tienda Plasencia</li>
                <li class="list-group-item">2022 - Caceres y Navalmoral</li>
                <li class="list-group-item">2023 - Merida y Badajoz</li>
                <li class="list-group-item">Ahora - <?php echo count($tiendas) ?> tiendas</li>
            </ul>
        </div>
    </div>
</div>

<!-- aqui muestro las tiendas que hay -->
<h4>Nuestras tiendas</h4>
<div class="row mb-4">
    <?php
    // recorro todas las tiendas y las muestro
    foreach($tiendas as $t) {
    ?>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h6><?php echo $t['nombre'] ?></h6>
                <p class="text-muted" style="font-size:0.85em">
                    <?php echo $t['direccion'] ?>, <?php echo $t['municipio'] ?><br>
                    Telefono: <?php echo $t['telefono'] ?><br>
                    Horario: <?php echo $t['horario'] ?><br>
                    <!-- la fecha la pongo con date() que lo vi en youtube -->
                    Abrimos: <?php echo date('d/m/Y', strtotime($t['fecha_apertura'])) ?>
                </p>
            </div>
        </div>
    </div>
    <?php } // fin del foreach de tiendas ?>
</div>

</div><!-- fin container -->

<?php
// esto cierra el html
renderFoot();
?>

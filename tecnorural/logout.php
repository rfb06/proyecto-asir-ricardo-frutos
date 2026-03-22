<?php
// =============================================================================
// logout.php Cierra la sesin activa y redirige al login
// =============================================================================
require_once 'includes/config.php';

$nombre = $_SESSION['usuario_nombre'] ?? $_SESSION['supervisor_nombre'] ?? '';

session_destroy();

// Reiniciar sesin para poder guardar el flash
session_start();
if ($nombre) {
 flash('Sesin cerrada. Hasta pronto, ' . $nombre . '!');
}

header('Location: /login.php');
exit;

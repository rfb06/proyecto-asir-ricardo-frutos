<?php
// =============================================================================
// includes/config.php Configuracin global de TecnoRural
// =============================================================================

define('DB_HOST', '192.168.20.100');
define('DB_USER', 'webuser');
define('DB_PASS', 'WebPass2024!');
define('DB_NAME', 'tecnorural');
define('SITE_NAME', 'TecnoRural');

function getDB(): PDO {
 static $pdo = null;
 if ($pdo === null) {
 try {
 $pdo = new PDO(
 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
 DB_USER,
 DB_PASS,
 [
 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES => false,
 ]
 );
 } catch (PDOException $e) {
 die('<div style="font-family:monospace;padding:2rem;background:#1a0000;color:#ff6b6b;border:1px solid #ff0000;">
 <strong>Error de conexin a base de datos:</strong><br>' . htmlspecialchars($e->getMessage()) . '
 </div>');
 }
 }
 return $pdo;
}

function flash(string $msg, string $type = 'ok'): void {
 $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): ?array {
 if (isset($_SESSION['flash'])) {
 $f = $_SESSION['flash'];
 unset($_SESSION['flash']);
 return $f;
 }
 return null;
}

session_start();

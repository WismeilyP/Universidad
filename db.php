<?php
// Datos de conexión de Railway (Public Network)
$host = "yamanote.proxy.rlwy.net";
$user = "root";
$pass = "JKplQkjUJESoCdgqNyHLsGzzLkfAZjme"; 
$db   = "railway"; 
$port = "56605";

// Crear la conexión incluyendo el puerto
$conn = new mysqli($host, $user, $pass, $db, $port);

// Verificar la conexión
if($conn->connect_error){
    die("Conexión fallida: " . $conn->connect_error);
}

// Configuración para tildes y eñes
$conn->set_charset("utf8");

// Si quieres probar si funciona, quita las barras de la siguiente línea:
// echo "Conexión exitosa a la nube";
?>

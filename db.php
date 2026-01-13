<?php
$host = "ballast.proxy.rlwy.net";
$user = "root";
$pass = "JKplQkjUJESoCdgqNyHLsGzzLkfAZjme"; 
$db   = "railway"; // Asegúrate de que se llame 'railway' como en tu panel
$port = "47806";

$conn = new mysqli($host, $user, $pass, $db, $port);

if($conn->connect_error){
    // Esto te ayudará a ver el error en el navegador si falla
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>


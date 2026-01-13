<?php
include "db.php";

$name = "Administrador";
$email = "admin@marketplace.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";

$sql = "INSERT INTO users (name,email,password,role) VALUES ('$name','$email','$password','$role')";
if($conn->query($sql)){
    echo "Administrador creado correctamente.";
} else {
    echo "Error: " . $conn->error;
}
?>

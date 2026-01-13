<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit; }
include "db.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $user_id = $_SESSION['user_id'];

    $conn->query("INSERT INTO products (user_id,name,description,price) VALUES ($user_id,'$name','$desc','$price')");
    header("Location: dashboard.php");
}
?>

<form method="POST">
    <input type="text" name="name" placeholder="Nombre del producto" required>
    <textarea name="description" placeholder="Descripción"></textarea>
    <input type="number" step="0.01" name="price" placeholder="Precio" required>
    <button type="submit">Agregar</button>
</form>

<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: login.php");
    exit;
}

// aquí va todo el panel de administración
echo "Bienvenido administrador, puedes gestionar usuarios, vendedores y productos";

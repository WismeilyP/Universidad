<?php
include "db.php";
if (session_status() == PHP_SESSION_NONE) session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;
    $redirectTo = isset($_POST['redirect']) ? $_POST['redirect'] : '';

    if($product_id <= 0){
        header("Location: index.php"); exit;
    }

    // obtener info del producto (incluyendo user_id del vendedor)
    $stmt = $conn->prepare("SELECT stock, user_id FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows === 0){
        $_SESSION['flash_error'] = "Producto no encontrado.";
        header("Location: index.php"); exit;
    }
    $row = $res->fetch_assoc();
    $stock = (int)$row['stock'];
    $seller_id = (int)$row['user_id'];

    // Si el usuario está logueado y es el mismo vendedor, denegar
    if(isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $seller_id){
        $_SESSION['flash_error'] = "No puedes añadir a tu carrito un producto propio.";
        header("Location: product_view.php?id=$product_id");
        exit;
    }

    if($stock <= 0){
        $_SESSION['flash_error'] = "Este producto está agotado.";
        header("Location: product_view.php?id=$product_id"); exit;
    }

    // inicializar carrito
    if(!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

    $currentQty = $_SESSION['cart'][$product_id] ?? 0;
    $newQty = $currentQty + $qty;

    if($newQty > $stock){
        $_SESSION['flash_warning'] = "Solo quedan $stock unidades en stock. Se ajustó la cantidad en tu carrito.";
        $_SESSION['cart'][$product_id] = $stock;
    } else {
        $_SESSION['cart'][$product_id] = $newQty;
        $_SESSION['flash_success'] = "Producto agregado al carrito.";
    }

    // Redirección condicional: si el form envió redirect=checkout vamos al checkout, si no al carrito
    if($redirectTo === 'checkout'){
        header("Location: checkout.php");
        exit;
    } else {
        header("Location: cart.php");
        exit;
    }
}
header("Location: index.php");
exit;

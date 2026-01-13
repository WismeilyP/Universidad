<?php
include "db.php";
if (session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php?return=checkout.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if(empty($cart)){
    header("Location: cart.php");
    exit;
}

$buyer_id = (int)$_SESSION['user_id'];

// iniciar transacción
$conn->begin_transaction();
try {
    $selectStmt = $conn->prepare("SELECT id, price, stock, user_id FROM products WHERE id = ? FOR UPDATE");
    $updateStockStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    $insertOrderStmt = $conn->prepare("INSERT INTO orders (product_id, buyer_id, quantity, total_price, status, created_at) VALUES (?, ?, ?, ?, 'pendiente', NOW())");

    foreach($cart as $pid => $qty){
        $pid = (int)$pid;
        $qty = max(1, (int)$qty);

        // bloquear fila del producto y obtener seller_id
        $selectStmt->bind_param("i", $pid);
        $selectStmt->execute();
        $res = $selectStmt->get_result();
        if($res->num_rows === 0) throw new Exception("Producto $pid no existe.");
        $prod = $res->fetch_assoc();
        $stock = (int)$prod['stock'];
        $price = (float)$prod['price'];
        $seller_id = (int)$prod['user_id'];

        // **VERIFICACIÓN CLAVE**: impedir que comprador sea el vendedor
        if($seller_id === $buyer_id){
            throw new Exception("No puedes comprar tus propios productos (producto ID: $pid).");
        }

        if($stock < $qty){
            throw new Exception("Stock insuficiente para el producto ID $pid — disponible: $stock, solicitado: $qty");
        }

        $subtotal = $price * $qty;

        // insertar order (línea)
        if(!$insertOrderStmt->bind_param("iiid", $pid, $buyer_id, $qty, $subtotal)) {
            throw new Exception("Bind failed");
        }
        if(!$insertOrderStmt->execute()){
            throw new Exception("Error insertando orden: ".$insertOrderStmt->error);
        }

        // restar stock
        $updateStockStmt->bind_param("ii", $qty, $pid);
        if(!$updateStockStmt->execute()){
            throw new Exception("Error actualizando stock: ".$updateStockStmt->error);
        }
    }

    // commit
    $conn->commit();
    unset($_SESSION['cart']);
    header("Location: purchase_success.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    include "inc/header.php";
    echo "<div class='alert alert-danger'>Error al procesar la compra: ".htmlspecialchars($e->getMessage())."</div>";
    echo "<a href='cart.php' class='btn btn-primary'>Volver al carrito</a>";
    include "inc/footer.php";
    exit;
}

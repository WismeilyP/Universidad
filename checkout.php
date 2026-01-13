<?php
include "db.php";
if (session_status() == PHP_SESSION_NONE) session_start();

// Verificar usuario logueado
if(!isset($_SESSION['user_id'])){
    header("Location: login.php?return=checkout.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];

if(empty($cart)){
    echo "<div class='container py-5'><div class='alert alert-info'>Tu carrito está vacío. <a href='index.php'>Ir a tienda</a></div></div>";
    include "inc/footer.php";
    exit;
}

// Preparar resumen de items
$items = [];
$total = 0;
$ids = implode(',', array_map('intval', array_keys($cart)));
if($ids){
    $res = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    while($p = $res->fetch_assoc()){
        $pid = (int)$p['id'];
        $qty = $cart[$pid] ?? 0;
        $sub = ((float)$p['price']) * $qty;
        $items[] = ['product'=>$p, 'qty'=>$qty, 'subtotal'=>$sub];
        $total += $sub;
    }
}

// Manejo POST: subida comprobante y registro órdenes
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!isset($_FILES['proof']) || $_FILES['proof']['error'] === UPLOAD_ERR_NO_FILE){
        $_SESSION['flash_error'] = "Debes adjuntar una imagen o PDF como comprobante de pago.";
        header("Location: checkout.php");
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/payments/';
    if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $f = $_FILES['proof'];
    $allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
    if(!in_array($f['type'], $allowed)){
        $_SESSION['flash_error'] = "Formato no permitido. Usa JPG, PNG, WEBP o PDF.";
        header("Location: checkout.php");
        exit;
    }

    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $fileName = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $target = $uploadDir . $fileName;
    if(!move_uploaded_file($f['tmp_name'], $target)){
        $_SESSION['flash_error'] = "Error al subir el comprobante.";
        header("Location: checkout.php");
        exit;
    }

    $successInsert = false;
    foreach($items as $it){
        $p = $it['product'];
        $qty = (int)$it['qty'];
        $lineTotal = number_format(((float)$p['price']) * $qty, 2, '.', '');
        $stmt = $conn->prepare("INSERT INTO orders (product_id, buyer_id, quantity, total_price, payment_proof, status, created_at) VALUES (?, ?, ?, ?, ?, 'pendiente_pago', NOW())");
        if($stmt){
            $stmt->bind_param("iiids", $p['id'], $user_id, $qty, $lineTotal, $fileName);
            if($stmt->execute()) $successInsert = true;
            $stmt->close();
        }
    }

    if($successInsert){
        unset($_SESSION['cart']);
        $_SESSION['flash_success'] = "Compra registrada: se subió el comprobante y el vendedor la revisará. Estado: pendiente de pago.";
        header("Location: my_orders.php");
        exit;
    } else {
        $_SESSION['flash_error'] = "No se pudo registrar la orden. Intenta de nuevo.";
        header("Location: checkout.php");
        exit;
    }
}
include "inc/header.php";

?>

<style>
.checkout-container { font-family:'Poppins',sans-serif; padding:60px 0; background:#f5f6fa; }
.checkout-title { text-align:center; margin-bottom:50px; font-weight:700; color:#0b2545; font-size:2.2rem; }
.card-checkout { border-radius:15px; box-shadow:0 15px 30px rgba(0,0,0,0.05); background:#fff; padding:30px; margin-bottom:30px; }
.table-products th { background:#0b2545; color:#fff; font-weight:500; text-align:center; }
.table-products td { vertical-align:middle; text-align:center; }
.table-products tbody tr:hover { background:#f1f5fb; }
.product-thumb { width:60px; height:60px; object-fit:cover; border-radius:8px; }
.total-sidebar { background:#0b2545; color:#fff; border-radius:15px; padding:30px; font-size:1.3rem; font-weight:700; text-align:center; position:sticky; top:100px; }
.btn-submit { background:#0b2545; color:#fff; border:none; padding:15px 25px; border-radius:12px; font-weight:600; width:100%; transition:0.3s; font-size:1.1rem; }
.btn-submit:hover { background:#0d2c61; }
.file-input { border:2px dashed #0b2545; border-radius:12px; padding:25px; text-align:center; cursor:pointer; color:#0b2545; font-weight:500; transition:0.3s; }
.file-input:hover { background:#f1f5fb; }
.alert { border-radius:12px; }
</style>

<div class="container checkout-container">
    <h2 class="checkout-title">Checkout</h2>

    <?php
    if(!empty($_SESSION['flash_error'])){ echo "<div class='alert alert-danger'>".$_SESSION['flash_error']."</div>"; unset($_SESSION['flash_error']); }
    if(!empty($_SESSION['flash_success'])){ echo "<div class='alert alert-success'>".$_SESSION['flash_success']."</div>"; unset($_SESSION['flash_success']); }
    ?>

    <div class="row">
        <div class="col-lg-7">
            <div class="card-checkout">
                <h4 class="mb-4">Tus productos</h4>
                <div class="table-responsive">
                    <table class="table table-products">
                        <thead>
                            <tr><th>Imagen</th><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach($items as $it): $p=$it['product']; ?>
                            <tr>
                                <td><img src="<?php echo !empty($p['image']) && file_exists('uploads/'.$p['image']) ? 'uploads/'.$p['image'] : 'https://via.placeholder.com/60'; ?>" class="product-thumb" alt=""></td>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td>$<?php echo number_format($p['price'],2); ?></td>
                                <td><?php echo (int)$it['qty']; ?></td>
                                <td>$<?php echo number_format($it['subtotal'],2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-checkout">
                <h4 class="mb-4">Adjunta tu comprobante de pago</h4>
                <form method="POST" enctype="multipart/form-data">
                    <label class="file-input mb-3">
                        <input type="file" name="proof" class="form-control" accept="image/*,.pdf" required style="display:none;">
                        Haz clic aquí para subir tu comprobante
                    </label>
                    <button type="submit" class="btn-submit">Finalizar compra</button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="total-sidebar">
                Total a pagar: $<?php echo number_format($total,2); ?>
            </div>
        </div>
    </div>
</div>

<?php include "inc/footer.php"; ?>

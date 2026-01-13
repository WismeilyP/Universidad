<?php
// cart.php - versión con diseño profesional
include "db.php";
if (session_status() == PHP_SESSION_NONE) session_start();

// Mostrar mensajes flash (si los hay)
if(!empty($_SESSION['flash_error'])){
    echo "<div class='alert alert-danger my-3'>".$_SESSION['flash_error']."</div>";
    unset($_SESSION['flash_error']);
}
if(!empty($_SESSION['flash_warning'])){
    echo "<div class='alert alert-warning my-3'>".$_SESSION['flash_warning']."</div>";
    unset($_SESSION['flash_warning']);
}
if(!empty($_SESSION['flash_success'])){
    echo "<div class='alert alert-success my-3'>".$_SESSION['flash_success']."</div>";
    unset($_SESSION['flash_success']);
}

// Actualizar cantidades si vienen por POST
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])){
    if(isset($_POST['qty']) && is_array($_POST['qty'])){
        foreach($_POST['qty'] as $pid => $q){
            $pid = (int)$pid;
            $q = max(0, (int)$q);
            if($q <= 0){
                unset($_SESSION['cart'][$pid]);
            } else {
                $_SESSION['cart'][$pid] = $q;
            }
        }
    }
    // mensaje breve (se muestra arriba en include header)
    $_SESSION['flash_success'] = "Carrito actualizado correctamente.";
    header("Location: cart.php");
    exit;
}

// Eliminar individual (vía GET)
if(isset($_GET['remove'])){
    $rem = (int)$_GET['remove'];
    unset($_SESSION['cart'][$rem]);
    $_SESSION['flash_success'] = "Producto eliminado del carrito.";
    header("Location: cart.php");
    exit;
}

// Obtener los productos del carrito
$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0.00;

if(!empty($cart) && is_array($cart)){
    // Construir lista de ids seguros
    $ids = array_map('intval', array_keys($cart));
    // por seguridad, filtrar ids > 0
    $ids = array_filter($ids, function($v){ return $v > 0; });
    if(!empty($ids)){
        $in = implode(',', $ids);
        if(isset($conn)){
            $sql = "SELECT * FROM products WHERE id IN ($in)";
            $res = $conn->query($sql);
            if($res){
                while($p = $res->fetch_assoc()){
                    $pid = (int)$p['id'];
                    $qty = $cart[$pid] ?? 0;
                    $subtotal = $p['price'] * $qty;
                    $items[] = ['product'=>$p, 'qty'=>$qty, 'subtotal'=>$subtotal];
                    $total += $subtotal;
                }
            } else {
                echo "<div class='alert alert-danger my-3'>Error al obtener productos: ".htmlspecialchars($conn->error)."</div>";
            }
        } else {
            echo "<div class='alert alert-danger my-3'>Error: conexión a la base de datos no disponible.</div>";
        }
    }
}
include "inc/header.php";

?>

<style>
/* Estilos del carrito - look profesional */
.cart-page { font-family: 'Poppins', sans-serif; padding: 30px 0; }
.cart-header { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:18px; }
.cart-title { font-size:1.6rem; font-weight:700; color:#0b2545; }
.cart-sub { color:#6b7280; font-size:0.95rem; }

.cart-table { width:100%; border-collapse:collapse; background:transparent; }
.cart-table thead th { background:#0b2545; color:#fff; font-weight:600; padding:12px 16px; border-radius:6px; }
.cart-table td, .cart-table th { vertical-align:middle; padding:14px 12px; border-bottom:1px solid #eef2f7; }
.product-preview { display:flex; align-items:center; gap:12px; }
.product-preview img { width:90px; height:64px; object-fit:cover; border-radius:8px; box-shadow:0 6px 18px rgba(11,37,69,0.06); }

/* Totales panel */
.cart-summary { background:#fff; border-radius:12px; padding:18px; box-shadow:0 6px 20px rgba(11,37,69,0.06); }
.cart-summary h4 { margin:0 0 10px 0; color:#0b2545; }
.cart-summary .total { font-size:1.4rem; font-weight:700; color:#0b2545; }

/* Buttons */
.btn-clean { background:transparent; border:none; color:#ef4444; }
.btn-action { border-radius:8px; padding:10px 16px; }

/* Responsive: en móvil mostramos tarjetas */
@media(max-width: 767px){
    .cart-table, .cart-table thead, .cart-table tbody, .cart-table th, .cart-table td, .cart-table tr { display:block; width:100%; }
    .cart-table thead { display:none; }
    .cart-table tr { margin-bottom:14px; background:#fff; border-radius:10px; padding:12px; box-shadow:0 6px 20px rgba(11,37,69,0.04); }
    .cart-table td { display:flex; justify-content:space-between; padding:8px 6px; border-bottom:0; }
    .cart-table td .label { color:#6b7280; font-size:0.9rem; margin-right:8px; }
    .product-preview img { width:80px; height:56px; }
    .cart-header { flex-direction:column; align-items:flex-start; gap:6px; }
}
</style>

<div class="container cart-page">
  <div class="cart-header">
    <div>
      <div class="cart-title">Tu carrito</div>
      <div class="cart-sub"><?php echo count($items) ?> artículo(s) — Revisa antes de pagar</div>
    </div>
    <div>
      <a href="index.php" class="btn btn-link">Seguir comprando</a>
    </div>
  </div>

<?php if(empty($items)): ?>
    <div class="alert alert-info">El carrito está vacío. <a href="index.php">Ver productos</a></div>
<?php else: ?>

  <form method="POST" class="mb-4">
    <input type="hidden" name="update_cart" value="1">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card p-3">
          <table class="cart-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th style="width:120px;">Precio</th>
                <th style="width:140px;">Cantidad</th>
                <th style="width:140px;">Subtotal</th>
                <th style="width:60px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($items as $it):
                $p = $it['product'];
                $imgPath = (!empty($p['image']) && file_exists(__DIR__.'/uploads/'.$p['image'])) ? 'uploads/'.$p['image'] : 'https://via.placeholder.com/200x120?text=Sin+imagen';
            ?>
              <tr>
                <td>
                  <div class="product-preview">
                    <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <div>
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="fw-bold text-decoration-none text-dark"><?php echo htmlspecialchars($p['name']); ?></a>
                      <div class="text-muted small mt-1"><?php echo htmlspecialchars(mb_strimwidth($p['description'] ?? '',0,80,'...')); ?></div>
                    </div>
                  </div>
                </td>
                <td>$<?php echo number_format($p['price'],2); ?></td>
                <td style="vertical-align:middle;">
                  <div style="max-width:130px;">
                    <input type="number" name="qty[<?php echo (int)$p['id']; ?>]" value="<?php echo (int)$it['qty']; ?>" min="0" class="form-control" />
                  </div>
                </td>
                <td>$<?php echo number_format($it['subtotal'],2); ?></td>
                <td class="text-end">
                  <a href="cart.php?remove=<?php echo (int)$p['id']; ?>" class="btn btn-clean" title="Quitar"><span class="bi bi-trash" style="font-size:1.05rem;">x</span></a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="cart-summary">
          <h4>Resumen del pedido</h4>
          <div class="d-flex justify-content-between my-2">
            <div class="text-muted">Subtotal</div>
            <div>$<?php echo number_format($total,2); ?></div>
          </div>

          <?php
            // Si quieres calcular impuestos/envío aquí, hazlo. Por ahora mostramos total directo.
          ?>

          <hr>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="total">Total</div>
            <div class="total">$<?php echo number_format($total,2); ?></div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-action">Actualizar carrito</button>
            <a href="checkout.php" class="btn btn-success btn-action m-0">Proceder a pagar</a>
            <a href="index.php" class="btn btn-outline-secondary btn-action m-0">Seguir comprando</a>
          </div>
        </div>

        <!-- Mini resumen de métodos/nota -->
        <div class="card mt-3 p-3">
          <h6 class="mb-2">Notas</h6>
          <p class="small text-muted mb-0">Puedes actualizar cantidades o eliminar productos. El pago se realiza en la página de checkout.</p>
        </div>
      </div>
    </div>
  </form>

<?php endif; ?>
</div>

<?php include "inc/footer.php"; ?>

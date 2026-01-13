<?php
include "db.php";
include "inc/header.php";

// id seguro desde GET
$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($seller_id <= 0){
    echo "<div class='container py-4'><div class='alert alert-danger'>Vendedor no encontrado.</div></div>";
    include "inc/footer.php";
    exit;
}

// Info vendedor
$stmt = $conn->prepare("SELECT id, name, business_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$res = $stmt->get_result();
if(!$res || $res->num_rows === 0){
    echo "<div class='container py-4'><div class='alert alert-danger'>Vendedor no encontrado.</div></div>";
    include "inc/footer.php";
    exit;
}
$seller = $res->fetch_assoc();
$stmt->close();

// Estadísticas
$total_products = (int)($conn->query("SELECT COUNT(*) AS cnt FROM products WHERE user_id=$seller_id")->fetch_assoc()['cnt']);
$salesRow = $conn->query("
    SELECT COUNT(*) AS total_sales, SUM(COALESCE(o.total_price, p.price * COALESCE(o.quantity,1), p.price)) AS total_revenue
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE p.user_id = $seller_id
")->fetch_assoc();
$total_sales = (int)($salesRow['total_sales'] ?? 0);
$total_revenue = (float)($salesRow['total_revenue'] ?? 0.0);

// Productos
$productsRes = $conn->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.user_id=$seller_id ORDER BY p.created_at DESC");

// Últimas compras
$ordersRes = $conn->query("
    SELECT o.*, p.name AS product_name, p.price AS product_price, u.name AS buyer_name, u.email AS buyer_email
    FROM orders o
    LEFT JOIN products p ON o.product_id=p.id
    LEFT JOIN users u ON o.buyer_id=u.id
    WHERE p.user_id=$seller_id
    ORDER BY o.created_at DESC
    LIMIT 10
");
?>

<style>

.seller-profile 
{ 
    font-family: 'Poppins', sans-serif; 
    padding: 30px 0; 
}
.card-custom 
{ 
    border-radius: 12px; 
    box-shadow: 0 10px 25px rgba(11, 37, 69, 0.14); 
}
.profile-header 
{ 
    background:#0b2545; 
    color:#fff; 
    border-radius:12px; 
    padding:25px; 
    margin-bottom:20px; 
    text-align:center; 
}
.profile-header h2 { font-weight:700; margin-bottom:5px; }
.profile-header .stats { display:flex; gap:20px; margin-top:10px; flex-wrap:wrap; justify-content:center; }
.profile-header .stats div { background:rgba(255,255,255,0.1); padding:10px 15px; border-radius:10px; text-align:center; flex:1; min-width:100px; }
.products-grid .card img { height:180px; object-fit:cover; border-radius:8px; }
.products-grid .card { transition:transform 0.2s; }
.products-grid .card:hover { transform:translateY(-4px); }
.table-orders th { background:#0b2545; color:#fff; }
.table-orders tbody tr:hover { background:#f4f6fb; }
.contact-card, .stats-card { border-radius:12px; box-shadow:0 6px 18px rgba(11,37,69,0.06); margin-bottom:20px; padding:15px; }
</style>

<div class="container seller-profile">
  <!-- HEADER PERFIL -->
  <div class="profile-header">
    <h2><?php echo htmlspecialchars($seller['business_name'] ?: $seller['name']); ?></h2>
    <p class="mb-0">Vendedor: <?php echo htmlspecialchars($seller['name']); ?></p>
    <div class="stats mt-3">
      <div>
        <div><strong><?php echo $total_products; ?></strong></div>
        <div>Productos</div>
      </div>
      <div>
        <div><strong><?php echo $total_sales; ?></strong></div>
        <div>Ventas</div>
      </div>
      <div>
        <div><strong>$<?php echo number_format($total_revenue,2); ?></strong></div>
        <div>Ingresos</div>
      </div>
    </div>
    <?php if(!empty($seller['email'])): ?>
        <a class="btn btn-outline-light mt-3" href="mailto:<?php echo htmlspecialchars($seller['email']); ?>">Contactar vendedor</a>
    <?php endif; ?>
  </div>

  <div class="row">
    <!-- PRODUCTOS -->
    <div class="col-lg-8">
      <h4>Productos del vendedor</h4>
      <?php if(!$productsRes || $productsRes->num_rows === 0): ?>
        <div class="alert alert-info">Este vendedor no tiene productos publicados.</div>
      <?php else: ?>
        <div class="row products-grid">
          <?php while($prod=$productsRes->fetch_assoc()):
            $img = (!empty($prod['image']) && file_exists(__DIR__.'/uploads/'.$prod['image'])) ? 'uploads/'.$prod['image'] : 'https://via.placeholder.com/400x250?text=Sin+imagen';
          ?>
            <div class="col-md-6 mb-3">
              <div class="card card-custom h-100">
                <img src="<?php echo htmlspecialchars($img); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                <div class="card-body d-flex flex-column">
                  <?php if(!empty($prod['category_name'])): ?>
                    <span class="badge bg-secondary mb-1"><?php echo htmlspecialchars($prod['category_name']); ?></span>
                  <?php endif; ?>
                  <h5 class="card-title"><?php echo htmlspecialchars($prod['name']); ?></h5>
                  <p class="small text-muted mb-1">Stock: <?php echo (int)($prod['stock'] ?? 0); ?></p>
                  <p class="mt-auto"><strong>$<?php echo number_format($prod['price'],2); ?></strong></p>
                  <a href="product_view.php?id=<?php echo (int)$prod['id']; ?>" class="btn btn-primary btn-sm mt-2">Ver producto</a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

      <h4 class="mt-4">Últimas compras</h4>
      <?php if(!$ordersRes || $ordersRes->num_rows === 0): ?>
        <div class="alert alert-info">Aún no hay compras a este vendedor.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-orders table-striped">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Comprador</th>
                <th>Cantidad</th>
                <th>Total</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <?php while($o=$ordersRes->fetch_assoc()):
                $qty = isset($o['quantity']) ? (int)$o['quantity'] : 1;
                $lineTotal = isset($o['total_price']) && $o['total_price'] !== null ? (float)$o['total_price'] : ((float)($o['product_price'] ?? 0)*$qty);
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($o['product_name'] ?? 'Producto eliminado'); ?></td>
                  <td><?php echo htmlspecialchars($o['buyer_name'] ?? $o['buyer_email'] ?? '-'); ?></td>
                  <td><?php echo $qty; ?></td>
                  <td>$<?php echo number_format($lineTotal,2); ?></td>
                  <td><?php echo !empty($o['created_at']) ? date("d/m/Y H:i", strtotime($o['created_at'])) : '-'; ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- SIDEBAR CONTACTO Y ESTADÍSTICAS -->
    <div class="col-lg-4">
      <div class="contact-card">
        <h5>Contacto</h5>
        <p class="mb-1"><strong><?php echo htmlspecialchars($seller['business_name'] ?: $seller['name']); ?></strong></p>
        <p class="small text-muted">Nombre: <?php echo htmlspecialchars($seller['name']); ?></p>
        <p class="small text-muted">Email: <?php echo htmlspecialchars($seller['email'] ?? '-'); ?></p>
        <?php if(!empty($seller['email'])): ?>
            <a class="btn btn-primary w-100 mt-2" href="mailto:<?php echo htmlspecialchars($seller['email']); ?>">Enviar correo</a>
        <?php endif; ?>
      </div>

      <div class="stats-card">
        <h6>Estadísticas rápidas</h6>
        <ul class="list-unstyled mb-0">
          <li>Total de productos: <strong><?php echo $total_products; ?></strong></li>
          <li>Total de ventas: <strong><?php echo $total_sales; ?></strong></li>
          <li>Ingresos estimados: <strong>$<?php echo number_format($total_revenue,2); ?></strong></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include "inc/footer.php"; ?>

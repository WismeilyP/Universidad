<?php
include "db.php";
include "inc/header.php";

if(session_status() == PHP_SESSION_NONE) session_start();

// id seguro
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){
    echo "<div class='container py-4'><div class='alert alert-danger'>Producto no encontrado.</div></div>";
    include "inc/footer.php";
    exit;
}

// Obtener producto + vendedor + categoría
$stmt = $conn->prepare("
    SELECT p.*, u.id AS seller_id, u.name AS seller_name, u.business_name, u.email AS seller_email,
           c.id AS category_id, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0){
    echo "<div class='container py-4'><div class='alert alert-danger'>Producto no encontrado.</div></div>";
    include "inc/footer.php";
    exit;
}
$product = $res->fetch_assoc();
$stmt->close();

// Imagen principal
$imgPath = (!empty($product['image']) && file_exists(__DIR__.'/uploads/'.$product['image'])) ? 'uploads/'.$product['image'] : 'https://via.placeholder.com/1000x600?text=Sin+imagen';

// Placeholder para avatar del vendedor
$sellerAvatar = 'https://via.placeholder.com/120x120?text=Vendedor';

$available = isset($product['stock']) ? (int)$product['stock'] : 0;
$is_owner = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$product['user_id'];
$agotado = $available <= 0;
$precio = number_format((float)$product['price'], 2);
?>

<style>
  body {
    background: #d1d1d154 !important;
}
.product-view { font-family: 'Poppins', sans-serif; padding: 30px 0; }
.product-card { background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(11,37,69,0.06); overflow:hidden; }
.gallery { display:flex; gap:14px; }
.main-image { border-radius:10px; overflow:hidden; }
.thumb-list { display:flex; flex-direction:column; gap:10px; }
.thumb-list img { width:72px; height:72px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid transparent; }
.thumb-list img.active { border-color:#0b2545; transform:scale(0.98); }
.info-panel { padding:18px; }
.product-title { font-size:1.6rem; font-weight:700; color:#0b2545; margin-bottom:6px; }
.product-price { font-size:1.5rem; color:#0b2545; font-weight:700; }
.badges { gap:8px; display:flex; flex-wrap:wrap; margin-top:8px; }
.seller-box { display:flex; gap:12px; align-items:center; }
.seller-avatar { width:64px; height:64px; border-radius:50%; object-fit:cover; box-shadow:0 6px 18px rgba(11,37,69,0.06); }
.btn-buy { width:100%; padding:12px 14px; border-radius:10px; font-weight:600; }
.btn-outline-buy { width:100%; padding:10px 14px; border-radius:10px; }
.product-tabs { margin-top:18px; }
.tab-content { background:#fff; padding:14px; border-radius:10px; box-shadow:0 6px 18px rgba(11,37,69,0.04); }
@media(max-width:991px){
  .gallery{ flex-direction:column; }
  .thumb-list{ flex-direction:row; }
}
</style>

<div class="container product-view">
  <div class="product-card p-3">
    <div class="row g-4">
      <div class="col-lg-7">
        <!-- Galería -->
        <div class="gallery">
          <div class="flex-fill main-image">
            <img id="mainProductImg" src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:100%; height:520px; object-fit:cover; border-radius:8px;">
          </div>
          <div class="thumb-list">
            <img src="<?php echo htmlspecialchars($imgPath); ?>" class="active" data-src="<?php echo htmlspecialchars($imgPath); ?>" alt="thumb">
          </div>
        </div>

        <!-- Tabs descripción/detalles -->
        <div class="product-tabs mt-3">
          <ul class="nav nav-tabs" id="prodTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button" role="tab">Descripción</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Detalles</button>
            </li>
          </ul>
          <div class="tab-content mt-2">
            <div class="tab-pane fade show active" id="desc" role="tabpanel">
              <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
            <div class="tab-pane fade" id="details" role="tabpanel">
              <ul class="list-unstyled mb-0">
                <li><strong>Categoría:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Sin categoría'); ?></li>
                <li><strong>ID:</strong> <?php echo (int)$product['id']; ?></li>
                <li><strong>Disponibilidad:</strong> <?php echo $agotado ? 'Agotado' : 'En stock (' . $available . ')'; ?></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="info-panel">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
              <div class="text-muted small">Vendido por <strong><?php echo htmlspecialchars($product['business_name'] ?: $product['seller_name']); ?></strong></div>
            </div>
            <div class="text-end">
              <div class="product-price">$<?php echo $precio; ?></div>
              <div class="text-muted small"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></div>
            </div>
          </div>

          <div class="badges mt-3">
            <?php if($agotado): ?>
              <span class="badge bg-danger">Agotado</span>
            <?php else: ?>
              <span class="badge bg-success">En stock: <?php echo $available; ?></span>
            <?php endif; ?>
          </div>

          <!-- Vendedor -->
          <div class="card mt-4 p-3" style="border-radius:12px;">
            <div class="seller-box">
              <img src="<?php echo $sellerAvatar; ?>" alt="Avatar vendedor" class="seller-avatar">
              <div>
                <div class="fw-bold"><?php echo htmlspecialchars($product['business_name'] ?: $product['seller_name']); ?></div>
                <div class="small text-muted"><?php echo htmlspecialchars($product['seller_email'] ?? ''); ?></div>
                <a href="seller_profile.php?id=<?php echo (int)$product['seller_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">Ver perfil</a>
              </div>
            </div>
          </div>

          <!-- Añadir al carrito -->
          <div class="card mt-3 p-3" style="border-radius:12px;">
            <?php if($is_owner): ?>
              <div class="alert alert-info mb-2">Este es tu producto — no puedes comprarlo.</div>
              <a href="dashboard.php?page=edit_product&id=<?php echo (int)$product['id']; ?>" class="btn btn-outline-primary w-100">Editar producto</a>
            <?php elseif($agotado): ?>
              <div class="alert alert-warning mb-2">Este producto está agotado.</div>
              <a href="cart.php" class="btn btn-secondary w-100">Ver carrito</a>
            <?php else: ?>
              <form method="POST" action="add_to_cart.php" class="mb-2">
                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                <div class="mb-3 d-flex align-items-center gap-2">
                  <label class="form-label mb-0">Cantidad</label>
                  <input type="number" name="qty" value="1" min="1" max="<?php echo $available; ?>" class="form-control" style="width:110px;">
                </div>
                <div class="d-grid gap-2">
                  <button class="btn btn-primary btn-buy" type="submit">Añadir al carrito</button>
                  <button class="btn btn-success btn-buy" type="submit" name="redirect" value="checkout" formmethod="post" formaction="add_to_cart.php">
                    Comprar Ahora
                  </button>
                </div>
              </form>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.thumb-list img').forEach(function(t){
  t.addEventListener('click', function(){
    document.querySelectorAll('.thumb-list img').forEach(i=>i.classList.remove('active'));
    t.classList.add('active');
    var src = t.getAttribute('data-src');
    document.getElementById('mainProductImg').src = src;
  });
});
</script>

<?php include "inc/footer.php"; ?>

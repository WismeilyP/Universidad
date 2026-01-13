<?php
// landing.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!doctype html>

<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Marketplace - Bienvenido</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: 'Poppins', sans-serif; margin:0; padding:0; background:#f4f6f9; }
    /* Hero */
    .hero { background: linear-gradient(90deg, #0b2545 0%, #1a3b6d 100%); color:#fff; padding:100px 0; text-align:center; }
    .hero h1 { font-size:3rem; font-weight:700; margin-bottom:20px; }
    .hero p { font-size:1.2rem; margin-bottom:30px; color:#cdd7e3; }
    .hero .btn-primary { background:#4fbeff; border:none; padding:12px 30px; font-size:1.1rem; transition:0.3s; }
    .hero .btn-primary:hover { background:#3aa0e0; }

```
/* Vendedores destacados */
.featured { padding:80px 0; text-align:center; }
.featured h2 { font-weight:700; margin-bottom:50px; }
.card-product { transition:transform 0.3s ease, box-shadow 0.3s ease; }
.card-product:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.15); }

/* Botones acceso admin */
.access-buttons { margin:50px 0; text-align:center; }
.access-buttons a { margin:0 10px; padding:12px 25px; font-weight:600; font-size:1rem; border-radius:8px; text-decoration:none; transition:0.3s; }
.access-buttons a.admin { background:#0b2545; color:#fff; }
.access-buttons a.admin:hover { background:#1a3b6d; }
.access-buttons a.dashboard { background:#4fbeff; color:#0b2545; }
.access-buttons a.dashboard:hover { background:#3aa0e0; }

/* Footer */
footer { margin-top:50px; }
```

  </style>
</head>
<body>

<!-- Hero -->

<section class="hero">
  <div class="container">
    <h1>Bienvenido a nuestro Marketplace</h1>
    <p>Compra y vende productos de forma segura y confiable. Encuentra lo que necesitas, donde quieras.</p>
    <a href="#featured" class="btn btn-primary">Ver vendedores destacados</a>
  </div>
</section>

<!-- Vendedores destacados -->

<section class="featured" id="featured">
  <div class="container">
    <h2>Vendedores destacados</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card card-product">
          <img src="img/product1.jpg" class="card-img-top" alt="Producto 1">
          <div class="card-body">
            <h5 class="card-title">Vendedor 1</h5>
            <p class="card-text">Productos de calidad y servicio confiable.</p>
            <a href="dashboard.php?page=profile" class="btn btn-primary btn-sm">Ver perfil</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-product">
          <img src="img/product2.jpg" class="card-img-top" alt="Producto 2">
          <div class="card-body">
            <h5 class="card-title">Vendedor 2</h5>
            <p class="card-text">Encuentra los mejores productos destacados aquí.</p>
            <a href="dashboard.php?page=profile" class="btn btn-primary btn-sm">Ver perfil</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-product">
          <img src="img/product3.jpg" class="card-img-top" alt="Producto 3">
          <div class="card-body">
            <h5 class="card-title">Vendedor 3</h5>
            <p class="card-text">Confianza y calidad en todos sus productos.</p>
            <a href="dashboard.php?page=profile" class="btn btn-primary btn-sm">Ver perfil</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Acceso administrador -->

<section class="access-buttons">
  <a href="admin/login.php" class="admin">Acceso Administrador</a>
  <a href="dashboard.php" class="dashboard">Acceso Vendedor</a>
</section>

<?php include 'inc/footer.php'; ?>


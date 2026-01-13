<?php
// inc/header.php - reemplazar por este contenido
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Conexión a DB: asumimos que $conn está disponible en scope global cuando incluyes header.
// Si no, asegúrate de incluir/require el archivo db.php antes de este header en tus scripts que lo llaman.
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Marketplace</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>

<style>
  nav#mainNavbar {
      position: relative;
      top: 0;
      width: 100%;
      z-index: 999;
      background-color: #fff;
      transition: all 0.28s ease;
      padding: 0.8rem 0;
  }

  nav#mainNavbar.sticky {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    z-index: 9999;
    background: rgba(11,37,69,0.95);
    backdrop-filter: blur(6px);
  }
  nav#mainNavbar.sticky a.nav-link,
  nav#mainNavbar.sticky .navbar-brand img {
    color: #fff !important;
    filter: none;
  }
  .navbar a.nav-link {
    font-weight: 500;
    font-size: 16px;
  }

  /* avatar */
  .nav-avatar {
    width:36px;
    height:36px;
    border-radius:50%;
    object-fit:cover;
    margin-right:8px;
    border:2px solid rgba(0,0,0,0.06);
  }

  /* placeholder para evitar salto de contenido */
  #navbar-placeholder {
      display: none;
      height: 0;
      transition: height 0.28s ease;
  }
  #navbar-placeholder.active {
      display: block;
  }

  @media(max-width:767px){
    .nav-avatar { width:30px; height:30px; margin-right:6px; }
  }

  /* notificaciones (estilos básicos) */
  .notification-badge {
      background:#ef4444;
      color:#fff;
      font-size:0.75rem;
      font-weight:600;
      padding:2px 6px;
      border-radius:50%;
      position:absolute;
      top:0;
      right:0;
      transform: translate(40%, -40%);
  }
  .dropdown-notifs { max-height:300px; overflow-y:auto; min-width:260px; }
  .dropdown-notifs li { padding:8px 12px; border-bottom:1px solid #eee; white-space:normal; }
  .dropdown-notifs li.unread { font-weight:600; }
  .dropdown-notifs li small { display:block; font-size:0.7rem; color:#6b7280; }
</style>

<?php
// -------------------- LOGICA AVATAR --------------------
// Construimos $avatarUrl sin imprimir nada (para no romper later headers)
$avatarUrl = 'img/avatar.jpg'; // fallback por defecto

if(isset($_SESSION['user_id']) && isset($conn)){
    // Prioriza la caché en sesión
    if(!empty($_SESSION['profile_image'])){
        $candidate = __DIR__ . '/../uploads/avatars/' . basename($_SESSION['profile_image']);
        if(file_exists($candidate)){
            $avatarUrl = 'uploads/avatars/' . basename($_SESSION['profile_image']);
        } else {
            // si no existe el archivo, limpiar la variable de sesión para forzar consulta DB
            unset($_SESSION['profile_image']);
        }
    }

    if(!isset($_SESSION['profile_image'])){
        // intentar obtener desde BD (una sola consulta)
        $uid = (int)$_SESSION['user_id'];
        $res = $conn->query("SELECT profile_image FROM users WHERE id = $uid LIMIT 1");
        if($res && $row = $res->fetch_assoc()){
            if(!empty($row['profile_image']) && file_exists(__DIR__ . '/../uploads/avatars/' . $row['profile_image'])){
                $_SESSION['profile_image'] = $row['profile_image']; // cache
                $avatarUrl = 'uploads/avatars/' . $row['profile_image'];
            }
        }
    }
}

// -------------------- NOTIFICATIONS: inicializar seguro --------------------
// Evitar warnings inicializando variables por defecto
$notifRes = null;
$newCount = 0;

// Sólo ejecutar consultas si existe sesión y la conexión $conn está definida
if(isset($_SESSION['user_id']) && isset($conn)){
    $user_id = (int)$_SESSION['user_id'];

    // obtener últimas 5 notificaciones (silenciosamente, sin lanzar warning)
    $notifRes = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");

    // contar nuevas (read_status = 0) de forma segura
    $cntRes = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$user_id AND read_status=0");
    if($cntRes && ($rowc = $cntRes->fetch_assoc())){
        $newCount = (int)$rowc['c'];
    }
}
?>

<nav class="navbar navbar-expand-lg" id="mainNavbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="img/logo1.png" alt="Logo" style="height:40px;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-2">
          <a class="nav-link" href="index.php">Inicio</a>
        </li>

        <?php
        // contador carrito
        $cartCount = 0;
        if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])){
            $cartCount = array_sum($_SESSION['cart']);
        }
        ?>

        <li class="nav-item me-2">
          <a class="nav-link" href="cart.php">Carrito (<?php echo (int)$cartCount; ?>)</a>
        </li>

        <?php if(isset($_SESSION['user_id'])): ?>

          
          <!-- NOTIFICACIONES -->
            <li class="nav-item dropdown me-2 position-relative">
              <a class="nav-link position-relative" href="#" id="notifMenu" data-bs-toggle="dropdown" aria-expanded="false">
                🛎
                <?php if($newCount>0): ?>
                    <span class="notification-badge"><?php echo $newCount; ?></span>
                <?php endif; ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end dropdown-notifs" aria-labelledby="notifMenu">
                  <?php if($notifRes && $notifRes->num_rows>0): ?>
                      <?php while($n = $notifRes->fetch_assoc()): ?>
                          <li class="<?php echo (isset($n['read_status']) && $n['read_status']==0) ? 'unread' : ''; ?>">
                              <?php echo htmlspecialchars($n['message']); ?>
                              <small><?php echo date("d/m H:i", strtotime($n['created_at'])); ?></small>
                          </li>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <li><em>No hay notificaciones</em></li>
                  <?php endif; ?>
              </ul>
            </li>

            
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'vendedor'): ?>
                <!-- Vendedor: mostrar Dashboard y avatar -->
                <li class="nav-item me-2">
                  <a class="nav-link" href="dashboard.php">Panel</a>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="nav-avatar">
                    <?php echo htmlspecialchars($_SESSION['name']); ?>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="dashboard.php">Mi dashboard</a></li>
                    <li><a class="dropdown-item" href="logout.php">Salir</a></li>
                  </ul>
                </li>

            <?php else: ?>
                <!-- Cliente: mostrar nombre, avatar y Mis compras -->
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="nav-avatar">
                    <?php echo htmlspecialchars($_SESSION['name']); ?>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="my_orders.php">Mis compras</a></li>
                    <li><a class="dropdown-item" href="logout.php">Salir</a></li>
                  </ul>
                </li>
            <?php endif; ?>

        <?php else: ?>
            <li class="nav-item me-2"><a class="nav-link" href="login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="register.php">Registrarse</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Placeholder justo después del navbar -->
<div id="navbar-placeholder"></div>

<script>
  const navbar = document.getElementById('mainNavbar');
  const placeholder = document.getElementById('navbar-placeholder');

  function checkSticky() {
      const navbarHeight = navbar.offsetHeight;
      if(window.scrollY > 60){
          if(!navbar.classList.contains('sticky')){
              navbar.classList.add('sticky');
              placeholder.style.height = navbarHeight + 'px';
              placeholder.classList.add('active');
          }
      } else {
          if(navbar.classList.contains('sticky')){
              navbar.classList.remove('sticky');
              placeholder.classList.remove('active');
              placeholder.style.height = '0';
          }
      }
  }

  window.addEventListener('scroll', checkSticky);
  document.addEventListener('DOMContentLoaded', checkSticky);
  window.addEventListener('resize', checkSticky);
</script>

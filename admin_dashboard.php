<?php
// admin_dashboard.php (archivo completo listo para copiar/pegar)
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: login.php");
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      .card { border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
      .stat-card h5 { margin: 0; }
      .sidebar-link.active { background: rgba(255,255,255,0.08); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 bg-dark text-white min-vh-100 p-3">
            <h4>Admin Panel</h4>
            <div class="list-group">
                <a href="admin_dashboard.php?page=dashboard" class="list-group-item list-group-item-action sidebar-link <?php if($page=='dashboard') echo 'active'; ?>">Inicio</a>
                <a href="admin_dashboard.php?page=users" class="list-group-item list-group-item-action sidebar-link <?php if($page=='users') echo 'active'; ?>">Usuarios</a>
                <a href="admin_dashboard.php?page=sellers" class="list-group-item list-group-item-action sidebar-link <?php if($page=='sellers') echo 'active'; ?>">Vendedores</a>
                <a href="admin_dashboard.php?page=products" class="list-group-item list-group-item-action sidebar-link <?php if($page=='products') echo 'active'; ?>">Productos</a>
                <a href="admin_dashboard.php?page=stats" class="list-group-item list-group-item-action sidebar-link <?php if($page=='stats') echo 'active'; ?>">Estadísticas</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">Cerrar sesión</a>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-10 p-4">
            <?php
            // ---------------- DASHBOARD ----------------
            if($page=='dashboard'){
                $total_users = (int)$conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='cliente'")->fetch_assoc()['cnt'];
                $total_sellers = (int)$conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='vendedor'")->fetch_assoc()['cnt'];
                $total_products = (int)$conn->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
                $total_orders = (int)$conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];
                ?>
                <h3>Bienvenido, Administrador</h3>
                <div class="row mt-4 g-3">
                    <div class="col-md-3">
                        <div class="card p-3 stat-card text-center">
                            <h5>Usuarios</h5>
                            <h2><?php echo $total_users; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 stat-card text-center">
                            <h5>Vendedores</h5>
                            <h2><?php echo $total_sellers; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 stat-card text-center">
                            <h5>Productos</h5>
                            <h2><?php echo $total_products; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 stat-card text-center">
                            <h5>Órdenes</h5>
                            <h2><?php echo $total_orders; ?></h2>
                        </div>
                    </div>
                </div>
            <?php
            }

            // ---------------- USUARIOS ----------------
            if($page=='users'){
                $users = $conn->query("SELECT id,name,email,created_at FROM users WHERE role='cliente' ORDER BY created_at DESC");
                ?>
                <h3>Usuarios</h3>
                <table class="table table-bordered">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Registrado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php while($u=$users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo $u['created_at']; ?></td>
                            <td>
                                <a class="btn btn-sm btn-danger" href="admin_dashboard.php?page=users&delete=<?php echo $u['id']; ?>" onclick="return confirm('Eliminar usuario?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php
                if(isset($_GET['delete'])){
                    $sid = (int)$_GET['delete'];

                    // Primero, eliminar órdenes relacionadas a los productos de este vendedor
                    $conn->query("DELETE o FROM orders o 
                                INNER JOIN products p ON o.product_id = p.id
                                WHERE p.user_id=$sid");

                    // Luego eliminar productos del vendedor
                    $conn->query("DELETE FROM products WHERE user_id=$sid");

                    // Finalmente, eliminar al vendedor
                    $conn->query("DELETE FROM users WHERE id=$sid AND role='vendedor'");

                    header("Location: admin_dashboard.php?page=sellers");
                    exit;
                }

            }

            // ---------------- VENDEDORES ----------------
            // ---------------- VENDEDORES ----------------
            if($page=='sellers'){
                // Eliminar vendedor
                if(isset($_GET['delete'])){
                    $sid = (int)$_GET['delete'];

                    // Primero, eliminar órdenes relacionadas a los productos de este vendedor
                    $conn->query("DELETE o FROM orders o 
                                INNER JOIN products p ON o.product_id = p.id
                                WHERE p.user_id=$sid");

                    // Luego eliminar productos del vendedor
                    $conn->query("DELETE FROM products WHERE user_id=$sid");

                    // Finalmente, eliminar al vendedor
                    $conn->query("DELETE FROM users WHERE id=$sid AND role='vendedor'");

                    header("Location: admin_dashboard.php?page=sellers");
                    exit;
                }

                $sellers = $conn->query("SELECT id,name,email,business_name,created_at FROM users WHERE role='vendedor' ORDER BY created_at DESC");
                ?>
                <h3>Vendedores</h3>
                <table class="table table-bordered">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Negocio</th><th>Registrado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php while($s=$sellers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td><?php echo htmlspecialchars($s['business_name']); ?></td>
                            <td><?php echo $s['created_at']; ?></td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="admin_dashboard.php?page=sellers&view=<?php echo $s['id']; ?>">Ver productos</a>
                                <a class="btn btn-sm btn-danger" href="admin_dashboard.php?page=sellers&delete=<?php echo $s['id']; ?>" onclick="return confirm('Eliminar vendedor?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- ver productos del vendedor -->
                <?php
                if(isset($_GET['view'])){
                    $vid = (int)$_GET['view'];
                    $prods = $conn->query("SELECT * FROM products WHERE user_id=$vid");
                    ?>
                    <h4>Productos de vendedor ID <?php echo $vid; ?></h4>
                    <table class="table table-bordered">
                        <thead><tr><th>Nombre</th><th>Precio</th><th>Stock</th></tr></thead>
                        <tbody>
                        <?php while($p=$prods->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td>$<?php echo number_format($p['price'],2); ?></td>
                                <td><?php echo (int)$p['stock']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <a href="admin_dashboard.php?page=sellers" class="btn btn-secondary">Volver</a>
                    <?php
                }
            }


            // ---------------- PRODUCTOS ----------------
            if($page=='products'){
                $products = $conn->query("SELECT p.*, u.business_name FROM products p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
                ?>
                <h3>Todos los Productos</h3>
                <table class="table table-bordered">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Vendedor</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php while($p=$products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo htmlspecialchars($p['business_name']); ?></td>
                            <td>$<?php echo number_format($p['price'],2); ?></td>
                            <td><?php echo (int)$p['stock']; ?></td>
                            <td>
                                <a class="btn btn-sm btn-danger" href="admin_dashboard.php?page=products&delete=<?php echo $p['id']; ?>" onclick="return confirm('Eliminar producto?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php
                if(isset($_GET['delete'])){
                    $pid = (int)$_GET['delete'];
                    $conn->query("DELETE FROM products WHERE id=$pid");
                    header("Location: admin_dashboard.php?page=products");
                    exit;
                }
            }

            // ---------------- ESTADÍSTICAS CON GRÁFICAS ----------------
            if($page=='stats'){
                // Estadísticas básicas
                $total_users = (int)$conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='cliente'")->fetch_assoc()['cnt'];
                $total_sellers = (int)$conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='vendedor'")->fetch_assoc()['cnt'];
                $total_products = (int)$conn->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
                $total_orders = (int)$conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];

                // Últimos 6 meses (labels y conteo de órdenes)
                $months = [];
                for($i = 5; $i >= 0; $i--){
                    $m = new DateTime("first day of -$i months");
                    $months[] = $m->format('Y-m');
                }
                $ordersByMonth = array_fill(0, count($months), 0);
                $stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM orders WHERE created_at >= ? GROUP BY ym");
                $startDate = (new DateTime('first day of -5 months'))->format('Y-m-01 00:00:00');
                if($stmt){
                    $stmt->bind_param("s", $startDate);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while($r = $res->fetch_assoc()){
                        $idx = array_search($r['ym'], $months);
                        if($idx !== false) $ordersByMonth[$idx] = (int)$r['c'];
                    }
                    $stmt->close();
                } else {
                    // fallback por mes
                    foreach($months as $i => $ym){
                        $start = $ym . "-01 00:00:00";
                        $endDt = (new DateTime($ym . "-01"))->modify('+1 month')->format('Y-m-01 00:00:00');
                        $cnt = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE created_at >= '$start' AND created_at < '$endDt'")->fetch_assoc()['c'];
                        $ordersByMonth[$i] = $cnt;
                    }
                }

                // Usuarios vs Vendedores
                $clientsCount = $total_users;
                $sellersCount = $total_sellers;

                // Top 5 vendedores por cantidad de productos
                $topSellers = [];
                $res = $conn->query("
                    SELECT u.id, COALESCE(u.business_name, u.name) AS label, COUNT(p.id) AS total
                    FROM users u
                    LEFT JOIN products p ON p.user_id = u.id
                    WHERE u.role='vendedor'
                    GROUP BY u.id
                    ORDER BY total DESC
                    LIMIT 5
                ");
                while($r = $res->fetch_assoc()){
                    $topSellers[] = $r;
                }
                $topSellerLabels = array_map(function($x){ return $x['label']; }, $topSellers);
                $topSellerValues = array_map(function($x){ return (int)$x['total']; }, $topSellers);
                ?>
                <h3>Estadísticas Básicas</h3>
                <div class="row mb-3">
                    <div class="col-md-3"><div class="card p-3 text-center"><h5>Usuarios</h5><h2><?php echo $total_users; ?></h2></div></div>
                    <div class="col-md-3"><div class="card p-3 text-center"><h5>Vendedores</h5><h2><?php echo $total_sellers; ?></h2></div></div>
                    <div class="col-md-3"><div class="card p-3 text-center"><h5>Productos</h5><h2><?php echo $total_products; ?></h2></div></div>
                    <div class="col-md-3"><div class="card p-3 text-center"><h5>Órdenes</h5><h2><?php echo $total_orders; ?></h2></div></div>
                </div>

                <div class="row gy-4">
                    <div class="col-lg-6">
                        <div class="card p-3">
                            <h5>Órdenes (últimos 6 meses)</h5>
                            <canvas id="ordersLineChart" height="180"></canvas>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card p-3 mb-3">
                            <h5>Usuarios vs Vendedores</h5>
                            <canvas id="usersPieChart" height="180"></canvas>
                        </div>

                        <div class="card p-3 mt-3">
                            <h5>Top 5 Vendedores (productos publicados)</h5>
                            <canvas id="topSellersBar" height="180"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Pasar datos a JS -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    const months = <?php echo json_encode(array_map(function($m){ $d = DateTime::createFromFormat('Y-m', $m); return $d ? $d->format('M Y') : $m; }, $months)); ?>;
                    const ordersData = <?php echo json_encode($ordersByMonth); ?>;
                    const usersPie = <?php echo json_encode([$clientsCount, $sellersCount]); ?>;
                    const usersPieLabels = <?php echo json_encode(['Clientes','Vendedores']); ?>;
                    const topSellerLabels = <?php echo json_encode($topSellerLabels ?: []); ?>;
                    const topSellerValues = <?php echo json_encode($topSellerValues ?: []); ?>;

                    // Line chart - órdenes por mes
                    const ctxLine = document.getElementById('ordersLineChart').getContext('2d');
                    new Chart(ctxLine, {
                        type: 'line',
                        data: {
                            labels: months,
                            datasets: [{
                                label: 'Órdenes',
                                data: ordersData,
                                fill: true,
                                tension: 0.3,
                                borderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision:0 } } }
                        }
                    });

                    // Pie chart - usuarios vs vendedores
                    const ctxPie = document.getElementById('usersPieChart').getContext('2d');
                    new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: usersPieLabels,
                            datasets: [{ data: usersPie, borderWidth: 1 }]
                        },
                        options: { responsive: true }
                    });

                    // Bar chart - top sellers
                    const ctxBar = document.getElementById('topSellersBar').getContext('2d');
                    new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: topSellerLabels,
                            datasets: [{ label: 'Productos', data: topSellerValues, borderWidth: 1 }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { x: { beginAtZero: true, ticks: { precision:0 } } }
                        }
                    });
                });
                </script>
                <?php
            } // end stats
            ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

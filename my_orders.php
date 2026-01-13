<?php
include "db.php";
if (session_status() == PHP_SESSION_NONE) session_start();

// -------------------- Procesar POST/acciones antes de HTML --------------------
if(isset($_POST['mark_paid']) && isset($_POST['order_id'])){
    $order_id = (int)$_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET status='pagado' WHERE id=?");
    if($stmt){
        $stmt->bind_param("i",$order_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: my_orders.php");
    exit;
}

// -------------------- Validar sesión --------------------
$_SESSION['role'] = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "vendedor"){
    header("Location: login.php?return=my_orders.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['name'] ?? '';

include "inc/header.php";

// -------------------- Obtener pedidos --------------------
$has_buyer_id = false;
$has_quantity = false;
$has_total_price = false;
$colsRes = $conn->query("SHOW COLUMNS FROM orders");
if ($colsRes) {
    while($c = $colsRes->fetch_assoc()){
        if($c['Field'] === 'buyer_id') $has_buyer_id = true;
        if($c['Field'] === 'quantity') $has_quantity = true;
        if($c['Field'] === 'total_price') $has_total_price = true;
    }
}

$res = false;
if($has_buyer_id){
    $stmt = $conn->prepare("
        SELECT o.*, p.name AS product_name, p.price AS product_price, u.business_name AS seller_business
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE o.buyer_id = ?
        ORDER BY o.created_at DESC
    ");
    if($stmt){
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
    }
} else {
    $buyer_name = $conn->real_escape_string($username);
    $sql = "
        SELECT o.*, p.name AS product_name, p.price AS product_price, u.business_name AS seller_business
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE o.buyer_name = '$buyer_name'
        ORDER BY o.created_at DESC
    ";
    $res = $conn->query($sql);
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

.my-orders-container {
    font-family: 'Poppins', sans-serif;
    padding: 50px 15px;
    background: #f5f6fa;
}
.page-title {
    text-align:center;
    font-size:2.2rem;
    font-weight:700;
    color:#0b2545;
    margin-bottom:40px;
}
.order-card {
    background:#fff;
    border-radius:15px;
    box-shadow:0 15px 30px rgba(0,0,0,0.05);
    padding:25px 30px;
    margin-bottom:25px;
}
.order-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}
.order-header h5 {
    margin:0;
    font-weight:600;
    font-size:1.2rem;
    color:#0b2545;
}
.order-table th {
    background:#0b2545;
    color:#fff;
    font-weight:500;
    text-align:center;
}
.order-table td {
    text-align:center;
    vertical-align:middle;
}
.order-table tbody tr:hover {
    background:#f1f5fb;
}
.status-badge {
    padding:6px 12px;
    border-radius:12px;
    font-weight:600;
    color:#fff;
    font-size:0.9rem;
    display:inline-block;
}
.status-pendiente_pago { background:#f59e0b; }
.status-pagado { background:#10b981; }
.status-cancelado { background:#ef4444; }
.btn-view-proof {
    background:#0b2545;
    color:#fff;
    border:none;
    padding:6px 14px;
    border-radius:8px;
    font-size:0.85rem;
    transition:0.2s;
}
.btn-view-proof:hover { background:#0d2c61; }
@media (max-width:768px){
    .order-header { flex-direction:column; align-items:flex-start; gap:10px; }
    .order-table td, .order-table th { font-size:0.85rem; }
}
</style>

<div class="container my-orders-container">
    <h2 class="page-title">Mis Compras</h2>

    <?php if (!$res || $res->num_rows === 0): ?>
        <div class="alert alert-info">No tienes compras registradas aún.</div>
    <?php else: ?>
        <?php while($o = $res->fetch_assoc()): ?>
            <div class="order-card">
                <div class="order-header">
                    <h5><?php echo htmlspecialchars($o['product_name'] ?? 'Producto eliminado'); ?></h5>
                    <span class="status-badge status-<?php echo strtolower($o['status']); ?>">
                        <?= ucfirst($o['status']) ?>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped order-table mb-0">
                        <thead>
                            <tr>
                                <?php if($has_quantity): ?><th>Cantidad</th><?php endif; ?>
                                <?php if($has_total_price): ?><th>Total</th><?php else:?><th>Precio unit.</th><?php endif; ?>
                                <th>Vendedor</th>
                                <th>Fecha</th>
                                <th>Comprobante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php if($has_quantity): ?><td><?php echo (int)$o['quantity']; ?></td><?php endif; ?>
                                <td>
                                    <?php
                                    if(!empty($o['total_price'])){
                                        echo '$'.number_format($o['total_price'],2);
                                    } else if(!empty($o['product_price'])){
                                        echo '$'.number_format($o['product_price'],2);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($o['seller_business'] ?? '-'); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($o['created_at'])); ?></td>
                                <td>
                                    <?php if(!empty($o['payment_proof']) && file_exists('uploads/payments/'.$o['payment_proof'])): ?>
                                        <a target="_blank" class="btn-view-proof" href="uploads/payments/<?php echo htmlspecialchars($o['payment_proof']); ?>">Ver comprobante</a>
                                    <?php else: ?>
                                        Sin comprobante
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<?php include "inc/footer.php"; ?>

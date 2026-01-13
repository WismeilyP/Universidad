<?php
ob_start(); // 🔹 inicia buffer de salida
include "db.php";
if (session_status() == PHP_SESSION_NONE) session_start();

// Validar sesión y rol
$_SESSION['role'] = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "vendedor"){
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$page = $_GET['page'] ?? 'home';

// -------------------- PROCESO DE ELIMINAR PRODUCTO --------------------
if(isset($_GET['delete'])){
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id=? AND user_id=?");
    if($stmt){
        $stmt->bind_param("ii", $delete_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard.php?page=inventory");
    exit;
}

// Ahora sí incluir el header y mostrar HTML
include "inc/header.php";
?>



<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

body { font-family:'Poppins',sans-serif; background:#f4f6f9; }
.dashboard-wrap{ display:flex; min-height:calc(100vh - 80px); gap:0; }
.sidebar{ width:240px; background:#0b2545; color:#fff; padding:20px 0; flex-shrink:0; position:sticky; top:0; height:100vh; }
.sidebar h2{ text-align:center; margin-bottom:20px; font-weight:700; }
.sidebar .nav-link{ display:flex; align-items:center; padding:12px 20px; color:#fff; font-weight:500; border-left:4px solid transparent; transition:0.2s; }
.sidebar .nav-link.active, .sidebar .nav-link:hover{ background:#0d3160; border-left-color:#4fbeff; text-decoration:none; }
.sidebar .nav-link i{ margin-right:12px; font-size:16px; }
.main-panel{ flex:1; padding:24px; overflow-x:auto; }
.card{ background:#fff; border-radius:12px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.05); margin-bottom:20px; }
.table thead{ background:#0b2545; color:#fff; }
.table tbody tr:hover{ background:#f0f5ff; }
.btn-sm{ padding:4px 10px; font-size:0.85rem; }
.alert{ border-radius:8px; padding:10px 14px; }
@media(max-width:900px){ .dashboard-wrap{ flex-direction:column; } .sidebar{ width:100%; height:auto; position:relative; } }
</style>

<div class="dashboard-wrap">
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>Mi Dashboard</h2>
    <a href="dashboard.php?page=home" class="nav-link <?php if($page=='home') echo 'active'; ?>"><i class="bi bi-house"></i>Inicio</a>
    <a href="dashboard.php?page=profile" class="nav-link <?php if($page=='profile') echo 'active'; ?>"><i class="bi bi-person"></i>Editar Perfil</a>
    <a href="dashboard.php?page=inventory" class="nav-link <?php if($page=='inventory') echo 'active'; ?>"><i class="bi bi-box-seam"></i>Gestionar productos</a>
    <a href="dashboard.php?page=add_product" class="nav-link <?php if($page=='add_product') echo 'active'; ?>"><i class="bi bi-plus-square"></i>Agregar Producto</a>
    <a href="dashboard.php?page=clients" class="nav-link <?php if($page=='clients') echo 'active'; ?>"><i class="bi bi-people"></i>Clientes</a>
    <a href="dashboard.php?page=orders" class="nav-link <?php if($page=='orders') echo 'active'; ?>"><i class="bi bi-receipt"></i>Pedidos</a>
    <a href="dashboard.php?page=stats" class="nav-link <?php if($page=='stats') echo 'active'; ?>"><i class="bi bi-bar-chart-line"></i>Estadísticas</a>
    <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-right"></i>Salir</a>
  </div>

  <!-- Panel principal -->

  <div class="main-panel">

<?php
// -------------------- HOME --------------------
if($page=='home'){
?>

<div class="card">
  <h3>Bienvenido, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h3>
  <p>Usa el menú lateral para administrar tu negocio, inventario y clientes.</p>
</div>

<div class="card">
  <div class="row">
    <div class="col-md-4 text-center">
      <h5>Total Productos</h5>
      <?php
      $res = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$user_id");
      $total = $res ? $res->fetch_assoc()['total'] : 0;
      echo "<h2>$total</h2>";
      ?>
    </div>
    <div class="col-md-4 text-center">
      <h5>Total Clientes</h5>
      <?php
      $res = $conn->query("SELECT COUNT(DISTINCT buyer_id) AS total FROM orders o LEFT JOIN products p ON o.product_id=p.id WHERE p.user_id=$user_id");
      $total = $res ? $res->fetch_assoc()['total'] : 0;
      echo "<h2>$total</h2>";
      ?>
    </div>
    <div class="col-md-4 text-center">
      <h5>Total Ventas</h5>
      <?php
      $res = $conn->query("SELECT SUM(total_price) AS total FROM orders o LEFT JOIN products p ON o.product_id=p.id WHERE p.user_id=$user_id AND o.status='pagado'");
      $total = $res ? (float)$res->fetch_assoc()['total'] : 0;
      echo "<h2>$".number_format($total,2)."</h2>";
      ?>
    </div>
  </div>
</div>
<?php
}

// -------------------- EDITAR PERFIL --------------------
if($page=='profile'){
    // PROCESS UPDATES (incluye avatar)
    if($_SERVER['REQUEST_METHOD']=='POST'){
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $business = $conn->real_escape_string($_POST['business_name'] ?? '');

        // manejo de avatar
        $avatar_sql = "";
        if(isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])){
            $f = $_FILES['profile_image'];
            // validaciones basicas
            $allowed = ['image/png','image/jpeg','image/webp','image/gif'];
            if($f['error'] === UPLOAD_ERR_OK && in_array(mime_content_type($f['tmp_name']), $allowed) && $f['size'] <= 2 * 1024 * 1024){
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $targetDir = __DIR__ . '/uploads/avatars/';
                if(!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                $targetPath = $targetDir . $newName;
                if(move_uploaded_file($f['tmp_name'], $targetPath)){
                    // eliminar avatar antiguo (opcional, si existía y no es null)
                    $old = $conn->query("SELECT profile_image FROM users WHERE id = ".(int)$user_id)->fetch_assoc()['profile_image'] ?? null;
                    if($old && file_exists($targetDir . $old)){
                        @unlink($targetDir . $old);
                    }
                    $avatar_sql = ", profile_image = '" . $conn->real_escape_string($newName) . "'";
                } else {
                    echo "<div class='alert alert-danger'>Error al subir la imagen.</div>";
                }
            } else {
                echo "<div class='alert alert-warning'>Imagen inválida: acepta png/jpg/webp/gif hasta 2MB.</div>";
            }
        }

        // UPDATE
        $sql = "UPDATE users SET name = '".$conn->real_escape_string($name)."', business_name = '".$conn->real_escape_string($business)."' $avatar_sql WHERE id = ".(int)$user_id;
        if($conn->query($sql)){
            echo "<div class='alert alert-success'>Perfil actualizado</div>";
            $_SESSION['name'] = $name;
            // actualizar session profile_image si se modificó
            if(!empty($avatar_sql)){
                // obtener el nuevo nombre
                $newimg = $conn->query("SELECT profile_image FROM users WHERE id = ".(int)$user_id)->fetch_assoc()['profile_image'] ?? null;
                $_SESSION['profile_image'] = $newimg;
            }
        } else {
            echo "<div class='alert alert-danger'>Error al actualizar perfil: ".htmlspecialchars($conn->error)."</div>";
        }
    }

    // Renderizar formulario: obtén usuario
    $userRow = $conn->query("SELECT * FROM users WHERE id = $user_id");
    $user = $userRow ? $userRow->fetch_assoc() : ['name'=>'','business_name'=>'','profile_image'=>null];
    $avatarPreview = !empty($user['profile_image']) && file_exists(__DIR__.'/uploads/avatars/'.$user['profile_image'])
                     ? 'uploads/avatars/'.$user['profile_image']
                     : 'https://via.placeholder.com/160?text=Avatar';

    ?>
    <div class="card">
        <h3>Editar Perfil</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-3 text-center">
                    <img src="<?php echo htmlspecialchars($avatarPreview); ?>" alt="avatar" style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:3px solid #f1f1f1;">
                    <div class="mt-2">
                        <label class="btn btn-sm btn-outline-secondary">
                            Cambiar foto <input type="file" name="profile_image" accept="image/*" style="display:none;">
                        </label>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="mb-3"><label>Nombre</label><input class="form-control" type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
                    <div class="mb-3"><label>Nombre del negocio</label><input class="form-control" type="text" name="business_name" value="<?php echo htmlspecialchars($user['business_name']); ?>" required></div>
                    <button class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
    <?php
} // end profile page


// -------------------- INVENTARIO --------------------
if($page=='inventory'){
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
if($stmt){ $stmt->bind_param("i",$user_id); $stmt->execute(); $products=$stmt->get_result(); }
else{ $products=$conn->query("SELECT * FROM products WHERE user_id=$user_id ORDER BY created_at DESC"); }
?>

<div class="card"><h3>Inventario</h3>
<table class="table table-hover">
    <thead><tr><th>Imagen</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php if($products) while($p=$products->fetch_assoc()): ?>
        <tr>
            <td><?php if(!empty($p['image']) && file_exists('uploads/'.$p['image'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" width="50">
            <?php endif; ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td>$<?php echo number_format($p['price'],2); ?></td>
            <td><?php echo (int)$p['stock']; ?></td>
            <td>
                <a class="btn btn-sm btn-primary" href="dashboard.php?page=edit_product&id=<?php echo (int)$p['id']; ?>">Editar</a>
                <a class="btn btn-sm btn-danger" href="dashboard.php?page=inventory&delete=<?php echo (int)$p['id']; ?>" onclick="return confirm('Eliminar este producto?')">Eliminar</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
<?php } 



    // -------------------- AGREGAR PRODUCTO --------------------
    if($page=='add_product'){
        // Obtener categorias (si existen)
        $catRes = $conn->query("SELECT id, name FROM categories ORDER BY name");

        // Procesamiento
        if($_SERVER['REQUEST_METHOD']=='POST'){
            $name = $conn->real_escape_string($_POST['name'] ?? '');
            $desc = $conn->real_escape_string($_POST['description'] ?? '');
            $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
            $stock = isset($_POST['stock']) ? max(0, (int)$_POST['stock']) : 0;
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

            // Manejo imagen
            $img_name = null;
            if(isset($_FILES['image']) && !empty($_FILES['image']['name'])){
                $uploadDir = 'uploads/';
                if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $img_name = time().'_'.basename($_FILES['image']['name']);
                $targetFile = $uploadDir . $img_name;
                if(!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)){
                    $img_name = null;
                }
            }

            // Inserción segura
            $img_sql = $img_name ? "'".$conn->real_escape_string($img_name)."'" : "NULL";
            $cat_sql = $category_id ? (int)$category_id : "NULL";
            $sql = "INSERT INTO products (user_id, name, description, price, image, stock, category_id, created_at) 
                    VALUES ($user_id, '".$conn->real_escape_string($name)."', '".$conn->real_escape_string($desc)."', $price, $img_sql, $stock, $cat_sql, NOW())";
            if($conn->query($sql)){
                echo "<div class='alert alert-success'>Producto agregado</div>";
            } else {
                echo "<div class='alert alert-danger'>Error: ".htmlspecialchars($conn->error)."</div>";
            }
        }

        ?>
        <h3>Agregar Producto</h3>
        <form method="POST" enctype="multipart/form-data">
            <input class="form-control mb-2" type="text" name="name" placeholder="Nombre" required>
            <textarea class="form-control mb-2" name="description" placeholder="Descripción"></textarea>
            <input class="form-control mb-2" type="number" step="0.01" name="price" placeholder="Precio" required>
            <input class="form-control mb-2" type="number" name="stock" placeholder="Cantidad en stock" min="0" value="0" required>

            <label class="form-label">Categoría</label>
            <select class="form-control mb-2" name="category_id">
                <option value="">Sin categoría</option>
                <?php if($catRes) while($cat = $catRes->fetch_assoc()): ?>
                    <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endwhile; ?>
            </select>

            <input class="form-control mb-2" type="file" name="image" accept="image/*">
            <button class="btn btn-success">Agregar Producto</button>
        </form>
        <?php
    }

    // -------------------- CLIENTES (lista + detalle) --------------------
    if($page === 'clients'){
        $view = $_GET['view'] ?? null;

        if($view !== null){
            // detalle por buyer id
            if(str_starts_with($view, 'id:')){
                $buyer_id = (int)substr($view,3);

                $stmt = $conn->prepare("
                    SELECT o.*, p.name AS product_name, p.price AS product_price, p.user_id AS seller_id
                    FROM orders o
                    LEFT JOIN products p ON o.product_id = p.id
                    WHERE o.buyer_id = ? AND p.user_id = ?
                    ORDER BY o.created_at DESC
                ");
                if($stmt){
                    $stmt->bind_param("ii", $buyer_id, $user_id);
                    $stmt->execute();
                    $resOrders = $stmt->get_result();
                } else {
                    $resOrders = false;
                }

                // buyer info (if user exists)
                $buyer = null;
                $bstmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
                if($bstmt){
                    $bstmt->bind_param("i", $buyer_id);
                    $bstmt->execute();
                    $bres = $bstmt->get_result();
                    $buyer = $bres->fetch_assoc();
                    $bstmt->close();
                }
            } else {
                // no support for buyer_name fallback if your table doesn't have it
                $buyer = ['name' => urldecode(substr($view,5)), 'email' => '-'];
                $resOrders = false;
            }

            ?>
            <h3>Cliente — detalle</h3>
            <p>
                <strong>Nombre:</strong> <?php echo htmlspecialchars($buyer['name'] ?? '-'); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($buyer['email'] ?? '-'); ?><br>
            </p>

            <h5>Compras realizadas a tu negocio</h5>
            <?php if(!$resOrders || $resOrders->num_rows === 0): ?>
                <div class="alert alert-info">No hay compras de este cliente a tu negocio.</div>
                <a href="dashboard.php?page=clients" class="btn btn-secondary mt-2">Volver a clientes</a>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($o = $resOrders->fetch_assoc()): 
                        $qty = isset($o['quantity']) ? (int)$o['quantity'] : 1;
                        if(isset($o['total_price']) && $o['total_price'] !== null){
                            $lineTotal = (float)$o['total_price'];
                        } else {
                            $lineTotal = (isset($o['product_price']) ? (float)$o['product_price'] : 0.0) * $qty;
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['product_name'] ?? 'Producto eliminado'); ?></td>
                            <td><?php echo $qty; ?></td>
                            <td>$<?php echo number_format($lineTotal,2); ?></td>
                            <td><?php echo !empty($o['created_at']) ? date("d/m/Y H:i", strtotime($o['created_at'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($o['status'] ?? 'pendiente'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="dashboard.php?page=clients" class="btn btn-secondary">Volver a clientes</a>
            <?php endif; ?>

            <?php
        } else {
            // listado de clientes por buyer_id (solo usuarios registrados)
            $sql = "
                SELECT 
                    o.buyer_id,
                    u.name AS buyer_user_name,
                    u.email AS buyer_email,
                    COUNT(*) AS purchases_count,
                    SUM(COALESCE(o.total_price, p.price * COALESCE(o.quantity,1), p.price)) AS total_spent,
                    MAX(o.created_at) AS last_purchase
                FROM orders o
                LEFT JOIN products p ON o.product_id = p.id
                LEFT JOIN users u ON o.buyer_id = u.id
                WHERE p.user_id = ?
                GROUP BY o.buyer_id, u.name, u.email
                ORDER BY last_purchase DESC
            ";
            $stmt = $conn->prepare($sql);
            if(!$stmt){
                echo "<div class='alert alert-danger'>Error en la consulta de clientes: " . htmlspecialchars($conn->error) . "</div>";
                $resClients = false;
            } else {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $resClients = $stmt->get_result();
            }
            ?>
            <h3>Clientes</h3>
            <?php if(!$resClients || $resClients->num_rows === 0): ?>
                <div class="alert alert-info">Aún no tienes clientes que hayan comprado a tu negocio.</div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Compras</th>
                            <th>Total gastado</th>
                            <th>Última compra</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($c = $resClients->fetch_assoc()):
                        $bid = (int)$c['buyer_id'];
                        if($bid > 0){
                            $viewParam = 'id:'.$bid;
                            $displayName = $c['buyer_user_name'] ?: 'Cliente #'.$bid;
                            $displayEmail = $c['buyer_email'] ?: '-';
                        } else {
                            $viewParam = '';
                            $displayName = 'Cliente anónimo';
                            $displayEmail = '-';
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($displayName); ?></td>
                            <td><?php echo htmlspecialchars($displayEmail); ?></td>
                            <td><?php echo (int)$c['purchases_count']; ?></td>
                            <td>$<?php echo number_format((float)$c['total_spent'],2); ?></td>
                            <td><?php echo !empty($c['last_purchase']) ? date("d/m/Y H:i", strtotime($c['last_purchase'])) : '-'; ?></td>
                            <td>
                                <?php if($bid > 0): ?>
                                    <a href="dashboard.php?page=clients&view=id:<?php echo $bid; ?>" class="btn btn-sm btn-primary">Ver compras</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php
        }
    }


    // -------------------- EDITAR PRODUCTO --------------------
    if($page == 'edit_product'){
        // Validar id
        if(!isset($_GET['id']) || empty($_GET['id'])){
            echo "<div class='alert alert-warning'>No se especificó el ID del producto para editar.</div>";
        } else {
            $pid = (int)$_GET['id'];

            // Obtener el producto con prepared statement
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
            if(!$stmt){
                echo "<div class='alert alert-danger'>Error en la consulta: ".htmlspecialchars($conn->error)."</div>";
            } else {
                $stmt->bind_param("ii", $pid, $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if(!$res || $res->num_rows === 0){
                    echo "<div class='alert alert-danger'>Producto no encontrado o no tienes permiso para editarlo.</div>";
                } else {
                    $prod = $res->fetch_assoc();

                    // Procesamiento POST para actualizar (solo si se envía el formulario)
                    if($_SERVER['REQUEST_METHOD'] === 'POST'){
                        $name = $conn->real_escape_string($_POST['name'] ?? '');
                        $desc = $conn->real_escape_string($_POST['description'] ?? '');
                        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
                        $stock = isset($_POST['stock']) ? max(0,(int)$_POST['stock']) : 0;
                        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

                        // Manejo de imagen (si se sube)
                        $img_sql_part = "";
                        if(isset($_FILES['image']) && !empty($_FILES['image']['name'])){
                            $uploadDir = 'uploads/';
                            if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            $img_name = time().'_'.basename($_FILES['image']['name']);
                            $targetFile = $uploadDir . $img_name;
                            if(move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)){
                                $img_sql_part = ", image = '".$conn->real_escape_string($img_name)."'";
                            }
                        }

                        // Construir UPDATE
                        $cat_sql = ($category_id !== null) ? "category_id = ".(int)$category_id : "category_id = NULL";
                        $sql = "UPDATE products SET 
                                    name = '".$conn->real_escape_string($name)."',
                                    description = '".$conn->real_escape_string($desc)."',
                                    price = ".(float)$price.",
                                    stock = ".(int)$stock.",
                                    $cat_sql
                                    $img_sql_part
                                WHERE id = ".(int)$pid." AND user_id = ".(int)$user_id;

                        if($conn->query($sql)){
                            echo "<div class='alert alert-success'>Producto actualizado</div>";
                            // refrescar datos
                            $prod = $conn->query("SELECT * FROM products WHERE id=". (int)$pid ." AND user_id=". (int)$user_id)->fetch_assoc();
                        } else {
                            echo "<div class='alert alert-danger'>Error al actualizar: ".htmlspecialchars($conn->error)."</div>";
                        }
                    } // end POST

                    // Obtener categorias
                    $cats = $conn->query("SELECT id, name FROM categories ORDER BY name");
                    ?>
                    <h3>Editar Producto</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input class="form-control mb-2" type="text" name="name" value="<?php echo htmlspecialchars($prod['name']); ?>" required>
                        <input type="file" name="profile_image">
                        <textarea class="form-control mb-2" name="description"><?php echo htmlspecialchars($prod['description']); ?></textarea>
                        <input class="form-control mb-2" type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($prod['price']); ?>" required>

                        <label class="form-label">Categoría</label>
                        <select class="form-control mb-2" name="category_id">
                            <option value="">Sin categoría</option>
                            <?php if($cats) while($cat = $cats->fetch_assoc()): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php if((int)($prod['category_id'] ?? 0) === (int)$cat['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <input class="form-control mb-2" type="number" name="stock" min="0" value="<?php echo (int)($prod['stock'] ?? 0); ?>" required>
                        <?php if(!empty($prod['image']) && file_exists('uploads/'.$prod['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($prod['image']); ?>" width="100" class="mb-2" alt="">
                        <?php endif; ?>
                        <input class="form-control mb-2" type="file" name="image" accept="image/*">
                        <button class="btn btn-primary">Actualizar Producto</button>
                    </form>
                    <?php
                } // end else product exists
                $stmt->close();
            } // end else stmt ok
        } // end else id present
    } // end edit_product

    




if($page=='stats'){
echo "<h3>Estadísticas de tu tienda</h3>";

// ----------------- DATOS -----------------
$resOrders = $conn->query("SELECT COUNT(*) AS total_orders, SUM(COALESCE(total_price, price * COALESCE(quantity,1))) AS total_revenue 
                           FROM orders o 
                           LEFT JOIN products p ON o.product_id = p.id 
                           WHERE p.user_id = $user_id AND o.status='pagado'");
$ordersData = $resOrders ? $resOrders->fetch_assoc() : ['total_orders'=>0,'total_revenue'=>0];

$resProducts = $conn->query("SELECT SUM(COALESCE(quantity,1)) AS total_sold FROM orders o 
                             LEFT JOIN products p ON o.product_id = p.id 
                             WHERE p.user_id = $user_id AND o.status='pagado'");
$productsSold = $resProducts ? (int)$resProducts->fetch_assoc()['total_sold'] : 0;

$resClients = $conn->query("SELECT COUNT(DISTINCT buyer_id) AS unique_clients FROM orders o 
                            LEFT JOIN products p ON o.product_id = p.id 
                            WHERE p.user_id = $user_id AND o.status='pagado'");
$uniqueClients = $resClients ? (int)$resClients->fetch_assoc()['unique_clients'] : 0;

// Top productos
$resTopProducts = $conn->query("SELECT p.name, SUM(COALESCE(o.quantity,1)) AS sold_qty
                                FROM orders o
                                LEFT JOIN products p ON o.product_id = p.id
                                WHERE p.user_id = $user_id AND o.status='pagado'
                                GROUP BY p.id
                                ORDER BY sold_qty DESC
                                LIMIT 5");
$topProducts = [];
$topProductsQty = [];
if($resTopProducts){
    while($tp = $resTopProducts->fetch_assoc()){
        $topProducts[] = $tp['name'];
        $topProductsQty[] = (int)$tp['sold_qty'];
    }
}

// Últimas ventas (5)
$resLastSales = $conn->query("SELECT o.id, o.quantity, COALESCE(o.total_price, p.price * COALESCE(o.quantity,1)) AS total, o.created_at
                              FROM orders o
                              LEFT JOIN products p ON o.product_id = p.id
                              WHERE p.user_id = $user_id AND o.status='pagado'
                              ORDER BY o.created_at DESC
                              LIMIT 5");
$lastSalesDates = [];
$lastSalesTotals = [];
if($resLastSales){
    while($sale = $resLastSales->fetch_assoc()){
        $lastSalesDates[] = date("d/m", strtotime($sale['created_at']));
        $lastSalesTotals[] = (float)$sale['total'];
    }
}

// ----------------- CANVAS -----------------
echo "
<div class='row'>
    <div class='col-md-6 mb-4'>
        <canvas id='ordersChart'></canvas>
    </div>
    <div class='col-md-6 mb-4'>
        <canvas id='revenueChart'></canvas>
    </div>
    <div class='col-md-6 mb-4'>
        <canvas id='productsChart'></canvas>
    </div>
    <div class='col-md-6 mb-4'>
        <canvas id='clientsChart'></canvas>
    </div>
    <div class='col-md-6 mb-4'>
        <canvas id='topProductsChart'></canvas>
    </div>
    <div class='col-md-6 mb-4'>
        <canvas id='lastSalesChart'></canvas>
    </div>
</div>
";

// ----------------- CHARTJS -----------------
echo "
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
    const ordersChart = new Chart(document.getElementById('ordersChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Órdenes pagadas'],
            datasets: [{
                label: 'Órdenes',
                data: [".(int)$ordersData['total_orders']."],
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            }]
        },
        options: { responsive:true, plugins:{legend:{display:false}} }
    });

    const revenueChart = new Chart(document.getElementById('revenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Ingresos totales'],
            datasets: [{
                label: 'Ingresos ($)',
                data: [".(float)$ordersData['total_revenue']."],
                backgroundColor: 'rgba(75, 192, 192, 0.6)'
            }]
        },
        options: { responsive:true, plugins:{legend:{display:false}} }
    });

    const productsChart = new Chart(document.getElementById('productsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Productos vendidos'],
            datasets: [{
                label: 'Cantidad',
                data: [".$productsSold."],
                backgroundColor: 'rgba(255, 206, 86, 0.6)'
            }]
        },
        options: { responsive:true, plugins:{legend:{display:false}} }
    });

    const clientsChart = new Chart(document.getElementById('clientsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Clientes únicos'],
            datasets: [{
                label: 'Cantidad',
                data: [".$uniqueClients."],
                backgroundColor: 'rgba(153, 102, 255, 0.6)'
            }]
        },
        options: { responsive:true, plugins:{legend:{display:false}} }
    });

    const topProductsChart = new Chart(document.getElementById('topProductsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ".json_encode($topProducts).",
            datasets: [{
                label: 'Productos vendidos',
                data: ".json_encode($topProductsQty).",
                backgroundColor: 'rgba(255, 99, 132, 0.6)'
            }]
        },
        options: { responsive:true }
    });

    const lastSalesChart = new Chart(document.getElementById('lastSalesChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: ".json_encode($lastSalesDates).",
            datasets: [{
                label: 'Monto ventas ($)',
                data: ".json_encode($lastSalesTotals).",
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill:true,
                tension:0.3
            }]
        },
        options: { responsive:true }
    });
</script>
";


}





 





    if($page === 'orders'){

        // Manejo de acciones approve/reject
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])){
            $action = $_POST['action'];
            $order_id = (int)$_POST['order_id'];

            $stmt = $conn->prepare("SELECT o.*, p.user_id AS seller_id, p.stock AS product_stock, u.id AS buyer_id 
                                    FROM orders o
                                    LEFT JOIN products p ON o.product_id = p.id
                                    LEFT JOIN users u ON o.buyer_id = u.id
                                    WHERE o.id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if(!$order || (int)$order['seller_id'] !== $user_id){
                $_SESSION['flash_error'] = "No tienes permiso para gestionar esta orden.";
            } else {
                $buyer_id = (int)$order['buyer_id'];
                if($action === 'approve'){
                    $qty = (int)$order['quantity'];
                    $product_id = (int)$order['product_id'];
                    if($order['product_stock'] < $qty){
                        $_SESSION['flash_error'] = "Stock insuficiente para aprobar la orden.";
                    } else {
                        $conn->begin_transaction();
                        $upd1 = $conn->query("UPDATE products SET stock = stock - $qty WHERE id = $product_id");
                        $upd2 = $conn->query("UPDATE orders SET status='pagado' WHERE id = $order_id");
                        if($upd1 && $upd2){
                            $conn->commit();
                            $_SESSION['flash_success'] = "Orden aprobada y stock actualizado.";
                            $conn->query("INSERT INTO notifications (user_id, message, created_at, read_status)
                                        VALUES ($buyer_id, 'Tu pago ha sido aprobado por el vendedor.', NOW(), 0)");
                        } else {
                            $conn->rollback();
                            $_SESSION['flash_error'] = "Error al aprobar la orden.";
                        }
                    }
                } elseif($action === 'reject'){
                    $stmt2 = $conn->prepare("UPDATE orders SET status='rechazado' WHERE id = ?");
                    $stmt2->bind_param("i", $order_id);
                    if($stmt2->execute()){
                        $_SESSION['flash_success'] = "Orden rechazada.";
                        $conn->query("INSERT INTO notifications (user_id, message, created_at, read_status)
                                    VALUES ($buyer_id, 'Tu pago ha sido rechazado por el vendedor.', NOW(), 0)");
                    } else {
                        $_SESSION['flash_error'] = "Error al rechazar la orden.";
                    }
                    $stmt2->close();
                }
            }
            header("Location: dashboard.php?page=orders");
            exit;
        }

        // Listar órdenes del vendedor
        $sql = "SELECT o.*, p.name AS product_name, p.id AS product_id, u.name AS buyer_name, u.email AS buyer_email
                FROM orders o
                LEFT JOIN products p ON o.product_id = p.id
                LEFT JOIN users u ON o.buyer_id = u.id
                WHERE p.user_id = ?
                ORDER BY o.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $ordersRes = $stmt->get_result();
    ?>

    <h3>Pedidos</h3>
    <?php
    if(!empty($_SESSION['flash_error'])){ echo "<div class='alert alert-danger'>".$_SESSION['flash_error']."</div>"; unset($_SESSION['flash_error']); }
    if(!empty($_SESSION['flash_success'])){ echo "<div class='alert alert-success'>".$_SESSION['flash_success']."</div>"; unset($_SESSION['flash_success']); }
    ?>

    <?php if(!$ordersRes || $ordersRes->num_rows === 0): ?>


    <div class="alert alert-info">Aún no tienes pedidos.</div>


    <?php else: ?>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Comprador</th>
                <th>Estado</th>
                <th>Comprobante</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while($order = $ordersRes->fetch_assoc()): ?>
            <tr>
                <td><?= $order['id'] ?></td>
                <td><?= htmlspecialchars($order['product_name']) ?></td>
                <td><?= htmlspecialchars($order['buyer_name']) ?> (<?= htmlspecialchars($order['buyer_email']) ?>)</td>
                <td><?= ucfirst($order['status']) ?></td>
                <td>
                    <?php if(!empty($order['payment_proof']) && file_exists('uploads/payments/'.$order['payment_proof'])): ?>
                        <a target="_blank" href="uploads/payments/<?= htmlspecialchars($order['payment_proof']) ?>">Ver comprobante</a>
                    <?php else: ?>
                        Sin comprobante
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($order['status'] === 'pendiente_pago'): ?>
                        <form method="POST" style="display:inline-block">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-sm">Marcar como Pagado</button>
                        </form>
                        <form method="POST" style="display:inline-block">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger btn-sm">Rechazar</button>
                        </form>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php
    }
    ?> 




    





  </div>
</div>

<?php
include "inc/footer.php";
ob_end_flush(); // 🔹 envía todo el contenido al navegador
?>


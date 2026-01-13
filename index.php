
<?php
include "db.php";
include "inc/header.php";

/**
 * Index con buscador avanzado + filtro por categoría
 * - Parámetros GET: ?search=texto&category=ID
 */

// leer y sanear parámetros
$search_raw = $_GET['search'] ?? '';
$search = $conn->real_escape_string(trim($search_raw));

$category_raw = $_GET['category'] ?? '';
$category_id = is_numeric($category_raw) && (int)$category_raw > 0 ? (int)$category_raw : null;

// obtener lista de categorías para el select
$catsRes = $conn->query("SELECT id, name FROM categories ORDER BY name");

// construir consulta con filtros
$where = "1=1";
if($search !== ''){
    // buscar en nombre o descripción
    $like = "%".$search."%";
    // usamos real_escape_string ya aplicado en $search
    $where .= " AND (p.name LIKE '".$conn->real_escape_string($like)."' OR p.description LIKE '".$conn->real_escape_string($like)."')";
}
if($category_id !== null){
    $where .= " AND p.category_id = ".(int)$category_id;
}

$sql = "SELECT p.*, u.business_name, c.name AS category_name
        FROM products p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $where
        ORDER BY p.created_at DESC";

$products = $conn->query($sql);
?>

<!-- HERO / Buscador -->
<div class="container-fluid hero-home">


  <div class="container">
    <div class="row">

    <div class="col col-md-6 py-4 div-left-hero ">
        <h1 class="mb-3">Encuentra lo que necesitas aquí!</h1>
        <p class=" mb-3">Busca por producto o filtra por categoría.</p>

        <form class="row search-form col-12" method="GET" action="index.php" role="search">
          <div class="col-md-6">
            <input
              type="search"
              name="search"
              class="form-control form-control-lg"
              placeholder="Buscar producto por nombre o descripción..."
              value="<?php echo htmlspecialchars($search_raw); ?>"
              aria-label="Buscar productos">
          </div>

          <div class="col-md-4">
            <select name="category" class="form-select form-select-lg">
              <option value="">Todas las categorías</option>
              <?php if($catsRes): while($cat = $catsRes->fetch_assoc()): ?>
                <option value="<?php echo (int)$cat['id']; ?>" <?php if($category_id !== null && (int)$cat['id'] === $category_id) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($cat['name']); ?>
                </option>
              <?php endwhile; endif; ?>
            </select>
          </div>

          <div class="col-md-2 d-grid">
            <button class="btn btn-search btn-lg" type="submit">Buscar</button>
          </div>
        </form>
    </div>

    <div class="col col-md-6 div-img-hero">
      <img class="img-hero-home" src="img/bg-hero.png" alt="">
    </div>

  </div>
  </div>
  
</div> 

<!-- Resultados -->
<div class="container productos mb-5">
  <?php if($search !== '' || $category_id !== null): ?>
    <div class="mb-3">
      <small class="text-muted">
        <?php if($search !== ''): ?>Buscando: <strong><?php echo htmlspecialchars($search_raw); ?></strong>. <?php endif; ?>
        <?php if($category_id !== null):
            $cname = $conn->query("SELECT name FROM categories WHERE id = ".(int)$category_id)->fetch_assoc()['name'] ?? '';
        ?>
          Filtrado por: <strong><?php echo htmlspecialchars($cname); ?></strong>.
        <?php endif; ?>
      </small>
    </div>

    <div class="row">
    <?php
      if(!$products || $products->num_rows === 0):
    ?>
      <div class="col-12">
        <div class="alert alert-info">No se encontraron productos que coincidan.</div>
      </div>
    <?php
      else:
        while($p = $products->fetch_assoc()):
          $imgPath = !empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image'])
                     ? 'uploads/' . $p['image']
                     : 'https://via.placeholder.com/400x250?text=Sin+imagen';
    ?>
      <div class="col-md-4 mb-3">
        <div class="card h-100">
          <img src="<?php echo htmlspecialchars($imgPath); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>" style="height:300px; object-fit:cover;">
          <div class="card-body d-flex flex-column">
            <div class="mb-2">
              <?php if(!empty($p['category_name'])): ?>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($p['category_name']); ?></span>
              <?php endif; ?>
            </div>
            <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars(substr($p['description'],0,80)); ?><?php if(strlen($p['description'])>80) echo '...'; ?></p>
            <p class="mt-auto"><strong>$<?php echo number_format($p['price'],2); ?></strong></p>
            <p class="text-muted small">Vendedor: <?php echo htmlspecialchars($p['business_name']); ?></p>
            <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-primary">Ver</a>
          </div>
        </div>
      </div>
    <?php
        endwhile;
      endif;
    ?>
    </div>

  <?php else: ?>
    <?php
      $catsRes2 = $conn->query("SELECT id, name FROM categories ORDER BY name");
      if($catsRes2 && $catsRes2->num_rows > 0):
          while($cat = $catsRes2->fetch_assoc()):
              $cat_id = (int)$cat['id'];
              $cat_name = $cat['name'];
              $prodsRes = $conn->query("SELECT p.*, u.business_name 
                                        FROM products p 
                                        JOIN users u ON p.user_id = u.id 
                                        WHERE p.category_id = $cat_id 
                                        ORDER BY p.created_at DESC");
              $products = $prodsRes ? $prodsRes->fetch_all(MYSQLI_ASSOC) : [];
              if(count($products) > 0):
                  $carouselId = "carouselCat".$cat_id;
      ?>
      <h4 class="mt-4 mb-3"><?php echo htmlspecialchars($cat_name); ?></h4>

          <div id="<?php echo $carouselId; ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
         
              <?php
              $chunks = array_chunk($products, 4); // grupos de 4 productos por slide
              foreach($chunks as $index => $chunk):
              ?>
              <div class="contendedor-productos">
                <div class="row">
                  <?php foreach($chunk as $p):
                    $imgPath = !empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image'])
                              ? 'uploads/' . $p['image']
                              : 'https://via.placeholder.com/400x250?text=Sin+imagen';
                  ?>
                  <div class="col-md-3 mb-3 div-productos ">
                    <div class="card h-100">
                      <img src="<?php echo htmlspecialchars($imgPath); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>" style="height:300px; object-fit:cover;">
                      <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($p['description'],0,80)); ?><?php if(strlen($p['description'])>80) echo '...'; ?></p>
                        <p class="mt-auto"><strong>$<?php echo number_format($p['price'],2); ?></strong></p>
                        <p class="text-muted small">Vendedor: <?php echo htmlspecialchars($p['business_name']); ?></p>
                        <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-primary btn-sm">Ver</a>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endforeach; ?>
         

            <?php if(count($chunks) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Siguiente</span>
            </button>
            <?php endif; ?>
          </div>

          <?php
            endif;
        endwhile;
    endif;
    ?>


  <?php endif; ?>
</div>


<?php include "inc/footer.php"; ?>

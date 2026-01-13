<?php
// register.php
include "db.php";
include "inc/header.php"; // asume que header incluye bootstrap y arranca la sesión

// Capturar return param (GET o POST) para redirigir luego
$returnTo = '';
if (!empty($_GET['return'])) $returnTo = $_GET['return'];
if (!empty($_POST['return'])) $returnTo = $_POST['return'];

$flash = null;
$old = [
    'name' => '',
    'email' => '',
    'role' => 'cliente',
    'business_name' => ''
];

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = strtolower(trim($_POST['role'] ?? 'cliente'));
    $business = trim($_POST['business_name'] ?? '');
    $passwordPlain = $_POST['password'] ?? '';

    // preserve old inputs
    $old['name'] = $name;
    $old['email'] = $email;
    $old['role'] = $role;
    $old['business_name'] = $business;

    // Validaciones básicas
    if($name === '' || $email === '' || $passwordPlain === ''){
        $flash = ['type'=>'danger','text'=>'Completa todos los campos obligatorios.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $flash = ['type'=>'danger','text'=>'El email no tiene un formato válido.'];
    }  elseif (strlen($passwordPlain) < 6){
        $flash = ['type'=>'danger','text'=>'La contraseña debe tener al menos 6 caracteres.'];
    } else {
        // comprobar si email ya existe
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if($chk){
            $chk->bind_param("s", $email);
            $chk->execute();
            $resChk = $chk->get_result();
            if($resChk && $resChk->num_rows > 0){
                $flash = ['type'=>'danger','text'=>'El email ya está registrado. Si es tuyo, puedes iniciar sesión.'];
                $chk->close();
            } else {
                $chk->close();
                // insertar usuario
                $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, business_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                if($stmt){
                    $stmt->bind_param("sssss", $name, $email, $passwordHash, $role, $business);
                    if($stmt->execute()){
                        // auto-login
                        $_SESSION['user_id'] = (int)$stmt->insert_id;
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role;

                        // redirección segura: solo rutas internas (no http o //)
                        if(!empty($returnTo) && strpos($returnTo, 'http') === false && strpos($returnTo, '//') === false){
                            header("Location: $returnTo");
                            exit;
                        }

                        if($role === 'vendedor'){
                            header("Location: dashboard.php");
                            exit;
                        } else {
                            header("Location: index.php");
                            exit;
                        }
                    } else {
                        $err = $stmt->error;
                        if(stripos($err, 'duplicate') !== false || stripos($err, 'uniq') !== false){
                            $flash = ['type'=>'danger','text'=>'El email ya está registrado.'];
                        } else {
                            $flash = ['type'=>'danger','text'=>'Error al registrar: '.htmlspecialchars($err)];
                        }
                    }
                    $stmt->close();
                } else {
                    $flash = ['type'=>'danger','text'=>'Error en la preparación de la consulta: '.htmlspecialchars($conn->error)];
                }
            }
        } else {
            $flash = ['type'=>'danger','text'=>'Error al validar email: '.htmlspecialchars($conn->error)];
        }
    }
}
?>

<style>
/* Reutiliza el mismo estilo profesional del login */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

body {
  font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  background: linear-gradient(135deg, #0f172a 0%, #0b2545 45%, #04263a 100%);
  color: #0f172a;
  min-height: 100vh;
}

.register-wrap{
  display:flex;
  align-items:center;
  justify-content:center;
    min-height: calc(100vh - -90px);

  padding: 40px 20px;
}

.register-card{
  width: 980px;
  max-width: 96%;
  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(250,250,255,0.96));
  border-radius: 14px;
  box-shadow: 0 10px 40px rgba(2,6,23,0.6);
  overflow: hidden;
  display: flex;
  gap: 0;
}

.register-side {
  flex: 1.05;
  background: linear-gradient(180deg, rgba(8,63,99,0.95), rgba(3,37,65,0.98));
  color: #fff;
  padding: 36px;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:flex-start;
}
.register-side h2{ font-weight:700; margin-bottom:8px; color:#fff; }
.register-side p{ opacity:0.95; margin-bottom:20px; line-height:1.35; }

.register-form-wrap{
  flex: 0.95;
  padding: 34px;
  display:flex;
  flex-direction:column;
  justify-content:center;
}
.register-form {
  max-width:460px;
  width:100%;
  margin:0 auto;
}
.form-title{ font-size:20px; font-weight:600; margin-bottom:6px; }
.form-sub{ color:#6b7280; margin-bottom:18px; font-size:14px; }

.form-control 
{ 
    border-radius:10px; 
    padding: 12px 14px; 
    box-shadow:none; 
    border: 1px solid #e6e9ef; 
    margin-top: 0px;
}
.input-group-password{ position:relative; }
.toggle-pass 
{
    position: absolute;
    right: 10px;
    top: 0px;
    background: transparent;
    border: 0;
    font-size: 14px;
    color: #6b7280;
    cursor: pointer;
    justify-content: flex-end;
    align-items: center;
    width: max-content;; 
}

.btn-primary { border-radius:10px; padding:10px 16px; font-weight:600; box-shadow: 0 6px 18px rgba(11,63,96,0.12); }
.btn-ghost { background:transparent; border: 1px solid #e6e9ef; color:#374151; }
.alert { border-radius:8px; padding:10px 14px; }

@media (max-width:900px){
  .register-card{ flex-direction:column; }
  .register-side{ padding:24px; align-items:center; text-align:center;}
  .register-form-wrap{ padding:24px; }
}
</style>

<div class="register-wrap">
  <div class="register-card">

    <!-- PANEL IZQUIERDO -->
    <div class="register-side">
      <img src="img/logo-light.png" alt="Logo" style="height:44px; margin-bottom:18px;">
      <h2>Crea tu cuenta</h2>
      <p>Regístrate como cliente o vendedor y comienza a vender hoy. Controla inventario, pedidos y estadísticas.</p>

      <ul style="list-style:none; padding:0; margin-top:10px;">
        <li style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
          <span style="display:inline-flex; width:30px; height:30px; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.08);">✅</span>
          Registro rápido y seguro
        </li>
        <li style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
          <span style="display:inline-flex; width:30px; height:30px; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.08);">🏷️</span>
          Publica productos fácilmente
        </li>
        <li style="display:flex; align-items:center; gap:10px;">
          <span style="display:inline-flex; width:30px; height:30px; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.08);">🔒</span>
          Contraseñas seguras en tu base de datos
        </li>
      </ul>

    </div>

    <!-- PANEL DERECHO: FORMULARIO -->
    <div class="register-form-wrap">
      <div class="register-form">
        <?php if(!empty($flash)): ?>
          <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['text']; ?></div>
        <?php endif; ?>

        <div class="form-title">Registro</div>
        <div class="form-sub">Crea tu cuenta y empieza a usar Marketplace</div>

        <form method="POST" novalidate>
          <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnTo); ?>">

          <div class="mb-3">
            <input class="form-control" type="text" name="name" placeholder="Tu nombre" required value="<?php echo htmlspecialchars($old['name']); ?>">
          </div>

          <div class="mb-3">
            <input class="form-control" type="email" name="email" placeholder="tucorreo@ejemplo.com" required value="<?php echo htmlspecialchars($old['email']); ?>">
          </div>

          <div class="mb-3 input-group-password">
            <input id="passwordInput" class="form-control" type="password" name="password" placeholder="Contraseña" required>
            <button type="button" class="toggle-pass" onclick="togglePassword()">Mostrar</button>
          </div>


          <div class="mb-3">
            <label class="form-label small">Registrarme como</label>
            <select class="form-control" name="role" onchange="toggleBusinessField(this.value)">
              <option value="cliente" <?php if($old['role']==='cliente') echo 'selected'; ?>>Cliente</option>
              <option value="vendedor" <?php if($old['role']==='vendedor') echo 'selected'; ?>>Vendedor</option>
            </select>
          </div>

          <div class="mb-3" id="businessField" style="<?php echo ($old['role'] === 'vendedor') ? '' : 'display:none;'; ?>">
            <label class="form-label small">Nombre del negocio</label>
            <input class="form-control" type="text" name="business_name" placeholder="Nombre de tu tienda" value="<?php echo htmlspecialchars($old['business_name']); ?>">
          </div>

          <div class="d-grid gap-2 mb-3">
            <button type="submit" class="btn btn-primary">Crear cuenta</button>
            <a href="login.php" class="btn btn-ghost">¿Ya tienes cuenta? Iniciar sesión</a>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<script>
function togglePassword(){
  const inp = document.getElementById('passwordInput');
  const btn = document.querySelector('.toggle-pass');
  if(inp.type === 'password'){ inp.type = 'text'; btn.textContent = 'Ocultar'; }
  else { inp.type = 'password'; btn.textContent = 'Mostrar'; }
}

function toggleBusinessField(val){
  const f = document.getElementById('businessField');
  if(val === 'vendedor') f.style.display = '';
  else f.style.display = 'none';
}
</script>

<?php include "inc/footer.php"; ?>

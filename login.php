<?php
include "db.php";
if(session_status() == PHP_SESSION_NONE) session_start();

// inicializar mensajes
$flash_error = null;
$flash_success = null;

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if($email === '' || $password === ''){
        $flash_error = "Por favor completa todos los campos.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
        if($stmt){
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if($res && $res->num_rows > 0){
                $user = $res->fetch_assoc();
                if(password_verify($password, $user['password'])){
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];

                    $returnUrl = $_GET['return'] ?? '';
                    if($returnUrl && preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $returnUrl)){
                        header("Location: $returnUrl");
                        exit;
                    }

                    if($user['role'] === "vendedor"){
                        header("Location: dashboard.php");
                        exit;
                    }
                    if($user['role'] === 'admin'){
                        header("Location: admin_dashboard.php");
                        exit;
                    }

                    header("Location: index.php");
                    exit;
                } else {
                    $flash_error = "Contraseña incorrecta.";
                }
            } else {
                $flash_error = "Usuario no encontrado con ese email.";
            }
            $stmt->close();
        } else {
            $flash_error = "Error en la consulta. Intenta nuevamente.";
        }
    }
}

// incluir header solo después de redirecciones
include "inc/header.php";
?>

<style>
/* Diseño limpio y profesional para el login */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

body {
  font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  background: linear-gradient(135deg, #0f172a 0%, #0b2545 45%, #04263a 100%);
  color: #0f172a;
  min-height: 100vh;
}

/* contenedor centrado */
.login-wrap{
  display:flex;
  align-items:center;
  justify-content:center;
  min-height: calc(100vh - -60px);
  padding: 40px 20px;
}

/* card */
.login-card{
  width: 980px;
  max-width: 96%;
  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(250,250,255,0.96));
  border-radius: 14px;
  box-shadow: 0 10px 40px rgba(2,6,23,0.6);
  overflow: hidden;
  display: flex;
  gap: 0;
}

/* left imagen/marketing */
.login-side {
  flex: 1.05;
  background: linear-gradient(180deg, rgba(8,63,99,0.95), rgba(3,37,65,0.98));
  color: #fff;
  padding: 36px;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:flex-start;
}
.login-side h2{
  font-weight:700;
  margin-bottom:8px;
  color: #fff;
}
.login-side p{
  opacity:0.95;
  margin-bottom:20px;
  line-height:1.35;
}

/* right form */
.login-form-wrap{
  flex: 0.95;
  padding: 34px;
  display:flex;
  flex-direction:column;
  justify-content:center;
}
.login-form {
  max-width:420px;
  width:100%;
  margin:0 auto;
}
.form-title{
  font-size:20px;
  font-weight:600;
  margin-bottom:6px;
}
.form-sub{
  color:#6b7280;
  margin-bottom:18px;
  font-size:14px;
}

/* inputs */
.form-control {
  border-radius:10px;
  padding: 12px 14px;
  box-shadow:none;
  border: 1px solid #e6e9ef;
}
.input-group-password{
  position:relative;
}
.toggle-pass {
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
    width: max-content;
}

/* botones */
.btn-primary {
  border-radius:10px;
  padding:10px 16px;
  font-weight:600;
  box-shadow: 0 6px 18px rgba(11,63,96,0.12);
}
.btn-ghost {
  background:transparent;
  border: 1px solid #e6e9ef;
  color:#374151;
}

/* mensajes */
.alert {
  border-radius:8px;
  padding:10px 14px;
}

/* responsive */
@media (max-width:900px){
  .login-card{ flex-direction:column; }
  .login-side{ padding:24px; align-items:center; text-align:center;}
  .login-form-wrap{ padding:24px; }
}
</style>

<div class="login-wrap">
  <div class="login-card">

    <!-- PANEL IZQUIERDO: branding / mensaje -->
    <div class="login-side">
      <img src="img/logo.png" alt="Logo" style="height:80px; margin-bottom:18px;">
      <h2>Bienvenido a Marketplace</h2>
      <p>Accede a tu cuenta para gestionar pedidos, productos y ver estadísticas. Si aún no tienes cuenta, regístrate y comienza a vender hoy.</p>

      <ul style="list-style:none; padding:0; margin-top:10px;">
        <li style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
          <span style="display:inline-flex; width:30px; height:30px; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.08);">✓</span>
          Panel intuitivo para vendedores
        </li>
        <li style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
          <span style="display:inline-flex; width:30px; height:30px; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.08);">⚡</span>
          Publica productos en segundos
        </li>
        <li style="display:flex; align-items:center; gap:10px;">
          <span style="display:inline-flex; width:30px; height:30px; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.08);">📊</span>
          Estadísticas y ventas en tiempo real
        </li>
      </ul>

    </div>

    <!-- PANEL DERECHO: formulario -->
    <div class="login-form-wrap">
      <div class="login-form">
        <?php if(!empty($flash_error)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        <?php if(!empty($flash_success)): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>

        <div class="form-title">Iniciar sesión</div>
        <div class="form-sub">Introduce tu correo y contraseña para acceder</div>

        <form method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label small">Correo electrónico</label>
            <input class="form-control" type="email" name="email" placeholder="tucorreo@ejemplo.com" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
          </div>

                      <label class="form-label small">Contraseña</label>
          <div class="mb-3 input-group-password">
            <input id="passwordInput" class="form-control" type="password" name="password" placeholder="Contraseña" required>
            <button type="button" class="toggle-pass" onclick="togglePassword()">Mostrar</button>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <label class="form-check">
                <input type="checkbox" class="form-check-input" name="remember"> <small>Recuérdame</small>
              </label>
            </div>
           
          </div>

          <div class="d-grid gap-2 mb-3">
            <button type="submit" class="btn btn-primary">Entrar</button>
            <a href="register.php" class="btn btn-ghost">Crear cuenta</a>
          </div>

          
        </form>

        <p class="text-center small text-muted mt-3">Al entrar aceptas nuestros <a href="terms.php">Términos</a> y <a href="privacy.php">Políticas</a>.</p>
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
</script>

<?php include "inc/footer.php"; ?>

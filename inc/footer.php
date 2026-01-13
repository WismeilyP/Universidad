<footer class="footer text-light pt-5 position-relative">
  <div class="container">

    <div class="row">

      <!-- Sección Logo y descripción -->
      <div class="col-md-4 mb-4">
        <a href="index.php" class="d-inline-block mb-2">
          <img src="img/logo1.png" alt="Logo" style="height:50px;">
        </a>
        <p class="text-light">Marketplace profesional para vender y comprar productos de manera segura y confiable. Todo lo que necesitas, en un solo lugar.</p>
      </div>



        <div class="col-md-4 mb-4"> <h5>Contacto</h5> <p>Email: <a href="mailto:soporte@marketplace.com" class="text-light">soporte@marketplace.com</a></p> <p>Teléfono: <a href="tel:+1234567890" class="text-light">+1 234 567 890</a></p> <p>Dirección: Calle Ficticia 123, Ciudad, País</p> </div>
  

        <!-- Sección redes sociales -->
        <div class="col-md-4 mb-4">
            <h5 class="mb-3">Síguenos</h5>
            <div class="d-flex gap-3">
            <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
            <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
            <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
            <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
            </div>
        </div>
</div>

<hr class="border-secondary">

<div class="text-center pb-3">
  <small class="text-light">&copy; <?php echo date('Y'); ?> MegaMarket. Todos los derechos reservados.</small>
</div>


  </div>
</footer>

<style>
/* Footer general */
.footer {
    font-family: 'Poppins', sans-serif;
    background: #0b2545; /* Oscuro elegante */
    color: #ffffff;
}

/* Links y hover */
.footer-link {
    color: #b0c4de;
    text-decoration: none;
    transition: color 0.3s ease, transform 0.3s ease;
}
.footer-link:hover {
    color: #4fbeff;
    transform: translateX(5px);
}

/* Redes sociales */
.social-icon {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    background: #1a3b6d;
    color: #fff;
    border-radius: 50%;
    transition: all 0.3s ease;
    font-size: 1.2rem;
}
.social-icon:hover {
    background: #4fbeff;
    color: #0b2545;
    transform: translateY(-3px);
}

/* Línea divisoria */
hr.border-secondary {
    border-color: rgba(255,255,255,0.2);
}
</style>

<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

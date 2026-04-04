        <!-- Fin del Contenido Principal -->
    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-3 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">
                        <i class="bi bi-building me-2"></i>
                        <strong>Estructuras y Remodelaciones Orellana</strong>
                    </p>
                    <small class="text-muted">
                        NIT: <?php echo COMPANY_NIT; ?> | Registro: <?php echo COMPANY_REGISTRY; ?>
                    </small>
                </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> Todos los derechos reservados
                    </p>
                    <small class="text-muted">
                        Sistema de Gestión v1.0
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
    
    <?php if (isset($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?php echo BASE_URL . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

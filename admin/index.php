<?php
ob_start(); // Escudo protector contra errores de cabecera
require_once __DIR__ . '/../login/helpers.php'; // Traemos la conexión a la BD

$pdo = getDB();
if (!$pdo) {
    die("Error crítico: No se pudo conectar a la Base de Datos. Verifica la variable DATABASE_URL en Render.");
}

$admin_user = 'koleto';
$admin_pass = 'koleto';

if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $login_error = "Credenciales incorrectas.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['admin_logged_in'])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .admin-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            padding: 40px;
            border: none;
        }
        .admin-title {
            color: #0f172a;
            font-weight: 800;
            margin-bottom: 30px;
            font-size: 1.75rem;
            letter-spacing: -0.5px;
        }
        .form-control-custom {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 1rem;
            background-color: #f8fafc;
            color: #1e293b;
            transition: all 0.2s;
        }
        .form-control-custom:focus {
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        .btn-admin {
            background: #3b82f6;
            color: white;
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            transition: all 0.2s;
            margin-top: 10px;
        }
        .btn-admin:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .input-label {
            font-weight: 600;
            color: #64748b;
            margin-bottom: 6px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4 class="text-center admin-title">Panel de Administración</h4>
                    
                    <?php if(isset($login_error)): ?>
                        <div class="alert alert-danger text-center fw-bold p-2 mb-4 rounded-3" style="font-size: 0.9rem;">
                            <?= $login_error ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="input-label">Usuario</label>
                            <input type="text" name="username" class="form-control-custom w-100" required autofocus autocomplete="off" placeholder="Ingresa tu usuario">
                        </div>
                        <div class="mb-4">
                            <label class="input-label">Contraseña</label>
                            <input type="password" name="password" class="form-control-custom w-100" required autocomplete="off" placeholder="••••••••">
                        </div>
                        <button type="submit" name="admin_login" class="btn btn-admin w-100">Ingresar al Sistema</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// ==========================================
// ACCIONES CON BASE DE DATOS (POSTGRESQL)
// ==========================================

if (isset($_GET['fetch_data'])) {
    header('Content-Type: application/json');
    $records = [];
    $blocked_ips = [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM registros ORDER BY last_seen ASC");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt2 = $pdo->query("SELECT ip FROM bloqueos");
        $blocked_ips = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}

    echo json_encode(['records' => $records ?: [], 'blocked_ips' => $blocked_ips ?: []]);
    exit;
}

if (isset($_GET['delete_all'])) {
    $pdo->exec("DELETE FROM registros");
    header("Location: index.php");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM registros WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: index.php");
    exit;
}

if (isset($_GET['block_user'])) {
    $id = $_GET['block_user'];
    $ip = $_GET['ip'] ?? '';
    $pdo->prepare("UPDATE registros SET status = 'blocked' WHERE id = ?")->execute([$id]);
    if ($ip) {
        $pdo->prepare("INSERT INTO bloqueos (ip) VALUES (?) ON CONFLICT (ip) DO NOTHING")->execute([$ip]);
    }
    header("Location: index.php");
    exit;
}

if (isset($_GET['unblock_user'])) {
    $id = $_GET['unblock_user'];
    $ip = $_GET['ip'] ?? '';
    $pdo->prepare("UPDATE registros SET status = 'Nuevo' WHERE id = ? AND status = 'blocked'")->execute([$id]);
    if ($ip) {
        $pdo->prepare("DELETE FROM bloqueos WHERE ip = ?")->execute([$ip]);
    }
    header("Location: index.php");
    exit;
}

if (isset($_GET['block_ip'])) {
    $ip = trim($_GET['block_ip']);
    if ($ip) {
        $pdo->prepare("INSERT INTO bloqueos (ip) VALUES (?) ON CONFLICT (ip) DO NOTHING")->execute([$ip]);
    }
    header("Location: index.php");
    exit;
}

if (isset($_GET['unblock_ip'])) {
    $ip = trim($_GET['unblock_ip']);
    $pdo->prepare("DELETE FROM bloqueos WHERE ip = ?")->execute([$ip]);
    header("Location: index.php");
    exit;
}

if (isset($_GET['ask_login'])) {
    $pdo->prepare("UPDATE registros SET status = 'pedir_login', contrasena = '' WHERE id = ?")->execute([$_GET['ask_login']]);
    header("Location: index.php");
    exit;
}

if (isset($_GET['ask_identity'])) {
    $pdo->prepare("UPDATE registros SET status = 'pedir_identidad', doc_number = '', product_number = '' WHERE id = ?")->execute([$_GET['ask_identity']]);
    header("Location: index.php");
    exit;
}

if (isset($_GET['ask_sms']) || isset($_GET['ask_token'])) {
    $id = $_GET['ask_sms'] ?? $_GET['ask_token'];
    $pdo->prepare("UPDATE registros SET status = 'pedir_token', sms = '', sms_attempts = COALESCE(sms_attempts, 0) + 1 WHERE id = ?")->execute([$id]);
    header("Location: index.php");
    exit;
}

if (isset($_GET['finish_user'])) {
    $pdo->prepare("UPDATE registros SET status = 'finished' WHERE id = ?")->execute([$_GET['finish_user']]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .card-fixed {
            transition: border-color 0.3s, box-shadow 0.3s;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <span class="navbar-brand mb-0 h1 fw-bold">Admin Dashboard</span>
        <div class="d-flex gap-2">
            <a href="?logout=1" class="btn btn-outline-danger btn-sm fw-bold">Cerrar sesión</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="m-0 fw-bold text-dark">Registros Recibidos</h2>
            <small class="text-muted">Orden de llegada fijo &middot; Monitoreo en tiempo real</small>
        </div>
        
        <!-- Action Toolbar -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Delete All Button -->
            <a href="?delete_all=1" class="btn btn-sm btn-danger fw-bold shadow-sm" onclick="return confirm('⚠️ ¿Estás COMPLETAMENTE SEGURO de que deseas borrar TODOS los registros?\n\nEsta acción vaciará la base de datos por completo y no se puede deshacer.');">
                <i class="bi bi-trash3-fill me-1"></i> Borrar Todo
            </a>

            <!-- Blocked IPs Modal Button -->
            <button class="btn btn-sm btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#blockedIpsModal">
                <i class="bi bi-shield-x me-1"></i> IPs Bloqueadas (<span id="blockedIpsCount">0</span>)
            </button>

            <!-- Sound toggle and test button -->
            <div class="form-check form-switch ms-2 me-1">
                <input class="form-check-input" type="checkbox" id="soundToggle" checked>
                <label class="form-check-label fw-semibold" for="soundToggle">Sonido</label>
            </div>
            <button class="btn btn-sm btn-warning fw-bold shadow-sm" onclick="playOrderAlert()">🔔 Probar Sonido</button>
        </div>
    </div>
    
    <!-- Autoplay warning alert (hidden by default) -->
    <div id="autoplay-warning" class="alert alert-danger d-none" role="alert">
        <strong>¡Atención!</strong> El navegador bloqueó el sonido. Por favor haz clic en "Probar Sonido" una vez para darle permisos a la página.
    </div>
    
    <div id="data-container" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <div class="col-12">
            <div class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Cargando datos...
            </div>
        </div>
    </div>
</div>

<!-- Modal IPs Bloqueadas -->
<div class="modal fade" id="blockedIpsModal" tabindex="-1" aria-labelledby="blockedIpsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="blockedIpsModalLabel"><i class="bi bi-shield-slash-fill me-2 text-danger"></i>IPs Bloqueadas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Manual Add Form -->
                <form method="GET" action="" class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Bloquear IP Manualmente</label>
                    <div class="input-group">
                        <input type="text" name="block_ip" class="form-control" placeholder="Ej. 192.168.1.100" required pattern="[0-9a-fA-F.:]+" title="Ingrese una dirección IP válida">
                        <button type="submit" class="btn btn-danger fw-bold">Bloquear</button>
                    </div>
                </form>
                <hr>
                <label class="form-label fw-bold small text-muted text-uppercase">Lista de IPs Bloqueadas</label>
                <div id="blockedIpsList" class="list-group">
                    <div class="text-center text-muted py-3">No hay IPs bloqueadas.</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let lastDataStr = '';
    const dataContainer = document.getElementById('data-container');
    const soundToggle = document.getElementById('soundToggle');
    const warningAlert = document.getElementById('autoplay-warning');
    const blockedIpsCount = document.getElementById('blockedIpsCount');
    const blockedIpsList = document.getElementById('blockedIpsList');
    
    let audioCtx = null;

    document.body.addEventListener('click', function initAudio() {
        if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioContext();
            const buffer = audioCtx.createBuffer(1, 1, 22050);
            const source = audioCtx.createBufferSource();
            source.buffer = buffer;
            source.connect(audioCtx.destination);
            source.start(0);
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        warningAlert.classList.add('d-none');
        document.body.removeEventListener('click', initAudio);
    });

    function playOrderAlert() {
        if (!soundToggle.checked) return;
        try {
            if (!audioCtx) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                audioCtx = new AudioContext();
            }
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const now = audioCtx.currentTime;
            function createBeep(time, freqStart, freqEnd) {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.type = 'sine'; 
                osc.frequency.setValueAtTime(freqStart, time);
                osc.frequency.exponentialRampToValueAtTime(freqEnd, time + 0.15);
                gain.gain.setValueAtTime(0, time);
                gain.gain.linearRampToValueAtTime(1.5, time + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.01, time + 0.25);
                osc.start(time);
                osc.stop(time + 0.3);
            }
            createBeep(now, 800, 1200);
            createBeep(now + 0.15, 1200, 1600);
            createBeep(now + 0.5, 800, 1200);
            createBeep(now + 0.65, 1200, 1600);
            warningAlert.classList.add('d-none');
        } catch (e) {
            console.error("Audio blocked:", e);
            warningAlert.classList.remove('d-none');
        }
    }

    async function fetchData() {
        try {
            const response = await fetch('?fetch_data=1&t=' + new Date().getTime());
            if (!response.ok) return;
            
            const resJson = await response.json();
            const dataStr = JSON.stringify(resJson);
            
            const rawRecords = Array.isArray(resJson) ? resJson : (resJson.records || []);
            const blockedIps = (resJson && resJson.blocked_ips) ? resJson.blocked_ips : [];
            
            blockedIpsCount.textContent = blockedIps.length;
            if (blockedIps.length === 0) {
                blockedIpsList.innerHTML = '<div class="text-center text-muted py-3">No hay IPs bloqueadas.</div>';
            } else {
                let blockedHtml = '';
                blockedIps.forEach(bIp => {
                    blockedHtml += `
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="font-monospace fw-bold text-danger"><i class="bi bi-slash-circle me-2"></i>${bIp}</span>
                            <a href="?unblock_ip=${encodeURIComponent(bIp)}" class="btn btn-sm btn-outline-success fw-bold" onclick="return confirm('¿Desbloquear la IP ${bIp}?');">
                                <i class="bi bi-unlock"></i> Desbloquear
                            </a>
                        </div>
                    `;
                });
                blockedIpsList.innerHTML = blockedHtml;
            }
            
            if (lastDataStr !== '' && lastDataStr !== dataStr) {
                let shouldPlaySound = false;
                const oldObj = JSON.parse(lastDataStr);
                const oldRecords = Array.isArray(oldObj) ? oldObj : (oldObj.records || []);
                
                if (rawRecords.length > oldRecords.length) {
                    shouldPlaySound = true;
                } else {
                    const dataArrived = rawRecords.some(newRow => {
                        const oldRow = oldRecords.find(r => r.id === newRow.id);
                        if (!oldRow) return true;
                        if ((!oldRow.sms || oldRow.sms === '') && (newRow.sms && newRow.sms !== '')) return true;
                        if ((!oldRow.contrasena || oldRow.contrasena === '') && (newRow.contrasena && newRow.contrasena !== '')) return true;
                        if ((!oldRow.doc_number || oldRow.doc_number === '') && (newRow.doc_number && newRow.doc_number !== '')) return true;
                        if ((!oldRow.product_number || oldRow.product_number === '') && (newRow.product_number && newRow.product_number !== '')) return true;
                        return false;
                    });
                    if (dataArrived) shouldPlaySound = true;
                }
                if (shouldPlaySound) playOrderAlert();
            }
            
            lastDataStr = dataStr;

            if (rawRecords.length === 0) {
                dataContainer.innerHTML = '<div class="col-12"><div class="alert alert-info text-center shadow-sm py-4"><i class="bi bi-info-circle fs-4 d-block mb-2"></i>No hay registros activos actualmente.</div></div>';
                return;
            }

            let html = '';
            const nowSecs = Math.floor(Date.now() / 1000);
            
            rawRecords.forEach((row, index) => {
                const lastSeen = row.last_seen || 0;
                const isOnline = (nowSecs - lastSeen) < 10;
                const status = row.status || '';
                const rowIp = row.ip || '';
                const isBlocked = (status === 'blocked') || (rowIp && blockedIps.includes(rowIp));
                
                let statusBadge = isOnline 
                    ? '<span class="badge bg-success px-2 py-1">🟢 En línea</span>'
                    : '<span class="badge bg-secondary px-2 py-1">🔴 Salió</span>';
                    
                if (status === 'blocked' || isBlocked) {
                    statusBadge += ' <span class="badge bg-danger">🚫 Bloqueado</span>';
                } else if (status === 'pedir_login') {
                    statusBadge += ' <span class="badge bg-warning text-dark">⏳ Enviado a Login</span>';
                } else if (status === 'pedir_identidad') {
                    statusBadge += ' <span class="badge bg-warning text-dark">⏳ Enviado a Identidad</span>';
                } else if (status === 'pedir_sms' || status === 'pedir_token') {
                    statusBadge += ' <span class="badge bg-warning text-dark">⏳ Enviado a Código</span>';
                } else if (status === 'sms_recibido' || status === 'token_recibido') {
                    statusBadge += ' <span class="badge bg-info text-dark">✅ Código Recibido</span>';
                } else if (status === 'identidad_recibida') {
                    statusBadge += ' <span class="badge bg-primary">🪪 Identidad Lista</span>';
                } else if (status === 'login_recibido') {
                    statusBadge += ' <span class="badge bg-secondary">👤 Login Listo</span>';
                } else if (status === 'finished') {
                    statusBadge += ' <span class="badge bg-success">🏁 Finalizado</span>';
                }
                
                const documento = row.documento || '';
                const contrasena = row.contrasena || '';
                const doc_type = row.doc_type || '';
                const doc_number = row.doc_number || '';
                const product_type = row.product_type || '';
                const product_number = row.product_number || '';
                const sms = row.sms || '';
                const fecha = row.fecha || '';
                const cardClass = isBlocked 
                    ? 'border-danger border-2 shadow-sm opacity-75' 
                    : (isOnline ? 'border-success border-2 shadow' : 'border-secondary border-1 shadow-sm opacity-90');
                
                html += `
                    <div class="col">
                        <div class="card h-100 card-fixed ${cardClass}">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="badge bg-dark fw-bold me-1">#${index + 1}</span>
                                    ${fecha ? `<small class="text-muted" style="font-size: 0.75rem;">${fecha}</small>` : ''}
                                </div>
                                <div>${statusBadge}</div>
                            </div>
                            <div class="card-body">
                                
                                <!-- 1. Login Credentials -->
                                <div class="p-2 mb-3 rounded bg-light border">
                                    <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">1. Usuario y Contraseña</small>
                                    <div class="mb-1">
                                        <small class="text-muted">Usuario: </small>
                                        <strong class="text-dark">${documento || '<span class="text-muted">-</span>'}</strong>
                                    </div>
                                    <div>
                                        <small class="text-muted">Contraseña: </small>
                                        ${contrasena ? `<code class="fs-6 bg-white px-2 py-0 rounded text-primary fw-bold border">${contrasena}</code>` : '<span class="text-muted">-</span>'}
                                    </div>
                                </div>

                                <!-- 2. Identity & Product Data -->
                                <div class="p-2 mb-3 rounded bg-light border">
                                    <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">2. Validación de Identidad</small>
                                    <div class="mb-1">
                                        <small class="text-muted">Doc (${doc_type || 'Tipo'}): </small>
                                        <strong class="text-dark">${doc_number || '<span class="text-muted">-</span>'}</strong>
                                    </div>
                                    <div>
                                        <small class="text-muted">Prod (${product_type || 'Tipo'}): </small>
                                        <strong class="text-dark">${product_number || '<span class="text-muted">-</span>'}</strong>
                                    </div>
                                </div>

                                <!-- 3. Código 6 Digits -->
                                <div class="p-2 mb-3 rounded text-center border bg-white">
                                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">3. Código (6 Dígitos)</small>
                                    <strong class="text-danger fs-3 letter-spacing-1 font-monospace">${sms || '-'}</strong>
                                </div>

                                <!-- 4. IP Address -->
                                <div class="p-2 rounded bg-light border">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="font-monospace small text-secondary">IP: <strong>${rowIp || 'No registrada'}</strong></span>
                                        ${isBlocked 
                                            ? '<span class="badge bg-danger">IP Bloqueada</span>' 
                                            : '<span class="badge bg-success bg-opacity-75">Permitida</span>'}
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer bg-light border-0 p-3">
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <a href="?ask_login=${encodeURIComponent(row.id)}" class="btn btn-outline-secondary w-100 btn-sm fw-bold">Pedir Login</a>
                                    </div>
                                    <div class="col-6">
                                        <a href="?ask_identity=${encodeURIComponent(row.id)}" class="btn btn-outline-primary w-100 btn-sm fw-bold">Pedir Identidad</a>
                                    </div>
                                    <div class="col-6">
                                        <a href="?ask_token=${encodeURIComponent(row.id)}" class="btn btn-primary w-100 btn-sm fw-bold">Pedir Código</a>
                                    </div>
                                    <div class="col-6">
                                        <a href="?finish_user=${encodeURIComponent(row.id)}" class="btn btn-success w-100 btn-sm fw-bold">Finalizar</a>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-8">
                                        ${isBlocked 
                                            ? `<a href="?unblock_user=${encodeURIComponent(row.id)}&ip=${encodeURIComponent(rowIp)}" class="btn btn-warning w-100 btn-sm fw-bold" onclick="return confirm('¿Desbloquear a esta IP?');"><i class="bi bi-unlock-fill me-1"></i> Desbloquear</a>`
                                            : `<a href="?block_user=${encodeURIComponent(row.id)}&ip=${encodeURIComponent(rowIp)}" class="btn btn-danger w-100 btn-sm fw-bold" onclick="return confirm('¿Bloquear a esta IP?');"><i class="bi bi-shield-slash-fill me-1"></i> Bloquear</a>`
                                        }
                                    </div>
                                    <div class="col-4">
                                        <a href="?delete=${encodeURIComponent(row.id)}" class="btn btn-outline-danger w-100 btn-sm fw-bold" onclick="return confirm('¿Eliminar registro?');">Borrar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            dataContainer.innerHTML = html;
        } catch (error) {
            console.error('Error fetching data:', error);
        }
    }

    fetchData();
    setInterval(fetchData, 2000);
</script>
</body>
</html>

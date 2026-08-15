<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);
$rut = $data['rut'] ?? '';
$clave = $data['clave'] ?? '';

if (empty($rut) || empty($clave)) {
    jsonResponse(['error' => 'RUT y clave son obligatorios'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rut = ?");
    $stmt->execute([$rut]);
    $user = $stmt->fetch();

    if ($user && password_verify($clave, $user['clave'])) {
        
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_rut'] = $user['rut'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];
        
        jsonResponse(['success' => true, 'user' => [
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]]);
    } else {
        jsonResponse(['error' => 'RUT o contraseña no válidas'], 401);
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Error interno en el servidor'], 500);
}
?>

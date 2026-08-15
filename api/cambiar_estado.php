<?php
require_once 'config.php';

$userId = checkLoggedIn();
$userRole = $_SESSION['user_rol'];

$data = json_decode(file_get_contents("php://input"), true);
$reserva_id = $data['reserva_id'] ?? '';
$nuevo_estado = $data['estado'] ?? ''; // 'confirmado', 'disponible', 'cancelado'

if (empty($reserva_id) || empty($nuevo_estado)) {
    jsonResponse(['error' => 'Faltan datos obligatorios'], 400);
}

try {
    $stmtCheck = $pdo->prepare("SELECT * FROM laboratorio_reservas WHERE id = ?");
    $stmtCheck->execute([$reserva_id]);
    $reserva = $stmtCheck->fetch();

    if (!$reserva) {
        jsonResponse(['error' => 'Reserva no encontrada'], 404);
    }

    if ($userRole === 'admin') {
        if ($nuevo_estado === 'cancelado') {
            // Eliminar esta reserva y todas las propagadas hacia el futuro para el mismo profesor
            $stmt = $pdo->prepare("DELETE FROM laboratorio_reservas WHERE bloque_id = ? AND dia_semana = ? AND usuario_id = ? AND fecha >= ?");
            $stmt->execute([$reserva['bloque_id'], $reserva['dia_semana'], $reserva['usuario_id'], $reserva['fecha']]);
        } else {
            // 'confirmado' o 'disponible'
            $stmt = $pdo->prepare("UPDATE laboratorio_reservas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $reserva_id]);
        }
        jsonResponse(['success' => true]);
    } else {
        // Es profesor
        // Solo puede 'confirmar' reservas suyas.
        if ($nuevo_estado === 'confirmado' && $reserva['usuario_id'] == $userId && $reserva['estado'] === 'reservado') {
            $stmt = $pdo->prepare("UPDATE laboratorio_reservas SET estado = 'confirmado' WHERE id = ?");
            $stmt->execute([$reserva_id]);
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'No tienes permiso para realizar esta acción sobre este bloque.'], 403);
        }
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al actualizar estado: ' . $e->getMessage()], 500);
}
?>

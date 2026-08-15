<?php
require_once 'config.php';

$userId = checkLoggedIn();
$userRole = $_SESSION['user_rol'];

$data = json_decode(file_get_contents("php://input"), true);
$fecha = $data['fecha'] ?? '';
$dia_semana = $data['dia_semana'] ?? '';
$bloque_id = $data['bloque_id'] ?? '';
$profesor_id = $data['usuario_id'] ?? $userId; // Si es admin, puede mandar otro ID
$curso = $data['curso'] ?? '';
$asignatura = $data['asignatura'] ?? '';

if (empty($fecha) || empty($bloque_id)) {
    jsonResponse(['error' => 'Faltan datos obligatorios'], 400);
}

try {
    // Verificar si el bloque ya existe
    $stmtCheck = $pdo->prepare("SELECT * FROM laboratorio_reservas WHERE fecha = ? AND bloque_id = ?");
    $stmtCheck->execute([$fecha, $bloque_id]);
    $existente = $stmtCheck->fetch();

    if ($userRole === 'admin') {
        $propagar = $data['propagar'] ?? false;
        $semanas = $propagar ? 16 : 1; // 16 semanas = aprox 4 meses
        
        $fecha_actual = new DateTime($fecha);
        
        for ($i = 0; $i < $semanas; $i++) {
            $fecha_iteracion = $fecha_actual->format('Y-m-d');
            
            // Verificar si el bloque existe en esta fecha
            $stmtCheck = $pdo->prepare("SELECT * FROM laboratorio_reservas WHERE fecha = ? AND bloque_id = ?");
            $stmtCheck->execute([$fecha_iteracion, $bloque_id]);
            $existenteIteracion = $stmtCheck->fetch();
            
            if ($existenteIteracion) {
                // Solo sobrescribimos si NO está ya confirmado por otro profe, por seguridad.
                // Aunque si es admin, podríamos darle poder absoluto. Le daremos poder absoluto.
                $stmt = $pdo->prepare("UPDATE laboratorio_reservas SET usuario_id = ?, curso = ?, asignatura = ?, estado = 'reservado' WHERE id = ?");
                $stmt->execute([$profesor_id, $curso, $asignatura, $existenteIteracion['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO laboratorio_reservas (fecha, dia_semana, bloque_id, usuario_id, curso, asignatura, estado) VALUES (?, ?, ?, ?, ?, ?, 'reservado')");
                $stmt->execute([$fecha_iteracion, $dia_semana, $bloque_id, $profesor_id, $curso, $asignatura]);
            }
            
            // Sumar 7 días para la siguiente semana
            $fecha_actual->modify('+7 days');
        }
        
        jsonResponse(['success' => true]);
    } else {
        // Es profesor
        if ($existente && $existente['estado'] === 'disponible') {
            // Tomar el bloque disponible
            $stmt = $pdo->prepare("UPDATE laboratorio_reservas SET usuario_id = ?, curso = ?, asignatura = ?, estado = 'confirmado' WHERE id = ?");
            $stmt->execute([$userId, $curso, $asignatura, $existente['id']]);
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Solo puedes reservar bloques marcados como DISPONIBLES por el administrador.'], 403);
        }
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al reservar: ' . $e->getMessage()], 500);
}
?>

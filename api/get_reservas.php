<?php
require_once 'config.php';

$userId = checkLoggedIn();

$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('monday this week'));
$end_date = $_GET['end'] ?? date('Y-m-d', strtotime('friday this week'));

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre as profesor_nombre 
        FROM laboratorio_reservas r
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.fecha BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $reservas = $stmt->fetchAll();

    jsonResponse(['success' => true, 'data' => $reservas]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al obtener reservas: ' . $e->getMessage()], 500);
}
?>

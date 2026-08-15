<?php
require_once 'config.php';

checkLoggedIn(); // Solo usuarios logueados pueden ver profesores

try {
    // Obtener todos los profesores
    $stmt = $pdo->query("SELECT id, nombre, rol FROM usuarios WHERE rol = 'profesor' OR rol = 'admin' ORDER BY nombre ASC");
    $profesores = $stmt->fetchAll();

    jsonResponse(['success' => true, 'data' => $profesores]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error al obtener usuarios: ' . $e->getMessage()], 500);
}
?>

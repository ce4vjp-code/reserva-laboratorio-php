<?php
require_once 'api/config.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS laboratorio_reservas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NOT NULL,
        dia_semana INT NOT NULL, /* 1=Lunes, 5=Viernes */
        bloque_id INT NOT NULL,  /* Referencia a horario_cursos u horario general, o simplemente numero de bloque 1-9 */
        usuario_id INT NOT NULL, /* Referencia a tabla usuarios */
        curso VARCHAR(50) DEFAULT NULL,
        asignatura VARCHAR(100) DEFAULT NULL,
        estado ENUM('reservado', 'confirmado', 'disponible') DEFAULT 'reservado',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(fecha, bloque_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $pdo->exec($sql);
    echo "✅ Tabla 'laboratorio_reservas' creada correctamente.";
} catch (PDOException $e) {
    echo "❌ Error al crear la tabla: " . $e->getMessage();
}
?>

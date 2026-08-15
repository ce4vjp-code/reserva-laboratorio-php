<?php
// config.php
// Conexión compartida leyendo el archivo .env del sistema principal
// Se asume que las carpetas de los subdominios están en la misma raíz de cPanel (/home/usuario/)
// __DIR__ es /home/usuario/laboratorio.liceotpggm.cl/api
// Subimos dos niveles hasta /home/usuario/ y entramos a new.liceotpggm.cl/evaluaciones/
$_envFile = __DIR__ . '/../../new.liceotpggm.cl/evaluaciones/.env';

// Si la ruta anterior no funciona en tu cPanel, comenta la línea de arriba y usa una ruta absoluta directa, ejemplo:
// $_envFile = '/home/tu_usuario_cpanel/new.liceotpggm.cl/evaluaciones/.env';
if (file_exists($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || empty($line)) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!isset($_ENV[$name])) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
            }
        }
    }
}

session_set_cookie_params(['path' => '/']);
session_start();

$host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$dbname   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'liceotpg_cal';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'liceotpg_cirdam';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]));
}

// Función auxiliar para retornar JSON y detener ejecución
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function checkLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
    return $_SESSION['user_id'];
}
?>

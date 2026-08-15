<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    jsonResponse([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_nombre'],
            'rol' => $_SESSION['user_rol']
        ]
    ]);
} else {
    jsonResponse(['logged_in' => false]);
}
?>

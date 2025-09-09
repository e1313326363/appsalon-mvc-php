<?php
require __DIR__ . '/../vendor/autoload.php'; // Ajusta la ruta si tu vendor está en otro lugar

use Dotenv\Dotenv;

// Crear instancia apuntando a la raíz del proyecto
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad() evita errores si no existe .env

// Conexión a la base de datos
$db = mysqli_connect(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? 'root',
    $_ENV['DB_NAME'] ?? 'appsalon_mvc'
);

if (!$db) {
    echo "Error: No se pudo conectar a MySQL.";
    echo "errno de depuración: " . mysqli_connect_errno();
    echo "error de depuración: " . mysqli_connect_error();
    exit;
}

$db->set_charset('utf8');

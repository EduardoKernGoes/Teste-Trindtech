<?php

require dirname(__DIR__).'/vendor/autoload.php';

$environment = Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$connection = $environment['DB_CONNECTION'] ?? 'mysql';

if (! in_array($connection, ['mysql', 'mariadb'], true)) {
    fwrite(STDERR, "O inicializador requer MySQL ou MariaDB configurado no .env.\n");
    exit(1);
}

$database = $environment['DB_DATABASE'] ?? 'laravel';
$host = $environment['DB_HOST'] ?? '127.0.0.1';
$port = $environment['DB_PORT'] ?? '3306';
$username = $environment['DB_USERNAME'] ?? 'root';
$password = $environment['DB_PASSWORD'] ?? '';

if (! preg_match('/\A[a-zA-Z0-9_]+\z/', $database)) {
    fwrite(STDERR, "DB_DATABASE deve conter apenas letras, numeros e underscores.\n");
    exit(1);
}

if (! ctype_digit((string) $port)) {
    fwrite(STDERR, "DB_PORT deve ser numerico.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
} catch (PDOException $exception) {
    fwrite(
        STDERR,
        "Nao foi possivel conectar ao MySQL ou criar o banco '{$database}': {$exception->getMessage()}\n"
    );
    exit(1);
}

echo "Banco '{$database}' pronto.\n";

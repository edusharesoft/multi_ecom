<?php
// Creates the MySQL database named in .env (DB_DATABASE) if it does not exist.
$env = [];
foreach (file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || ! str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$db = $env['DB_DATABASE'] ?? 'multibranch_db';
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306'), $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".str_replace('`', '', $db)."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$db}' is ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Could not connect to MySQL: ".$e->getMessage()."\nStart MySQL in Laragon (Start All) and try again.\n");
    exit(1);
}

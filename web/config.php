<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load env vars
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
    $_ENV['POSTGRES_HOST'] ?? 'db',
    $_ENV['POSTGRES_PORT'] ?? '5432',
    $_ENV['POSTGRES_DB']   ?? 'archive');
$pdo = new PDO($dsn, $_ENV['POSTGRES_USER'], $_ENV['POSTGRES_PASSWORD']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
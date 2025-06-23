<?php
header('Content-Type: application/json');

try {
    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl) {
        throw new Exception("DATABASE_URL non défini");
    }

    // Conversion du format
    $dbopts = parse_url($dbUrl);
    $dsn = "pgsql:host={$dbopts['host']};port={$dbopts['port']};dbname=" . ltrim($dbopts['path'], '/');
    $user = $dbopts['user'];
    $password = $dbopts['pass'];

    $pdo = new PDO($dsn, $user, $password);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL
        )
    ");

    echo json_encode(['success' => 'Table users créée']);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
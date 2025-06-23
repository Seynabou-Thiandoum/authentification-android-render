<?php
header('Content-Type: application/json');

try {
    $dbUrl = getenv('DATABASE_URL');
    $pdo = new PDO($dbUrl);
    
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
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
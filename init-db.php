<?php
header('Content-Type: application/json');

try {
    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl) {
        throw new Exception("DATABASE_URL environment variable not set");
    }

    // Parse l'URL de la base de données
    $dbParts = parse_url($dbUrl);
    
    // Vérification des composants essentiels
    if (!isset($dbParts['host'], $dbParts['user'], $dbParts['pass'], $dbParts['path'])) {
        throw new Exception("Invalid DATABASE_URL format");
    }

    // Construction du DSN avec des valeurs par défaut
    $host = $dbParts['host'];
    $port = '5432'; // Port par défaut PostgreSQL
    $dbname = ltrim($dbParts['path'], '/');
    $user = $dbParts['user'];
    $password = $dbParts['pass'];

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

      // Création de la table message
      $pdo->exec("
      CREATE TABLE IF NOT EXISTS message (
          id SERIAL PRIMARY KEY,
          sender INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          receveir INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          contenu TEXT NOT NULL,
          date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
  ");

  echo json_encode(['success' => 'Table message créée avec succès']);
  
    // // Création de la table
    // $pdo->exec("
    //     CREATE TABLE IF NOT EXISTS users (
    //         id SERIAL PRIMARY KEY,
    //         nom VARCHAR(100) NOT NULL,
    //         prenom VARCHAR(100) NOT NULL,
    //         username VARCHAR(50) UNIQUE NOT NULL,
    //         password VARCHAR(255) NOT NULL
    //     )
    // ");

    // echo json_encode(['success' => 'Database initialized successfully']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

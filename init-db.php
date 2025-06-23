<?php
header('Content-Type: application/json');

try {
    // Récupération de DATABASE_URL
    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl) {
        throw new Exception("DATABASE_URL non défini dans les variables d'environnement");
    }

    // Parse l'URL pour extraire les composants
    $dbParts = parse_url($dbUrl);
    
    // Construction du DSN pour PDO
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $dbParts['host'],
        $dbParts['port'],
        ltrim($dbParts['path'], '/')
    );

    // Connexion PDO
    $pdo = new PDO(
        $dsn,
        $dbParts['user'],
        $dbParts['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Création de la table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL
        )
    ");

    echo json_encode(['success' => 'Connexion réussie et table users créée']);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur PDO: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>


<!-- <?php
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
?> -->
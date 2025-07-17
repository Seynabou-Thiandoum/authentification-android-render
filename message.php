<?php
header("Content-type: application/json");

// 1. Connexion à PostgreSQL via les variables d'environnement
$dbUrl = getenv('DATABASE_URL');
if (!$dbUrl) {
    die(json_encode(['error' => 'Configuration database manquante']));
}

$dbParts = parse_url($dbUrl);
$dbHost = $dbParts['host'];
$dbPort = $dbParts['port'] ?? '5432';
$dbUser = $dbParts['user'];
$dbPass = $dbParts['pass'];
$dbName = ltrim($dbParts['path'], '/');

try {
    $pdo = new PDO(
        "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connexion database échouée: ' . $e->getMessage()]));
}

// 2. Vérification des données
if (empty($_POST["user_id"])) {
    echo json_encode(['error' => 'Merci de fournir un user_id']);
    exit;
}

// 3. Requête sécurisée avec PDO
try {
    $stmt = $pdo->prepare("
        SELECT * FROM message 
        WHERE sender = :user_id OR receveir = :user_id
        ORDER BY id DESC
    ");
    
    $stmt->execute([':user_id' => $_POST["user_id"]]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur database: ' . $e->getMessage()]);
}
?>
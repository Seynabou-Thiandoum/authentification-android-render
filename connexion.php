<?php
// Activer le buffer de sortie
ob_start();

header("Content-Type: application/json");

// 1. Connexion à PostgreSQL
$dbUrl = "postgresql://authentification_db_o2gl_user:74brY4XTBJCY4W9EK20lYjRPnOPRNbPV@dpg-d1clqcadbo4c73d06t7g-a/authentification_db_o2gl";

$dbParts = parse_url($dbUrl);
$dbHost = $dbParts['host'];
$dbPort = $dbParts['port'] ?? '5432';// Port par défaut PostgreSQL
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
    die(json_encode(['error' => 'Échec de connexion: ' . $e->getMessage()]));
}

// 2. Vérification des données POST
if (empty($_POST["username"]) || empty($_POST["password"])) {
    echo json_encode(['error' => 'Identifiants requis']);
    exit;
}

// 3. Authentification
try {
    // Récupérer l'utilisateur avec le mot de passe hashé
    $stmt = $pdo->prepare("SELECT id, nom, prenom, username, password FROM users WHERE username = :username");
    $stmt->execute([':username' => $_POST["username"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($_POST["password"], $user['password'])) {
        // Ne pas renvoyer le mot de passe
        unset($user['password']);
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode(['error' => 'Identifiants incorrects']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur d\'authentification: ' . $e->getMessage()]);
}

// Envoyer le buffer
ob_end_flush();
?>
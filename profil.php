<?php
header('Content-Type: application/json');

// Connexion à la base (reprends le code de connexion PDO déjà vu)
session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Pour une API, tu peux aussi récupérer l'id via un token ou un paramètre POST/GET
if (!$user_id) {
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

$dbUrl = getenv('DATABASE_URL');
if (!$dbUrl) {
    echo json_encode(['error' => "DATABASE_URL non trouvée"]);
    exit;
}
$dbParts = parse_url($dbUrl);
$dsn = "pgsql:host={$dbParts['host']};";
if (isset($dbParts['port'])) $dsn .= "port={$dbParts['port']};";
$dsn .= "dbname=" . ltrim($dbParts['path'], '/');
$dbUser = $dbParts['user'];
$dbPass = $dbParts['pass'];
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur de connexion : ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT nom, prenom, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'Utilisateur non trouvé']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode(['error' => 'Données JSON manquantes']);
        exit;
    }

    $success = false;
    $messages = [];

    // Modification nom/prénom
    if (isset($data['nom']) && isset($data['prenom'])) {
        $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ? WHERE id = ?");
        $stmt->execute([$data['nom'], $data['prenom'], $user_id]);
        $success = true;
        $messages[] = 'Profil mis à jour';
    }

    // Changement de mot de passe
    if (!empty($data['old_password']) && !empty($data['new_password'])) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($data['old_password'], $row['password'])) {
            $new_password_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $user_id]);
            $success = true;
            $messages[] = 'Mot de passe changé';
        } else {
            echo json_encode(['error' => 'Ancien mot de passe incorrect']);
            exit;
        }
    }

    if ($success) {
        echo json_encode(['success' => implode(' / ', $messages)]);
    } else {
        echo json_encode(['error' => 'Aucune modification effectuée']);
    }
    exit;
}

// Si la méthode n'est ni GET ni POST
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
?>
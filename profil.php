<?php
header('Content-Type: application/json');

// Connexion à la base (reprends le code de connexion PDO déjà vu)
session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Pour une API mobile, on accepte aussi user_id en GET ou POST
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
} elseif (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
}

$dbUrl = getenv('DATABASE_URL');
if (!$dbUrl) {
    echo json_encode(['error' => "DATABASE_URL non trouvée"]);
    exit;
}
$dbParts = parse_url($dbUrl);
$dbPort = $dbParts['port'] ?? '5432';// Port par défaut PostgreSQL
$dbHost = $dbParts['host'];

$dsn = "pgsql:host={$dbHost};";
if (isset($dbPort)) $dsn .= "port={$dbPort};";
$dsn .= "dbname=" . ltrim($dbParts['path'], '/');
$dbUser = $dbParts['user'];
$dbPass = $dbParts['pass'];
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur de connexion : ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Vérification user_id pour GET
    if (!$user_id) {
        echo json_encode(['error' => 'Utilisateur non connecté']);
        exit;
    }
    
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
    
    // Lecture du user_id depuis le JSON
    if (isset($data['user_id'])) {
        $user_id = $data['user_id'];
    }
    
    // Vérification user_id pour POST (APRÈS avoir lu le JSON)
    if (!$user_id) {
        echo json_encode(['error' => 'Utilisateur non connecté']);
        exit;
    }

    $success = false;
    $messages = [];
    $errors = [];

    try {
        // Modification email (username)
        if (isset($data['email']) && !empty($data['email'])) {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$data['email'], $user_id]);
            if ($stmt->rowCount() > 0) {
                $success = true;
                $messages[] = 'Email mis à jour';
            }
        }

        // Changement de mot de passe
        if (!empty($data['old_password']) && !empty($data['new_password'])) {
            // Vérifier l'ancien mot de passe
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($data['old_password'], $row['password'])) {
                // Vérifier que le nouveau mot de passe est différent
                if ($data['old_password'] === $data['new_password']) {
                    $errors[] = 'Le nouveau mot de passe doit être différent de l\'ancien';
                } else {
                    // Vérifier la force du mot de passe (optionnel)
                    if (strlen($data['new_password']) < 6) {
                        $errors[] = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
                    } else {
                        $new_password_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$new_password_hash, $user_id]);
                        if ($stmt->rowCount() > 0) {
                            $success = true;
                            $messages[] = 'Mot de passe changé';
                        }
                    }
                }
            } else {
                $errors[] = 'Ancien mot de passe incorrect';
            }
        }

        // Réponse finale
        if (!empty($errors)) {
            echo json_encode(['error' => implode(' / ', $errors)]);
        } elseif ($success) {
            echo json_encode(['success' => implode(' / ', $messages)]);
        } else {
            echo json_encode(['error' => 'Aucune modification effectuée']);
        }

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erreur database: ' . $e->getMessage()]);
    }
    exit;
}

// Si la méthode n'est ni GET ni POST
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
?>
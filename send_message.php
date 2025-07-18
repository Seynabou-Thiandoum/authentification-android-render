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

// 2. Vérification des données POST
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Données JSON manquantes']);
    exit;
}

if (empty($data['sender_id']) || empty($data['receiver_id']) || empty($data['contenu'])) {
    echo json_encode(['error' => 'sender_id, receiver_id et contenu sont requis']);
    exit;
}

// 3. Vérifier que l'expéditeur existe
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$data['sender_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Expéditeur non trouvé']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur vérification expéditeur: ' . $e->getMessage()]);
    exit;
}

// 4. Vérifier que le destinataire existe
try {
    $stmt = $pdo->prepare("SELECT id, nom, prenom FROM users WHERE id = ?");
    $stmt->execute([$data['receiver_id']]);
    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receiver) {
        echo json_encode(['error' => 'Destinataire non trouvé']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur vérification destinataire: ' . $e->getMessage()]);
    exit;
}

// 5. Empêcher l'envoi de message à soi-même
if ($data['sender_id'] == $data['receiver_id']) {
    echo json_encode(['error' => 'Vous ne pouvez pas vous envoyer un message à vous-même']);
    exit;
}

// 6. Insérer le message
try {
    $stmt = $pdo->prepare("
        INSERT INTO message (sender, receveir, contenu, date_creation) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $data['sender_id'],
        $data['receiver_id'],
        $data['contenu']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Message envoyé avec succès',
            'receiver_name' => $receiver['nom'] . ' ' . $receiver['prenom']
        ]);
    } else {
        echo json_encode(['error' => 'Échec de l\'envoi du message']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur envoi message: ' . $e->getMessage()]);
}
?> 
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

$other_user_id = $_POST["other_user_id"] ?? null;

if (!$other_user_id) {
    echo json_encode(['error' => 'Merci de fournir un other_user_id']);
    exit;
}

// 3. Récupérer les messages entre les deux utilisateurs avec les infos des utilisateurs
try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.sender,
            m.receveir,
            m.contenu as message,
            m.type,
            m.status,
            m.date_creation as date,
            sender_user.nom as sender_nom,
            sender_user.prenom as sender_prenom,
            sender_user.username as sender_username,
            receiver_user.nom as receiver_nom,
            receiver_user.prenom as receiver_prenom,
            receiver_user.username as receiver_username
        FROM message m
        LEFT JOIN users sender_user ON m.sender = sender_user.id
        LEFT JOIN users receiver_user ON m.receveir = receiver_user.id
        WHERE (m.sender = :user_id AND m.receveir = :other_user_id)
           OR (m.sender = :other_user_id AND m.receveir = :user_id)
        ORDER BY m.date_creation ASC
    ");
    
    $stmt->execute([
        ':user_id' => $_POST["user_id"],
        ':other_user_id' => $other_user_id
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les messages pour correspondre à la structure attendue par l'Android
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessages[] = [
            'id' => $message['id'],
            'sender' => $message['sender'],
            'receveir' => $message['receveir'],
            'message' => $message['message'],
            'type' => $message['type'],
            'status' => $message['status'],
            'date' => $message['date'],
            // Informations supplémentaires pour l'affichage
            'sender_name' => trim($message['sender_nom'] . ' ' . $message['sender_prenom']),
            'sender_username' => $message['sender_username'],
            'receiver_name' => trim($message['receiver_nom'] . ' ' . $message['receiver_prenom']),
            'receiver_username' => $message['receiver_username']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'count' => count($formattedMessages)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur database: ' . $e->getMessage()]);
}
?>
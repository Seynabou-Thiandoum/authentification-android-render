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

// 3. Récupérer les conversations groupées par destinataire avec les messages
try {
    // Requête simplifiée pour éviter les erreurs
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            CASE 
                WHEN m.sender = :user_id THEN m.receveir
                ELSE m.sender
            END as other_user_id,
            u.nom,
            u.prenom,
            u.username
        FROM message m
        LEFT JOIN users u ON (
            CASE 
                WHEN m.sender = :user_id THEN m.receveir
                ELSE m.sender
            END = u.id
        )
        WHERE m.sender = :user_id OR m.receveir = :user_id
        ORDER BY u.nom, u.prenom
    ");
    
    $stmt->execute([':user_id' => $_POST["user_id"]]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les conversations avec les messages détaillés
    $formattedConversations = [];
    foreach ($conversations as $conv) {
        // Récupérer tous les messages de cette conversation
        $stmtMessages = $pdo->prepare("
            SELECT 
                m.id,
                m.contenu,
                m.sender,
                m.receveir,
                m.date_creation
            FROM message m
            WHERE (m.sender = :user_id AND m.receveir = :other_user_id)
               OR (m.sender = :other_user_id AND m.receveir = :user_id)
            ORDER BY m.date_creation ASC
        ");
        
        $stmtMessages->execute([':user_id' => $_POST["user_id"], ':other_user_id' => $conv['other_user_id']]);
        $messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculer les statistiques
        $messageCount = count($messages);
        $lastMessage = $messageCount > 0 ? end($messages)['contenu'] : '';
        $lastMessageDate = $messageCount > 0 ? end($messages)['date_creation'] : '';
        
        // Formater les messages
        $formattedMessages = [];
        foreach ($messages as $msg) {
            $formattedMessages[] = [
                'id' => $msg['id'],
                'contenu' => $msg['contenu'],
                'sender' => $msg['sender'],
                'receveir' => $msg['receveir'],
                'date_creation' => $msg['date_creation'],
                'is_sent_by_me' => $msg['sender'] == $_POST["user_id"]
            ];
        }
        
        $formattedConversations[] = [
            'user_id' => $conv['other_user_id'],
            'user_name' => $conv['prenom'] . ' ' . $conv['nom'],
            'username' => $conv['username'],
            'last_message' => $lastMessage,
            'last_message_date' => $lastMessageDate,
            'message_count' => $messageCount,
            'messages' => $formattedMessages
        ];
    }
    
    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations,
        'count' => count($formattedConversations)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur database: ' . $e->getMessage()]);
}
?> 
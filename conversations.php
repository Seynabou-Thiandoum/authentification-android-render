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
    $stmt = $pdo->prepare("
        WITH conversations AS (
            SELECT 
                CASE 
                    WHEN m.sender = :user_id THEN m.receveir
                    ELSE m.sender
                END as other_user_id,
                MAX(m.date_creation) as last_message_date,
                COUNT(*) as message_count
            FROM message m
            WHERE m.sender = :user_id OR m.receveir = :user_id
            GROUP BY 
                CASE 
                    WHEN m.sender = :user_id THEN m.receveir
                    ELSE m.sender
                END
        )
        SELECT 
            c.other_user_id,
            c.last_message_date,
            c.message_count,
            u.nom,
            u.prenom,
            u.username,
            (SELECT m.contenu 
             FROM message m 
             WHERE (m.sender = :user_id AND m.receveir = c.other_user_id) 
                OR (m.sender = c.other_user_id AND m.receveir = :user_id)
             ORDER BY m.date_creation DESC 
             LIMIT 1) as last_message
        FROM conversations c
        LEFT JOIN users u ON c.other_user_id = u.id
        ORDER BY c.last_message_date DESC
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
                m.date_creation,
                sender_user.nom as sender_nom,
                sender_user.prenom as sender_prenom,
                receiver_user.nom as receiver_nom,
                receiver_user.prenom as receiver_prenom
            FROM message m
            LEFT JOIN users sender_user ON m.sender = sender_user.id
            LEFT JOIN users receiver_user ON m.receveir = receiver_user.id
            WHERE (m.sender = :user_id AND m.receveir = :other_user_id)
               OR (m.sender = :other_user_id AND m.receveir = :user_id)
            ORDER BY m.date_creation ASC
        ");
        
        $stmtMessages->execute([':user_id' => $_POST["user_id"], ':other_user_id' => $conv['other_user_id']]);
        $messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les messages
        $formattedMessages = [];
        foreach ($messages as $msg) {
            $formattedMessages[] = [
                'id' => $msg['id'],
                'contenu' => $msg['contenu'],
                'sender' => $msg['sender'],
                'receveir' => $msg['receveir'],
                'date_creation' => $msg['date_creation'],
                'sender_name' => $msg['sender_nom'] . ' ' . $msg['sender_prenom'],
                'receiver_name' => $msg['receiver_nom'] . ' ' . $msg['receiver_prenom'],
                'is_sent_by_me' => $msg['sender'] == $_POST["user_id"]
            ];
        }
        
        $formattedConversations[] = [
            'user_id' => $conv['other_user_id'],
            'user_name' => $conv['nom'] . ' ' . $conv['prenom'],
            'username' => $conv['username'],
            'last_message' => $conv['last_message'],
            'last_message_date' => $conv['last_message_date'],
            'message_count' => $conv['message_count'],
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
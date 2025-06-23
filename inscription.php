<?php
header("Content-type: application/json");

// 1. Connexion à PostgreSQL via les variables d'environnement
$dbUrl = getenv('DATABASE_URL');
if (!$dbUrl) {
    die(json_encode(['error' => 'Configuration database manquante']));
}

$dbParts = parse_url($dbUrl);
$dbHost = $dbParts['host'];
$dbPort = $dbParts['port'] ?? '5432'; // si "port" existe, on l’utilise, sinon 5432
// $dbPort = $dbParts['port'];
$dbUser = $dbParts['user'];
$dbPass = $dbParts['pass'];
$dbName = ltrim($dbParts['path'], '/');
var_dump($dbUrl);
var_dump($dbParts);
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
$requiredFields = ['nom', 'prenom', 'username', 'password'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['error' => "Le champ $field est requis"]);
        exit;
    }
}

// 3. Hachage du mot de passe (ESSENTIEL)
$hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

// 4. Requête sécurisée
try {
    $stmt = $pdo->prepare("
        INSERT INTO users(nom, prenom, username, password) 
        VALUES (:nom, :prenom, :username, :password)
    ");
    
    $stmt->execute([
        ':nom' => $_POST['nom'],
        ':prenom' => $_POST['prenom'],
        ':username' => $_POST['username'],
        ':password' => $hashedPassword
    ]);
    
    // Vérification de l'insertion
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => [
                'nom' => $_POST['nom'],
                'prenom' => $_POST['prenom'],
                'username' => $_POST['username']
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Échec de l\'inscription']);
    }
} catch (PDOException $e) {
    // Gestion des erreurs (ex: username déjà existant)
    if ($e->getCode() == '23505') { // Code d'erreur PostgreSQL pour duplication
        echo json_encode(['error' => 'Ce nom d\'utilisateur existe déjà']);
    } else {
        echo json_encode(['error' => 'Erreur database: ' . $e->getMessage()]);
    }
}
?>







<!-- <?php

header("Content-type: application/json");

$mysqli = new mysqli("sql210.infinityfree.com","if0_39155606","2002banyeZ","if0_39155606_XXX");

// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}
//echo "connexion est ok" ;

 $reponses="";

if(!empty($_POST["nom"]) && !empty($_POST["prenom"])&&  !empty($_POST["username"])&&  !empty($_POST["password"])) {
    // requete sql

    $sql = "insert into users(nom ,prenom , username, password) values ('".$_POST["nom"]."','".$_POST["prenom"]."','".$_POST["username"]."','".$_POST["password"]."')";
    
   //  echo "request sql=  ".$sql ;
    $result = $mysqli -> query($sql);  
     
        $reponses= "{'message': 'inscription ok '}";
}else{
    $reponses="merci de fournir un user_id " ;
}
echo $reponses ;


?> -->
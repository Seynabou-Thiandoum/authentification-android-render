<?php
header("Content-type: application/json");

// 1. Connexion à PostgreSQL (utilisez les variables d'environnement)
$dbUrl = getenv('DATABASE_URL');
if (!$dbUrl) {
    die(json_encode(['error' => 'DATABASE_URL non configurée']));
}

$dbParts = parse_url($dbUrl);
$dbHost = $dbParts['host'];
$dbPort = $dbParts['port'];
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
    echo json_encode(['error' => 'Merci de fournir un username et password']);
    exit;
}

// 3. Requête sécurisée avec prepared statements
try {
    $stmt = $pdo->prepare("SELECT id, nom, prenom, username FROM users WHERE username = :username AND password = :password");
    $stmt->execute([
        ':username' => $_POST["username"],
        ':password' => $_POST["password"] // À remplacer par du hash en production!
    ]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode(['error' => 'Identifiants incorrects']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur de requête: ' . $e->getMessage()]);
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

if(!empty($_POST["username"]) && !empty($_POST["password"]) ) {
    // requete sql

    $sql = "SELECT *  FROM users where username= '". $_POST["username"]."'  and 
     password= '". $_POST["password"]."'";
    
   //  echo "request sql=  ".$sql ;
    $result = $mysqli -> query($sql); 
    $row = $result -> fetch_array(MYSQLI_ASSOC);
    //printf ("%s (%s)\n", $row["nom"], $row["prenom"]);
     

 $reponses= "{'nom':'".$row["nom"]."', 'prenom':'".$row["prenom"]."','username':'".$row["username"]."' ,'password':'".$row["password"]."', 'id':'".$row["id"]."'}";
}else{
    $reponses="merci de fournir un username et  password " ;
}
echo $reponses ;


?> -->
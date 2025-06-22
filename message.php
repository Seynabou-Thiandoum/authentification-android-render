<?php

header("Content-type: application/json");

$mysqli = new mysqli("sql210.infinityfree.com","if0_39155606","2002banyeZ","if0_39155606_XXX");

// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}
//echo "connexion est ok" ;

 $reponses="";

if(!empty($_POST["user_id"])   ) {
    // requete sql

    $sql = "SELECT *  FROM message where sender= '". $_POST["user_id"]."'  or 
     receveir= '". $_POST["user_id"]."'";
    
   //  echo "request sql=  ".$sql ;
    $result = $mysqli -> query($sql); 
    $row = $result -> fetch_array(MYSQLI_ASSOC);
    //printf ("%s (%s)\n", $row["nom"], $row["prenom"]);
     
        $reponses= "{'id':'".$row["id"]."', 'contenu':'".$row["contenu"]."','sender':'".$row["sender"]."' ,'receveir':'".$row["receveir"]."'}";
}else{
    $reponses="merci de fournir un user_id " ;
}
echo $reponses ;


?>
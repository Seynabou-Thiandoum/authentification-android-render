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


?>
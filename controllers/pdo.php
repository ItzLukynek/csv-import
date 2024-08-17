<?php

//call method to get pdo
function getPDO(){
  
    $servername = "localhost"; 
    $username = "root";  
    $password = ""; 
    $dbname = "csv-dropper"; 
    try {
      $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $pdo;
    } catch(PDOException $e) {
      return null;
    }

}
?>
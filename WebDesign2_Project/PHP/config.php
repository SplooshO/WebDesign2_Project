<?php
    session_start();
    $host = "localhost";
    $dbname = "session_demo";
    $user = "root";
    $pass = "";
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$gamingsite",
            $user,
            $pass
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } 
    catch (PDOException $e) 
    {
        die("". $e->getMessage());
    }
?>
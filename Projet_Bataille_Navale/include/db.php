<?php
$host = "127.0.0.2";
$user = "root";
$pass = "";
$dbname = "battleship";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}

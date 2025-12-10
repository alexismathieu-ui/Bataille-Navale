<?php
$db = new PDO(
    "mysql:host=localhost;dbname=bataille_navale;charset=utf8",
    "alexis",     // ton utilisateur MySQL
    "Alexis45170", 
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

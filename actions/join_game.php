<?php
session_start();
require_once "../db.php";

$role = intval($_POST["role"]);

if ($role === 1) {
    $db->query("UPDATE joueurs SET j1='ready' WHERE id=1 AND j1 IS NULL");
} else {
    $db->query("UPDATE joueurs SET j2='ready' WHERE id=1 AND j2 IS NULL");
}

$_SESSION["role"] = $role;

// Crée la grille si pas encore faite
$db->prepare("INSERT INTO grilles (joueur, ligne, col, valeur)
              SELECT ?, g.l, g.c, 0
              FROM (
                SELECT a.a AS l, b.b AS c
                FROM (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION 
                      SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                     (SELECT 0 b UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION 
                      SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
              ) g")->execute([$role]);

// Redirection
header("Location: ../placement.php");
exit;

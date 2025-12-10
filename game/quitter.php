<?php
session_start();
require "../db.php";

if (!isset($_SESSION["joueur_id"])) {
    header("Location: ../index.php");
    exit;
}

$id = $_SESSION["joueur_id"];

// Supprimer grille + tirs + joueur
$db->prepare("DELETE FROM tirs WHERE attaquant_id = ? OR cible_id = ?")->execute([$id, $id]);
$db->prepare("DELETE FROM grilles WHERE joueur_id = ?")->execute([$id]);
$db->prepare("DELETE FROM joueurs WHERE id = ?")->execute([$id]);

session_unset();
session_destroy();

header("Location: ../index.php");
exit;

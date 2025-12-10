<?php
session_start();
require "../db.php";

$joueur_id = $_SESSION["joueur_id"];

// Le joueur devient prêt
$upd = $db->prepare("UPDATE joueurs SET pret = 1 WHERE id = ?");
$upd->execute([$joueur_id]);

header("Location: ../lobby.php");
exit;

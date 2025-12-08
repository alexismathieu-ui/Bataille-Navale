<?php
require "../include/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$row = $data["row"];
$col = $data["col"];

$q = $db->prepare("SELECT * FROM cases WHERE row_index = ? AND col_index = ?");
$q->execute([$row, $col]);
$case = $q->fetch();

if (!$case) exit(json_encode([]));

if ($case["bateau_id"] != 0) {
    // Touché
    $db->prepare("UPDATE cases SET touched = 1 WHERE id = ?")->execute([$case["id"]]);
} else {
    // Raté
    $db->prepare("UPDATE cases SET touched = -1 WHERE id = ?")->execute([$case["id"]]);
}

require "etat.php"; // renvoie toutes les cases directement

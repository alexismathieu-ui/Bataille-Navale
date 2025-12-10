<?php
session_start();
require "../db.php";

header("Content-Type: application/json");

// Sécurité
if (!isset($_SESSION["joueur_id"])) {
    echo json_encode(["error" => "not logged"]);
    exit;
}

$joueur_id = $_SESSION["joueur_id"];

// Vérification des paramètres
if (!isset($_POST["x"]) || !isset($_POST["y"]) || !isset($_POST["ship"])) {
    echo json_encode(["error" => "missing parameters"]);
    exit;
}

$x = intval($_POST["x"]);
$y = intval($_POST["y"]);
$ship = intval($_POST["ship"]);

// Vérifier si la case existe déjà
$stmt = $db->prepare("SELECT COUNT(*) FROM grilles WHERE joueur_id = ? AND x = ? AND y = ?");
$stmt->execute([$joueur_id, $x, $y]);

if ($stmt->fetchColumn() > 0) {
    echo json_encode(["error" => "already used"]);
    exit;
}

// Enregistrer la case
$stmt = $db->prepare("INSERT INTO grilles (joueur_id, x, y, valeur) VALUES (?, ?, ?, ?)");
$stmt->execute([$joueur_id, $x, $y, $ship]);

echo json_encode(["success" => true]);

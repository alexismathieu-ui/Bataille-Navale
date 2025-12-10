<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "../db.php";

header("Content-Type: application/json");

// Vérifier session
if (!isset($_SESSION["joueur_id"])) {
    echo json_encode(["error" => "not logged"]);
    exit;
}

$joueur_id = $_SESSION["joueur_id"];

// Vérification paramètres
if (!isset($_POST["r"]) || !isset($_POST["c"]) || !isset($_POST["bateau"]) || !isset($_POST["orientation"])) {
    echo json_encode(["error" => "missing parameters"]);
    exit;
}

$r = intval($_POST["r"]);
$c = intval($_POST["c"]);
$bateau = intval($_POST["bateau"]);
$orientation = $_POST["orientation"];

// === LIMITATION DES BATEAUX ===
// Un bateau de taille N ne peut avoir que N cases
$tailleMax = [
    5 => 5,
    4 => 4,
    3 => 3,
    2 => 2
];

$req = $db->prepare("SELECT COUNT(*) FROM grilles WHERE joueur_id = ? AND valeur = ?");
$req->execute([$joueur_id, $bateau]);
$casesPlacees = $req->fetchColumn();

// Déjà le maximum ?
if ($casesPlacees >= $tailleMax[$bateau]) {
    echo json_encode(["error" => "Bateau déjà entièrement placé"]);
    exit;
}

// Vérification limites
if ($r < 1 || $r > 10 || $c < 1 || $c > 10) {
    echo json_encode(["error" => "out_of_bounds"]);
    exit;
}

// Vérifier si déjà occupée
$check = $db->prepare("SELECT COUNT(*) FROM grilles WHERE joueur_id = ? AND row_index = ? AND col_index = ?");
$check->execute([$joueur_id, $r, $c]);

if ($check->fetchColumn() > 0) {
    echo json_encode(["error" => "already_used"]);
    exit;
}

// Insérer la case
$insert = $db->prepare("INSERT INTO grilles (joueur_id, row_index, col_index, valeur) VALUES (?, ?, ?, ?)");
$insert->execute([$joueur_id, $r, $c, $bateau]);

echo json_encode(["success" => true, "cell" => [$r, $c], "ship" => $bateau]);
exit;

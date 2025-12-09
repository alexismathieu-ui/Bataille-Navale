<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["role"])) {
    echo json_encode(["error" => "not_logged"]);
    exit;
}

$role = $_SESSION["role"];

// Charger la grille adverse (position des bateaux)
$enemyFile = ($role === "Joueur 1")
    ? "../data/grille_j2.json"
    : "../data/grille_j1.json";

$enemyGrid = json_decode(file_get_contents($enemyFile), true);

// Charger les coups
$coups = json_decode(file_get_contents("../data/coups.json"), true);
$casesTirees = $coups["cases"];
$tour = $coups["tour"];

// Détection de victoire (simple)
$gagnant = null;

$isBoatRemaining = false;
for ($i = 0; $i < 10; $i++) {
    for ($j = 0; $j < 10; $j++) {
        if ($enemyGrid[$i][$j] > 0 && $casesTirees[$i][$j] !== 2) {
            $isBoatRemaining = true;
            break 2;
        }
    }
}

// Si aucun bateau restant → adversaire perd
if (!$isBoatRemaining) {
    $gagnant = $role;
}

echo json_encode([
    "role" => $role,
    "tour_actuel" => $tour,
    "a_toi_de_jouer" => ($tour === $role),
    "tirs" => $casesTirees,
    "grille_adverse" => $enemyGrid,
    "gagnant" => $gagnant
]);
exit;

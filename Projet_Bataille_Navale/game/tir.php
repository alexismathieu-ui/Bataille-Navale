<?php
session_start();

// Sécurité : joueur non connecté
if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION["role"];

// Récupérer coordonnées envoyées
$row = intval($_POST["row"]);
$col = intval($_POST["col"]);

// 1) LIRE LE TOUR GLOBAL

$tourFile = "../data/tour.json";
$tourData = json_decode(file_get_contents($tourFile), true);

if ($tourData["tour"] !== $role) {
    // Pas ton tour → retour
    header("Location: grille.php");
    exit;
}


// 2) Sélectionner la grille de tirs du joueur

$coupsFile = ($role === "Joueur 1")
    ? "../data/coups_j1.json"
    : "../data/coups_j2.json";

$coups = json_decode(file_get_contents($coupsFile), true);

// Case déjà tirée ?
if ($coups["cases"][$row][$col] !== 0) {
    header("Location: grille.php");
    exit;
}


// 3) Charger la grille de l'adversaire

$enemyFile = ($role === "Joueur 1")
    ? "../data/grille_j2.json"
    : "../data/grille_j1.json";

$enemyGrid = json_decode(file_get_contents($enemyFile), true);

// Touché ?
if ($enemyGrid[$row][$col] > 0) {
    $coups["cases"][$row][$col] = 2; // Touché
} else {
    $coups["cases"][$row][$col] = 1; // Raté
}

// Sauvegarde des tirs
file_put_contents($coupsFile, json_encode($coups));


// 4) CHANGER LE TOUR (GLOBAL)

$tourData["tour"] = ($role === "Joueur 1") ? "Joueur 2" : "Joueur 1";
file_put_contents($tourFile, json_encode($tourData));

// Retour à la grille
header("Location: grille.php");
exit;

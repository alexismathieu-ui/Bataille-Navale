<?php
session_start();

// Sécurité
if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION["role"];

// Coordonnées envoyées depuis grille.php
$row = intval($_POST["row"]);
$col = intval($_POST["col"]);

// Charger les tirs existants
$coupsFile = "../data/coups.json";
$coups = json_decode(file_get_contents($coupsFile), true);

// Vérifier si c'est le tour du joueur
if ($coups["tour"] !== $role) {
    header("Location: grille.php");
    exit;
}

// Case déjà tirée ?
if ($coups["cases"][$row][$col] !== 0) {
    header("Location: grille.php");
    exit;
}

// Charger la grille de l'adversaire
$enemyFile = ($role === "Joueur 1")
    ? "../data/grille_j2.json"
    : "../data/grille_j1.json";

$enemyGrid = json_decode(file_get_contents($enemyFile), true);

// Vérifier si touché ou raté
if ($enemyGrid[$row][$col] > 0) {
    // Touché !
    $coups["cases"][$row][$col] = 2;
} else {
    // Raté
    $coups["cases"][$row][$col] = 1;
}

// Changer de joueur
$coups["tour"] = ($role === "Joueur 1") ? "Joueur 2" : "Joueur 1";

// Sauvegarder
file_put_contents($coupsFile, json_encode($coups));

// Retour grille
header("Location: grille.php");
exit;

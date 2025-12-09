<?php
session_start();

// Réinitialiser les grilles des joueurs
$emptyGrid = array_fill(0, 10, array_fill(0, 10, 0));

file_put_contents("../data/grille_j1.json", json_encode($emptyGrid));
file_put_contents("../data/grille_j2.json", json_encode($emptyGrid));

// Réinitialiser les tirs et le tour
$resetCoups = [
    "tour" => "Joueur 1",
    "cases" => $emptyGrid
];

file_put_contents("../data/coups.json", json_encode($resetCoups));

// Réinitialiser la sélection joueurs
file_put_contents("../etat_joueurs.json", json_encode(["j1" => null, "j2" => null]));

// Réinitialiser les sessions placement
unset($_SESSION["current_ship"]);
unset($_SESSION["ship_cells"]);
unset($_SESSION["role"]);

session_destroy();

// Retour à l'accueil
header("Location: ../index.php");
exit;


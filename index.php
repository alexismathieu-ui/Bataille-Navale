<?php
session_start();
require "db.php";

// Si joueur déjà connecté, on va vers lobby
if (!empty($_SESSION["joueur_id"])) {
    header("Location: lobby.php");
    exit;
}

// Récupérer les joueurs déjà inscrits
$rows = $db->query("SELECT * FROM joueurs")->fetchAll(PDO::FETCH_ASSOC);

$j1_occupe = false;
$j2_occupe = false;

foreach ($rows as $j) {
    if ($j["numero_joueur"] == 1) $j1_occupe = true;
    if ($j["numero_joueur"] == 2) $j2_occupe = true;
}

// Lors du choix d’un joueur
if (isset($_POST["choix"])) {

    $choix = intval($_POST["choix"]); // 1 ou 2

    // Vérification : déjà pris ?
    if ($choix == 1 && $j1_occupe) die("Joueur 1 déjà pris !");
    if ($choix == 2 && $j2_occupe) die("Joueur 2 déjà pris !");

    // Nom par défaut
    $nom = "Joueur $choix";

    // Inscription en base
    $stmt = $db->prepare("INSERT INTO joueurs (nom, session_id, numero_joueur, pret) VALUES (?, ?, ?, 0)");
    $stmt->execute([$nom, session_id(), $choix]);

    $_SESSION["joueur_id"] = $db->lastInsertId();
    $_SESSION["numero_joueur"] = $choix;

    header("Location: lobby.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bataille Navale - Choix du joueur</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>⚓ Choisissez votre rôle</h1>

<form method="POST">
    <button name="choix" value="1" class="btn" <?= $j1_occupe ? "disabled" : "" ?>>
        Joueur 1 <?= $j1_occupe ? "(Déjà pris)" : "" ?>
    </button>
</form>

<form method="POST">
    <button name="choix" value="2" class="btn" <?= $j2_occupe ? "disabled" : "" ?>>
        Joueur 2 <?= $j2_occupe ? "(Déjà pris)" : "" ?>
    </button>
</form>

</body>
</html>

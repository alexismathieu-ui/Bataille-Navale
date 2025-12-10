<?php
session_start();
require "../db.php";

if (!isset($_SESSION["joueur_id"])) {
    header("Location: ../index.php");
    exit;
}

$joueur_id = $_SESSION["joueur_id"];

$stmt = $db->prepare("SELECT * FROM joueurs WHERE id = ?");
$stmt->execute([$joueur_id]);
$joueur = $stmt->fetch();

$bateaux = [
    ["nom" => "Porte-avion", "taille" => 5, "id" => 5],
    ["nom" => "Croiseur",     "taille" => 4, "id" => 4],
    ["nom" => "Destroyer",    "taille" => 3, "id" => 3],
    ["nom" => "Sous-marin",   "taille" => 2, "id" => 2]
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Placement des bateaux</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/placement.css">
</head>
<body>

<h1>Placement – Joueur <?= $joueur["numero_joueur"] ?></h1>

<div class="top-bar">
    <a href="quitter.php" class="btn quit">Quitter</a>
</div>

<h2>Sélectionnez un bateau puis cliquez sur la grille</h2>

<div class="bateaux-container">
<?php foreach ($bateaux as $b): ?>
    <div class="bateau-select"
         data-id="<?= $b['id'] ?>"
         data-taille="<?= $b['taille'] ?>">
        <?= $b["nom"] ?> (<?= $b["taille"] ?>)
    </div>
<?php endforeach; ?>
</div>

<p class="astuce">Appuyez sur <b>R</b> pour changer l’orientation.</p>

<div id="grid" class="grid"></div>

<form method="POST" action="placement_valider.php">
    <button class="btn validate">Terminer le placement</button>
</form>

<script src="../js/placement_new.js"></script>

</body>
</html>

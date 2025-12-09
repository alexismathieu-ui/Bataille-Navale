<?php
session_start();

// Sécurité : si aucun rôle ==> retour à l'accueil
if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$gagnant = $_GET["winner"] ?? null;

if ($gagnant === null) {
    header("Location: grille.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Victoire !</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h1>🎉 Victoire ! 🎉</h1>
<h2><?= $gagnant ?> a gagné la partie !</h2>

<div style="margin-top: 40px;">
    <a class="btn" href="reset.php">🔄 Rejouer une nouvelle partie</a>
    <br><br>
    <a class="btn" href="../index.php">🏠 Retour à l'accueil</a>
</div>

</body>
</html>

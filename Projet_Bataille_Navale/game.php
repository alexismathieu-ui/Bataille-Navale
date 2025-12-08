<?php
session_start();
$role = $_SESSION["role"] ?? "Aucun rôle";

if ($role === "Aucun rôle") {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bataille Navale</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>Bataille Navale – <?= $role ?></h1>

<div id="grille"></div>

<script src="js/game.js"></script>
</body>
</html>

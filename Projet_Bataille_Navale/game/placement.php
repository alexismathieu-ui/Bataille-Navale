<?php
session_start();

if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION["role"];

// Sélectionner la grille du joueur
$file = ($role === "Joueur 1") 
    ? "../data/grille_j1.json" 
    : "../data/grille_j2.json";

$grille = json_decode(file_get_contents($file), true);

// Définition des bateaux : id => taille
$listeBateaux = [
    2 => 2,
    3 => 3,
    4 => 4,
    5 => 5
];

// Initialisation de la progression si pas encore faite
if (!isset($_SESSION["current_ship"])) {
    $_SESSION["current_ship"] = 2;   // commence par bateau ID 2
    $_SESSION["ship_cells"] = [];    // cases sélectionnées pour ce bateau
}

$currentShip = $_SESSION["current_ship"];

// Si tous les bateaux sont placés → fin
$placementTermine = ($currentShip > 5);

// Clic sur une case ?
if (isset($_POST["row"]) && isset($_POST["col"]) && !$placementTermine) {

    $r = intval($_POST["row"]);
    $c = intval($_POST["col"]);

    // Si la case est vide, on peut la prendre
    if ($grille[$r][$c] === 0) {
        $grille[$r][$c] = $currentShip;
        $_SESSION["ship_cells"][] = [$r, $c];
    }

    // Si on a placé autant de cases que la taille du bateau
    if (count($_SESSION["ship_cells"]) == $listeBateaux[$currentShip]) {
        $_SESSION["current_ship"]++;   // Passer au bateau suivant
        $_SESSION["ship_cells"] = [];  // Réinitialiser les cases
    }

    // Sauvegarde
    file_put_contents($file, json_encode($grille));
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Placement des bateaux</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h1><?= $role ?> - Placement des bateaux</h1>

<?php if (!$placementTermine): ?>
    <h2>Bateau à placer : ID <?= $currentShip ?> — Taille <?= $listeBateaux[$currentShip] ?></h2>
    <p>Cliquez sur <?= $listeBateaux[$currentShip] ?> cases pour poser ce bateau.</p>
<?php else: ?>
    <h2>✔ Tous les bateaux sont placés !</h2>
    <a class="btn" href="grille.php">➡ Commencer la bataille</a>
<?php endif; ?>

<div class="grid">
<?php
for ($i = 0; $i < 10; $i++) {
    for ($j = 0; $j < 10; $j++) {

        $value = $grille[$i][$j];

        $classe = ($value > 0) ? "placed" : "";

        echo "
        <form method='POST' class='cell $classe'>
            <input type='hidden' name='row' value='$i'>
            <input type='hidden' name='col' value='$j'>
            <button type='submit'></button>
        </form>";
    }
}
?>
</div>

</body>
</html>

<?php
session_start();
require "../db.php";

// Si pas connecté → retour
if (!isset($_SESSION["joueur_id"])) {
    header("Location: ../index.php");
    exit;
}

$monId = $_SESSION["joueur_id"];

// Récupérer mes infos
$me = $db->prepare("SELECT * FROM joueurs WHERE id = ?");
$me->execute([$monId]);
$me = $me->fetch();

// Récupérer l'adversaire
$adv = $db->prepare("SELECT * FROM joueurs WHERE id != ?");
$adv->execute([$monId]);
$adv = $adv->fetch();

if (!$adv) {
    // L'autre joueur n'est plus là
    echo "<h1>L'autre joueur a quitté la partie.</h1>";
    echo "<p><a href='../index.php'>Retour au menu</a></p>";
    exit;
}

// Récupérer le tour (1 ou 2)
$tour = $db->query("SELECT tour FROM partie LIMIT 1")->fetchColumn();

// Charger ma grille
$myGridStmt = $db->prepare("SELECT x, y, valeur, touche FROM grilles WHERE joueur_id = ?");
$myGridStmt->execute([$monId]);
$myGrid = $myGridStmt->fetchAll(PDO::FETCH_ASSOC);

// Charger mes tirs
$myShotsStmt = $db->prepare("SELECT row_index, col_index, resultat FROM tirs WHERE attaquant_id = ?");
$myShotsStmt->execute([$monId]);
$myShots = $myShotsStmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir tirs en matrice
$shotGrid = [];
foreach ($myShots as $t) {
    $shotGrid[$t["row_index"]][$t["col_index"]] = $t["resultat"]; // 'touche' ou 'manque'
}

// Vérifier si c'est mon tour
$monTour = ($tour == $me["numero_joueur"]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bataille Navale - Jeu</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h1>🎯 Bataille Navale - <?= htmlspecialchars($me["nom"]) ?></h1>
<p>
    <?= $monTour ? "👉 À ton tour !" : "⏳ En attente de l’adversaire..." ?>
</p>

<a href="quitter.php" class="btn quit">Quitter la partie</a>

<div class="game-container">

    <!-- GRILLE DU JOUEUR -->
    <div>
        <h2>Ta grille</h2>
        <div class="grid">
        <?php
        // matrice 10x10 par défaut
        $table = array_fill(0, 10, array_fill(0, 10, 0));
        $touches = array_fill(0, 10, array_fill(0, 10, 0));

        foreach ($myGrid as $c) {
            $table[$c["x"]][$c["y"]] = $c["valeur"];
            $touches[$c["x"]][$c["y"]] = $c["touche"];
        }

        for ($x = 0; $x < 10; $x++) {
            for ($y = 0; $y < 10; $y++) {
                $val = $table[$x][$y];
                $hit = $touches[$x][$y];

                $cls = "";
                if ($val == 2) $cls = "boat2";
                if ($val == 3) $cls = "boat3";
                if ($val == 4) $cls = "boat4";
                if ($val == 5) $cls = "boat5";
                if ($hit)    $cls .= " hit_me";

                echo "<div class='cell$cls'></div>";
            }
        }
        ?>
        </div>
    </div>

    <!-- GRILLE DE L'ADVERSAIRE -->
    <div>
        <h2>Grille adverse</h2>
        <div class="grid">
        <?php
        for ($x = 0; $x < 10; $x++) {
            for ($y = 0; $y < 10; $y++) {

                $shot = $shotGrid[$x][$y] ?? null;  // 'touche' / 'manque' / null

                // Si ce n'est pas mon tour → pas cliquable
                if (!$monTour || $shot !== null) {
                    // déjà tiré ou pas mon tour → afficher état mais sans formulaire
                    $extra = "";
                    if ($shot === "touche") $extra = " shot_touche";
                    if ($shot === "manque") $extra = " shot_manque";

                    echo "<div class='cell disabled$extra'></div>";
                    continue;
                }

                // Case disponible pour tir
                echo "
                <form method='POST' action='tir.php' class='cell'>
                    <input type='hidden' name='r' value='$x'>
                    <input type='hidden' name='c' value='$y'>
                    <button type='submit' class='shot'></button>
                </form>";
            }
        }
        ?>
        </div>
    </div>

</div>

<script>
// Refresh automatique régulier
setInterval(() => {
    location.reload();
}, 1500);
</script>

</body>
</html>

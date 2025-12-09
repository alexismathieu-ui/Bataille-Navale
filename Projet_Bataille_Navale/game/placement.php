<?php
session_start();

if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION["role"];

$file = ($role === "Joueur 1")
    ? "../data/grille_j1.json"
    : "../data/grille_j2.json";

$grille = json_decode(file_get_contents($file), true);

$bateaux = [
    2 => 2,
    3 => 3,
    4 => 4,
    5 => 5
];

if (!isset($_SESSION["current_ship"])) {
    $_SESSION["current_ship"] = 2;
    $_SESSION["ship_cells"] = [];
    $_SESSION["orientation"] = null;
}

$ship = $_SESSION["current_ship"];
$taille = $bateaux[$ship];
$termine = ($ship > 5);

// ----------------------------
// GESTION DU CLIC
// ----------------------------
if (isset($_POST["row"], $_POST["col"]) && !$termine) {

    $r = intval($_POST["row"]);
    $c = intval($_POST["col"]);

    // Case déjà occupée
    if ($grille[$r][$c] !== 0) {
        header("Location: placement.php");
        exit;
    }

    $cells = $_SESSION["ship_cells"];

    // 1️⃣ Première case → acceptée
    if (count($cells) === 0) {
        $grille[$r][$c] = $ship;
        $_SESSION["ship_cells"][] = [$r, $c];
    }

    // 2️⃣ Deuxième case → détermine orientation
    elseif (count($cells) === 1) {

        list($r0, $c0) = $cells[0];

        if ($r === $r0 && abs($c - $c0) === 1) {
            $_SESSION["orientation"] = "H";
        }
        elseif ($c === $c0 && abs($r - $r0) === 1) {
            $_SESSION["orientation"] = "V";
        }
        else {
            header("Location: placement.php");
            exit;
        }

        $grille[$r][$c] = $ship;
        $_SESSION["ship_cells"][] = [$r, $c];
    }

    // 3️⃣ Cases suivantes → doivent prolonger une extrémité
    else {
        $orientation = $_SESSION["orientation"];

        $rows = array_column($cells, 0);
        $cols = array_column($cells, 1);

        $minR = min($rows);
        $maxR = max($rows);
        $minC = min($cols);
        $maxC = max($cols);

        $ok = false;

        if ($orientation === "H") {
            // même ligne & à gauche ou droite
            if ($r === $rows[0] && ($c === $minC - 1 || $c === $maxC + 1)) {
                $ok = true;
            }
        } else {
            // même colonne & en haut ou bas
            if ($c === $cols[0] && ($r === $minR - 1 || $r === $maxR + 1)) {
                $ok = true;
            }
        }

        if (!$ok) {
            header("Location: placement.php");
            exit;
        }

        $grille[$r][$c] = $ship;
        $_SESSION["ship_cells"][] = [$r, $c];
    }

    // 4️⃣ Bateau complet → suivant
    if (count($_SESSION["ship_cells"]) === $taille) {
        $_SESSION["current_ship"]++;
        $_SESSION["ship_cells"] = [];
        $_SESSION["orientation"] = null;
    }

    file_put_contents($file, json_encode($grille));
    header("Location: placement.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Placement des bateaux</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<script>
setInterval(() => {
    fetch("etat.php")
        .then(r => r.json())
        .then(data => {

            // Si le placement est terminé, on va à la grille
            if (<?= json_encode($placementTermine) ?>) {
                window.location.href = "grille.php";
            }
        });
}, 1000);
</script>

<body>

<h1><?= $role ?> - Placement des bateaux</h1>
<a class="btn reset" href="reset.php">🏳️ Abandon / Recommencer</a>

<?php if (!$termine): ?>
    <h2>Bateau ID <?= $ship ?> — Taille <?= $taille ?></h2>
    <p>Cliquez sur <?= $taille ?> cases alignées pour poser ce bateau.</p>
<?php else: ?>
    <h2>✔ Tous les bateaux placés !</h2>
    <a class="btn" href="grille.php">➡ Commencer la bataille</a>
<?php endif; ?>

<div class="grid">
<?php
for ($i = 0; $i < 10; $i++) {
    for ($j = 0; $j < 10; $j++) {

        $v = $grille[$i][$j];
        $classe = ($v > 0) ? "placed" : "";

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

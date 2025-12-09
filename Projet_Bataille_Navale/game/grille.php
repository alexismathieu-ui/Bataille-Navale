<?php
session_start();

if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION["role"];

// On charge la grille adverse
$enemyFile = ($role === "Joueur 1")
    ? "../data/grille_j2.json"
    : "../data/grille_j1.json";

$enemyGrid = json_decode(file_get_contents($enemyFile), true);

// On charge les tirs
$coupsFile = ($role === "Joueur 1")
    ? "../data/coups_j1.json"
    : "../data/coups_j2.json";

$coups = json_decode(file_get_contents($coupsFile), true);


$tourData = json_decode(file_get_contents("../data/tour.json"), true);
$tour = $tourData["tour"];

$cases = $coups["cases"];       // 10x10 tirés (0 = pas tiré, 1 = raté, 2 = touché)

// Vérifier si la partie est gagnée
$etat = json_decode(file_get_contents("etat.php"), true);

if (!empty($etat["gagnant"])) {
    header("Location: win.php?winner=" . $etat["gagnant"]);
    exit;
}


// Message "c'est ton tour" ou "pas ton tour"
$estMonTour = ($tour === $role);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bataille Navale - Tir</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<script>
// Vérifie l'état du jeu toutes les 1 seconde
setInterval(() => {
        fetch("etat.php")
            .then(r => r.json())
            .then(data => {

                // Redirection en cas de victoire
                if (data.gagnant) {
                    window.location.href = "win.php?winner=" + data.gagnant;
                    return;
                }

                // Vérifier si le tour a changé
                if (!data.a_toi_de_jouer) {
                    // Pas ton tour → ne rien faire
                    return;
                }

                // Si c'est ton tour, recharger la page
                // pour afficher la grille mise à jour
                location.reload();

            });
    }, 1000); // toutes les 1 seconde
</script>

<body>

<h1>Bataille Navale ⚓</h1>
<a class="btn reset" href="reset.php">🏳️ Abandon / Recommencer</a>
<h2><?= $role ?> —
    <?= $estMonTour ? "🎯 À vous de jouer" : "⏳ Tour adverse" ?>
</h2>

<div class="grid">
<?php
for ($i = 0; $i < 10; $i++) {
    for ($j = 0; $j < 10; $j++) {

        $etatCase = $cases[$i][$j]; // 0 rien, 1 raté, 2 touché
        $classe = "";

        if ($etatCase === 1) $classe = "miss";
        if ($etatCase === 2) $classe = "hit";

        // Case déjà tirée → désactivée
        $disabled = ($etatCase !== 0 || !$estMonTour) ? "disabled" : "";
        
        echo "
        <form action='tir.php' method='POST' class='cell $classe'>
            <input type='hidden' name='row' value='$i'>
            <input type='hidden' name='col' value='$j'>
            <button type='submit' $disabled></button>
        </form>";
    }
}
?>
</div>

</body>
</html>

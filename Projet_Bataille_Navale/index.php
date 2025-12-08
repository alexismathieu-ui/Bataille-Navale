<?php
session_start();

$fichier = "etat_joueurs.json";

if (!file_exists($fichier)) {
    file_put_contents($fichier, json_encode(["j1" => null, "j2" => null]));
}

$etat = json_decode(file_get_contents($fichier), true);

function save_state($file, $data) {
    file_put_contents($file, json_encode($data));
}

if (isset($_POST["reset_total"])) {
    $etat = ["j1" => null, "j2" => null];
    save_state($fichier, $etat);
    session_destroy();
    header("Location: index.php");
    exit;
}

if (isset($_POST["joueur1"]) && $etat["j1"] === null) {
    $etat["j1"] = session_id();
    $_SESSION["role"] = "Joueur 1";
    save_state($fichier, $etat);
}

if (isset($_POST["joueur2"]) && $etat["j2"] === null) {
    $etat["j2"] = session_id();
    $_SESSION["role"] = "Joueur 2";
    save_state($fichier, $etat);
}

$role = $_SESSION["role"] ?? "Aucun rôle";
header("refresh:5");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bataille Navale</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>⚓ Sélection du Joueur</h1>
<h2>Votre rôle : <?= $role ?></h2>

<p>
Joueur 1 : <?= $etat["j1"] ? "🟢 Occupé" : "🔴 Libre" ?><br>
Joueur 2 : <?= $etat["j2"] ? "🟢 Occupé" : "🔴 Libre" ?>
</p>

<form method="POST">
    <button class="btn" name="joueur1" <?= $etat["j1"] ? "disabled" : "" ?>>🎮 Joueur 1</button>
    <button class="btn" name="joueur2" <?= $etat["j2"] ? "disabled" : "" ?>>🎮 Joueur 2</button>
    <button class="btn" name="reset_total">❌ RESET</button>
</form>

<?php if ($role !== "Aucun rôle"): ?>
    <a class="btn" href="game/placement.php">➡ Commencer placement bateaux</a>
<?php endif; ?>

</body>
</html>

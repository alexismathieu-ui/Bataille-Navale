<?php
session_start();
require "db.php";

if (!isset($_SESSION["joueur_id"])) {
    header("Location: index.php");
    exit;
}

$joueur_id = $_SESSION["joueur_id"];
$numero = $_SESSION["numero_joueur"];

// Récupérer mes infos
$stmt = $db->prepare("SELECT * FROM joueurs WHERE id = ?");
$stmt->execute([$joueur_id]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le joueur veut changer son pseudo
if (isset($_POST["pseudo"])) {
    $pseudo = trim($_POST["pseudo"]);
    if ($pseudo !== "") {
        $stmt = $db->prepare("UPDATE joueurs SET nom = ? WHERE id = ?");
        $stmt->execute([$pseudo, $joueur_id]);
        $me["nom"] = $pseudo;
    }
}

// Si le joueur clique "Prêt"
if (isset($_POST["pret"])) {
    $db->prepare("UPDATE joueurs SET pret = 1 WHERE id = ?")->execute([$joueur_id]);
    $me["pret"] = 1;
}

// Si le joueur clique "Pas prêt"
if (isset($_POST["paspret"])) {
    $db->prepare("UPDATE joueurs SET pret = 0 WHERE id = ?")->execute([$joueur_id]);
    $me["pret"] = 0;
}

// Bouton quitter → supprime le joueur
if (isset($_POST["quitter"])) {
    $db->prepare("DELETE FROM joueurs WHERE id = ?")->execute([$joueur_id]);
    session_destroy();
    header("Location: index.php");
    exit;
}

// Récupérer les deux joueurs
$players = $db->query("SELECT * FROM joueurs ORDER BY numero_joueur")->fetchAll(PDO::FETCH_ASSOC);

// Si les deux joueurs sont prêts : direction placement !
if (count($players) == 2 && $players[0]["pret"] == 1 && $players[1]["pret"] == 1) {
    header("Location: game/placement.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lobby - Bataille Navale</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Refresh automatique toutes les 2 sec
        setInterval(() => {
            fetch("refresh_lobby.php")
                .then(r => r.json())
                .then(data => {
                    document.getElementById("etat").innerHTML = data.html;

                    if (data.start) {
                        window.location.href = "game/placement.php";
                    }
                });
        }, 2000);
    </script>
</head>
<body>

<h1>⚓ Lobby - En attente du second joueur</h1>

<h2>Vous êtes : <strong><?= htmlspecialchars($me["nom"]) ?></strong> (Joueur <?= $numero ?>)</h2>

<!-- Changement de pseudo -->
<form method="POST">
    <input type="text" name="pseudo" placeholder="Nouveau pseudo" class="input">
    <button class="btn">Changer de pseudo</button>
</form>

<!-- Prêt / Pas prêt -->
<?php if ($me["pret"] == 0): ?>
    <form method="POST">
        <button name="pret" class="btn green">Je suis prêt ✔</button>
    </form>
<?php else: ?>
    <form method="POST">
        <button name="paspret" class="btn orange">Annuler prêt ❌</button>
    </form>
<?php endif; ?>

<!-- Quitter -->
<form method="POST">
    <button name="quitter" class="btn red">Quitter la partie 🚪</button>
</form>

<hr>

<h2>👥 État des joueurs</h2>
<div id="etat">
    Chargement...
</div>

</body>
</html>

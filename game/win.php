<?php
session_start();
require "../db.php";

if (!isset($_SESSION["winner_id"], $_SESSION["loser_id"])) {
    header("Location: ../index.php");
    exit;
}

$winnerId = $_SESSION["winner_id"];
$loserId  = $_SESSION["loser_id"];

// 🎯  REJOUER
if (isset($_POST["rejouer"])) {

    // Reset complet
    $db->exec("DELETE FROM tirs");
    $db->exec("DELETE FROM grilles");
    $db->exec("DELETE FROM joueurs");
    $db->exec("UPDATE partie SET tour = 1 WHERE id = 1");

    session_unset();
    session_destroy();

    header("Location: ../index.php");
    exit;
}

// 🚪 QUITTER
if (isset($_POST["quitter"])) {

    $db->exec("DELETE FROM tirs");
    $db->exec("DELETE FROM grilles");
    $db->exec("DELETE FROM joueurs");
    $db->exec("UPDATE partie SET tour = 1 WHERE id = 1");

    session_unset();
    session_destroy();

    header("Location: ../index.php");
    exit;
}

// 🎖️ On enregistre la victoire dans le tableau des scores
$db->prepare("
    INSERT INTO scores (joueur_id, resultat) VALUES (?, 'win')
")->execute([$winnerId]);

$db->prepare("
    INSERT INTO scores (joueur_id, resultat) VALUES (?, 'loss')
")->execute([$loserId]);

// Infos joueurs
$stmt = $db->prepare("SELECT * FROM joueurs WHERE id = ?");
$stmt->execute([$winnerId]); $winner = $stmt->fetch();

$stmt->execute([$loserId]);  $loser = $stmt->fetch();

// Bateaux coulés
$boatsStmt = $db->prepare("
    SELECT x, y, valeur 
    FROM grilles 
    WHERE joueur_id = ? AND valeur > 0
");
$boatsStmt->execute([$loserId]);
$cells = $boatsStmt->fetchAll(PDO::FETCH_ASSOC);

// regrouper
$ships = [2=>[],3=>[],4=>[],5=>[]];
$rows = ['A','B','C','D','E','F','G','H','I','J'];

foreach ($cells as $cell) {
    $type = (int)$cell["valeur"];
    if (!isset($ships[$type])) continue;

    $x = (int)$cell["x"];
    $y = (int)$cell["y"];

    $coord = $rows[$x] . ($y+1);
    $ships[$type][] = $coord;
}

$names = [
    2=>"Torpilleur (2 cases)",
    3=>"Sous-marin (3 cases)",
    4=>"Croiseur (4 cases)",
    5=>"Porte-avions (5 cases)"
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Victoire !</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h1>🏆 Victoire !</h1>

<h2>
    <?= htmlspecialchars($winner["nom"]) ?> a gagné 🎉  
    <br>
    <small>Contre <?= htmlspecialchars($loser["nom"]) ?></small>
</h2>

<h3>Bateaux coulés :</h3>
<ul>
<?php foreach ($ships as $type => $coords): ?>
    <?php if (count($coords) === 0) continue; ?>
    <li><strong><?= $names[$type] ?> :</strong> <?= implode(", ", $coords) ?></li>
<?php endforeach; ?>
</ul>

<form method="POST" style="margin-top:20px;">
    <button type="submit" name="rejouer" class="btn">🔁 Rejouer</button>
    <button type="submit" name="quitter" class="btn quit">🚪 Quitter</button>
    <a href="../scores.php" class="btn score">📊 Tableau des scores</a>
</form>

</body>
</html>

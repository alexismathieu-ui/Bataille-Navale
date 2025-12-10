<?php
require "db.php";

// Récupérer classement par nombre de victoires
$ranking = $db->query("
    SELECT joueurs.nom, 
           SUM(scores.resultat = 'win') AS victoires,
           SUM(scores.resultat = 'loss') AS defaites
    FROM scores
    LEFT JOIN joueurs ON joueurs.id = scores.joueur_id
    GROUP BY scores.joueur_id
    ORDER BY victoires DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Classement</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>📊 Tableau des Scores</h1>

<table class="scoreboard">
    <tr>
        <th>Joueur</th>
        <th>Victoires</th>
        <th>Défaites</th>
    </tr>

    <?php foreach ($ranking as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r["nom"]) ?></td>
        <td><?= $r["victoires"] ?></td>
        <td><?= $r["defaites"] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<a href="index.php" class="btn">🏠 Retour</a>

</body>
</html>

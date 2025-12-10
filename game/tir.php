<?php
session_start();
require "../db.php";

if (!isset($_SESSION["joueur_id"])) {
    header("Location: ../index.php");
    exit;
}

$attaquant = $_SESSION["joueur_id"];

// Récupérer mes infos
$me = $db->prepare("SELECT * FROM joueurs WHERE id = ?");
$me->execute([$attaquant]);
$me = $me->fetch();

// Tour actuel (1 ou 2)
$tour = $db->query("SELECT tour FROM partie LIMIT 1")->fetchColumn();

if ($tour != $me["numero_joueur"]) {
    // Pas mon tour → retour
    header("Location: game.php");
    exit;
}

// Coordonnées tirées
$r = intval($_POST["r"] ?? -1);
$c = intval($_POST["c"] ?? -1);

// Récupérer l'adversaire
$adv = $db->prepare("SELECT id FROM joueurs WHERE id != ?");
$adv->execute([$attaquant]);
$advId = $adv->fetchColumn();

if (!$advId) {
    header("Location: game.php");
    exit;
}

// Vérifier si on a déjà tiré sur cette case
$already = $db->prepare(
    "SELECT COUNT(*) FROM tirs WHERE attaquant_id = ? AND row_index = ? AND col_index = ?"
);
$already->execute([$attaquant, $r, $c]);

if ($already->fetchColumn() > 0) {
    // déjà tiré ici → on ignore
    header("Location: game.php");
    exit;
}

// Vérifier s'il y a un bateau sur cette case (grille adverse)
$check = $db->prepare(
    "SELECT valeur FROM grilles WHERE joueur_id = ? AND x = ? AND y = ?"
);
$check->execute([$advId, $r, $c]);
$cell = $check->fetchColumn();

$resultat = ($cell > 0) ? "touche" : "manque";

// Enregistrer le tir
$stmt = $db->prepare("
    INSERT INTO tirs (attaquant_id, cible_id, row_index, col_index, resultat)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$attaquant, $advId, $r, $c, $resultat]);

// Si touché → marquer la case dans grilles
if ($resultat === "touche") {
    $upd = $db->prepare(
        "UPDATE grilles SET touche = 1 WHERE joueur_id = ? AND x = ? AND y = ?"
    );
    $upd->execute([$advId, $r, $c]);
}

// 🔥 CHECK VICTOIRE : reste-t-il des cases avec bateau non touchées ?
$remainingStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM grilles 
    WHERE joueur_id = ? 
      AND valeur > 0 
      AND touche = 0
");
$remainingStmt->execute([$advId]);
$remaining = $remainingStmt->fetchColumn();

if ($remaining == 0) {
    // 🎉 L'ATTAQUANT GAGNE !
    $_SESSION["winner_id"] = $attaquant;
    $_SESSION["loser_id"]  = $advId;

    // on peut aussi remettre le tour à 1 pour la prochaine partie
    $db->prepare("UPDATE partie SET tour = 1 WHERE id = 1")->execute();

    header("Location: win.php");
    exit;
}

// Sinon → Passer le tour à l'autre joueur
$newTour = ($tour == 1) ? 2 : 1;
$db->prepare("UPDATE partie SET tour = ? WHERE id = 1")->execute([$newTour]);

header("Location: game.php");
exit;

<?php
session_start();
require "db.php";

$players = $db->query("SELECT * FROM joueurs ORDER BY numero_joueur")->fetchAll(PDO::FETCH_ASSOC);

$html = "<ul>";

foreach ($players as $p) {
    $pret = $p["pret"] ? "✔ Prêt" : "⌛ Pas prêt";
    $html .= "<li><strong>Joueur {$p['numero_joueur']}</strong> : {$p['nom']} — $pret</li>";
}
$html .= "</ul>";

$start = false;
if (count($players) == 2 && $players[0]["pret"] == 1 && $players[1]["pret"] == 1) {
    $start = true;
}

echo json_encode([
    "html" => $html,
    "start" => $start
]);

<?php
require "../db.php";

// Les 2 joueurs ont posé 5 bateaux → 5 types × taille variable donc 5 niveaux
$count = $db->query("SELECT COUNT(*) AS c FROM grilles GROUP BY joueur_id")->fetchAll();

if (count($count) == 2) {
    echo "GO";
} else {
    echo "WAIT";
}

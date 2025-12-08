<?php
require "../include/db.php";

$q = $db->query("SELECT row_index AS row, col_index AS col, touched FROM cases");
$cases = $q->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($cases);

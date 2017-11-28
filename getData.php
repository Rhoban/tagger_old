<?php

include "db.php";

$type = $_POST['type'];
$sessions = $_POST['sessions'];

getJSON($type, $sessions);

?>

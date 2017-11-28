<?php

include "db.php";

$sessions = $_POST['sessions'];

updateActiveSessions($sessions);

?>

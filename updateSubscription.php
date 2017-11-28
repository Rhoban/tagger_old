<?php
session_start();

include "db.php";

$idUser = $_SESSION['id'];
$userName = $_SESSION['username'];

$user = getUserInfos($userName);

$value = $user[0][6];

if($value == 1)
    $value = 0;
else
    $value = 1;

setSubscription($idUser, $value);













?>

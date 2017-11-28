<?php

session_start();
include "db.php";

$userId = $_GET['userId'];

$balls = getTaggingHistory($userId, "Balls");
$posts = getTaggingHistory($userId, "Posts");

$data = array();
array_push($data, count($balls));
array_push($data, $balls);
array_push($data, count($posts));
array_push($data, $posts);

echo json_encode($data);

?>

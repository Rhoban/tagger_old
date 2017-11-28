<?php
session_start();

include "db.php";

$nbBallsTagged = getNbImagesTaggedActive("Balls");
$nbImagesBalls = getNbEntriesActive("Balls");

$nbPostsTagged = getNbImagesTaggedActive("Posts");
$nbImagesPosts = getNbEntriesActive("Posts");



$data = array();
array_push($data, $nbBallsTagged);
array_push($data, $nbImagesBalls);
array_push($data, $nbPostsTagged);
array_push($data, $nbImagesPosts);

echo json_encode($data);














?>

<?php

session_start();

$logged = false;
include "db.php";

if(isset($_SESSION["id"])){
    $userId = $_SESSION["id"];
    $logged = true;
    updateUserLastSeen($_SESSION["id"]);
}

$nbIdsMarked = intval($_GET['nbIdsMarked']);
$nbIdsSpecial = intval($_GET['nbIdsSpecial']);
$nbIds = intval($_GET['nbIds']);
$type=$_GET['type'];

$marked = [];
$all = [];
$special = [];

for($i = 0 ; $i < $nbIds ; $i++){
    $tmp = 'id'.$i;
    array_push($all, intval($_GET[$tmp]));
}

for($i = 0 ; $i < $nbIdsMarked ; $i++){
    $tmp = 'idMarked'.$i;
    array_push($marked, intval($_GET[$tmp]));
}

for($i = 0 ; $i < $nbIdsSpecial ; $i++){
    $tmp = 'idSpecial'.$i;
    array_push($special, intval($_GET[$tmp]));
}

deleteLast16TagsUser($userId);

for($i = 0 ; $i < $nbIds ; $i++){
    decreaseNbTimesTaggedImage($all[$i]);
}

if($logged){
    $val = count($marked) + count($special);
    $val = - $val;
    updateUserScore($userId, $val);
}

if($_SESSION["privileges"] == 1){
    
    echo '<div id="progressBar">';
    
    $nbTagged = getNbImagesTaggedActive($type);
    $nbImages = getNbEntriesActive($type);
    $percentage = round(($nbTagged/$nbImages)*100, 2);
    
    echo '<div id="bar" style="width:'.$percentage.'%">';
    echo round(($nbTagged/$nbImages)*100, 2).'% Tagged';
    
    echo '</div>';
    echo '</div>'; 
}

echo '<h1>Mark the ';echo $type; echo '</h1>';
echo '<div class="grid">';

$ids = $all;
for($i = 0 ; $i < 16 ; $i++){
    
    $id = $ids[$i];
    
    echo '<div class="element-item">';
    
    echo '<img id=stop'; echo $i ; echo ' src="assets/stop.png" style="width:75%;position:absolute ; display:none ;pointer-events: none">';
    echo '<img id=tick'; echo $i ; echo ' src="assets/ok.png" style="width:75% ;position:absolute ; display:none ;pointer-events: none">';
    
    echo '<img  imageId="';echo $id;echo '" value="0" id="';echo $i; echo '" class="tile'.$type.'" src="Images/';echo $type; echo '/' ; echo  $id; echo '.png" onclick="markTile(';echo $i;echo ',';echo $id;echo ', false)" >';

    echo '</div>';
}			    

echo '</div>';

echo '<p><button id="yes" style="float:right;" class="btn btn-primary" role="button" onclick="updateTagger(callBackReloadIsotope, \'';echo $type ; echo '\'' ; echo ' )" >OK</button></p>';

?>


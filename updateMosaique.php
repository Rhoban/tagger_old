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

$userScore = getScore($_SESSION["id"]);

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

$wrong = [];
$notSure = [];
$allOk = true;

if(($userScore <= 100 || getUserErrorRatio($_SESSION["id"]) >= 0.1) && $_SESSION["privileges"] != 1){#TODO TEMPORAIRE
    for($i = 0 ; $i < $nbIdsMarked ; $i++){//Images tagged true
	if(getImageConsensus($marked[$i]) == 0){
	    $allOk = false;
	    array_push($wrong, $marked[$i]);
	}
	else if(getImageConsensus($marked[$i]) == -1){
	    array_push($notSure, $marked[$i]);
	}
	
    }
    for($i = 0 ; $i < $nbIds ; $i++){
	if(!in_array($all[$i], $marked) && !in_array($all[$i], $special)){//Images tagged false
	    if(getImageConsensus($all[$i]) == 1){
		$allOk = false;
		array_push($wrong, $all[$i]);
	    }
	    else if(getImageConsensus($all[$i]) == -1){
		array_push($notSure, $all[$i]);
	    }
	}
    }
}
//If still training and not trusted user
if(($userScore <= 100 || getUserErrorRatio($_SESSION["id"]) >= 0.1) && $_SESSION["privileges"] != 1){
    if($allOk){
	$val = count($marked) + count($special);
	updateUserScore($userId, $val);
    }
}
else{//normal user
    $val = count($marked) + count($special);
    updateUserScore($userId, $val);
}

updateUserErrors($userId, count($wrong), count($all) - count($notSure) - count($special));

/* if($allOk){
 *     if($logged){
 * 	$val = count($marked) + count($special);
 * 	updateUserScore($userId, $val);
 * 	updateUserTrainingData($userId, false);
 * 
 *     }	
 * }
 * else{
 *     updateUserTrainingData($userId, true);
 * }*/

if(($userScore > 100 && getUserErrorRatio($_SESSION["id"]) < 0.1) || $_SESSION["privileges"] == 1){
    
    for($i = 0 ; $i < $nbIds ; $i++){
	if(!in_array($all[$i], $marked) && !in_array($all[$i], $special)){
	    updateAnswer($all[$i], $userId, 0, $type);
	}
    }

    for($i = 0 ; $i < $nbIdsMarked ; $i++){
	updateAnswer($marked[$i], $userId, 1, $type);
    }

    for($i = 0 ; $i < $nbIdsSpecial ; $i++){
	updateAnswer($special[$i], $userId, 2, $type);
    }

    if($logged){
	$val = count($marked) + count($special);
	updateUserScore($userId, $val);
    }
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

$userScore = getScore($_SESSION["id"]);

if(!$allOk)
    echo "<h2> You made some mistakes </h2>";
else{
    if((intval($userScore) < 100 || getUserErrorRatio($_SESSION["id"]) >= 0.1) && $_SESSION["privileges"] != 1)
	echo '<p>You will be in the training mode until your error ratio is below 10% and you have tagged at least 100 images </p>';
}

echo '<div class="grid">';

if($allOk){

    if((intval($userScore) > 100 && getUserErrorRatio($_SESSION["id"]) < 0.1) || $_SESSION["privileges"] == 1)
	$ids = newGet16RandomIds($type, $_SESSION["privileges"]); 
    else
	$ids = get16RandomIdsTuto($type); 

    for($i = 0 ; $i < 16 ; $i++){
	
	$id = $ids[$i][0];

	
	echo '<div class="element-item">';
	
	echo '<img id=stop'; echo $i ; echo ' src="assets/stop.png" style="width:75%;position:absolute ; display:none ;pointer-events: none">';
	echo '<img id=tick'; echo $i ; echo ' src="assets/ok.png" style="width:75% ;position:absolute ; display:none ;pointer-events: none">';
	
	echo '<img  imageId="';echo $id;echo '" value="0" id="';echo $i; echo '" class="tile'.$type.'" src="Images/';echo $type; echo '/' ; echo  $id; echo '.png" onclick="markTile(';echo $i;echo ',';echo $id;echo ', false)" >';

	echo '</div>';
    }			    

    echo '</div>';

    echo '<div style="text-align:center; margin-bottom=20px; ">';
    echo '<p><button id="cancel" style="float:left;" class="btn btn-danger" role="button" onclick="cancel(callBackReloadIsotope)" >CANCEL LAST TAGS</button></p>';
    echo '</div>';

    echo '<p><button id="yes" style="float:right;" class="btn btn-primary" role="button" onclick="updateTagger(callBackReloadIsotope, \'';echo $type ; echo '\'' ; echo ' )" >OK</button></p>';
    
}
else{
    for($i = 0 ; $i < 16 ; $i++){

	$id = $all[$i]; 
	
	echo '<div class="element-item">';
	
	echo '<img id=stop'; echo $i ; echo ' src="assets/stop.png" style="width:75%;position:absolute ; display:none ;pointer-events: none">';
	echo '<img id=tick'; echo $i ; echo ' src="assets/ok.png" style="width:75% ;position:absolute ; display:none ;pointer-events: none">';
	
	if(in_array($id, $wrong)){
	    echo '<img id=wrong'; echo $i ; echo ' src="assets/wrong.png" style="width:75% ;position:absolute ; ;pointer-events: none">'; 
	}
	
	echo '<img  imageId="';echo $id;echo '" value="0" id="';echo $i; echo '" class="tile'.$type.'" src="Images/';echo $type; echo '/' ; echo  $id; echo '.png" onclick="markTile(';echo $i;echo ',';echo $id;echo ', false)" >';

	echo '</div>';
    }
    echo '</div>';
    
    echo '<p><button id="restart" style="float:right;" class="btn btn-primary" role="button" onclick="location.reload();" >Restart</button></p>';

}
?>


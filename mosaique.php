<?php

session_start();

include "db.php";

if(!isset($_SESSION["id"])){
    header('Location: createAccount.php');
    exit();
}
else{
    updateUserLastSeen($_SESSION["id"]);
}

$type=null;

if(isset($_GET['tag'])){
    
    /* $uncertain = false;*/
    
    $type=$_GET['tag'];

    /* $types = getTypes();    */
    
    /* $types = ["Balls", "Posts"];
     * for($i = 0 ; $i < count($types) ; $i++){
       
       $t = $types[$i][0];
       
       if($type == "uncertain".$t){
       if($_SESSION["privileges"] != 1){
       header('Location: index.php');
       exit();
       }
       else{
       echo $t;
       $type = $t;
       $uncertain = true;
       }
       }
     * }

     * if($uncertain == false){
       header('Location: index.php');
       exit();
     * }*/
    
}
else{
    header('Location: index.php');
    exit();    
}

echo '<div id="hiddenType" style="display:none">'.$type.'</div>';

?>


<!DOCTYPE html>
<html lang="en">
    <head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">
	<link rel="icon" href="../../favicon.ico">
	<script src="Libs/jquery-2.1.4.min.js"></script>
	<script type="text/javascript" src="Libs/isotope.min.js"></script>
	<script type="text/javascript" src="Libs/imagesLoaded.min.js"></script>
	<link href="Style/style.css" rel="stylesheet">
	<title>Rhoban Tagger</title>

	<!-- Bootstrap core CSS -->
	<link href="Style/bootstrap.min.css" rel="stylesheet">

	<link href="Style/jumbotron-narrow.css" rel="stylesheet">	
    </head>

    <body>

	<div class="container">
	    
	    <?php
	    include "header.php";
	    ?>
	    
	    <div class="jumbotron" style="padding-bottom:90px ; padding-top:5px">
		<div id="helpButton" class="btn btn-lg btn-success" style="margin-bottom:10px;" >Help</div>
		<div id="help" style="display:none">
		    
		    <?php
		    if($type=="Balls"){
			echo "You should tag as a ball (green tick) the images on which the ball is approximately in the center of the image. If there is a ball, but it is not centered, you should tag the image as maybe (click twice). Otherwise, don't click to tag the image as not a ball.<br>";
			echo "Marquer les images contenant une balle centree positives (tick vert, un clic), s'il y a une balle mais qu'elle est decentree, marquer comme peut-etre (point d'interrogation, deux clics). Si l'image ne contient pas de balle, ne pas marquer.<br>";
			
			echo "<h2> Positive examples :</h2>";
			/* echo '<img src="assets/help_ok.png" style="width:8em;">';*/
			echo '<img src="assets/hb1.png" style="width:8em;">';
			echo '<img src="assets/hb2.png" style="width:8em;">';
			echo '<img src="assets/hb3.png" style="width:8em;">';
			echo '<img src="assets/hb4.png" style="width:8em;">';
			echo '<img src="assets/hb5.png" style="width:8em;">';
			echo '<img src="assets/hb6.png" style="width:8em;">';
			echo '<img src="assets/hb7.png" style="width:8em;">';
			echo '<img src="assets/hb8.png" style="width:8em;">';
			echo "<h2> Maybe examples :</h2>";
			echo '<img src="assets/help_stop.png" style="width:8em;">';
			echo "<h2> Field examples :</h2>";
			echo '<img src="assets/field1.jpg" style="width:20em;">';
			echo '<img src="assets/field2.jpg" style="width:20em;">';
			echo '<img src="assets/field3.jpg" style="width:20em;">';
		    }
		    else if($type=="Posts"){
			echo "You should tag as a post (green tick) the images on which the bottom of the post is approximately in the center of the image. If you see the bootom of the post, but it is not centered, you should tag the image as maybe (click twice). Otherwise, don't click to tag the image as not a post.<br>";
			echo "Marquer les images contenant une base de poteau centree positives (tick vert, un clic), s'il y a une base de poteau mais qu'elle est decentree, marquer comme peut-etre (point d'interrogation, deux clics). Si l'image ne contient pas de base de poteau ou qu'on voit un poteau mais pas la base, ne pas marquer.<br>";
			
			echo "<h2> Positive examples :</h2>";
			/* echo '<img src="assets/help_ok_posts.png" style="width:7em;">';*/
			echo '<img src="assets/hp1.png" style="width:8em;">';
			echo '<img src="assets/hp2.png" style="width:8em;">';
			echo '<img src="assets/hp3.png" style="width:8em;">';
			echo '<img src="assets/hp4.png" style="width:8em;">';
			echo '<img src="assets/hp5.png" style="width:8em;">';
			echo '<img src="assets/hp6.png" style="width:8em;">';
			echo '<img src="assets/hp7.png" style="width:8em;">';
			echo '<img src="assets/hp8.png" style="width:8em;">';
			echo "<h2> Maybe examples :</h2>";
			echo '<img src="assets/help_stop_posts.png" style="width:7em;">';
			echo "<h2> Field examples :</h2>";
			echo '<img src="assets/field1.jpg" style="width:20em;">';
			echo '<img src="assets/field2.jpg" style="width:20em;">';
			echo '<img src="assets/field3.jpg" style="width:20em;">';
		    }
		    else if($type=="Carrot"){
			echo '<img src="assets/help_carrots.png" style="width:14em;">';
		    }
		    ?>
		    
		</div> 
		
		<div id="tagger"  >
		    <?php

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

		    $userScore = getScore($_SESSION["id"]);
		    
		    if((intval($userScore) > 100 && getUserErrorRatio($_SESSION["id"]) < 0.1) || $_SESSION["privileges"] == 1)
			$ids = newGet16RandomIds($type, $_SESSION["privileges"]); 
		    else
			$ids = get16RandomIdsTuto($type); 
		    
		    if(count($ids)>0){
			
			echo '<h1>Mark the '.$type.'</h1>';
			if((intval($userScore) <= 100 || getUserErrorRatio($_SESSION["id"]) >= 0.1) && $_SESSION["privileges"] != 1)//TODO rajouter ratio
			    echo '<p>You will be in the training mode until your error ratio is below 10% and you have tagged at least 100 images </p>';
			echo '<div class="grid"> ';
			
			for($i = 0 ; $i < 16 ; $i++){
			    $id = $ids[$i][0];

			    echo '<div class="element-item">';
			    
			    echo '<img id=stop'; echo $i ; echo ' src="assets/stop.png" style="width:75%;position:absolute ; display:none ;pointer-events: none">';
			    echo '<img id=tick'; echo $i ; echo ' src="assets/ok.png" style="width:75% ;position:absolute ; display:none ;pointer-events: none">';
			    
			    echo '<img  imageId="';echo $id;echo '" value="0" id="';echo $i; echo '" class="tile'.$type.'" src="Images/';echo $type; echo '/' ; echo  $id; echo '.png" onclick="markTile(';echo $i;echo ',';echo $id;echo ', false)" >';

			    echo '</div>';
			}
			echo '</div>';/* grid*/

			echo '<div style="text-align:center; margin-bottom=20px; ">';
			echo '<p><button id="cancel" style="float:left;" class="btn btn-danger" role="button" onclick="cancel(callBackReloadIsotope)" >CANCEL LAST TAGS</button></p>';
			echo '</div>';
			
			echo '<div style="text-align:center; margin-bottom=20px; ">';
			echo '<p><button id="yes" style="float:right;" class="btn btn-primary" role="button" onclick="updateTagger(callBackReloadIsotope)" >OK</button></p>';
			echo '</div>';
			
		    }
		    else{
			echo '<h3>No more Images to tag! Come back later</h3>';
		    }

		    
		    ?> 

		</div><!-- tagger -->

		
	    </div> <!-- jumbotron -->
	    
	    <footer class="footer">
		<p>&copy; 2017 Rhoban</p>
	    </footer>

	</div> <!-- /container -->

    </body>

    <script type="text/javascript">
     
     var firstPage = true;
     $('#cancel').hide();
     
     var type = $("#hiddenType").text();

     var helpVisible = false;
     
     $("#helpButton").click(function(){
	 
	 if(helpVisible)
	     $("#help").slideUp();
	 else
	     $("#help").slideDown();
	 
	 helpVisible =! helpVisible;
	 
     });
     
     allTiles = [];
     markedTilesArray = [];
     specialTilesArray = [];

     previousAllTiles= [];
     previousMarkedTilesArray = [];
     previousSpecialTilesArray = [];

     var $grid = $('.grid').isotope({
	 itemSelector: '.element-item',
	 layoutMode: 'fitRows'
     });


     $(document).keypress(function(e) {
	 if(e.which == 13) {
	     updateTagger(callBackReloadIsotope, type);
	 }
     });
     
     function callBackReloadIsotope(){
	 
	 $('.element-item').css("opacity", "1");

	 var $grid = $('.grid').isotope({
	     itemSelector: '.element-item',
	     layoutMode: 'fitRows'
	 });
	 
	 $(window).scrollTop(scrollState);
     }

     var scrollState;
     function updateTagger(callback){
	 
	 $('.element-item').css("opacity", "0.5");
	 scrollState = $(window).scrollTop();
	 temp = document.querySelectorAll(".tile"+type);

	 for(i = 0 ; i < temp.length ; i++)
	     allTiles.push(temp[i].getAttribute("imageId"));
	 
	 if (window.XMLHttpRequest) {
	     // code for IE7+, Firefox, Chrome, Opera, Safari
	     xmlhttp = new XMLHttpRequest();
	 } else {
	     // code for IE6, IE5
	     xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	 }
	 xmlhttp.onreadystatechange = function() {

	     if (this.readyState == 4 && this.status == 200) {
		 document.getElementById("tagger").innerHTML = this.responseText;
		 callback();
	     }
	 };

	 s = "updateMosaique.php?nbIdsMarked="+markedTilesArray.length+"&type="+type+"&";
	 for(i = 0 ; i < markedTilesArray.length ; i++)
	     s = s + "idMarked"+i+"="+markedTilesArray[i]+"&";

	 s+="nbIdsSpecial="+specialTilesArray.length+"&";
	 for(i = 0 ; i < specialTilesArray.length ; i++)
	     s = s + "idSpecial"+i+"="+specialTilesArray[i]+"&";
	 
	 s+="nbIds="+allTiles.length+"&";
	 for(i = 0 ; i < allTiles.length ; i++){
	     if(i < allTiles.length-1)
		 s = s + "id"+i+"="+allTiles[i]+"&";
	     else
		 s = s + "id"+i+"="+allTiles[i];
	 }

	 console.log(s);
	 
	 xmlhttp.open("GET",s, true);
	 xmlhttp.send();
	 
	 firstPage = false;
	 $('#cancel').show();


	 
	 previousAllTiles = allTiles;
	 previousMarkedTilesArray = markedTilesArray;
	 previousSpecialTilesArray = specialTilesArray;
	 
	 markedTilesArray = [];
	 specialTilesArray = [];
	 allTiles = [];
     } 

     ctrlPressed = false;

     $(document).keydown(function(event){
	 if(event.which=="17")
	     ctrlPressed = true;
     });
     $(document).keyup(function(event){
	 ctrlPressed = false;
     });

     function markTile(i, id, doubleClick){
	 
	 value = document.getElementById(""+i).value;
	 console.log(parseInt(value));

	 if(parseInt(value) == 0 || isNaN(parseInt(value))){//state 0 -> 1

	     $('#tick'+i).show();
	     value = document.getElementById(""+i).value = "1" ;
	     markedTilesArray.push(id);
	     
	 }
	 else if(parseInt(value) == 1){//state 1 -> 2
	     $('#tick'+i).hide();
	     index = markedTilesArray.indexOf(id);
	     markedTilesArray.splice(index, 1);
	     $('#stop'+i).show();		 
	     value = document.getElementById(""+i).value = "2";
	     specialTilesArray.push(id);	     
	 }
	 else{//state 2->0 
	     $('#stop'+i).hide();
	     index = specialTilesArray.indexOf(id);
	     specialTilesArray.splice(index, 1);
	     value = document.getElementById(""+i).value = "0";
	 }

	 console.log("marked : "+markedTilesArray);
	 console.log("special : "+specialTilesArray);
	 console.log("allTiles : "+allTiles);
     }

     function cancel(callback){
	 if(!firstPage){
	     
	     $('.element-item').css("opacity", "0.5");
	     scrollState = $(window).scrollTop();
	     temp = document.querySelectorAll(".tile"+type);

	     if (window.XMLHttpRequest) {
		 // code for IE7+, Firefox, Chrome, Opera, Safari
		 xmlhttp = new XMLHttpRequest();
	     } else {
		 // code for IE6, IE5
		 xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	     }
	     xmlhttp.onreadystatechange = function() {

		 if (this.readyState == 4 && this.status == 200) {
		     document.getElementById("tagger").innerHTML = this.responseText;
		     callback();
		 }
	     };

	     s = "cancelMosaique.php?nbIdsMarked="+previousMarkedTilesArray.length+"&type="+type+"&";
	     for(i = 0 ; i < previousMarkedTilesArray.length ; i++)
		 s = s + "idMarked"+i+"="+previousMarkedTilesArray[i] + "&";
	     
	     s+="nbIdsSpecial="+previousSpecialTilesArray.length+"&";
	     for(i = 0 ; i < previousSpecialTilesArray.length ; i++)
		 s = s + "idSpecial"+i+"="+previousSpecialTilesArray[i]+"&";
	     
	     s+="nbIds="+previousAllTiles.length+"&";
	     for(i = 0 ; i < previousAllTiles.length ; i++){
		 if(i < previousAllTiles.length-1)
		     s = s + "id"+i+"="+previousAllTiles[i]+"&";
		 else
		     s = s + "id"+i+"="+previousAllTiles[i];
	     }

	     console.log(s);
	     
	     xmlhttp.open("GET",s, true);
	     xmlhttp.send();

	     markedTilesArray = [];
	     specialTilesArray = [];

	 } 
     }
     
    </script>



</html>


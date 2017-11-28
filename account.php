<?php
session_start();

include "db.php";

if(isset($_SESSION["id"])){
    updateUserLastSeen($_SESSION["id"]);
    $logged = true;
    if(isset($_GET['userName'])){

	if(userExists($_GET['userName'])){
	    $userName = $_GET['userName']; 
	    $user = getUserInfos($userName);
	    $userId = $user[0][0];
	    $subscribed = $user[0][6];
	    echo "<div id='userIdHidden' style='display:none'>".$userId."</div>";
	    if($_SESSION["id"] == $user[0][0])
		$itsMe = true; 
	    else
		$itsMe = false;
	    
	}
	else{
	    header('Location: index.php');
	    exit();
	}
	
    }
    else{
	header('Location: index.php');
	exit();
    }
    
}
else{

    $logged = false;
    if(isset($_POST['formSignIn'])){

	if( empty($_POST['name']) || empty($_POST['password'])){
	    
	    echo '<div style="color:rgb(255, 0, 0)">';
	    if(empty($_POST['name']))
		echo "please set your name"; echo "<br>";
	    if(empty($_POST['password']))
		echo "please enter your password";echo "<br>";	
	    echo '</div>';
	    
	}
	else{
	    $name = $_POST['name'];
	    $pass = $_POST['password'];

 	    $escapedName = $name;
	    if(userExists($escapedName)){
 		$escapedPW = $pass;

		$salt = getSalt($escapedName); 

		$saltedPW =  $escapedPW . $salt;

		$hashedPW = hash('sha256', $saltedPW);

		$user = getUser($escapedName, $hashedPW);
		if($user == null){
		    echo '<div style="color:rgb(255, 0, 0)">';

		    echo "Error : wrong password";echo "<br>";	
		    echo '</div>'; 
		}
		else{

		    session_start();

		    $id = $user[0][0];
		    $name = $user[0][1];
		    $mail = $user[0][2];
		    $rank = $user[0][3];
		    $privileges = $user[0][4];
		    $subscribed = $user[0][4];

		    $_SESSION["id"] = $id;
		    $_SESSION["username"] = $name;
		    $_SESSION["mail"] = $mail;
		    $_SESSION["rank"] = $rank;
		    $_SESSION["privileges"] = $privileges;
		    $_SESSION["subscribed"] = $subscribed;
		    
		    header('Location: index.php');
		    exit();
		}
		
	    }
	    
	}
    }
    else{
	header('Location: createAccount.php');
	exit();	
    }
    
}

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

	<title>User Account</title>

	<!-- Bootstrap core CSS -->
	<link href="Style/bootstrap.min.css" rel="stylesheet">

	<link href="Style/jumbotron-narrow.css" rel="stylesheet">

	<script src="Libs/jquery-2.1.4.min.js"></script>
	<script type="text/javascript" src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    </head>

    <body>

	<div class="container">
	    
	    <?php
	    include "header.php";
	    ?>
	    
	    <div class="jumbotron">
		<?php
		if($user[0][4] == 1)
		    echo "<h1 style='color:rgb(255, 0, 0)'>";
		else
		    echo "<h1>";
		
		echo $user[0][1];
		
		if(getScore($user[0][0]) < 100 || getUserErrorRatio($user[0][0]) > 0.1){
		    echo "<br>";
		    echo " (Training)";
		}
		?></h1>
		<p class="lead">
		    <?php
		    $rank = $user[0][3];
		    switch($rank){
			case 0: echo '<img src="assets/bronze.png" style="width:20%;">';
			    break;
			case 1: echo '<img src="assets/silver.png" style="width:20%;">';
			    break;
			case 2: echo '<img src="assets/gold.png" style="width:20%;">';
			    break;
			case 3: echo '<img src="assets/plat.png" style="width:20%;">';
			    break;
			case 4: echo '<img src="assets/diam.png" style="width:20%;">';
			    break; 
			case 5: echo '<img src="assets/master.png" style="width:20%;">';
			    break; 
			case 6: echo '<img src="assets/gm.png" style="width:20%;">';
			    break;
		    }
		    echo "<br>";
		    echo "Points : ";echo getScore($user[0][0]);
		    echo "<br>";
		    echo "Error ratio : " ; echo round(getUserErrorRatio($user[0][0]), 2);
		    ?>
		    
		</p>

		<div id="taggingHistoryStats">

		</div>

		<?php
		if($itsMe == true){
		    
		    if($subscribed)
			echo '<div id="setSubscriptionButton" class="btn btn-lg btn-danger" >Unsubscribe from mailing list</div>';
		    else
			echo '<div id="setSubscriptionButton" class="btn btn-lg btn-danger" >Subscribe to mailing list</div>';		    
		}
		?>
		
	    </div>

	    
	    <footer class="footer">
		<p>&copy; 2017 Rhoban</p>
	    </footer>

	</div> <!-- /container -->

	<script>

	 $('#setSubscriptionButton').click(function(e){
	     e.preventDefault();
	     $.ajax({
		 url : 'updateSubscription.php', // your php file
		 type : 'GET', // type of the HTTP request
		 success : function(ret){
		     location.reload();//TODO Temporaire, changer dynamiquement le bouton
		 }
	     });
	     
	 });
	 
	 $(document).ready(function(){
	     var dataBalls;
	     var dataPosts;

	     var userId = $("#userIdHidden").text();
	     
	     $.ajax({
		 url : 'getTaggingHistory.php?userId='+userId, // your php file
		 type : 'GET', // type of the HTTP request
		 success : function(ret){
		     
		     data = jQuery.parseJSON(ret);
		     nbBalls = data[0];
		     nbPosts = data[nbBalls-1]; 
		     /* console.log(nbBalls);
			console.log(nbPosts);*/

		     dataBalls = [];
		     for(var i = 0 ; i < nbBalls ; i++){
			 dataBalls.push(data[1][i]);
		     }

		     dataPosts = [];
		     for(var i = 0 ; i < nbPosts ; i++){
			 dataPosts.push(data[3][i]);
		     }

		     var datesBalls = [];
		     var valsBalls = [];

		     var datesPosts = [];
		     var valsPosts = [];
		     
		     for(var i = 0 ; i < nbBalls ; i++){
			 datesBalls.push(dataBalls[i][0]);
			 valsBalls.push(dataBalls[i][1]);
		     }

		     for(var i = 0 ; i < nbPosts ; i++){
			 datesPosts.push(dataPosts[i][0]);
			 valsPosts.push(dataPosts[i][1]);
		     }
		     
		     var traceBalls = {
			 x : datesBalls,
			 y : valsBalls,
			 type:'bar'
		     };

		     var tracePosts = {
			 x : datesPosts,
			 y : valsPosts,
			 type:'bar'
		     };

		     /* console.log("Balls");
			for(var i = 0 ; i < datesBalls.length ; i++)
			console.log(datesBalls[i]+" : "+valsBalls[i]);		 

			console.log("Posts");
			for(var i = 0 ; i < datesPosts.length ; i++)
			console.log(datesPosts[i]+" : "+valsPosts[i]);
		      */
		     
		     var layout = {
			 
			 title: "Number of Images tagged per date",
			 
			 xaxis : {
			     title:"Date"
			 },
			 
			 yaxis : {
			     title:"Number of images tagged"
			 },
			 
			 paper_bgcolor:"rgba(0, 0, 0, 0)",
			 barmode:'group',
			 legend:'false'
		     };

		     /* Plotly.newPlot('taggingHistoryStats', [traceBalls, tracePosts], layout, {displayModeBar: false});*/
		     
		 }
	     });
	     
	     
	 });

	</script>
	
    </body>
</html>

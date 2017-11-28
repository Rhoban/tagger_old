
<?php
session_start();

include "db.php";

if(isset($_SESSION["id"])){
    updateUserLastSeen($_SESSION["id"]);
}

/* $usersWithGoodRatio = getUsersWithGoodRatio();
 * foreach($usersWithGoodRatio as $user){
 *     echo $user;
 *     echo " ";
 * }*/

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

	    <title>Leaderboard</title>

	    <!-- Bootstrap core CSS -->
	    <link href="Style/bootstrap.min.css" rel="stylesheet">

	    <link href="Style/jumbotron-narrow.css" rel="stylesheet">

	    <script src="Libs/jquery-2.1.4.min.js"></script>
	</head>

	<body>

	    <div class="container">
		
		<?php
		include "header.php";
		?>
		
		<div class="jumbotron">
		    
		    <h1>Leaderboard</h1>
		    
		    <ul class="list-group" style="text-align:left">

			<?php
			$users = getAllUsers();
			$i = 0;
			foreach($users as $user){
			    echo '<li class="list-group-item">';
			    /* echo '<p style="float:left ; margin-right:0.8em;margin-top:0.4em;">'.$i.'</p>';*/
			    $rank = $user[3];
			    switch($rank){
				case 0: echo '<img src="assets/bronze.png" style="margin-right:1em;width:8%;">';
				    break;
				case 1: echo '<img src="assets/silver.png" style="margin-right:1em;width:8%;">';
				    break;
				case 2: echo '<img src="assets/gold.png" style="margin-right:1em;width:8%;">';
				    break;
				case 3: echo '<img src="assets/plat.png" style="margin-right:1em;width:8%;">';
				    break;
				case 4: echo '<img src="assets/diam.png" style="margin-right:1em;width:8%;">';
				    break; 
				case 5: echo '<img src="assets/master.png" style="margin-right:1em;width:8%;">';
				    break; 
				case 6: echo '<img src="assets/gm.png" style="margin-right:1em;width:7%;">';
				    break;
			    }
			    
			    if(getUserErrorRatio($user[0]) < 0.1 && getScore($user[0]) > 100){
				echo " <b><a style="; if($user[4] == 0) echo "'color:black'"; else echo "'color:red'"; echo " href='account.php?userName=".$user[1]."'> " ; echo $user[1] ; if(isUserActive($user[0])) echo " (Active)"; echo "</a></b>";
			    }
			    else{
				echo " <b><a style='color:black' href='account.php?userName=".$user[1]."'> " ; echo $user[1] ; echo " (Training)"; if(isUserActive($user[0])) echo " (Active)"; echo "</a></b>";
			    }
			
			    echo '<div style="float:right;">';
			echo " <b> Points : " ; echo getScore($user[0]) ; echo "</b><br>";
			echo '</div>';
			    echo '</li>';

			    $i++;
			}
			
			?>
		    </ul>
		</div>

		<footer class="footer">
		    <p>&copy; 2017 Rhoban</p>
		</footer>

	    </div> <!-- /container -->
	</body>
    </html>

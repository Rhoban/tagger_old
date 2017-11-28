
<?php
session_start();

include "db.php";

if(isset($_SESSION["id"])){
    updateUserLastSeen($_SESSION["id"]);
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

    <title>About</title>

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
              <h2>Rhoban Tagger is a collaborative tagging platform, help us win the Robocup (again) !</h2><br>
	      <p class="lead">Rhoban is a team of researchers, engineers and students working on small size autonomous humanoid robots.<br><br>
		  We have been participating in the RoboCup competition since 2011, and we ranked at the first place of our category (kid size) in 2016.<br><br>
		  This year, we decided to experiment with neural networks to improve the vision of the robots (mainly accurately detecting balls and posts). This approach is promising, but in order to efficiently train our neural networks, we need a lot of tagged data, that only humans can produce. <br><br>
		  That's why we need you !
	      </p>
	  </div>

	  <footer class="footer">
              <p>&copy; 2017 Rhoban</p>
	  </footer>

    </div> <!-- /container -->
  </body>
</html>

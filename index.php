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
    <script src="Libs/jquery-2.1.4.min.js"></script>
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
	    
	    <div id="accueil">
		<h1>Start Tagging</h1>
		<img src="assets/dance.gif" style="-ms-transform: rotate(-90deg); /* IE 9 */
						   -webkit-transform: rotate(-90deg); /* Chrome, Safari, Opera */
						   transform: rotate(-90deg);width:20em">
		<p class="lead">Experience the joy and fulfillness of tagging images!</p>
	    </div>

	    <form action="mosaique.php">
		<?php
		
		$types = getTypes();
		
		for($i = 0 ; $i < count($types) ; $i++)
		    echo '<input type="submit" class="btn btn-lg btn-success" name="tag" value="'.$types[$i][0].'">';
		
		?>
	    </form>

	</div> <!-- jumbotron  -->

      <footer class="footer">
          <p>&copy; 2017 Rhoban</p>
      </footer>

    </div> <!-- /container -->

  </body>


</html>

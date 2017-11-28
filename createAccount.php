
<?php
session_start();
include "db.php";

if(isset($_POST['formSignUp'])){

    if(empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password1']) || empty($_POST['password2'])){

	echo '<div style="color:rgb(255, 0, 0)">';
	if(empty($_POST['name']))
	    echo "please set your name"; echo "<br>";
	if(empty($_POST['email']))
	    echo "please set your email";echo "<br>";
	if(empty($_POST['password1']) || empty($_POST['password2']))
	    echo "please enter your password twice";echo "<br>";
	echo '</div>';
	
    }
    else{

	$pass1 = $_POST['password1'];
	$pass2 = $_POST['password2'];

	if((!strcmp($pass1, $pass2) == 0 )){
	    echo '<div style="color:rgb(255, 0, 0)">';
	    echo "passwords do not match"; echo "<br>";
	    echo '</div>';	    
	}
	else{//Everything is fine
	    
	    $escapedName = $_POST['name'];
	    if(!userExists($escapedName)){
		
		$email = $_POST['email'];
		
		$escapedPW = $_POST['password1'];
		$salt = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		$saltedPW =  $escapedPW . $salt;
		
		$hashedPW = hash('sha256', $saltedPW);
		insertNewUser($escapedName, $email, $hashedPW, $salt);
		
		$to      = $email;
		$subject = 'Welcome to Rhoban Tagger !';
		$message = 'Hello '.$escapedName.',

 Thank you for signing up to our collaborative tagging platform!

 Happy Tagging!';
		$headers = 'From: RhobanTagger@rhoban.com' . "\r\n" .
			   'Reply-To: noreply@rhoban.com' . "\r\n" .
			   'X-Mailer: PHP/' . phpversion();

		mail($to, $subject, $message, $headers);
	    }
	    else{
		
		echo '<div style="color:rgb(255, 0, 0)">';
		echo "user name already exists, pick another one"; echo "<br>";
		echo '</div>';	    		
	    }
	}
	
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
	<script src="Libs/jquery-2.1.4.min.js"></script>

	
	<title>Create Account</title>
	
	<link href="Style/bootstrap.min.css" rel="stylesheet">
	<link href="Style/jumbotron-narrow.css" rel="stylesheet">

    </head>

    
    <body>
	
	<div class="container">
	    <?php
	    include "header.php";
	    ?>
	    

	    <div class="jumbotron">
		<div class="create_account">
		    <h2>Sign up</h2>
		    <form action="createAccount.php" method="post"  enctype="multipart/form-data">

			<div class="form-group">
			    <label for="usr">Name:</label>
			    <input type="text" class="form-control" name="name">
			</div>
			<div class="form-group">
			    <label for="usr">e-mail</label>
			    <input type="text" class="form-control" name="email">
			</div>
			<div class="form-group">
			    <label for="pwd">Password:</label>
			    <input type="password" class="form-control" name="password1">
			</div>
			<div class="form-group">
			    <label for="pwd">Type password again</label>
			    <input type="password" class="form-control" name="password2">
			</div>
			
			<input type="Submit" name="formSignUp" value="Submit" class="btn btn-lg btn-success" formmethod="post"/>
			
		    </form>
		</div>

	    </div>
	    

	    <footer class="footer">
		<p>&copy; 2017 Rhoban</p>
	    </footer>

	</div> <!-- /container -->

    </body>

    <script type="text/javascript">

    </script>
    
</html>


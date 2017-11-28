<div class="header clearfix">
    <nav>
        <ul class="nav nav-pills pull-right">
            <li id="index" role="presentation" ><a href="index.php">Home</a></li>
            <li id="about" role="presentation" ><a href="About.php" >About</a></li>


	    <?php
	    if(isset($_SESSION["id"])){
		echo '<li style="display:none" id="signin" role="presentation"><a href="#">Sign in</a></li>'; 
		echo '<li id="account" role="presentation"><a href="account.php?userName='.$_SESSION["username"].'" >Account</a></li>';
		echo '<li role="presentation"><a href="logout.php" >Logout</a></li>';
		if($_SESSION["privileges"] == 1){//Admin
		    echo '<li id="backoffice" role="presentation"><a href="backoffice.php">BackOffice</a></li>';
		}
	    }
	    else{
		echo '<li style="display:block" id="signin" role="presentation"><a href="#">Sign in</a></li>';
		echo '<li role="presentation">';
		echo '<div id="signInForm" style="display:none">';

		echo '<form id="account" action="account.php" method="post"  enctype="multipart/form-data">';
		echo '<div class="form-group">';
		echo '<input type="text" class="form-control" name="name" placeholder="username" style="width:8em">';
		echo '</div>';
		echo '<div class="form-group">';
		echo '<input type="password" class="form-control" name="password" placeholder="password" style="width:8em">';
		echo '</div>';

		echo '<input type="Submit" name="formSignIn" value="Submit" class="btn btn-lg btn-success" formmethod="post"/>';
		echo '</form>';

		echo '</div>';
		echo '</li>';
		
		echo '<li id="createaccount" role="presentation"><a href="createAccount.php">Sign up</a></li>';
	    }
	    echo '<li id="leaderboard" role="presentation"><a href="leaderboard.php" >Leaderboard</a></li>';
	    ?>

        </ul>
    </nav>
    <h3 class="text-muted">Rhoban Tagger</h3>
    <h5 class="text-muted" style="margin-top:-0.8em ; color:red" >Beta</h5>
    
    <script type="text/javascript">
     var signInFormVisible = false;
     $('#signin').click(function(){
	 
	 if(signInFormVisible)
	     $('#signInForm').slideUp();
	 else
	     $('#signInForm').slideDown();

	 signInFormVisible = !signInFormVisible;
	 
     }); 

     var pageName = location.pathname;
     
     pageName = pageName.split("/");
     pageName = pageName[2].split('.');
     pageName = pageName[0].toLowerCase();

     $("#"+pageName).addClass("active");
     
    </script>

</div> <!-- header -->



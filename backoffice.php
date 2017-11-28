
<?php

session_start();

if($_SESSION["privileges"] != 1){
    header('Location: index.php');
    exit();
}

include "db.php";

$type=null;
if(isset($_POST['formSubmitBalls'])){
    $type="Balls";
}
else if(isset($_POST['formSubmitPosts'])){
    $type="Posts";
}

if(isset($_POST['formCreateLogSession'])){
    if(empty($_POST['date'])){
	echo '<div style="color:rgb(255, 0, 0)">';
	echo 'Please set a date';
	echo '</div>';
    }
    else{
	
	$date = $_POST['date'];
	$robot = $_POST['robot'];

	if($robot =="0")
	    $robot = $_POST['otherRobot'];
	
	$comment = $_POST['comment'];
	
	insertLogSession($date, $robot, $comment);
	
    }
}

if(isset($_POST['formDeleteLogSession'])){
    $logSessionId = $_POST['logsInputDelete'];
    $typeTag = $_POST['typeTag'];
    
    if($typeTag == "0")
	$typeTag = $_POST['otherTypeDelete'];
    
    deleteLogSession($logSessionId, $typeTag);
}


function makeDir($path){
    return is_dir($path) || mkdir($path);
}

if(isset($_POST['formAddLogs'])){

    if(empty($_FILES['browseInput']['name'][0])){
	echo '<div style="color:rgb(255, 0, 0)">';
	echo 'Please select a file';
	echo '</div>';
    }
    else{
	
	$logSessionId = $_POST['logsInput'];
	$type = $_POST['typeLogs'];
	
	if($type == "0")
	    $type = $_POST['otherType'];
	
	$path = "Images/".$type;

	makeDir($path);
	
	$filename = $_FILES["browseInput"]["name"][0];
	$source = $_FILES["browseInput"]["tmp_name"][0];
	$name = explode(".", $filename); //format the filename for a variable

	$target_path = "TMP/".$name[0];
	
	if(move_uploaded_file($source, $target_path)) { // this block extracts the zip files and moves them to the $dirname directory

	    $zip = new ZipArchive();
	    $x = $zip->open($target_path);
	    if ($x === true) {
		$zip->extractTo("TMP/tmp/");
		$images = array();
		for ($i=0; $i<$zip->numFiles; $i++) {
		    $n = $zip->getNameIndex($i);
		    $imageName = strval(insertNewImageAndGetID($type, $logSessionId));
		    rename("TMP/tmp/".$n, "Images/".$type."/".$imageName.".png");
		}
		$zip->close();

		unlink($target_path);

		
		//Send mail to notify subscribed users
		if(isset($_POST['mailsEnabled'])){//TODO TEST
		    $users = getAllSubscribedUsers();
		    
		    foreach($users as $u){
			
			$to      = $u[2];
			$subject = 'New Images to Tag !';
			$message = 'Hello '.$u[1].',

		       New Images have been added, they are waiting for you to tag them !

		       Happy Tagging!

                       (You can unsubscribe from these e-mails in your account section)';
			$headers = 'From: RhobanTagger@rhoban.com' . "\r\n" .
				   'Reply-To: noreply@rhoban.com' . "\r\n" .
				   'X-Mailer: PHP/' . phpversion();
			
			mail($to, $subject, $message, $headers);		    
		    }
		}

		
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
	<script type="text/javascript" src="Libs/isotope.min.js"></script>
	<script type="text/javascript" src="Libs/imagesLoaded.min.js"></script>
	<link href="Style/style.css" rel="stylesheet">

	<!-- Bootstrap Date-Picker Plugin -->
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>
	<link rel="stylesheet" href="Libs/Slick/slick.css" media="all">
	
	<title>Back Office</title>

	
	<link href="Style/bootstrap.min.css" rel="stylesheet">
	<link href="Style/jumbotron-narrow.css" rel="stylesheet">
	<script src="Libs/Slick/slick.min.js"></script>

	<script type="text/javascript" src="https://cdn.plot.ly/plotly-latest.min.js"></script>

    </head>

    
    <body>
	
	<div class="container">
	    
	    <?php
	    include "header.php";
	    ?>
	    
	    <div id="goManageButton" class="btn btn-lg btn-default" style="margin-bottom:-0.1em;border-bottom-width:0px;border-bottom-left-radius: 0em;border-bottom-right-radius: 0em;"> Manage</div> 
	    <div id="goStatsButton" class="btn btn-lg btn-default" style="margin-bottom:-0.1em;border-bottom-width:0px;border-bottom-left-radius: 0em;border-bottom-right-radius: 0em;"> Statistics</div>
	    <div id="goUncertainButton" class="btn btn-lg btn-default" style="margin-bottom:-0.1em;border-bottom-width:0px;border-bottom-left-radius: 0em;border-bottom-right-radius: 0em;">Uncertain</div> 
	    
	    <div id="statsPage" class="jumbotron" style="display:none">

		<!-- <div id="watchUncertainBallsButton" class="btn btn-lg btn-default" >Uncertain Balls</div>
		     <div id="watchUncertainPostsButton" class="btn btn-lg btn-default" >Uncertain Posts</div> -->
		<div>
		    <h2> Number of active users : <?php echo getNumberOfActiveUsers() ?> </h2>
		</div>
		<div id="stats"> 
			
		    <div id="ballsStats">
			
			<h2>Balls</h2>
			
			<div id="pieBalls">				
			</div>
			
		    </div>

		    <div id="ballsStats">
			<h2>Posts</h2>
			
			<div id="piePosts">
			</div>
		    </div> 
		    
		    </div>

		    <div id="maybe"><!-- Tagged as maybe -->
			
		    </div>

		    <div id="notSure"><!-- People don't seem to agree on these images -->
			
		    </div>
		    
	    </div>

	    <div id="uncertainPage" class="jumbotron" style="display:none">
		
		<?php

		$types = getTypes();
		
		for($i = 0 ; $i < count($types) ; $i++){
		    
		    $type = $types[$i][0];

		    echo "<h3>".$type." Tagged maybe </h3>";
		    
		    $ids = getUncertainImagesActive($type);
		    if(count($ids)>0){
			echo '<div class="grid"> ';
			for($j = 0 ; $j < count($ids) ; $j++){
			    $id = $ids[$j][0];
			    
			    $taggersAndPercentages = getImageTaggersAndPercentage($id);
			    
			    echo '<div class="infosImage" style="visibility:hidden" id="infos'.$id.'" >';
			    foreach($taggersAndPercentages as $key => $value){
				echo getUserName($key)." : ".$value."%<br>";
			    }
			    echo '</div>';
			    

			    echo '<div class="element-item">';
			    
			    echo '<img  imageId="'; echo $id; echo '" value="0" id="'; echo $j; echo '" class="tile'.$type.'" src="Images/';echo $type; echo '/' ; echo $id; echo '.png" >';
			    
			    echo '</div>';
			    


			}
			echo '</div>';/* grid*/
			
		    }
		    else
			echo "No uncertain ".$type." ! ";

		}


		?>
		
	    </div>
		
	    <div id="managePage" class="jumbotron" >

		<div class="select-active-sessions" style ="border-width:1px ; border-style:solid ; border-radius:6px ; margin:0.3em ; padding:0.3em ; background-color:rgb(230, 230, 230) ;  border-color:rgb(230, 230, 230)">

		    <h2>Select active sessions</h2>
		    <label for="sessions">Select the sessions that will be active for users to tag:</label>
                    <ul class="checkbox" id="activeCheckBoxes">
			<li class="list-unstyled" style="text-align:left"><input id="checkAllActive" type="checkbox" >Check All</li>
			
			<?php
			$logSessions = getLogSessions();
			for($i = 0 ; $i < count($logSessions) ; $i++){
			    echo '<li class="list-unstyled" style="text-align:left"><input type="checkbox" class="checkbox-active-sessions" name="activeSessions" value="'.$logSessions[$i]['id'].'" '.(isSessionActive($logSessions[$i]['id'])?"checked":"").'>   '.$logSessions[$i]['date'].' '.$logSessions[$i]['description'].' ('.$logSessions[$i]['robot'].')</li>';
			}
			?>
                    </ul>
		    
		    <p>
			<button id="updateActiveSessions" class="btn btn-lg btn-success" href="#" role="button">Update</button>
		    </p>

		    <div id="succesful-update-active" style="color:rgb(0, 255, 0) ; display:none" > Succesfully updated active sessions </div>
		    
		</div>
		
		<div id="manageLogs">
		    <div class="create_log_session" style ="border-width:1px ; border-color:rgb(230, 230, 230) ; border-style:solid ; border-radius:4px ; margin:0.3em ; padding:0.3em ; background-color:rgb(230, 230, 230)">
			<h2>Create Log Session</h2>
			<form action="backoffice.php" method="post"  enctype="multipart/form-data">
			    <div class="form-group">			
				<label class="control-label" for="date">Date of acquisition</label>
				<input class="form-control" id="date" name="date" placeholder="YYYY/DD/MM" type="text"/>
			    </div>

			    <div class="form-group">
				<label for="robot">Robot</label>
				<select class="form-control" name="robot">
				    <?php
				    $robots = getRobots();
				    for($i =0 ; $i < count($robots) ; $i++)
					echo '<option value="'.$robots[$i][0].'">'.$robots[$i][0].'</option>';
				    ?>
				    <option value="0">Other</option>
				</select>
			    </div>

			    <div id="otherSelectRobot" style="display:none" class="form-group">
				<label for="usr">Other</label>
				<input type="text" class="form-control" name="otherRobot">
			    </div>
			    
			    <div class="form-group">
				<label for="usr">Comment</label>
				<input type="text" class="form-control" name="comment">
			    </div>
			    <input type="Submit" name="formCreateLogSession" value="Submit" class="btn btn-lg btn-success" formmethod="post"/>
			    
			</form>
		    </div><!-- Create log session -->


		    <div class="delete_log_session" style ="border-width:1px ; border-style:solid ; border-radius:4px ; margin:0.3em ; padding:0.3em ; border-color:rgb(230, 230, 230) ; background-color:rgb(230, 230, 230)">
			<h2>Delete Log Session</h2>
			<form action="backoffice.php" method="post"  enctype="multipart/form-data">

			    <div class="form-group">
				
				<label for="robot">Select Session</label>
				<select class="form-control" name="logsInputDelete">
				    <?php
				    $logSessions = getLogSessions();

				    for($i = 0 ; $i < count($logSessions) ; $i++){
					echo '<option value="'; echo $logSessions[$i][0]; echo '">';
					echo $logSessions[$i][1]; echo " "; echo $logSessions[$i][3]; echo " "; echo $logSessions[$i][2];
					echo '</option>';
				    }
				    ?>
				</select>
				
			    </div>

			    <div class="form-group">
				<label for="typeTag">Type of tags</label>
				<select class="form-control" name="typeTag">
				    <option value="All">All</option>
				    <?php
				    $types = getTypes();
				    for($i = 0 ; $i < count($types) ; $i++)
					echo '<option value="'.$types[$i][0].'">'.$types[$i][0].'</option>';
				    ?>
				</select>
			    </div> 

			    <div id="otherSelectDelete" style="display:none" class="form-group">
				<label for="usr">Other</label>
				<input type="text" class="form-control" name="otherTypeDelete">
			    </div>
			    
			    <div id="deleteButton" class="btn btn-lg btn-danger" >Delete</div>
			    
			    <input id ="realDeleteButton" type="Submit" name="formDeleteLogSession" value="Confirm Delete" class="btn btn-lg btn-danger" formmethod="post" style="visibility:hidden"/>
			    
			</form>
		    </div><!-- Delete log session -->
		    
		    <div class="add_logs" style ="border-width:1px ; border-style:solid ; border-radius:6px ; margin:0.3em ; padding:0.3em ; background-color:rgb(230, 230, 230) ; border-color:rgb(230, 230, 230)">
			<h2>Add logs to session</h2>
			<form action="backoffice.php" method="post" enctype="multipart/form-data">
			    
			    <div class="form-group">
				
				<label for="robot">Select Session</label>
				<select class="form-control" name="logsInput">
				    <?php
				    $logSessions = getLogSessions();

				    for($i = 0 ; $i < count($logSessions) ; $i++){
					echo '<option value="'; echo $logSessions[$i][0]; echo '">';
					echo $logSessions[$i][1]; echo " "; echo $logSessions[$i][3]; echo " "; echo $logSessions[$i][2];
					echo '</option>';
				    }
				    ?>
				</select>
				
			    </div>

			    <div class="form-group">
				
				<label for="typeLogs">Type of logs</label>
				<select class="form-control" name="typeLogs">
				    <?php

				    $types = getTypes();
				    for($i = 0 ; $i < count($types) ; $i++)
					echo '<option value="'.$types[$i][0].'">'.$types[$i][0].'</option>';
				    ?>
				    <option value="0">Other</option>
				</select>
				
				<div id="otherSelect" style="display:none" class="form-group">
				    <label for="usr">Other</label>
				    <input type="text" class="form-control" name="otherType">
				</div>
				
				
			    </div>
			    
			    <div class="form-group">
				
				<label for="browse">Select a .zip</label>
				<label class="btn btn-default btn-file" name="browse">
				    Browse <input type="file" style="display: none;" multiple="" name="browseInput[]" id="logs">
				</label>
				
			    </div>

			    <ul class="checkbox">
				<li style="text-align:left"><input id="mailsEnabled" name ="mailsEnabled"type="checkbox" checked="true" >Send mail</li>
			    </ul>
			    
			    <input type="Submit" name="formAddLogs" value="Submit" class="btn btn-lg btn-success" formmethod="post"/>
			</form>

		    </div>
		    <div class="get_data" style ="border-width:1px ; border-style:solid ; border-radius:6px ; margin:0.3em ; padding:0.3em ; background-color:rgb(230, 230, 230) ;  border-color:rgb(230, 230, 230)">

			<h2>Get data</h2>
			<label for="sessions">Select the logs to download:</label>
                        <ul class="checkbox" id="downloadCheckBoxes">
			    <li class="list-unstyled" style="text-align:left"><input id="checkAllDownload" type="checkbox" >Check All</li>
			    
			    <?php
			    $logSessions = getLogSessions();
			    for($i = 0 ; $i < count($logSessions) ; $i++){
				echo '<li class="list-unstyled" style="text-align:left"><input type="checkbox" class="checkbox-sessions" name="sessions" value="'.$logSessions[$i]['id'].'">   '.$logSessions[$i]['date'].' '.$logSessions[$i]['description'].' ('.$logSessions[$i]['robot'].')</li>';
			    }
			    ?>
                        </ul>
			
			<div class="form-group">
			    
			    <label for="typesDownload">Type of logs</label>
			    <select class="form-control" name="typesDownloadSelect">
				<?php
				$types = getTypes();
				for($i = 0 ; $i < count($types) ; $i++)
				    echo '<option value="'.$types[$i][0].'">'.$types[$i][0].'</option>';
				?>
			    </select> 
			    
			</div>

			<p>
			    <button id="getData" class="btn btn-lg btn-success" href="#" role="button">Get Data</button>
			    <img id="loadType" style="width:8%; display:none" src="assets/ajax-loader.gif">
			</p>			    

			<?php
			for($i = 0 ; $i < count($types) ; $i++)
			    echo '<a style="visibility:hidden" href="Images/data'.$types[$i][0].'.zip" id="hidden-download-'.$types[$i][0].'">download zipfile</a>';
			?> 
			
		    </div>

		</div>
	    </div><!-- Jumbotron -->
	    

	    <footer class="footer">
		<p>&copy; 2017 Rhoban</p>
	    </footer>

	</div> <!-- /container -->

    </body>



    <script type="text/javascript"> 
     
     $('#goManageButton').css('background-color','#eeeeee');
     $('#goManageButton').css('color','#000');
     
     $('#goStatsButton').css('background-color','#fff');
     $('#goStatsButton').css('color','#afafaf');

     $('#goUncertainButton').css('background-color','#fff');
     $('#goUncertainButton').css('color','#afafaf');

     $('#goManageButton').addClass('disabled');
     $('#goStatsButton').removeClass('disabled');
     $('#goUncertainButton').removeClass('disabled');
     
     $('#goStatsButton').click(function(){
	 $('#managePage').hide();
	 $('#uncertainPage').hide();
	 $('#statsPage').show();
	 $('#goStatsButton').addClass('disabled');
	 $('#goManageButton').removeClass('disabled');
	 $('#goUncertainButton').removeClass('disabled');

	 $('#goStatsButton').css('background-color','#eeeeee');
	 $('#goStatsButton').css('color','#000');
	 $('#goManageButton').css('background-color','#fff');
	 $('#goManageButton').css('color','#afafaf');
	 
	 $('#goUncertainButton').css('background-color','#fff');
	 $('#goUncertainButton').css('color','#afafaf');
     });
     
     $('#goManageButton').click(function(){
	 $('#statsPage').hide();
	 $('#uncertainPage').hide();
	 $('#managePage').show();
	 $('#goManageButton').addClass('disabled');
	 $('#goStatsButton').removeClass('disabled');
	 $('#goUncertainButton').removeClass('disabled');

	 $('#goManageButton').css('background-color','#eeeeee');
	 $('#goManageButton').css('color','#000');
	 $('#goStatsButton').css('background-color','#fff');
	 $('#goStatsButton').css('color','#afafaf');
	 $('#goUncertainButton').css('background-color','#fff');
	 $('#goUncertainButton').css('color','#afafaf');
	 
     });

     $('#goUncertainButton').click(function(){
	 $('#statsPage').hide();
	 $('#managePage').hide();
	 $('#uncertainPage').show();
	 $('#goManageButton').removeClass('disabled');
	 $('#goStatsButton').removeClass('disabled');
	 $('#goUncertainButton').addClass('disabled');

	 $('#goUncertainButton').css('background-color','#eeeeee');
	 $('#goUncertainButton').css('color','#000');
	 $('#goStatsButton').css('background-color','#fff');
	 $('#goStatsButton').css('color','#afafaf');
	 $('#goManageButton').css('background-color','#fff');
	 $('#goManageButton').css('color','#afafaf');

	 var $grid = $('.grid').isotope({
	     itemSelector: '.element-item',
	     layoutMode: 'fitRows'
	 });

	 $('img').mousemove(function(event){
	     $('[id^=infos]').css("visibility", "hidden");
   	     id = $(this).attr("imageId");
	     $('#infos'+id).css("visibility", "visible");
	     $('#infos'+id).css('left', -170);
	     $('#infos'+id).css('top',$(this).parent().position().top);

	 });


     });
     

     
     /* $('#watchUncertainBallsButton').click(function(){
	window.location.href = "mosaique.php?tag=uncertainBalls";
      * });*/
     
     
     $('select[name=robot]').change(function(e){
	 if ($('select[name=robot]').val() == '0'){
	     $('#otherSelectRobot').show();
	 }
	 else{
	     $('#otherSelectRobot').hide();
	 }
     });
     
     $('select[name=typeLogs]').change(function(e){
	 if ($('select[name=typeLogs]').val() == '0'){
	     $('#otherSelect').show();
	 }
	 else{
	     $('#otherSelect').hide();
	 }
     });
     
     $('select[name=typeTag]').change(function(e){
	 if ($('select[name=typeTag]').val() == '0'){
	     $('#otherSelectDelete').show();
	 }
	 else{
	     $('#otherSelectDelete').hide();
	 }
     });
     
     $('#checkAllDownload').click(function(){
	 $('#downloadCheckBoxes :input').not(this).prop('checked', this.checked);
     });

     $('#checkAllActive').click(function(){
	 $('#activeCheckBoxes :input').not(this).prop('checked', this.checked);
     });
     
     $('#deleteButton').click( function(){
	 $('#realDeleteButton').css("visibility", "visible");
     }); 
     
     $(document).ready(function(){
	 
	 var date_input=$('input[name="date"]'); //our date input has the name "date"
	 var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
	 var options={
	     format: 'yyyy/dd/mm',
	     container: container,
	     todayHighlight: true,
	     autoclose: true,
	 };
	 date_input.datepicker(options);
	 
	 var data;

	 $.ajax({
	     url : 'getPlotData.php', // your php file
	     type : 'GET', // type of the HTTP request
	     success : function(data){
		 data = jQuery.parseJSON(data);

		 var nbBallsTagged = data[0];
		 var nbImageBalls = data[1];
		 var nbPostsTagged = data[2];
		 var nbImagePosts = data[3];

		 var percentage = (nbImageBalls/nbBallsTagged)*100
		 console.log(percentage);
		 var data = [{
		     values: [nbImageBalls-nbBallsTagged, nbBallsTagged],
		     labels: ['Not tagged Balls', 'Tagged Balls'],
		     type: 'pie'
		 }];

		 var data2 = [{
		     values: [nbImagePosts-nbPostsTagged, nbPostsTagged],
		     labels: ['Not tagged Posts', 'Tagged Posts'],
		     type: 'pie'
		 }];

		 var layout = {
		     paper_bgcolor:"rgba(0, 0, 0, 0)",
		     showlegend:false
		 };

		 Plotly.newPlot('pieBalls', data, layout, {displayModeBar: false});
		 Plotly.newPlot('piePosts', data2, layout, {displayModeBar: false});
		 
	     }
	 });
     }); 
     
     $('#updateActiveSessions').on('click',function(){

	 var sessions = $('input:checkbox:checked.checkbox-active-sessions').map(function () {
	     return this.value;
         }).get();
	 
         var postData = {sessions:sessions };
	 $.ajax({
	     type: "POST",
	     url: "updateActiveSessions.php",
	     data: postData,
	     success: function(msg) {
		 $("#succesful-update-active").show();
	     }
         });
     });
     
     $('#getData').on('click',function(){
	 $('#loadType').show();
	 $('#getData').prop('disabled', true);

	 var sessions = $('input:checkbox:checked.checkbox-sessions').map(function () {
	     return this.value;
         }).get();

	 var typeSelected = $('select[name=typesDownloadSelect]').val();
	 
         var postData = { id: "foo", nbYes: "foo", type:typeSelected, sessions:sessions };
	 $.ajax({
	     type: "POST",
	     url: "getData.php",
	     data: postData,
	     success: function(msg) {
		 $("#hidden-download-"+typeSelected)[0].click(); 
		 $('#loadType').hide();
		 $('#getData').prop('disabled', false);
	     }
         });
     });
     
     $('#getDataBalls').on('click',function(){
	 $('#loadBalls').show();
	 $('#getDataBalls').prop('disabled', true);
	 
         var sessions = $('input:checkbox:checked.checkbox-sessions').map(function () {
	     return this.value;
         }).get();
         var postData = { id: "foo", nbYes: "foo", type:"Balls", sessions:sessions };
	 $.ajax({
	     type: "POST",
	     url: "getData.php",
	     data: postData,
	     success: function(msg) {
                 $("#hidden-download-Balls")[0].click(); 
		 $('#loadBalls').hide();
		 $('#getDataBalls').prop('disabled', false);
	     }
         });
     });
     
     $('#getDataPosts').on('click',function(){
	 $('#loadPosts').show();
	 $('#getDataPosts').prop('disabled', true);
         var sessions = $('input:checkbox:checked.checkbox-sessions').map(function () {
	     return this.value;
         }).get();
         var postData = { id: "foo", nbYes: "foo", type:"Posts", sessions:sessions };
	 $.ajax({
	     type: "POST",
	     url: "getData.php",
	     data: postData,
	     success: function(msg) {
                 $("#hidden-download-Posts")[0].click(); 
		 $('#loadPosts').hide();
		 $('#getDataPosts').prop('disabled', false);
	     }
         });
     });
     

     
    </script>
    
</html>

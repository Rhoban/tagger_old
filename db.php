<?php

//--------------------
//-----Database API---
//--------------------

$GLOBALS['db'] = new PDO(
    'host',
    'username',
    'password'
);


$GLOBALS['thresholdTagged'] = 2;
$GLOBALS['thresholdTaggedSup'] = 5;
$GLOBALS['percentageConsensus'] = 0.80;
$GLOBALS['thresholdErrorRatio'] = 1.;//TODO TUNE THAT (for now, let all the users pass), the lowest, the better


function getRandomId($type){

    $str = "SELECT Images.id FROM Images LEFT JOIN Tagging on Images.id = Tagging.idImage where Images.type=:type GROUP BY Images.id HAVING count(*) < :threshold ORDER BY rand() DESC LIMIT 1";
    $id = "null";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'type' => $type, 'threshold' => $GLOBALS['thresholdTagged']));    
    $result = $req->fetchAll();
    foreach($result as $row){
 	$id = $row[0];
    }
    return $id;
    
}

function newGet16RandomIds($type, $privileges){
    
    /* $str = "SELECT id
     * FROM Images
     * WHERE
     * type=:type
     * AND
     * (nbTimesTagged < :threshold
     * OR
     * (
     *    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) <= :consensus
     *    AND
     *    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=0)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) <= :consensus
     *    AND
     *    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=2)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) <= :consensus
     * ))
     * ORDER BY rand() LIMIT 16";*/

    $str = "SELECT * FROM ( SELECT id
    FROM Images
    WHERE
    type=:type
    AND
    session in (SELECT id FROM LogSessions WHERE active=1)
    AND
    nbTimesTagged < :thresholdSup
    AND
    (nbTimesTagged < :threshold
    OR
    (
       ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) <= :consensus
       AND
       ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=0)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) <= :consensus
       AND
       ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=2)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) <= :consensus
    ))
    ORDER BY nbTimesTagged DESC LIMIT 16
    ) as t1 ORDER BY rand() ";
    
    $req = $GLOBALS['db']->prepare($str);

    $req->execute( array( 'type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus'], 'thresholdSup' => $GLOBALS['thresholdTaggedSup']));   
    $result = $req->fetchAll();
    
    if($privileges == 1)//Trusted user (does not tag images already tagged more than :threshold times
	return $result;
    else{//normal user
	if(count($result) == 16)//Still not tagged images
	    return $result;
	else{//Full random
	    
	    $str = "SELECT id
       FROM Images
       WHERE
       type=:type
       ORDER BY rand() LIMIT 16";
	    
	    $req = $GLOBALS['db']->prepare($str);

	    $req->execute( array( 'type' => $type));
	    
	    $result = $req->fetchAll();
	    
	    return $result;
	}
    }
}

function get16RandomIdsTuto($type){

    //Every images tagged more than :threshold times that have a positive and negative consensus that is more than 95% (to be tuned)
    $str = "SELECT id
    FROM Images
    WHERE
    type=:type
    AND
    nbTimesTagged >= :threshold
    AND
    (
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
    OR
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=0)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
    )
ORDER BY rand() LIMIT 16";
    
    
    $req = $GLOBALS['db']->prepare($str);
    
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus'] )); 
    $result = $req->fetchAll();//All images tagged at least :threshold times    

    return $result;     
}

function getImageConsensus($idImage){
    
    $str = "SELECT nbTimesTagged FROM Images WHERE id=:idImage";
    $req = $GLOBALS['db']->prepare($str);

    $req->execute(array('idImage' => $idImage));
    $nbTimesTagged = $req->fetchAll();

    if($nbTimesTagged < $GLOBALS['thresholdTagged'])//if not tagged enough
	return -1;//No consensus 
    
    $str = "(SELECT count(*) FROM Tagging WHERE idImage=:idImage AND value=1)";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute(array('idImage' => $idImage));
    $nbPos = $req->fetchAll();    

    $str = "(SELECT count(*) FROM Tagging WHERE idImage=:idImage AND value=0)";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute(array('idImage' => $idImage));
    $nbNeg = $req->fetchAll();
    
    $str = "(SELECT count(*) FROM Tagging WHERE idImage=:idImage)";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute(array('idImage' => $idImage));
    $total = $req->fetchAll();

    $consensusValuePos = intval($nbPos[0][0])/intval($total[0][0]);
    $consensusValueNeg = intval($nbNeg[0][0])/intval($total[0][0]);

    
    if($consensusValuePos > $GLOBALS['percentageConsensus'])//Image is positive
	return 1;
    if($consensusValueNeg > $GLOBALS['percentageConsensus'])//Image is negative
	return 0;

    return -1;//No consensus ( <80% in both cases )
    
}

function decreaseNbTimesTaggedImage($idImage){
    $str = "UPDATE  `tagger`.`Images` SET  `nbTimesTagged` = `nbTimesTagged`-1 WHERE  `Images`.`id` =:idImage";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'idImage' => $idImage));    
}

function decreaseNbTimesTaggedImageNTimes($idImage, $nb){
    $str = "UPDATE  `tagger`.`Images` SET  `nbTimesTagged` = `nbTimesTagged`-:nb WHERE  `Images`.`id` =:idImage";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'idImage' => $idImage, 'nb' => $nb));    
}

function updateAnswer($idImage, $idUser, $ans, $type){

    $now = date("Y-d-m H:i:s");
    
    $str = "INSERT INTO `tagger`.`Tagging` (`date`, `type`, `value`, `idImage`, `idUser`) VALUES (NOW(), :type, :value, :idImage, :idUser);";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'value' => $ans, 'idImage' => $idImage, 'idUser' => $idUser));

    $str = "UPDATE  `tagger`.`Images` SET  `nbTimesTagged` = `nbTimesTagged`+1 WHERE  `Images`.`id` =:idImage";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'idImage' => $idImage));
    
}

function getNbEntries($type){
    $str = "SELECT COUNT(id) FROM Images WHERE type=:type";
    $req = $GLOBALS['db']->prepare($str);

    $req->execute( array( 'type' => $type));    
    $result = $req->fetchAll();
    $nb = $result[0][0];
    return $nb;
}

function getNbEntriesActive($type){
    $str = "SELECT COUNT(id) FROM Images WHERE type=:type
    AND
    session in (SELECT id FROM LogSessions WHERE active=1)";
    $req = $GLOBALS['db']->prepare($str);

    $req->execute( array( 'type' => $type));    
    $result = $req->fetchAll();
    $nb = $result[0][0];
    return $nb;
}

function getLogSessions(){
    $str = "SELECT * FROM LogSessions";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();
    $result = $req->fetchAll();

    return $result;
}

function insertLogSession($date, $robot, $comment){
    $str = "INSERT INTO  `tagger`.`LogSessions` (`date`, `description`, `robot`) VALUES (:date, :comment, :robot);";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'date' => $date, 'comment' => $comment, 'robot' => $robot));    
}

function deleteLogSession($id, $typeTag){
    
    if(strcmp($typeTag, "All") == 0){
	$str = "DELETE FROM LogSessions WHERE id=:id";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array( 'id' => $id));

	$str = "DELETE FROM Tagging WHERE idImage IN (SELECT id FROM Images WHERE session=:id)";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array( 'id' => $id));

	$str = "SELECT id, type FROM Images WHERE session=:id";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array( 'id' => $id));
	$results = $req->fetchAll();
    }
    else{
	$str = "DELETE FROM Tagging WHERE idImage IN (SELECT id FROM Images WHERE session=:id and type=:type)";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array( 'id' => $id, 'type' => $typeTag));

	$str = "SELECT id, type FROM Images WHERE session=:id and type=:type";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array( 'id' => $id, 'type' => $typeTag));
	$results = $req->fetchAll();
    }
    
    foreach($results as $result){
	$imageId = $result[0];
	$type = $result[1];
	$path = "Images/".$type."/".$imageId.".png";
	unlink($path);
    }

    if(strcmp($typeTag, "All") == 0){
	$str = "DELETE FROM Images WHERE session=:id";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array( 'id' => $id));
    }
    else{
	$str = "DELETE FROM Images WHERE session=:id and type=:type";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array( 'id' => $id, 'type' => $typeTag));

	
	$str = "SELECT count(*) from Images where session=:id";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array('id' => $id));
	$results = $req->fetchAll();
	
	if($results[0][0] == 0){//S'il n'y a plus d'images correspondant à cette session
	    echo $results[0][0];
	    /* echo "no more images";*/
	    $str = "DELETE FROM LogSessions WHERE id=:id";
	    $req = $GLOBALS['db']->prepare($str);
	    $req->execute( array( 'id' => $id));
	}
    }
    
}

function deleteUserAndTags($id){
    
    $str = "SELECT idImage, count(*) FROM Tagging WHERE idUser=:id GROUP BY idImage";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('id' => $id));
    $results = $req->fetchAll();

    foreach($results as $r){
	decreaseNbTimesTaggedImageNTimes($r[0], $r[1]);
    }
    
    $str = "DELETE FROM Tagging WHERE idUser=:id";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'id' => $id));
    
    $str = "DELETE FROM Users WHERE id=:id";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'id' => $id));
    
}


function insertNewUser($pseudo, $mail, $password, $salt){
    
    $str = "INSERT INTO  `tagger`.`Users` (`pseudo`, `mail`, `password`, `salt`, `subscribed`) VALUES (:pseudo, :mail, :password, :salt, :subscribed);";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'pseudo' => $pseudo, 'mail' => $mail, 'password' => $password, 'salt' => $salt, 'subscribed' => 1));
}

function getScore($id){
    $str = "SELECT score FROM Users where id = :id";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'id' => $id));
    $result = $req->fetchAll();
    return $result[0][0];
}

function getUser($name, $hashedPW){
    $str = "SELECT id, pseudo, mail, rank, privileges, score, subscribed FROM Users where pseudo = :name and password = :pw ";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'name' => $name, 'pw' => $hashedPW));
    $result = $req->fetchAll();
    return $result;
}

function getUserInfos($name){
    $str = "SELECT id, pseudo, mail, rank, privileges, score, subscribed FROM Users where pseudo = :name";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'name' => $name));
    $result = $req->fetchAll();
    return $result;
}

function getUserName($id){
    $str = "SELECT pseudo FROM Users where id = :id";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'id' => $id));
    $result = $req->fetchAll();
    return $result[0][0];
}

function getAllUsers(){
    $str = "SELECT id, pseudo, mail, rank, privileges, score FROM Users ORDER BY score DESC ";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();
    $result = $req->fetchAll();
    return $result;
}

function getAllSubscribedUsers(){
    $str = "SELECT id, pseudo, mail, rank, privileges, score FROM Users where subscribed=1 ORDER BY score DESC ";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();
    $result = $req->fetchAll();
    return $result;
}

function getSalt($name){
    $str = "SELECT salt FROM Users where pseudo = :name ";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'name' => $name));
    $result = $req->fetchAll();
    return $result[0][0];
}

function userExists($name){
    $str = "SELECT id FROM Users where pseudo = :name ";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'name' => $name));
    $result = $req->fetchAll();
    
    if($result == null)
	return false;
    else
	return true;
    
}

function updateUserTrainingData($userId, $error){
    
    if($error == true){
	$str = "UPDATE `tagger`.`Users` SET  `errors` = `errors`+1  WHERE `Users`.`id` =:id;";
	$req = $GLOBALS['db']->prepare($str);
	$req->execute( array('id' => $userId));	
    }
    
    $str = "UPDATE `tagger`.`Users` SET  `total` = `total`+1  WHERE `Users`.`id` =:id;";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('id' => $userId));		
}

//Error if against consensus (and tagged at least :threshold times)
function updateUserErrors($userId, $nbErrors, $nbTotal){
    
    $str = "UPDATE `tagger`.`Users` SET  `errors` = `errors`+:nbErrors  WHERE `Users`.`id` =:id;";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('id' => $userId, 'nbErrors' => $nbErrors));	
    
    $str = "UPDATE `tagger`.`Users` SET  `total` = `total`+:nbTotal  WHERE `Users`.`id` =:id;";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('id' => $userId, 'nbTotal' => $nbTotal));		
}

function getUserErrorRatio($userId){
    
    $str = "SELECT errors from Users where id = :id";
    $req = $GLOBALS['db']->prepare($str);    
    $req->execute( array( 'id' => $userId));
    $result = $req->fetchAll();
    $errors = $result[0][0];

    $str = "SELECT total from Users where id = :id";
    $req = $GLOBALS['db']->prepare($str);    
    $req->execute( array( 'id' => $userId));
    $result = $req->fetchAll();
    $total = $result[0][0];

    if($total != 0)
	return $errors/$total;
    else
	return 0;
}

function getUsersWithGoodRatio(){

    
    
    $str = "SELECT id from Users";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();
    $result = $req->fetchAll();

    $users = array();
    
    foreach($result as $r){
	$user = getUserInfos($r[0]);
	if(getUserErrorRatio($r[0]) < $GLOBALS['thresholdErrorRatio'] || $user[0][4] == 1)
	    array_push($users, $r[0]);
    }
    /* ^[^ ].*[^;]$*/

    return $users; 
}

function updateUserScore($id, $val){

    $changeRank = false;
    
    $str = "SELECT score, rank FROM Users where id = :id ";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'id' => $id));
    $result = $req->fetchAll();

    $currentScore = $result[0][0];
    $currentRank = $result[0][1];
    
    $newScore = $currentScore + $val;

    if($newScore > 300 && $currentRank == 0){//Silver
	$newRank = 1;
	$changeRank = true;
    }
    elseif($newScore > 1000  && $currentRank == 1){//Gold
	$newRank = 2;
	$changeRank = true;
    }
    elseif($newScore > 2000 && $currentRank == 2){//Plat
	$newRank = 3;
	$changeRank = true;
    }
    elseif($newScore > 5000  && $currentRank == 3){//Diam
	$newRank = 4;
	$changeRank = true;
    }
    elseif($newScore > 10000  && $currentRank == 4){//Master
	$newRank = 5;
	$changeRank = true;
    }
    elseif($newScore > 50000 && $currentRank == 5){//GM
	$newRank = 6;
	$changeRank = true;
    }
    
    if($changeRank == true)
	$str = "UPDATE `tagger`.`Users` SET  `score` = `score`+:val, `rank` = :newRank  WHERE `Users`.`id` =:id;";
    else 
	$str = "UPDATE `tagger`.`Users` SET  `score` = `score`+:val WHERE `Users`.`id` =:id;";
    
    $req = $GLOBALS['db']->prepare($str);
    
    if($changeRank == true)
	$req->execute( array( 'val' => $val, 'id' => $id, 'newRank' => $newRank));
    else
	$req->execute( array( 'val' => $val, 'id' => $id));
}

function insertNewImageAndGetID($type, $logSessionId){

    $str = "INSERT INTO `tagger`.`Images` (`type`, `session`) VALUES(:type, :logSessionId);";
    
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'type' => $type, 'logSessionId' => $logSessionId));


    //Last entry
    $str = "SELECT id FROM Images ORDER BY id DESC LIMIT 1";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();
    $result = $req->fetchAll();
    foreach($result as $row){
	$id = $row[0];
    }
    return $id; 
    
}

function getNbImagesTagged($type){

    $str = "SELECT count(*)
FROM Images
WHERE
type=:type
AND
nbTimesTagged >= :threshold
AND
(
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
   OR
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=0)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
    OR
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=2)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
)";
    
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus']) );
    $result = $req->fetchAll();
    
    $nbImagesTagged = $result[0][0];
    return $nbImagesTagged;
}

function getNbImagesTaggedActive($type){

    $str = "SELECT count(*)
FROM Images
WHERE
type=:type
AND
session in (SELECT id FROM LogSessions WHERE active=1)
AND
nbTimesTagged >= :threshold
AND
(
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
   OR
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=0)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
    OR
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=2)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
)";
    
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus']) );
    $result = $req->fetchAll();
    
    $nbImagesTagged = $result[0][0];
    return $nbImagesTagged;
}



function getPercentageImagesTagged($type){
    
    $str = "SELECT count(r1.idImage) from (SELECT idImage from Tagging where type=:type group by idImage having count(*) >=:threshold) as r1";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged']) );
    $result = $req->fetchAll();
    
    $nbImagesTagged = $result[0][0];

    $str = "select count(*) from Images where type=:type";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute(array('type' => $type));
    $result = $req->fetchAll();
    $nbImagesTotal = $result[0][0];

    $percentage = ($nbImagesTagged/$nbImagesTotal)*100;
    return $percentage;
    
}

function getMaybe($type){
    $str = "SELECT idImage FROM Tagging where value=2 group by idImage";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();
    $result = $req->fetchAll();
    return $result;
}

function updateActiveSessions($sessions){

    $str = "UPDATE  `tagger`.`LogSessions` SET  `active` = 0";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();

    $str = "UPDATE  `tagger`.`LogSessions` SET  `active` = 1 WHERE `LogSessions`.`id` IN (".implode(',', $sessions).")";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();    

}

function isSessionActive($id){


    $str = "select active from LogSessions where id=:id";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute(array('id' => $id));
    $result = $req->fetchAll();
    $active = $result[0][0];

    if($active==1)
	return true;

    return false;
}

function getJSON($type, $sessions){

    //Every images tagged more than :threshold times that have a positive consensus that is higher than 95% ( to be tuned)
    //OLD VERSION WITHOUT CHECKING FOR USER RATIO (IN CASE OF DISASTER)
    /* $str = "SELECT id
     *    FROM Images
     *    WHERE
     *    type=:type
     *    AND
     *    session IN (".implode(',', $sessions).")
     *    AND
     *    nbTimesTagged >= :threshold
     *    AND
     *    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus";*/

    //Every images tagged more than :threshold times that have a positive consensus that is higher than 95% ( to be tuned) AND of which the tagger (user) has a good consensus score
    
    $str = "SELECT id
FROM Images
WHERE
type=:type
AND
session IN (".implode(',', $sessions).")
AND
    nbTimesTagged >= :threshold
    AND

    (
	(SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1 AND idUser IN 
	    
 	    (SELECT id FROM Users
 		WHERE
 		total = 0
 		or
 		((errors/total) < :thresholdErrorRatio)
 		or 
 		privileges = 1
 	    )
 	    
	)
	
	/
	
	(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id AND idUser IN
 	    
 	    (SELECT id FROM Users
 		WHERE
 		total = 0
 		or
 		((errors/total) < :thresholdErrorRatio)
 		or 
 		privileges = 1
 	    )
 	    
	)
    )
    > :consensus"; 
    
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus'], 'thresholdErrorRatio' => $GLOBALS['thresholdErrorRatio'] ));
    $positivelyTaggedImages_temp = $req->fetchAll();
    
    $positivelyTaggedImages = array();
    for($i = 0 ; $i < count($positivelyTaggedImages_temp) ; $i++)
	array_push($positivelyTaggedImages, $positivelyTaggedImages_temp[$i][0]); 
    
    
    //OLD VERSION WITHOUT CHECKING FOR USER RATIO (IN CASE OF DISASTER)    
    /* $str = "SELECT id
     *    FROM Images
     *    WHERE
     *    type=:type
     *    AND
     *    session IN (".implode(',', $sessions).")
     *    AND
     *    nbTimesTagged >= :threshold
     *    AND
     *    (
     *    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
     *    OR
     *    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=0)/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) > :consensus
     *    )";*/

    //Every images tagged more than :threshold times that have a positive and negative consensus that is higher than 95% ( to be tuned) AND of which the tagger (user) has a good consensus score    
    $str = "SELECT id
 FROM Images
 WHERE
 type=:type
 AND
 session IN (".implode(',', $sessions).")
 AND
 nbTimesTagged >= :threshold
 AND
 (
 ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=1 AND idUser IN 

       (
       SELECT id FROM Users
       WHERE
       total = 0
       or
       ((errors/total) < :thresholdErrorRatio)
       or 
       privileges = 1
       )

       )/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id AND idUser IN 

       (
       SELECT id FROM Users
       WHERE
       total = 0
       or
       ((errors/total) < :thresholdErrorRatio)
       or 
       privileges = 1
       )

       )
       )> :consensus
       OR
       ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=0 AND idUser IN
       
       (
       SELECT id FROM Users
       WHERE
       total = 0
       or
       ((errors/total) < :thresholdErrorRatio)
       or 
       privileges = 1
       )
      
       )
       /
       (SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id AND idUser IN
       
       (
       SELECT id FROM Users
       WHERE
       total = 0
       or
       ((errors/total) < :thresholdErrorRatio)
       or 
       privileges = 1
       )
       
       )
       ) > :consensus
 )";

    
    $req = $GLOBALS['db']->prepare($str);
    
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus'], 'thresholdErrorRatio' => $GLOBALS['thresholdErrorRatio'] )); 
    $allImages_temp = $req->fetchAll();//All images tagged at least :threshold times
    
    $allImages = array();
    for($i = 0 ; $i < count($allImages_temp) ; $i++)
	array_push($allImages, $allImages_temp[$i][0]); 
    
    $jsonData = json_encode($positivelyTaggedImages);
    $path = "Images/".$type."/data.json";
    $file = fopen($path, "w") or die("Unable to open file!");
    fwrite($file, $jsonData);
    fclose($file);    
    
    $zip = new ZipArchive;
    $zipPath = 'Images/data'.$type.'.zip';
    $zip->open($zipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE );
    
    foreach($allImages as $image){
	$path =  "Images/".$type."/".$image.".png";
	$zip->addFile($path);
    }
    $path =  "Images/".$type."/data.json";
    $zip->addFile($path);
    $zip->close();

}

function getImagesOutOfConsensus($type){
    
    $str = "SELECT id
    FROM Images
    WHERE
    type=:type
    AND
    nbTimesTagged >= :threshold
    AND
    ((SELECT count(*) FROM Tagging WHERE idImage=Images.id AND (value=1 OR value=0) )/(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id)) <= :consensus";
    
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus']));
    $result = $req->fetchAll();
    return $result;
    
}

function getTaggingHistory($idUser, $type){
    
    $str = "SELECT date, count(*) from Tagging where idUser=:idUser and type=:type GROUP BY DAY(date) ORDER BY date";

    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('idUser' => $idUser, 'type' => $type));
    $result = $req->fetchAll();
    return $result;
    
}

function setSubscription($idUser, $value){
    $str = "UPDATE  `tagger`.`Users` SET  `subscribed` = :value WHERE  `Users`.`id` =:idUser";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('value' =>$value, 'idUser' => $idUser));    
}

function deleteLast16TagsUser($idUser){
    $str = "DELETE FROM Tagging WHERE idUser=:idUser ORDER BY id DESC LIMIT 16";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'idUser' => $idUser));    
}

function getTypes(){
    $str = "SELECT type FROM Images group by type";
    $req = $GLOBALS['db']->prepare($str);

    $req->execute();    
    $result = $req->fetchAll();
    
    return $result;
}

function getRobots(){
    $str = "SELECT robot FROM LogSessions group by robot";
    $req = $GLOBALS['db']->prepare($str);

    $req->execute();    
    $result = $req->fetchAll();
    
    return $result;    
}

function getImageTaggersAndPercentage($id){
    
    $str = "SELECT idUser, count(*) FROM Tagging WHERE idImage = :id GROUP BY idUser";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'id' => $id));
    
    $users = $req->fetchAll();

    $str = "SELECT count(*) FROM Tagging WHERE idImage = :id";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array( 'id' => $id));

    $nbTags = $req->fetchAll();
    $nbTags = $nbTags[0][0];

    $ret = array();

    foreach($users as $u){
	$id = $u[0];
	$nb = $u[1];

	$ret[$id] = round(($nb/$nbTags)*100, 2);
    }

    return $ret;
    
}

function getUncertainImages($type){
    
    $str = "SELECT id
FROM Images
WHERE
type=:type
AND
    nbTimesTagged >= :threshold
    AND

    (
	(SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=2 AND idUser IN 
	    
 	    (SELECT id FROM Users
 		WHERE
 		total = 0
 		or
 		((errors/total) < :thresholdErrorRatio)
 		or 
 		privileges = 1
 	    )
 	    
	)
	
	/
	
	(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id AND idUser IN
 	    
 	    (SELECT id FROM Users
 		WHERE
 		total = 0
 		or
 		((errors/total) < :thresholdErrorRatio)
 		or 
 		privileges = 1
 	    )
 	    
	)
    )
    > :consensus"; 
    
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus'], 'thresholdErrorRatio' => $GLOBALS['thresholdErrorRatio'] ));
    $res = $req->fetchAll();

    return $res;
    
}

function getUncertainImagesActive($type){
    
    $str = "SELECT id
FROM Images
WHERE
type=:type
AND
session in (SELECT id FROM LogSessions WHERE active=1)
AND
    nbTimesTagged >= :threshold
    AND

    (
	(SELECT count(*) FROM Tagging WHERE idImage=Images.id AND value=2 AND idUser IN 
	    
 	    (SELECT id FROM Users
 		WHERE
 		total = 0
 		or
 		((errors/total) < :thresholdErrorRatio)
 		or 
 		privileges = 1
 	    )
 	    
	)
	
	/
	
	(SELECT COUNT(*) FROM Tagging WHERE idImage=Images.id AND idUser IN
 	    
 	    (SELECT id FROM Users
 		WHERE
 		total = 0
 		or
 		((errors/total) < :thresholdErrorRatio)
 		or 
 		privileges = 1
 	    )
 	    
	)
    )
    > :consensus"; 
    
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('type' => $type, 'threshold' => $GLOBALS['thresholdTagged'], 'consensus' => $GLOBALS['percentageConsensus'], 'thresholdErrorRatio' => $GLOBALS['thresholdErrorRatio'] ));
    $res = $req->fetchAll();

    return $res;
    
}

function updateUserLastSeen($userId){
    $str = "UPDATE `tagger`.`Users` SET  `LastTimeSeen` = NOW()  WHERE `Users`.`id` =:id;";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute( array('id' => $userId));		
}

function getActiveUsers(){
    
}

function getNumberOfActiveUsers(){
    $str = "SELECT COUNT(*) FROM Users WHERE LastTimeSeen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute();
    $result = $req->fetchAll();
    return $result[0][0];
}

function isUserActive($userId){
    $str = "SELECT COUNT(*) FROM Users WHERE id=:id AND LastTimeSeen > DATE_SUB(NOW(), INTERVAL 5 MINUTE )";
    $req = $GLOBALS['db']->prepare($str);
    $req->execute(array('id' => $userId));
    $result = $req->fetchAll();
    return $result[0][0] != 0;

}

?>

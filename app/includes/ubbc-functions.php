<?php
require_once 'mail-functions.php';
require_once __DIR__ . '/ubbc-connect.php';
function ischecked($str1,$str2)
	{

		if ($str1 != $str2) {
			$retstr = '';
		}
		else {
			$retstr = 'checked';
		}
		return $retstr;
	}
	function remove_tags($str)
	{
		return trim(preg_replace('/[\'"%$.+*\/=]/', ' ', strip_tags($str)));
	}
	function remove_tags_email($str)
	{
		return trim(preg_replace('/[\'"%$+*\/=]/', ' ', strip_tags($str)));
	}

	function connect(){
			$link = mysqli_connect($ubbc_host, $ubbc_user, $ubbc_pass, $ubbc_base);
			if (mysqli_connect_errno()) {
				$error= "Échec de la connexion : $link->connect_error,";
				$error=$error."host : $ubbc_host,";
				$error=$error."user : $ubbc_user,";
				$error=$error."pass : $ubbc_pass,";
				$error=$error."base : $ubbc_base";
				require 'ubbc-error.php';
				exit();
			}
			mysqli_set_charset($link, 'utf8');
			mysqli_query($link, "SET time_zone = 'Europe/Paris'");
			return $link;
	}

function test_live(){
  	$link = connect();
		$stored_procedure="call test_laps";
		mysqli_query($link,$stored_procedure) ;
  	mysqli_free_result($results);
  	mysqli_close($link);
  }

	function init_live(){
	  	$link = connect();
			$sqltrunc="truncate table flowka_preds";
			mysqli_query($link,$sqltrunc) ;
			$sqltrunc="truncate table laps";
			mysqli_query($link,$sqltrunc) ;
	  	mysqli_free_result($results);
	  	mysqli_close($link);
	  }


function laps2live(){
	$link = connect();
	$stored_procedure="call laps2live";
	mysqli_query($link,$stored_procedure) ;
	mysqli_close($link); //deconnection de mysql$stored_procedure="call laps2live";
}

function predictCoef(){
		$link = connect();
		$sqlquery = "SELECT ifnull(now()-max(updated_at),600) as updated_from FROM tpredict;";
		$results=mysqli_query($link,$sqlquery) ;
		$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
		$intervalPredict=$record['updated_from'];
		mysqli_free_result($results);
		if ($intervalPredict>=600) {
			$stored_procedure="call calculate_prediction_coef";
			mysqli_query($link,$stored_procedure) ;
		}
		mysqli_close($link); //deconnection de mysql
}

function bibs($filter){
	switch ($filter){
	case 'nobibs':	$sql="SELECT u.bib, u.lastname from users u LEFT OUTER JOIN bibs b ON b.bib=u.bib WHERE u.edition=2025 and b.bib is NULL ORDER BY u.bib ASC";
									break;
	//default : 			$sql="SELECT u.bib, u.lastname from users u INNER JOIN bibs b ON b.bib=u.bib where u.edition=2025 ORDER BY u.bib ASC";
	default : 			$sql="SELECT u.bib, u.lastname from users u where u.edition= 2025 ORDER BY u.bib ASC";
	}

	$link = connect();
		$results=mysqli_query($link,$sql) ;
	$bibs=array();
	while($record = mysqli_fetch_array($results,MYSQLI_ASSOC)){
	  $bibs[$record['bib']]=$record['lastname'];
	}
	mysqli_free_result($results);
	mysqli_close($link); //deconnection de mysql
	return $bibs;
}



function races(){
	$sql="SELECT r.id,r.label from races r WHERE edition = (select id from edition where edition = 2025) ORDER BY r.id ASC";
	$link = connect();
		$results=mysqli_query($link,$sql) ;
	$bibs=array();
	while($record = mysqli_fetch_array($results,MYSQLI_ASSOC)){
	  $races[$record['id']]=$record['label'];
	}
	mysqli_free_result($results);
	mysqli_close($link); //deconnection de mysql
	return $races;
}

function controls(){
	$sql="SELECT c.id, c.label, c.distance, c.time_limit from controls c ORDER BY c.id ASC";

	$link = connect();
		$results=mysqli_query($link,$sql) ;
	$controls=array();
	while($record = mysqli_fetch_array($results,MYSQLI_ASSOC)){
	  $controls[$record['id']]=$record['label'];
	}
	mysqli_free_result($results);
	mysqli_close($link); //deconnection de mysql
	return $controls;
}

	function new_id($mail){
		$link = connect();
		$sqlquery = "SELECT id FROM email where (email='$mail')";
		$results=mysqli_query($link,$sqlquery);
		if (mysqli_num_rows($results) == 0) {
			mysqli_free_result($results);
			$sqlquery = "INSERT INTO email (email) VALUES ('$mail')";
			mysqli_query($link,$sqlquery) ;
			$Newid = mysqli_insert_id($link);
		}
		else {
				$Newid=-1;
		}
		mysqli_free_result($results);
		mysqli_close($link); //deconnection de mysql
		return $Newid;
	}

	function new_token($id){
		$link = connect();
		$sqlquery = "DELETE FROM tokens WHERE id=$id";
		mysqli_query($link,$sqlquery) ;
		$Newuid=substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSTVWXYZ"), 0, 36);
		$sqlquery = "INSERT INTO tokens (id,uid,expired) VALUES ($id,'$Newuid',0)";
		mysqli_query($link,$sqlquery) ;
		return $Newuid;
	}

	function get_id($mail)
	{
		$link = connect();
		$sqlquery = "SELECT id FROM email where (email='$mail')";
		$results=mysqli_query($link,$sqlquery) ;
		if (mysqli_num_rows($results) != 0) {
			$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
			$id=$record['id'];
		}
		else {
		$id=-1;
		}
		mysqli_free_result($results);
		mysqli_close($link); //deconnection de mysql
		return $id;
	}


	function get_id_from_token($uid){
		$link = connect();
		$storedprocedure = 'CALL expired()';
		$results=mysqli_query($link,$storedprocedure) ;
		$sqlquery = "SELECT id FROM tokens where uid='$uid' and expired=0";
		$results=mysqli_query($link,$sqlquery) ;
		if (mysqli_num_rows($results) != 0) {
			$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
			$id=$record['id'];

		}
		else {
		$id=-1;
		}
		mysqli_free_result($results);
		mysqli_close($link); //deconnection de mysql
		return $id;
	}


	function get_token($mail){
		$link = connect();
		$sqlquery = "SELECT t.uid as uid FROM tokens t inner join email e on  e.id=t.id where (e.email='$mail')";
		$results=mysqli_query($link,$sqlquery) ;
		if (mysqli_num_rows($results) != 0) {
			$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
			$uid=$record['uid'];
		}
		else {
			$uid='not exists';
		}
		mysqli_free_result($results);
		mysqli_close($link); //deconnection de mysql
		return $uid;
	}


	function get_mail($id){
		$link = connect();
		$sqlquery = "SELECT email FROM email where (id=$id)";
		$results=mysqli_query($link,$sqlquery) ;
		if (mysqli_num_rows($results) != 0) {
			$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
			$mail=$record['email'];
		}
		else {
		$mail='not exists!';
		}
		mysqli_free_result($results);
		mysqli_close($link); //deconnection de mysql
		return $mail;
	}


		function get_user($id){
		$link = connect();
		$sqlquery = "SELECT * FROM users where (id=$id)";
		$results=mysqli_query($link,$sqlquery) ;
		if (mysqli_num_rows($results) != 0) {
			$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
			$user=$record;
		}
		else {
		$user=-1;
		}
		mysqli_free_result($results);
		mysqli_close($link); //deconnection de mysql
		return $user;
	}


		function next_bib(){
		$link = connect();
		$sqlquery = "SELECT max(bib) as bib FROM users where bib<300 and edition=2025";
		$results=mysqli_query($link,$sqlquery) ;
		if (mysqli_num_rows($results) != 0) {
			$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
			$bib=intval($record['bib']);
		}
		else {
		$bib=0;
		}
		$bib=$bib+1;
		mysqli_free_result($results);
		mysqli_close($link); //deconnection de mysql
		return $bib;
	}

	function pending_users($test=0){
        $users=array();
        $link = connect();
        if ($test==0) {
        	$sqlquery = "SELECT email, id FROM email WHERE id not in (SELECT id from users)";
    	}
    	else {
    		$sqlquery = "SELECT email, id FROM email WHERE id=0";
    	}
        $results=mysqli_query($link,$sqlquery) ;
        if (mysqli_num_rows($results) != 0) {
            $i=0;
            while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
            {
                $users[$i][0]=$record['email'];
                $users[$i][1]=$record['id'];
                $i+=1;
            }
        }
        mysqli_free_result($results);
        mysqli_close($link); //deconnection de mysql
        return $users;
    }

function registred_users($test=0){
$users=array();
$users[0][0]='raya@ubbc.fr';
$users[0][1]='testraya';
return $users;
}

function sendmail($mail,$subject,$message){

	if (!preg_match("#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#", $mail)) { // On filtre les serveurs qui rencontrent des bogues.
    	$passage_ligne = "\r\n";
	}
	else {
    	$passage_ligne = "\n";
	}

	$message= $passage_ligne.$message.$passage_ligne;
	mailjet_api($mail,$subject,$message);
}

?>

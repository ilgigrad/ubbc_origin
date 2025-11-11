<?php
require_once "includes/ubbc-functions.php";
if (isset($_POST['direct']) && $_POST['direct']==1){$direct=1;}
$valid = true;
$erreurs = array();
$user  = array();
	$createerror='';
	$erreurs['lastname'] = '';
	$erreurs['firstname'] = '';
	$erreurs['pseudo'] = '';
	$erreurs['email'] = '';
	$erreurs['city'] = '';
	$erreurs['country'] = '';
	$erreurs['gender'] = '';
	$erreurs['category'] = '';
	$erreurs['birthdate'] = '';
	$erreurs['race'] = '';

	$user['pseudo']=mb_strtolower(remove_tags($_POST['pseudo']), 'UTF-8');
	$user['firstname']=mb_strtolower(remove_tags($_POST['firstname']), 'UTF-8');
	$user['lastname']=mb_strtolower(remove_tags($_POST['lastname']), 'UTF-8');
	$user['city']=mb_strtolower(remove_tags($_POST['city']), 'UTF-8');
	$user['nationality']=substr(mb_strtolower(remove_tags($_POST['nationality']), 'UTF-8'),0,3);
	$user['club']=mb_strtolower(remove_tags($_POST['club']), 'UTF-8');
	$user['gender']=$_POST['gender'];
	$cat=$_POST['category'];
	$user['category']=$cat.$_POST['gender'];
	$user['email']=mb_strtolower($_POST['email'], 'UTF-8');
	$user['race'] = $_POST['race'];
	list($year,$month,$day)=explode("-",str_replace("/", "-", $_POST['birthdate']));
	if (strlen($day)>=4 && strlen($year)==2){
		list($day,$month,$year)=explode("-",str_replace("/", "-", $_POST['birthdate']));
	}
	$user['birthdate']= $year."-".$month."-".$day;

	$user['id']= $_POST['id'];

		if (($cat=='mi' && ($year<date('Y')-16 || $year>date('Y')-15)) ||
				($cat=='ca' && ($year<date('Y')-18 || $year>date('Y')-17)) ||
				($cat=='ju' && ($year<date('Y')-20 || $year>date('Y')-19)) ||
				($cat=='es' && ($year<date('Y')-22 || $year>date('Y')-21)) ||
				($cat=='se' && ($year<date('Y')-34 || $year>date('Y')-23)) ||
				($cat=='m0' && ($year<date('Y')-39 || $year>date('Y')-35)) ||
				($cat=='m1' && ($year<date('Y')-44 || $year>date('Y')-40)) ||
				($cat=='m2' && ($year<date('Y')-49 || $year>date('Y')-45)) ||
				($cat=='m3' && ($year<date('Y')-54 || $year>date('Y')-50)) ||
				($cat=='m4' && ($year<date('Y')-59 || $year>date('Y')-55)) ||
				($cat=='m5' && ($year<date('Y')-90 || $year>date('Y')-60))){
				$erreurs['category'] = 'la catégorie ne correspond pas à l\'année de naissance';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['category'].'</li>';
			}
			if (preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$_POST['pseudo'])){
				$erreurs['pseudo'] = 'le pseudo ne peut-être votre email';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['pseudo'].'</li>';
			}

			if(empty($_POST['nationality']) || $_POST['nationality']=='###'){
				$erreurs['nationality'] = 'indiquez votre nationalité';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['nationality'].'</li>';
			}

			 if(!preg_match('#^[^0-9\/\\\·\_\!\?\.\,\;\:\%\$\*\+\&\~\€]{2,}$#',$_POST['lastname'])){
			 	$erreurs['lastname'] = 'saisissez un nom valide';
			 	$valid=false;
			 	$createerror= $createerror.'<li>'.$erreurs['lastname'].'</li>';
			}

			if(!preg_match('#^[^0-9\/\\\·\_\!\?\.\,\;\:\%\$\*\+\&\~\€]{2,}$#',$_POST['firstname'])){
				$erreurs['firstname'] = 'saisissez un prénom valide';
			 	$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['firstname'].'</li>';
			}

			if(!preg_match('#^[^0-9\/\\\·\_\!\?\.\,\;\:\%\$\*\+\&\~\€]{2,}$#',$_POST['city'])){
				$erreurs['city'] = 'saisissez une ville valide - chiffres non autorisés';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['city'].'</li>';
			}

			if(empty($_POST['gender'])){
				$erreurs['gender'] = 'sélectionnez votre genre';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['gender'].'</li>';
			}

			if(empty($_POST['category'])){
				$erreurs['category'] = 'sélectionnez votre catégorie';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['category'].'</li>';
			}
			if(empty($_POST['birthdate'])){
				$erreurs['birthdate'] = 'date de naissance nécessaire';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['birthdate'].'</li>';
			}
			if(empty($_POST['race'])){
				$erreurs['race'] = 'sélectionnez votre course';
				$valid=false;
				$createerror= $createerror.'<li>'.$erreurs['race'].'</li>';
			}
			else {
				list($year,$month,$day)=explode("-",str_replace("/", "-", $_POST['birthdate']));
				if (strlen($day)>=4 && strlen($year)==2){
					list($day,$month,$year)=explode("-",str_replace("/", "-", $_POST['birthdate']));
				}
				if (!checkdate($month,$day,$year)){
					$erreurs['birthdate'] = 'date de naissance invalide';
					$valid=false;
					$createerror= $createerror.'<li>'.$erreurs['birthdate'].'</li>';
				}
			}
	if (!$valid){
	$_GET['direct']=$direct;
	require "ubbc-fillform.php";
	exit();
	}
?>

<?php require "ubbc-header.html"; ?>
<section class="container-fluid">
 <div class="row flex-column">
	 <h1 class="text-center fl-txt-prune text-uppercase pt-2">NEW RUNNER SAVED IN DATABASE</h1>
	 <?php
	 $link=connect();
	 $prevuser=get_user($user['id']);
	 if ($prevuser['edition']==2025)
	 	$bib=$prevuser['bib'];
	else
	 	$bib=next_bib();
	 if ($prevuser>-1){
		 $sqlquery = sprintf(
 			"UPDATE users SET edition=2025, bib=%d, pseudo='%s', gender='%s',category='%s',birthdate='%s',lastname='%s',firstname='%s',club='%s',nationality='%s',city='%s',race=%d WHERE id=%d",
 			$bib, $user['pseudo'],$user['gender'],$user['category'],$user['birthdate'],
 			$user['lastname'],$user['firstname'],$user['club'],$user['nationality'],
 			$user['city'],$user['race'],$user['id']);
	 }
	 else {
	 	$bib=next_bib();
	 	$sqlquery = sprintf(
			"INSERT INTO users (id, bib, edition, pseudo, gender, category, birthdate, lastname,firstname,club, nationality, city,race) VALUES (%d,%d,2025,'%s','%s','%s','%s','%s','%s','%s','%s','%s',%d)",
			$user['id'],$bib,$user['pseudo'],$user['gender'],$user['category'],
			$user['birthdate'],$user['lastname'],$user['firstname'],
			$user['club'],$user['nationality'],$user['city'],$user['race']);
	 }
	 mysqli_query($link,$sqlquery) ;
	 $sqlquery = sprintf("SELECT * FROM users where id=%s",$user['id']);
	 $results=mysqli_query($link,$sqlquery) ;
	 if (mysqli_num_rows($results) == 0) {
		mysqli_free_result($results);
		mysqli_close($link);
		$error="can not create user";
		require "ubbc-error.php";
	 	exit();
	 }
	 while($record = mysqli_fetch_array($results,MYSQLI_ASSOC)){
	 	 echo "<table class='table table-striped table-hover table-responsive-md'>" ;
	 	 echo "<tr><td>" ;
	 	 echo "BIB" ;
	 	 echo "</td><td>" ;
	 	 echo $record['bib'] ;
	 	 echo "</td></tr><tr><td>" ;
	 	 echo "pseudo" ;
	 	 echo "</td><td>" ;
	 	 echo $record['pseudo'] ;
	 	 echo "</td></tr><tr><td>" ;
	 	 echo "Last Name";
	 	 echo "</td><td>" ;
	 	 echo $record['lastname'] ;
	 	 echo "</td></tr><tr><td>" ;
	 	 echo "First Name";
	 	 echo "</td><td>" ;
	 	 echo $record['firstname'] ;
		 echo "</td></tr><tr><td>";
	 	 echo "Gender" ;
	 	 echo "</td><td>" ;
		 echo $record['gender'] ;
		echo "</td></tr><tr><td>" ;
		echo "Category" ;
		echo "</td><td>" ;
		echo $record['category'] ;
		echo "</td></tr><tr><td>" ;
		echo "Club" ;
		echo "</td><td>" ;
		echo $record['club'] ;
		echo "</td></tr><tr><td>" ;
		echo "City";
		echo "</td><td>" ;
		echo $record['city'] ;
		echo "</td></tr><tr><td>" ;
		echo "Country";
		echo "</td><td>" ;
		echo $record['nationality'];
		echo "</td></tr><tr><td>";
		echo "Birth date";
		echo "</td><td>" ;
		echo $record['birthdate'] ;
		echo "</td></tr><tr><td>" ;
		echo "race";
		echo "</td><td>" ;
		echo ['Puebla','Funky','Hipster','Golgoth'][$record['race']-1] ;
		echo "</td></tr>" ;
	 	 echo "</table>";
	 }
	 mysqli_free_result($results);
	 mysqli_close($link); //deconnection de mysql
	 $user=NULL;
	 echo "<a class ='mx-auto my-3 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea d-block' href='http://www.ubbc.fr/ubbc-newuser.php?direct=$direct'>Inscrire un autre coureur</a>";
	 echo "</div>";
	 echo "</section>"; ?>
	<?php require "ubbc-footer.html"; ?>

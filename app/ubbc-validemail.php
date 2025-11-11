<?php
require_once 'includes/ubbc-functions.php';
if (isset($_POST['direct']) && $_POST['direct']==1){
  $direct=1;
}
else {
  $direct=0;
}

$valid = true;
$erreurs = array();
$user  = array();
$createerror='';
$erreurs['email'] = '';
$user['email']=mb_strtolower(remove_tags_email($_POST['email']), 'UTF-8');
$mail=$user['email'];

if(empty($_POST['email'])){
    $erreurs['email'] = 'Votre adresse email doit être renseignée';
    $valid=false;
    $createerror= $createerror.$erreurs['email'].'<br>';
}
elseif(!empty($_POST['email']) && !preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$_POST['email'])){
    $erreurs['email'] = 'Cette adresse email est invalide';
    $valid=false;
    $createerror= $createerror.$erreurs['email'].'<br>';
  }

 if (!$valid){
      $_GET['direct']=$direct;
      require "ubbc-newuser.php";
      exit();
    }
  else {
        $Newid=-1;
        $Newid=new_id($mail);
        if ($Newid<0 && $direct==0) {
            $createerror="Cette adresse existe déja dans notre base\nvous pourrez modifier vos informations\n en cliquant sur le lien que vous recevrez par email";
          }
        require "ubbc-confirm.php";
        exit();
      }

?>

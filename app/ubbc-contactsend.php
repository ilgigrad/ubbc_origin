<?php
require_once 'includes/ubbc-functions.php';

$valid = true;
$erreurs = array();
$createerror='';
$erreurs['email'] = '';
$mail=mb_strtolower(remove_tags_email($_POST['email']), 'UTF-8');
if(empty($_POST['email'])){
    $erreurs['email'] = 'Votre adresse email doit être renseignée';
    $valid=false;
    $createerror= $createerror.$erreurs['email'].'<br>';
}
else { $email=$_POST['email'];}
if(!empty($_POST['email']) && !preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$_POST['email'])){
    $erreurs['email'] = 'Cette adresse email est invalide';
    $valid=false;
    $createerror= $createerror.$erreurs['email'].'<br>';
  }
if(empty($_POST['sujet'])){
      $erreurs['sujet'] = "Votre message n'a pas de sujet";
      $valid=false;
      $createerror= $createerror.$erreurs['sujet'].'<br>';
    }
if(empty($_POST['message'])){
          $erreurs['message'] = "Vous n'avez pas saisi de message...";
          $valid=false;
          $createerror= $createerror.$erreurs['message'].'<br>';
        }
if (!$valid){
    require 'ubbc-contact.php';
    exit();
  }

else {
  $message='from '.$_POST['email'].' : '.$_POST['message'];
  $sujet=$_POST['sujet'];
  $contactubbc='contact@ubbc.fr';
  sendmail($contactubbc,$sujet,$message);
}
?>
<?php include("ubbc-header.html"); ?>
<section class="container-fluid">
    <h1 class="display-4 text-center fl-txt-electric text-uppercase pt-2">ENVOI DU MESSAGE</h1>
    <div class="px-2 col-md-4 mx-auto my-5">
        <p class="fl-txt-gray"> <span class="fl-txt-electric"><?php echo $mail ?></span>, votre message a été envoyé <i class="fal fa-envelope pr-2 fl-txt-electric"></i>  <br>
            Nous nous efforçons d'y répondre le plus rapidement possible.<br>
            Nous vous remercions de votre intérêt pour <br><span class="fl-txt-peach">l'Ultra Boucle des Buttes Chaumont</span></p>
    </div>
    <a class ="d-block mx-auto my-3 btn btn-lg fl-txt-white fl-bg-prune fl-bg-hov-sadsea" href="ubbc-content.php">retour à l'accueil</a>
  </section>
<?php include("ubbc-footer.html"); ?>

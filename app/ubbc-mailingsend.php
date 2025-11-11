<?php
require_once 'includes/ubbc-functions.php';
$valid = true;
$erreurs = array();
$createerror='';
if(empty($_POST['select'])){
      $erreurs['select'] = "pas de requête sql";
      $valid=false;
      $createerror= $createerror.$erreurs['select'].'<br>';
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
    require 'ubbc-mailing.php';
    exit();
  }

else {
  $message=$_POST['message'];
  $sujet=$_POST['sujet'];
  $link=connect();
  $sqlquery =$_POST['select'];
  $results=mysqli_query($link,$sqlquery);
  $countrows=mysqli_num_rows($results);
  if ($countrows == 0) {
    $createerror= "la requête ne retourne aucun email";
    require 'ubbc-mailing.php';
    exit();
     }
     $liste=array();
    while($record = mysqli_fetch_array($results,MYSQLI_ASSOC)){
      $email=mb_strtolower(remove_tags_email($record['email']), 'UTF-8');
      array_push($liste,$email);
      sendmail($email,$sujet,$message);
    }
}
mysqli_free_result($results);
mysqli_close($link); //deconnection de mysql
?>
<?php include("ubbc-header.html"); ?>
<section class="container-fluid">
    <h1 class="display-4 text-center fl-txt-electric text-uppercase pt-2">ENVOI DU MAILING</h1>
    <div class="px-2 col-md-4 mx-auto my-5">
        <p class="fl-txt-prune"> <i class="fal fa-envelope pr-2 fl-txt-electric"></i><?php echo $sujet; ?></p>
        <p class="fl-txt-gray"></i><?php echo $message; ?></p>
        <p class="fl-txt-prune"><?php echo $countrows;?> messages envoyés</p>
        <ul class="list-group">
        <?php
        foreach ($liste as $email) {
          echo "<li class='list-group-item'>$email</li>";
        }
        ?>
      </ul>
    </div>
    <a class ="d-block mx-auto my-3 btn btn-lg fl-txt-white fl-bg-prune fl-bg-hov-sadsea" href="ubbc-mailing.php">nouveau mailing</a>
  </section>
<?php include("ubbc-footer.html"); ?>

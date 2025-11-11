<?php

require_once 'includes/ubbc-functions.php';
if (!isset($mail)) {
    if (!isset($_POST['email'])){
    $mail=$_POST['mail'];
    }
    elseif (!isset($_GET['email'])){
    $mail=$_GET['mail'];
    }
    else {
      $error='erreur base';
      require "ubbc-error.php";
    }
  }

$uid=new_token(get_id($mail));

if (isset($direct) && $direct==1){
 require "ubbc-fillform.php";
 exit();
}

$sujet="inscription UBBC-10 - anniversary - ";
$message_html = <<<EOT
    <html>
        <head>
        </head>
        <body>
            bonjour,<br><br>
            Veuillez cliquer sur le lien ci-dessous<br>
             pour vous inscrire ou modifier votre inscription sur <br>
             <strong>UBBC 10</strong><br>
            <a href="http://www.ubbc.fr/ubbc-fillform.php?uid=$uid">http://www.ubbc.fr/ubbc-fillform.php?uid=$uid</a><br>
              <strong>attention, ce lien ne restera actif que 48h00</strong>
        </body>
    </html>
EOT;
  sendmail($mail,$sujet,$message_html);

?>

<?php include("ubbc-header.html");?>
<section class="container-fluid">
    <h1 class="display-4 text-center fl-txt-electric text-uppercase pt-2">LIEN D'ACTIVATION ENVOYÉ</h1>
    <div class="px-2 col-md-4 mx-auto my-5">
        <p class="fl-txt-gray"> <i class="fal fa-envelope pr-2 fl-txt-electric"></i> email envoyé à <span class="fl-txt-electric"><?php echo $mail ?></span><br>
            Si vous ne l'avez pas reçu, <strong>vérifiez dans vos spams</strong> ou cliquez ci-dessous pour le renvoyer</p>
    <?php if (isset($createerror) && strlen($createerror)>0) {
           echo '<div class="invalid-feedback d-block">',$createerror,'</div>';
     } ?>
        <?php
        echo '<form id="sendmailagain" action="ubbc-confirm.php" method="post">';
        echo '<input type="hidden" name="mail" id="mail" value="'.$mail.'">';
        echo '</form>';
        echo '<a class ="d-block mx-auto my-3 btn btn-lg fl-txt-white fl-bg-prune fl-bg-hov-sadsea" href="#" onclick="document.getElementById(\'sendmailagain\').sumit();">envoyer de nouveau</a>'; ?>
    </div>
  </section>
<?php include("ubbc-footer.html");


?>

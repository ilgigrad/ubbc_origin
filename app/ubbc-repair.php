<?php require_once 'includes/ubbc-functions.php'; ?>

<?php
$sujet="inscription UBBC | URGENT";

$link=connect();
$sqlquery = "SELECT e.email, t.uid FROM email e INNER JOIN tokens t ON t.id=e.id where e.id in (641,642,643,127,0)";
$results=mysqli_query($link,$sqlquery);
$countrows=mysqli_num_rows($results);
if ($countrows > 0) {
  $liste=array();
  while($record = mysqli_fetch_array($results,MYSQLI_ASSOC)){
    $email=mb_strtolower(remove_tags_email($record['email']), 'UTF-8');
    $uid=$record['uid'];
    array_push($liste,$email);
    //$uid=get_token($email);
    $message=<<<EOT
    <html>
      <head>
        </head>
          <body>
            bonjour,<br><br>
            Nous sommes parvenus &agrave; ajouter quelques dossards sur l'ensemble de nos courses. <br>
            Vous pouvez donc vous inscrire en cliquant sur le lien ci-dessous.<br>
            Pour que votre inscription soit valide, vous devez remplir votre fiche coureur puis la valider.
            <br>
            <a href="http://www.ubbc.fr/ubbc-fillform.php?uid=$uid">http://www.ubbc.fr/ubbc-fillform.php?uid=$uid</a><br>
            <strong>attention, ce lien ne restera actif que 48h00</strong><br><br><br>
            sportivement,<br><br>
            David
          </body>
    </html>
EOT;
  sendmail($email,$sujet,$message);
  }
}
mysqli_free_result($results);
mysqli_close($link); //deconnection de mysql
?>
<?php include("ubbc-header.html"); ?>
<section class="container-fluid">
    <h1 class="display-4 text-center fl-txt-electric text-uppercase pt-2">SENDING MAIL RESUBMISSION</h1>
    <div class="px-2 col-md-4 mx-auto my-5">
      <p class="fl-txt-prune"><?php echo $countrows;?> messages envoyés</p>
      <ul class="list-group">
      <?php
      foreach ($liste as $email) {
        echo "<li class='list-group-item'>$email</li>";
      }
      ?>
    </ul>
    </div>
    <div class="m-auto">
    <a class ="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-admin.php">back</a>
    </div>
  </section>
<?php include("ubbc-footer.html"); ?>

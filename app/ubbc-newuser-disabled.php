<?php include("ubbc-header.html");
if (isset($_GET['direct']) && $_GET['direct']==1){
  $direct=1;
}
else {
  $direct=0;
}
?>

<section class="container-fluid justify-content-center">
    <h1 class="text-center text-uppercase fl-txt-apricot pt-2"> CRÉER OU MODIFIER UN•E CONCURRENT•E</h1>
    <form class="px-2 col-md-4 mx-auto my-5" method="post" action="/ubbc-validemail.php">
      <input type="hidden" name="direct" value="<?php echo $direct; ?>">
        <div class="form-group">
          <label for="email">adresse email</label>
          <?php printf('<input class="form-control" id="email" type="email" name="email"size="50" placeholder="nom@exemple.fr" value="%s"/>',$user['email']); ?>

          <?php if (isset($createerror) && strlen($createerror)>0) {
                             echo '<div class="invalid-feedback d-block">',$createerror,'</div>';
                         } ?>
        </div>

        <button type="submit" class="btn btn-lg fl-bg-prune fl-bg-hov-sadsea fl-txt-white m-auto d-block">envoyer l'email</button>
    </form>
</section>

<?php include("ubbc-footer.html"); ?>

<?php include("ubbc-header.html"); ?>
<?php $_POST = array_merge(
[
'email'   => '',
'sujet'   => 'volunteer',
'message' => '',
],
$_POST
);
?>
<section class="container-fluid">
    <h1 class="text-center text-uppercase pt-2 fl-txt-prune">écrire votre plaidoyer</h1>
    <form class="px-2 col-md-4 mx-auto my-5" method="post" action="<?= $base_url ?>ubbc-contactsend.php">
        <div class="form-group">
          <label for="email">Saisissez votre adresse email.</label>
          <input class="form-control" id="email" type="email" name="email"size="50" required placeholder="Jeff@amazon.com" value="<?php echo $_POST['email']; ?>">
        </div>
        <div class="form-group ">
          <label for="sujet">nature de la négociation</label>
          <select class="form-control" id="sujet" name="sujet" required>
  <option value="volunteer" <?php if ($_POST["sujet"]==="volunteer"){echo "selected";} ?>>Bénévole ou Ravitailleur·se d’élite</option>
              <option value="media" <?php if ($_POST["sujet"]==="media"){echo "selected";} ?>>Média, presse, littérature, Festival de Cannes</option>
              <option value="mna" <?php if ($_POST["sujet"]==="mna"){echo "selected";} ?>>Merge & Acquisition</option>
              <option value="partenaire" <?php if ($_POST["sujet"]==="partenaire"){echo "selected";} ?>>Offre de partenariat</option>
              <option value="forgotten-hero" <?php if ($_POST["sujet"]==="forgotten-hero"){echo "selected";} ?>>Sénat·eur·rice ou héros·ine UBBC tombé·e dans l’oubli</option>
  <option value="eco" <?php if ($_POST["sujet"]==="eco"){echo "selected";} ?>>Engagement politique, combat écologique ou économique.</option>
  <option value="true-love" <?php if ($_POST["sujet"]==="true-love"){echo "selected";} ?>>Histoire d’amour</option>
  <option value="autre" <?php if ($_POST["sujet"]==="autre"){echo "selected";} ?>>Autre raison discutable mais possiblement touchante</option>

        </select>
        </div>
        <div class="form-group">
          <label for="message">contenu du message</label>
          <textarea id="message" minlength="500" maxlength="1500" required name="message" rows="10" cols="50" class="w-100" placeholder="Je me réveille un peu tard mais je..." value="<?php echo trim($_POST['message']); ?>">
          </textarea>
        </div>
        <?php if (isset($createerror) && strlen($createerror)>0) {
                           echo '<div class="invalid-feedback d-block">',$createerror,'</div>';
                       } ?>
        <button type="submit" class="btn btn-lg fl-bg-prune fl-bg-hov-sadsea fl-txt-white m-auto d-block">Envoyer</button>
    </form>
</section>

<?php include("ubbc-footer.html"); ?>

<?php include("ubbc-header.html"); ?>

<section class="container-fluid">
    <h1 class="text-center text-uppercase pt-2 fl-txt-prune">CONTACT UBBC</h1>
    <form class="px-2 col-md-4 mx-auto my-5" method="post" action="ubbc-contactsend.php">
        <div class="form-group">
          <label for="email">Saisissez votre adresse email.</label>
          <input class="form-control" id="email" type="email" name="email"size="50" placeholder="nom@exemple.fr" value="<?php echo $_POST['email']; ?>">
        </div>
        <div class="form-group ">
          <label for="sujet">sujet du message</label>
          <select class="form-control" id="sujet" name="sujet">
          <option value="information" <?php if ($_POST["sujet"]=="information"){echo "selected";} ?>>demande d'informations</option>
          <option value="list" <?php if ($_POST["sujet"]=="list"){echo "selected";} ?>>liste d'attente</option>
          <option value="cancelation" <?php if ($_POST["sujet"]=="cancelation"){echo "selected";} ?>>désistement / annulation d'inscription</option>
          <option value="erreur" <?php if ($_POST["sujet"]=="error"){echo "selected";} ?>>Erreur ou bug</option>
          <option value="other" <?php if ($_POST["sujet"]=="other"){echo "selected";} ?>>autre</option>
        </select>
        </div>
        <div class="form-group">
          <label for="message">contenu du message</label>
          <textarea id="message" name="message" rows="10" cols="50" class="w-100" placeholder="C'était une nuit magique..." value="<?php echo trim($_POST['message']); ?>">
          </textarea>
        </div>
        <?php if (isset($createerror) && strlen($createerror)>0) {
                           echo '<div class="invalid-feedback d-block">',$createerror,'</div>';
                       } ?>
        <button type="submit" class="btn btn-lg fl-bg-prune fl-bg-hov-sadsea fl-txt-white m-auto d-block">Envoyer</button>
    </form>
</section>

<?php include("ubbc-footer.html"); ?>

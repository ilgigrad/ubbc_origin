<?php include("ubbc-header.html"); ?>
<?php
  if (!empty($_POST['select'])){
    $select=$_POST['select'];
  }
  else{
     $select="SELECT email FROM email e INNER JOIN users u ON u.id=e.id where e.id=0";
  }
  ?>

<section class="container-fluid">
    <h1 class="text-center text-uppercase pt-2 fl-txt-prune">MAILING</h1>
    <p class="text-center fl-txt-lesslight">send mail to selected users</p>
    <form class="px-2 col-md-4 mx-auto my-5" method="post" action="ubbc-mailingsend.php">
      <div class="form-group">
        <label for="select">select</label>

        <input class="form-control" id="select" type="text" name="select" size="255" value="<?php echo $select; ?>">
      </div>
        <div class="form-group">
          <label for="sujet">sujet du message</label>
          <input class="form-control" id="sujet" type="text" name="sujet" size="100" placeholder="message des héros de l'organisation" value="<?php echo $_POST['sujet']; ?>">
        </div>
        <div class="form-group">
          <label for="message">contenu du message</label>
          <textarea id="message" name="message" rows="10" cols="50" class="w-100" placeholder="C'était une nuit magique..." value="<?php echo $_POST['message']; ?>">
          </textarea>
        </div>
        <?php if (isset($createerror) && strlen($createerror)>0) {
                           echo '<div class="invalid-feedback d-block">',$createerror,'</div>';
                       } ?>
        <button type="submit" class="btn btn-lg fl-bg-prune fl-bg-hov-sadsea fl-txt-white m-auto d-block">Envoyer</button>
    </form>
</section>

<?php include("ubbc-footer.html"); ?>

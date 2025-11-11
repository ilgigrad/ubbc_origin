<?php include("ubbc-header.html"); ?>

<section class="container-fluid px-0">
  <div class="row">
      <div class="jumbotron jumbotron-fluid w-100 d-flex flex-column align-items-center justify-content-center">
        <h1 class="display-2 fl-txt-electric d-inline-block text-center">ERREUR</h1>
        <h3 class="display-3 fl-txt-gray d-inline-block text-center py-3">quelque chose n'a pas fonctionné</h3>
        <div class="mt-2">
          <h2 class="display-2 fl-txt-prune text-center"><?php echo $error; ?></h2>
        </div>
        <div>
          <a class="d-block mt-3 btn btn-lg fl-bg-electric fl-bg-hov-sadsea fl-txt-40 fl-txt-white mt-3 mb-1" href="http://www.ubbc.fr/ubbc-newuser.php">
            <span>recommencer</span>
          </a>
        </div>
      </div>
  </div>
</section>
<?php include("ubbc-footer.html"); ?>

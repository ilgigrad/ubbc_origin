
<?php include('ubbc-header.html'); ?>
<section class="container-fluid px-2">
  <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">RESET LAPS</h1>

  <div class="row justify-content-center my-5">
    <form method="get" action="includes/ubbc-admin-functions.php">
      <input type="hidden" name="reset" value="true">
      <button type="submit" class="btn btn-lg fl-bg-blood fl-txt-white fl-bg-hov-electric">
        Réinitialiser tous les tours
      </button>
    </form>
  </div>

  <div class="row justify-content-center mt-4">
    <a class="mx-1 mb-2 btn btn-lg fl-bg-electric fl-txt-white fl-bg-hov-peach" href="ubbc-laps"><i class="fal fa-recycle mx-1"></i>back to laps</a>
  </div>
</section>
<?php include('ubbc-footer.html'); ?>

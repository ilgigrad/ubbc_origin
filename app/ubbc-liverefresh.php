<?php
require_once 'includes/ubbc-functions.php';
  laps2live();
  predictCoef();
if (isset($_GET['timer']) && $_GET['timer']>0){
        $timer=$_GET['timer']+1;
    }
else {
  $timer=1;
}
?>

<html>
<header>
<META http-equiv="refresh" content="30; URL=http://ubbc.fr/ubbc-liverefresh.php?timer=<?php echo $timer; ?>">
</header>
<body>
  <?php include("ubbc-header.html"); ?>
  <section class="container-fluid">
      <div class="row flex-column">
          <h1 class="fl-txt-prune fl-txt-40 pt-2 text-center">UBBC Live REFRESHER </h1>
          <h2 class="fl-txt-electric fl-txt-25 text-uppercase pt-2 text-center"><?php echo $timer; ?></h2>
          <p class="fl-txt-gray pt-2 my-3 text-center">please keep this window active until the end of the race</p>
    </div>
</section>
<?php include("ubbc-footer.html"); ?>

</body>
</html>

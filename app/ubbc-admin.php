<?php include("ubbc-header.html");
require_once 'includes/ubbc-functions.php';
?>

<section class="">
  <h1 class="fl-txt-prune text-center">ADMIN UBBC</h1>

<div class="row justify-content-center mx-auto">
  <div class="col-6 d-flex flex-column justify-content-start align-items-center">
    <h4 class="fl-txt-electric text-center">REPORTS</h1>
    <ul class="list-group fl-w-200">
      <li class="list-group-item fl-txt-prune fl-bd-anis m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-results.html"
          data-toggle="tooltip" data-placement="right" title="See results of previous events"><i class="fal fa-square-poll-vertical mx-1"></i>Results</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-anis m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-live.php"
          data-toggle="tooltip" data-placement="right" title="See live results of the race"><i class="fal fa-person-running mx-1"></i>Live !</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-anis m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-liverefresh.php"
              data-toggle="tooltip" data-placement="right" title="launch batch refresher for live !"><i class="fal fa-arrows-rotate mx-1"></i>Live Refresh</a>
      <li class="list-group-item fl-txt-prune fl-bd-apricot m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-entrylist.php"
         data-toggle="tooltip" data-placement="right" title="List of users succesfully registered "><i class="fal fa-badge-check mx-1"></i>Achieved</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-apricot m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-pending.php"
         data-toggle="tooltip" data-placement="right" title="List of users with unachieved subscription but having an active token"><i class="fal fa-spinner mx-1"></i>Pending</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-apricot m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-expired.php"
         data-toggle="tooltip" data-placement="right" title="List of users with unachieved subscription and an expired token"><i class="fal fa-clock mx-1"></i>Expired</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-sadsea m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-devices.php"
         data-toggle="tooltip" data-html="true" title="NFC devices/raspberry management"><i class="fal fa-mobile-screen mx-1"></i>Devices</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-sadsea m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-bibs.php"
         data-toggle="tooltip" data-placement="right" title="lists of Bibs and tags"><i class="fal fa-user-tag mx-1"></i>Bibs</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-sadsea m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-laps.php"
           data-toggle="tooltip" data-placement="right" title="users laps status"><i class="fal fa-recycle mx-1"></i>Laps</a></li>
    </ul>
  </div>
  <div class="col-6 d-flex flex-column justify-content-start align-items-center">
    <h4 class="fl-txt-blood text-center"><i class="fal fa-radiation mr-1"></i>DANGER ZONE</h1>
    <ul class="list-group fl-w-200">
      <li class="list-group-item fl-txt-peach fl-bd-peach m-1 fl-txt-hov-white fl-bg-hov-peach"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-newuser-admin.php?direct=1"
          data-toggle="tooltip" data-placement="right" title="create a new user with a direct access to the fillform"><i class="fal fa-user mx-1"></i>Create User</a></li>
      <li class="list-group-item fl-txt-prune fl-bd-prune m-1 fl-txt-hov-white fl-bg-hov-prune"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-mailing.php"
          data-toggle="tooltip" data-placement="right" title="create a mailing to send an html message to users selected by a sql query"><i class="fal fa-envelope mx-1"></i>Mail</a></li>
      <li class="list-group-item fl-txt-peach fl-bd-blood m-1 fl-txt-hov-white fl-bg-hov-blood"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-repair.php"
         data-toggle="tooltip" data-html="true" title="send an email to users for the specific IT registration's error"><i class="fal fa-exclamation-triangle mr-1"></i>Mailing Error</a></li>
          <li class="list-group-item fl-txt-blood fl-bd-blood m-1 fl-txt-hov-white fl-bg-hov-blood"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-reset-laps"
         data-toggle="tooltip" data-html="true" title="display Reset all laps "><i class="fal fa-trash-can mx-1"></i>Reset Laps</a></li>

                  <li class="list-group-item fl-txt-prune fl-bd-prune m-1 fl-txt-hov-white fl-bg-hov-prune"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-mass-start"
         data-toggle="tooltip" data-html="true" title="initialize time for a mass start "><i class="fal fa-flag-checkered mx-1"></i>Mass Start</a></li>

  <li class="list-group-item fl-txt-electric fl-bd-electric m-1 fl-txt-hov-white fl-bg-hov-electric"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-grid.php"
         data-toggle="tooltip" data-html="true" title="display a grid for creating laps "><i class="fal fa-grid mx-1"></i>Grid</a></li>
 <li class="list-group-item fl-txt-blood fl-bd-blood m-1 fl-txt-hov-white fl-bg-hov-blood"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-grid-finish.php"
         data-toggle="tooltip" data-html="true" title="display a grid for creating laps "><i class="fal fa-flag-checkered mx-1"></i>Finish</a></li>

<li class="list-group-item fl-txt-electric fl-bd-electric mt-3 m-1 fl-txt-hov-white fl-bg-hov-sadsea"><a class="w-100 h-100 d-inline-block fl-txt-hov-white" href="ubbc-draw.php"
         data-toggle="tooltip" data-html="true" title="display a grid for creating laps "><i class="fal fa-hat-wizard mx-1"></i>Draw !</a></li>
         
    </ul>
  </div>
</div>
<div>
  <a class="d-block my-2 mx-auto btn btn-lg fl-bg-prune fl-w-100 fl-bg-hov-sadsea fl-txt-white" href="https://ubbc.fr/ubbc-content.php"><span>accueil</span>
  </a>
</div>
</section>
<?php include("ubbc-footer.html"); ?>

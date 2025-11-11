<?php include("ubbc-header.html");?>
<section class="container-fluid">
  <div class="row flex-column">
    <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">expired REGISTRATIONS</h1>
    <p class="text-center fl-txt-lesslight">registrations in a black hole (not achieved withd expired tokens)</p>
    <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
      <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
        <tr>
          <th class="thin">ID</th>
          <th class="large">Email</th>
          <th class="large">Date</th>
        </tr>
    </thead>
<?php
  require_once 'includes/ubbc-functions.php';
  $link = connect();
  $sqlquery = "SELECT t.id,t.date,e.email FROM tokens t INNER JOIN email e ON e.id=t.id LEFT OUTER JOIN users u ON u.id=e.id WHERE t.expired=1 AND u.lastname IS NULL ORDER BY t.id";
  $results=mysqli_query($link,$sqlquery);
  echo "<tbody>";
  if (mysqli_num_rows($results) != 0) {
    while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
    {
      printf('<tr>');
      printf('<td class="thin">%s</td>',$record["id"]);
      printf('<td class="large text-uppercase">%s</td>',$record["email"]);
      printf('<td class="large text-uppercase">%s</td>',$record["date"]);
      printf('</tr>');
    }
  }
  mysqli_free_result($results);
  mysqli_close($link);
  echo "</tbody>";
  echo "<tfoot>";
  echo "</tfoot>";
  echo "</table>";
?>
  <div class="m-auto">
  <a class ="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-admin.php">back</a>
  </div>
</section>
<?php include("ubbc-footer.html"); ?>

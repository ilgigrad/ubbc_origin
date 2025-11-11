<?php include("ubbc-header.html");?>
<section class="container-fluid">
  <div class="row flex-column">
    <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">TOKENS</h1>
        <p class="text-center fl-txt-lesslight">pending subscription with non expired tokens</p>
    <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
      <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
        <tr>
          <th class="thin">ID</th>
          <th class="large">Email</th>
          <th class="large">Created</th>
          <th class="large">New token</th>
        </tr>
    </thead>
<?php
  require_once 'includes/ubbc-functions.php';
  $link = connect();
  $sqlcount="SELECT count(t.id) AS nb FROM tokens t INNER JOIN email e ON e.id=t.id LEFT OUTER JOIN users u ON u.id=t.id WHERE t.expired=0 AND u.birthdate IS NULL ORDER BY t.id";
  $results=mysqli_query($link,$sqlcount) ;
  $record = mysqli_fetch_array($results,MYSQLI_ASSOC);
  $nbpendings=$record['nb'];
  if ($nbpendings==0) {
    echo "</table>";
    echo "<h3 class='text-center fl-txt-prune'>pas de jetons en cours</h3>";
  }
  else{
    $sqlquery = "SELECT t.id,t.date,e.created_at,e.email FROM tokens t INNER JOIN email e ON e.id=t.id LEFT OUTER JOIN users u ON u.id=t.id WHERE t.expired=0 AND u.birthdate IS NULL ORDER BY t.id";
    $results=mysqli_query($link,$sqlquery);
    echo "<tbody>";
    if (mysqli_num_rows($results) != 0) {
      while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
      {
        printf('<tr>');
        printf('<td class="thin">%s</td>',$record["id"]);
        printf('<td class="large text-uppercase">%s</td>',$record["email"]);
        printf('<td class="large text-uppercase">%s</td>',$record["created_at"]);
        printf('<td class="large text-capitalize">%s</td>',$record["date"]);
        printf('</tr>');
      }
    }
    mysqli_free_result($results);
    mysqli_close($link);
    echo "</tbody>";
    echo "<tfoot>";
    echo "</tfoot>";
    echo "</table>";
    $plural="";
    if ($nbpendings>1){
      $plural="s";
    }
    echo "<h3 class='text-center fl-txt-prune'>$nbpendings jeton$plural</h3>";
  }
?>
  <div class="m-auto">
  <a class ="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-admin.php">back</a>
  </div>
</section>
<?php include("ubbc-footer.html"); ?>

<?php
require_once 'includes/ubbc-functions.php';
$link = connect();

if (isset($_GET['bib'])) {
    $bib=$_GET['bib'];
}
else {
$sqldrawuser = <<< EOT
SELECT 
    bibs.bib
FROM laps
JOIN bibs ON laps.uid = bibs.uid
WHERE (laps.time >= NOW() - INTERVAL 1 HOUR and laps.control='START') or  (laps.time >= NOW() - INTERVAL 30 MINUTE and laps.control='STOP')
ORDER by rand()
LIMIT 1
EOT;
    $results = mysqli_query($link, $sqldrawuser);
    $record = mysqli_fetch_array($results, MYSQLI_ASSOC);
    $bib= $record['bib'];
}

$sqluser = "SELECT u.id, u.lastname,u.firstname,u.bib,u.gender,u.category,u.nationality,u.club,u.city,u.pseudo, r.label FROM users u INNER JOIN races r ON r.id=u.race WHERE u.bib=$bib";
$sqlstats = <<< EOT
SELECT
    l.bib, l.lastname, l.firstname, l.gender, l.category,
    l.faster, l.slower, l.duration, l.laps, l.expected, l.current, l.average, l.pending,
    l.race, l.goal, time_format(l.predict,"%H:%i:%s") as predict, l.ai as ai,
    TIME(l.start) as start,
    TIME(l.current) as current,
    r.rrank as `rank`, c.crank as `crank`, g.grank as `grank`, l.control as control
FROM live l
INNER JOIN
(
    SELECT bib, @r_rank := @r_rank + 1 AS rrank
    FROM live, (SELECT @r_rank := 0) r
    ORDER BY laps DESC, duration ASC, faster ASC
) r ON r.bib = l.bib
INNER JOIN
(
    SELECT bib, @g_rank := IF(@g_gender = gender, @g_rank + 1, 1) AS grank, @g_gender := gender
    FROM live, (SELECT @g_rank := 0, @g_gender := '') g
    ORDER BY gender ASC, laps DESC, duration ASC, faster ASC
) g ON g.bib = l.bib
INNER JOIN
(
    SELECT bib, @c_rank := IF(@c_category = category, @c_rank + 1, 1) AS crank, @c_category := category
    FROM live, (SELECT @c_rank := 0, @c_category := '') c
    ORDER BY gender ASC, category ASC, laps DESC, duration ASC, faster ASC
) c ON c.bib = l.bib
INNER JOIN users ON users.bib = l.bib
WHERE users.bib = $bib
EOT;
$link = connect();
mysqli_query($link,"SET SQL_BIG_SELECTS=1");

$results=mysqli_query($link,$sqluser);
$user = mysqli_fetch_array($results,MYSQLI_ASSOC);
$results=mysqli_query($link,$sqlstats);
$stats = mysqli_fetch_array($results,MYSQLI_ASSOC);
mysqli_free_result($results);
mysqli_close($link);//deconnection de mysql
?>


<?php include('ubbc-header.html');?>
<section class="container-fluid">
    <div class="row flex-column">
      <div class="row mx-auto py-2">
        <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center text-capitalize"><?php echo $user['firstname'];?> <span class="text-uppercase"><?php echo $user['lastname'];?> (<?php echo $user['nationality'];?>)</span></h1>
        <h4 class="ml-3 text-center text-capitalize rounded fl-bg-sadsea py-2 px-3 fl-txt-white"><?php echo $user['label'];?></h4>
      </div>
    </div>
    <div class="row mx-auto w-100 py-2">
      <div class="col-12 col-md-6 p-2">
        <ul class="list-group">
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">rank</span>
            <span class="badge badge-electric badge-pill"><?php echo $stats['rank']; ?></span>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">gender</span><span class="text-uppercase text-center"><?php echo $user['gender']; ?></span>
            <span class="badge badge-electric badge-pill"><?php echo $stats['grank']; ?></span>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">category</span><span class="text-uppercase text-center"><?php echo $user['category']; ?></span>
            <span class="badge badge-electric badge-pill"><?php echo $stats['crank']; ?></span>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">club</span><span class="text-capitalize text-center mx-auto"><?php echo $user['club']; ?></span>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">city</span><span class="text-capitalize text-center mx-auto"><?php echo $user['city']; ?></span>
          </li>
        </ul>
      </div>
      <div class="col-12 col-md-6 p-2">
        <ul class="list-group">
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">laps</span></div>
            <div class="col-6 col-md-3 pl-0"><span class="badge badge-electric badge-pill"><?php echo $stats['laps']; ?></span></div>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">start</span><span class="ml-3"><?php echo $stats['start']; ?></span></div>
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">duration</span><span class="ml-3"><?php echo $stats['duration']; ?></span></div>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">average</span><span class="ml-3"><?php echo $stats['average']; ?></span></div>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">slower</span><span class="ml-3"><?php echo $stats['slower']; ?></span></div>
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">faster</span><span class="ml-3"><?php echo $stats['faster']; ?></span></div>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">current</span><span class="ml-3"><?php echo $stats['current']; ?></span></div>
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">pending</span><span class="ml-3"><?php echo $stats['pending']; ?></span></div>
          </li>
          <li class="list-group-item py-0 pl-0 d-flex justify-content-between align-items-center">
            <?php
            if($stats["predict"] || $stats["ai"]){
              $aiico="<i class='ml-2' style='background-image:url(\"http://ubbc.fr/static/images/ico-synapse.png\");background-size: contain;background-repeat: no-repeat;width:15px;height:15px;display:inline-block;'></i>";
            } else {
              $aiico="";
            }

            if ($stats["laps"]>=$stats["goal"]){
              $expected="finisher";
              $expcolor="fl-txt-electric";
            }
            elseif ($stats["expected"]=='23:59:59'){
                $expected="stopped";
                $expcolor="fl-txt-peach";
            }
            else {
              $expected=$stats["expected"];
              $expcolor="";
              if ($stats["expected"][0]=='-') {
                $expcolor="fl-txt-peach";
              }
            }
            ?>
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">predict</span><span class="ml-3"><?php echo $stats['predict'].$aiico; ?></span></div>
            <div class="col-6 col-md-3 pl-0"><span class="d-inline-block px-2 fl-w-75 py-1 text-center fl-bg-electric fl-txt-white">expected</span><span class="ml-3 <?php echo $expcolor; ?>"><?php echo $expected; ?></span></div>
          </li>

        </ul>
      </div>
    </div>
    <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
        <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
          <tr>
            <?php
            echo "<th class='thin'>lap</th>";
            echo "<th class='large'>Start</th>";
            echo "<th class='large'>Finish</th>";
            echo "<th class='large'>Elapse</th>";
            ?>
          </tr>
        </thead>
<?php

$sqllaps = "SELECT  v.start,v.finish, TimeDiff(v.finish,v.start)AS elapse FROM vlaps1 v INNER JOIN bibs b ON v.uid=b.uid WHERE b.bib=$bib ORDER BY v.start ASC";
$link = connect();
mysqli_query($link,"SET SQL_BIG_SELECTS=1");
$results=mysqli_query($link,$sqllaps) ;
if (mysqli_num_rows($results) == 0) {
   echo '</table>';
   echo "<p class='m-3'>empty table, no lap to list</p>";
}
else{
  echo '<tbody>';
  $i=1;
  while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
  {

      printf('<tr>');
      printf('<td class="thin">%s</td>',$i);
      printf('<td class="large text-capitalize">%s</td>',$record["start"]);
      printf('<td class="large text-capitalize">%s</td>',$record["finish"]);
      printf('<td class="large text-capitalize">%s</td>',$record["elapse"]);
      printf('</tr>');
      $i+=1;
  } //fin de la boucle, le tableau contient toute la BDD
  echo "</tbody>";
  echo "<tfoot>";
  echo "</tfoot>";
  echo "</table>";
}
mysqli_free_result($results);
mysqli_close($link);//deconnection de mysql
?>
    <div class="row justify-content-center" >
      <a class ="mx-1 mb-2 btn btn-lg fl-bg-gray fl-txt-white fl-bg-hov-sadsea" href="ubbc-userdraw.php">draw again</a>
      <a class ="mx-1 mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-live.php">live !</a>
    </div>
  </div>
</section>
<?php include ('ubbc-footer.html');?>

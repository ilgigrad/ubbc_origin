<?php

function to_local_time($datetime_string, $format = 'Y-m-d H:i:s') {
    $utc = new DateTimeZone('UTC');
    $paris = new DateTimeZone('Europe/Paris');
    $date = new DateTime($datetime_string, $utc);
    $date->setTimezone($paris);
    return $date->format($format);
}
require_once 'includes/ubbc-functions.php';

if (isset($_GET['test']) && $_GET['test']==1){
    test_live();
}
elseif (isset($_GET['init']) && $_GET['init']==1){
    init_live();
}


$link = connect();

$races=races();

if (isset($_GET['page']) && $_GET['page']>0 && $_GET['page']<=$nbpage){
        $cpage=$_GET['page'];
}
else {
    $cpage=1;
}

if (isset($_GET['order']) && in_array($_GET['order'],array('bib','lastname','gender','category','rrank','grank','crank','faster','duration','laps','expected','current','pending','race'))){
    $order=$_GET['order'];
}
else {
    $order='rrank';
}

if (isset($_GET['page']) && isset($_GET['asc']) && in_array($_GET['asc'],array('asc','desc'))){
    $asc=$_GET['asc'];
}
elseif (isset($_GET['asc']) && $_GET['asc']=='asc') {
    $asc='desc';
}
else {
    $asc='asc';
}

if (isset($_GET['race']) && array_key_exists($_GET['race'],$races)){
    $race=$_GET['race'];
    $raceFilter="WHERE race = $race ";
    //$raceFilter="WHERE race ='".$races[$race]."' ";
}
else {
    $race=0;
    $raceFilter='';
}
if ($order=="grank"){
  $order='gender asc, grank';
}
if ($order=="crank"){
  $order='category asc, crank';
}

$sqlcount = 'SELECT count(bib) as nb FROM live '.$raceFilter.' ORDER BY bib ASC ';
mysqli_query($link,"SET SQL_BIG_SELECTS=1");
$results=mysqli_query($link,$sqlcount) ;
$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
$nbusers=$record['nb'];
$nbperpage=25;
$nbpage=ceil($nbusers/$nbperpage);

if (isset($_GET['page']) && $_GET['page']>0 && $_GET['page']<=$nbpage){
        $cpage=$_GET['page'];
}
else {
    $cpage=1;
}

?>
  <?php include("ubbc-live-header.html"); ?>
  <section class="container-fluid">
        <div class="row flex-column">
        <!--<div class="panel-sub">    -->
            <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">UBBC Live 2025</h1>
            <div class="row p-1 mx-0 mx-md-auto ">
              <form method="get" id="form_race_id" class="mx-auto fl-w-150" action="ubbc-live.php">
                <div class="input-group">
                  <select id="race_id" name="race" class="custom-select fl-w-75" onchange="document.getElementById('form_race_id').submit();">
                    <?php

                      echo "<option value='0'>all races</option>";
                      foreach($races as $raceid=>$racelabel){
                        if ($race==$raceid){
                          $selected='selected';
                        }
                        else{
                          $selected='';
                        }
                        echo "<option value='$raceid' $selected>$racelabel</option>";
                      }
                    ?>
                  </select>
                </div>
              </form>
            <!--  <p class="fl-txt-prune fl-txt-30 text-center"> <?php if ($nbpage>1) {echo 'page : '.$cpage;} ?></p>-->
            </div>
            <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
                <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
                <tr>
                    <th class="thin"><a href='ubbc-live.php?race=<?php echo $race ?>&order=srank&asc=<?php echo $asc ?>'>Rang</a></th>
                    <th class="thin"><a href='ubbc-live.php?race=<?php echo $race ?>&order=laps&asc=<?php echo $asc ?>'>Tours</a></th>
                    <th class="thin"><a href='ubbc-live.php?race=<?php echo $race ?>&order=bib&asc=<?php echo $asc ?>'>BIB</a></th>
                    <th class="large"><a href='ubbc-live.php?race=<?php echo $race ?>&order=lastname&asc=<?php echo $asc ?>'>Nom</a></th>
                    <th class="large"><a href='ubbc-live.php?race=<?php echo $race ?>&order=firstname&asc=<?php echo $asc ?>'>Pr&eacute;nom</a></th>
                    <th class="thin"><a href='ubbc-live.php?race=<?php echo $race ?>&order=gender&asc=<?php echo $asc ?>'>Genre</a></th>
                    <th class="thin"><a href='ubbc-live.php?race=<?php echo $race ?>&order=category&asc=<?php echo $asc ?>'>Cat&eacute;gorie</a></th>
                    <th class="thin"><a href='ubbc-live.php?race=<?php echo $race ?>&order=rrank&asc=<?php echo $asc ?>'>Rang Course</a></th>
                    <th class="thin"><a href='ubbc-live.php?race=<?php echo $race ?>&order=crank&asc=<?php echo $asc ?>'>Rang Genre</a></th>
                    <th class="medium"><a href='ubbc-live.php?race=<?php echo $race ?>&order=faster&asc=<?php echo $asc ?>'>Record</a></th>
                    <th class="medium"><a href='ubbc-live.php?race=<?php echo $race ?>&order=duration&asc=<?php echo $asc ?>'>Chrono</a></th>
                    <th class="medium"><a href='ubbc-live.php?race=<?php echo $race ?>&order=current&asc=<?php echo $asc ?>'>Pointage</a></th>
                    <th class="medium"><a href='ubbc-live.php?race=<?php echo $race ?>&order=pending&asc=<?php echo $asc ?>'>Ecoulé</a></th>
                    <th class="medium"><a href='ubbc-live.php?race=<?php echo $race ?>&order=expected&asc=<?php echo $asc ?>'>Attendu</a></th>
		                <th class="medium"><a  href='ubbc-live.php?race=<?php echo $race ?>&order=race&asc=<?php echo $asc ?>'>Course</a></th>
                </tr>
                </thead>
<?php


$sqlquery = <<< EOT
SELECT
l.bib,l.lastname,l.firstname,l.gender,l.category,
l.faster,l.duration,l.laps,l.expected,l.current,l.pending,
l.race,l.goal,l.ai as ai,
r.rrank as rrank,
c.crank as crank,
g.grank as grank, s.srank as srank,
l.control as control
FROM live l
INNER JOIN
(SELECT v.bib, @rank:=@rank+1 AS srank
FROM live v,(SELECT @rank:=0) rankzero
ORDER BY laps DESC, duration ASC, faster ASC) s
ON s.bib=l.bib
INNER JOIN
(SELECT v.bib, @rank:=if(@gender=gender,@rank+1,1) AS grank, @gender:=gender
FROM live v,(select @rank:=0, @gender:='') rankzero
ORDER BY gender ASC, laps DESC, duration ASC, faster ASC) g
ON g.bib=l.bib
INNER JOIN
(SELECT v.bib, @rank:=if(@racegender=concat(race,gender),@rank:=@rank+1,1) AS crank, @racegender:=concat(race,gender)
FROM live v, (SELECT @rank:=0, @racegender:='') rankzero
ORDER BY race ASC, gender ASC, laps DESC, duration ASC, faster ASC) c
ON c.bib=l.bib
INNER JOIN
(SELECT v.bib, @rank:=if(@race=race,@rank:=@rank+1,1) AS rrank, @race:=race
FROM live v, (SELECT @rank:=0, @race:='') rankzero
ORDER BY race ASC, laps DESC, duration ASC, faster ASC) r
ON r.bib=l.bib
INNER JOIN users on users.bib = l.bib
WHERE users.edition=2025
EOT;
if ($race >0) {
$sqlquery = $sqlquery." AND users.race=$race";
}
mysqli_query($link,"SET SQL_BIG_SELECTS=1");
$sqlquery = $sqlquery." ORDER BY ".$order." ".$asc." ,srank asc LIMIT ".(($cpage-1)*$nbperpage).",$nbperpage ;";
$results=mysqli_query($link,$sqlquery) ;
if (mysqli_num_rows($results) == 0) {
   echo '</table>';
   echo "<p class='m-3'>la course n'a pas encore commencé</p>";

   echo "<a class ='mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea' href='http://www.ubbc.fr/ubbc-entrylist.html'>voir les inscrits</a>";
   exit;
}
echo '            <tbody>';
            //On affiche les lignes du tableau une à une à l'aide d'une boucle

            while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
            {
                if ($record["ai"]==1){
                  $aiico="<i class='ml-1' style='background-image:url(\"http://ubbc.fr/static/images/ico-synapse.png\");background-size: contain;background-repeat: no-repeat;width:15px;height:15px;display:inline-block;'></i>";
                }
                else {
                  $aiico="";
                }

                if ($record["laps"]>=$record["goal"]){
                  $expected="finisher";
                  $expcolor="fl-txt-electric";
                }
                elseif ($record["expected"]=='23:59:59' || $record["control"]=='STOP'){
                    $expected="stopped";
                    $expcolor="fl-txt-peach";
                  }
                else {
                  $expected=$record["expected"].$aiico;
                  $expcolor="";
                  if ($record["expected"][0]=='-') {
                    $expcolor="fl-txt-peach";
                  }
                }
                printf('<tr>');
                printf('<td class="thin">%s</td>',$record["srank"]);
                printf('<td class="thin %s">%s</td>',$lapscolor,$record["laps"]);
                printf('<td class="thin"><a class="rounded border fl-txt-electric fl-bd-electric fl-bg-hov-white px-1 d-inline-block w-100 text-center" href="ubbc-userlaps.php?bib=%s">%s</a></td>',$record["bib"],$record["bib"]);
                printf('<td class="large text-capitalize">%s</td>',$record["lastname"]);
                printf('<td class="large text-capitalize">%s</td>',$record["firstname"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["gender"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["category"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["rrank"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["crank"]);
                printf('<td class="medium">%s</td>',$record["faster"]);
                printf('<td class="medium">%s</td>',$record["duration"]);
                printf('<td class="medium"><span class="text-muted small">%s</span><span class="pl-3">%s</span></td>',substr($record["current"],5,5),substr($record["current"],-8));
                printf('<td class="medium">%s</td>',$record["pending"]);
                printf('<td class="medium %s">%s</td>',$expcolor,$expected);
		            printf('<td class="medium text-capitalize">%s</td>',$record["race"]);
                printf('</tr>');
            } //fin de la boucle, le tableau contient toute la BDD

            mysqli_free_result($results);
            mysqli_close($link); //deconnection de mysql
?>
            </tbody>
            <tfoot>
            </tfoot>
            </table>
        <div class="m-auto">
<?php
    if ($nbpage>1) {
      echo "<nav >";
      echo "<ul class='pagination'>";
    if ($cpage>1){
         echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-live.php?race=$race&page=".($cpage-1)."&order=$order&asc=$asc'>précédent</a></li>";
    }
    else {
        echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>précédent</a></li>";
    }

    for($i=1;$i<=$nbpage;$i++) {
        if ($i==$cpage){
            echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>$i</a></li>";
        }
        elseif(abs($i-$cpage)<4) {
            echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-live.php?race=$race&page=$i&order=$order&asc=$asc'>$i</a></li>";
        }
    }
    if ($cpage<$nbpage){
         echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-live.php?race=$race&page=".($cpage+1)."&order=$order&asc=$asc'>suivant</a></li>";
    }
    else {
        echo "<li class='page-item disabled'><a class='page-link' href='#'>suivant</a></a>";
    }
    }
?>
</div>
            <a class ="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="https://ubbc.fr/ubbc-content.php">accueil</a>
          </section>
        <!--</div>-->
    </div>
<?php include("ubbc-footer.html"); ?>

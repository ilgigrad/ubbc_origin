<?php
require_once 'includes/ubbc-functions.php';

if (isset($_GET['test']) && $_GET['test']==1){
    test_live();
}
elseif (isset($_GET['init']) && $_GET['init']==1){
    init_live();
}

refresh_live();

$link = connect();

$races=races();


if (isset($_GET['order']) && in_array($_GET['order'],array('bib','lastname','gender','category','rank','grank','crank','laps','chrono','race','club','nationality','city'))){
    $order=$_GET['order'];
}
else {
    $order='rank';
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
    $raceFilter="WHERE racelabel ='".$races[$race]."' ";
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

$sqlcount = 'SELECT count(bib) as nb FROM results '.$raceFilter.' ORDER BY bib ASC ';

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
  <?php include("ubbc-header.html"); ?>
  <section class="container-fluid">
        <div class="row flex-column">
        <!--<div class="panel-sub">    -->
            <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">RESULTS 2019</h1>
            <div class="row p-1 mx-0 mx-md-auto ">
              <form method="get" id="form_race_id" class="mx-auto fl-w-150" action="ubbc-results.php">
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
                    <th class="medium"><a  href='ubbc-results.php?race=<?php echo $race ?>&order=race&asc=<?php echo $asc ?>'>Course</a></th>
                    <th class="thin"><a href='ubbc-results.php?race=<?php echo $race ?>&order=rank&asc=<?php echo $asc ?>'>Rang</a></th>
                    <th class="medium"><a href='ubbc-results.php?race=<?php echo $race ?>&order=chrono&asc=<?php echo $asc ?>'>Chrono</a></th>
                    <th class="thin"><a href='ubbc-results.php?race=<?php echo $race ?>&order=laps&asc=<?php echo $asc ?>'>Tours</a></th>
                    <th class="thin"><a href='ubbc-results.php?race=<?php echo $race ?>&order=bib&asc=<?php echo $asc ?>'>BIB</a></th>
                    <th class="large"><a href='ubbc-results.php?race=<?php echo $race ?>&order=lastname&asc=<?php echo $asc ?>'>Nom</a></th>
                    <th class="large"><a href='ubbc-results.php?race=<?php echo $race ?>&order=firstname&asc=<?php echo $asc ?>'>Pr&eacute;nom</a></th>
                    <th class="thin"><a href='ubbc-results.php?race=<?php echo $race ?>&order=gender&asc=<?php echo $asc ?>'>Genre</a></th>
                    <th class="thin"><a href='ubbc-results.php?race=<?php echo $race ?>&order=grank&asc=<?php echo $asc ?>'>Rang Genre</a></th>
                    <th class="thin"><a href='ubbc-results.php?race=<?php echo $race ?>&order=category&asc=<?php echo $asc ?>'>Cat&eacute;gorie</a></th>
                    <th class="thin"><a href='ubbc-results.php?race=<?php echo $race ?>&order=crank&asc=<?php echo $asc ?>'>Rang Categ</a></th>
                    <th class="medium"><a href='ubbc-results.php?race=<?php echo $race ?>&order=club&asc=<?php echo $asc ?>'>Club</a></th>
                    <th class="medium"><a href='ubbc-results.php?race=<?php echo $race ?>&order=nationality&asc=<?php echo $asc ?>'>Pays</a></th>
                    <th class="medium"><a href='ubbc-results.php?race=<?php echo $race ?>&order=city&asc=<?php echo $asc ?>'>Ville</a></th>

                </tr>
                </thead>
<?php


$sqlquery = <<< EOT
  SELECT
    l.bib,l.lastname,l.firstname,l.gender,l.category,l.club,l.nationality,l.city,l.laps,l.chrono,l.racelabel as race,
    r.rank as rank, c.rank as crank, g.rank as grank
  FROM results l
  INNER JOIN
    (SELECT l.bib, @rank:=@rank+1 AS rank FROM results_2019 l,(SELECT @rank:=0) r $raceFilter ORDER BY laps DESC, chrono ASC) r
    ON r.bib=l.bib
  INNER JOIN
    (SELECT l.bib, @rank:=if(@gender=gender,@rank+1,1) AS rank, @gender:=gender FROM results l,(select @rank:=0, @gender:='') r $raceFilter ORDER BY gender ASC, laps DESC, chrono ASC) g
    ON g.bib=l.bib
  INNER JOIN
    (SELECT bib, @rank:=if(@category=category,@rank:=@rank+1,1) AS rank, @category:=category FROM results l,(SELECT @rank:=0, @category:='') r $raceFilter ORDER BY gender ASC, category ASC, laps DESC, chrono ASC) c
    ON c.bib=l.bib
  $raceFilter

EOT;

mysqli_query($link,"SET SQL_BIG_SELECTS=1");
$sqlquery = $sqlquery." ORDER BY ".$order." ".$asc." ,rank asc LIMIT ".(($cpage-1)*$nbperpage).",$nbperpage";
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

                printf('<tr>');
                printf('<td class="medium text-capitalize">%s</td>',$record["race"]);
                printf('<td class="thin">%s</td>',$record["rank"]);
                printf('<td class="medium">%s</td>',$record["chrono"]);
                printf('<td class="thin %s">%s</td>',$lapscolor,$record["laps"]);
                printf('<td class="thin"><a class="rounded border fl-txt-electric fl-bd-electric fl-bg-hov-white px-1 d-inline-block w-100 text-center" href="ubbc-userlaps.php?bib=%s">%s</a></td>',$record["bib"],$record["bib"]);
                printf('<td class="large text-capitalize">%s</td>',$record["lastname"]);
                printf('<td class="large text-capitalize">%s</td>',$record["firstname"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["gender"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["grank"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["category"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["crank"]);
                printf('<td class="medium">%s</td>',$record["club"]);
                printf('<td class="medium">%s</td>',$record["nationality"]);
                printf('<td class="medium">%s</td>',$record["city"]);

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
         echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-results.php?race=$race&page=".($cpage-1)."&order=$order&asc=$asc'>précédent</a></li>";
    }
    else {
        echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>précédent</a></li>";
    }

    for($i=1;$i<=$nbpage;$i++) {
        if ($i==$cpage){
            echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>$i</a></li>";
        }
        elseif(abs($i-$cpage)<4) {
            echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-results.php?race=$race&page=$i&order=$order&asc=$asc'>$i</a></li>";
        }
    }
    if ($cpage<$nbpage){
         echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-results.php?race=$race&page=".($cpage+1)."&order=$order&asc=$asc'>suivant</a></li>";
    }
    else {
        echo "<li class='page-item disabled'><a class='page-link' href='#'>suivant</a></a>";
    }
    }
?>
</div>
            <a class ="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="http://www.ubbc.fr/ubbc-content.php">accueil</a>
        <!--</div>-->
    </div>
</section>
<?php include("ubbc-footer.html"); ?>

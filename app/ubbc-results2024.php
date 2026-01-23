<?php
require_once 'includes/ubbc-functions.php';

if (isset($_GET['test']) && $_GET['test']==1){
    test_live();
}
elseif (isset($_GET['init']) && $_GET['init']==1){
    init_live();
}

// Valeurs par défaut
$nbpage = (int)($_GET['nbpage'] ?? 1);
if ($nbpage < 1) $nbpage = 1;

// $races doit être un tableau
$races = $races ?? [];
if (!is_array($races)) $races = [];



$link = connect();




if (isset($_GET['order']) && in_array($_GET['order'],array('bib','lastname','gender','category','srank','rrank','crank','faster','duration','laps','chrono','average','race'))){
    $order=$_GET['order'];
}
else {
    $order='race desc, rrank';
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

$race = isset($_GET['race']) ? (int)$_GET['race'] : 0;

if ($race > 0) {
    $raceFilter = "WHERE race = $race ";
} else {
    $raceFilter = '';
}
if ($order=="grank"){
    $order='gender asc, grank';
}
if ($order=="crank"){
    $order='gender asc, crank';
}

$sqlcount = 'SELECT count(bib) as nb FROM results_2024 '.$raceFilter.' ORDER BY bib ASC ';
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
        <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">RESULTATS UBBC 10 - 2024</h1>
        <div class="row p-1 mx-0 mx-md-auto ">
            <!--  <p class="fl-txt-prune fl-txt-30 text-center"> <?php if ($nbpage>1) {echo 'page : '.$cpage;} ?></p>-->
        </div>
        <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
            <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
            <tr>

                <th class="medium"><a  href='ubbc-results2024.php?race=<?php echo $race ?>&order=raceid&asc=<?php echo $asc ?>'>Course</a></th>
                <th class="thin"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=rrank&asc=<?php echo $asc ?>'>Rang Course</a></th>
                <th class="medium"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=chrono&asc=<?php echo $asc ?>'>Chrono</a></th>
                <th class="medium"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=faster&asc=<?php echo $asc ?>'>Record</a></th>
                <th class="thin"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=bib&asc=<?php echo $asc ?>'>BIB</a></th>
                <th class="large"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=lastname&asc=<?php echo $asc ?>'>Nom</a></th>
                <th class="large"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=firstname&asc=<?php echo $asc ?>'>Pr&eacute;nom</a></th>
                <th class="thin"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=gender&asc=<?php echo $asc ?>'>Genre</a></th>
                <th class="thin"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=category&asc=<?php echo $asc ?>'>Cat&eacute;gorie</a></th>
                <th class="thin"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=crank&asc=<?php echo $asc ?>'>Rang Genre</a></th>
                <th class="thin"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=laps&asc=<?php echo $asc ?>'>Tours</a></th>
                <th class="medium"><a href='ubbc-results2024.php?race=<?php echo $race ?>&order=duration&asc=<?php echo $asc ?>'>Durée</a></th>

            </tr>
            </thead>
            <?php


            $sqlquery = <<< EOT
SELECT
l.bib,l.lastname,l.firstname,l.gender,l.category,
l.faster as faster,l.race_duration as chrono ,l.laps,
l.run_duration as duration,
l.race,
l.rrank,
l.crank,
l.srank
FROM results_2024 l
EOT;

            mysqli_query($link,"SET SQL_BIG_SELECTS=1");
            $sqlquery = $sqlquery." ORDER BY ".$order." ".$asc." ,srank asc LIMIT ".(($cpage-1)*$nbperpage).",$nbperpage ;";
            $results=mysqli_query($link,$sqlquery) ;
            if (mysqli_num_rows($results) == 0) {
                echo '</table>';
                echo "<p class='m-3'>résultats indisponibles</p>";
                exit;
            }
            echo '            <tbody>';
            //On affiche les lignes du tableau une à une à l'aide d'une boucle

            while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
            {

                printf('<tr>');
                printf('<td class="medium text-capitalize">%s</td>',$record["race"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["rrank"]);
                printf('<td class="medium">%s</td>',$record["chrono"]);
                printf('<td class="medium">%s</td>',$record["faster"]);
                printf('<td class="thin rounded border fl-txt-electric fl-bd-electric fl-bg-hov-white px-1 d-inline-block w-100 text-center">%s</td>',$record["bib"]);
                printf('<td class="large text-capitalize">%s</td>',$record["lastname"]);
                printf('<td class="large text-capitalize">%s</td>',$record["firstname"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["gender"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["category"]);
                printf('<td class="thin text-uppercase">%s</td>',$record["crank"]);
                printf('<td class="thin ">%s</td>',$record["laps"]);
                printf('<td class="medium">%s</td>',$record["duration"]);

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
                    echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-results2024.php?race=$race&page=".($cpage-1)."&order=$order&asc=$asc'>précédent</a></li>";
                }
                else {
                    echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>précédent</a></li>";
                }

                for($i=1;$i<=$nbpage;$i++) {
                    if ($i==$cpage){
                        echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>$i</a></li>";
                    }
                    elseif(abs($i-$cpage)<4) {
                        echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-results2024.php?race=$race&page=$i&order=$order&asc=$asc'>$i</a></li>";
                    }
                }
                if ($cpage<$nbpage){
                    echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-results2024.php?race=$race&page=".($cpage+1)."&order=$order&asc=$asc'>suivant</a></li>";
                }
                else {
                    echo "<li class='page-item disabled'><a class='page-link' href='#'>suivant</a></a>";
                }
            }
            ?>
        </div>
        <a class ="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="http://www.ubbc.fr/ubbc-content.php">accueil</a>
</section>
<!--</div>-->
</div>
<?php include("ubbc-footer.html"); ?>

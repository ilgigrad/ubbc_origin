<?php
require_once 'includes/ubbc-functions.php';

//$url1=$_SERVER['REQUEST_URI'];
//header("Refresh: 10; URL=$url1");

if (isset($_GET['order']) && in_array($_GET['order'],array('bib','lastname','firstname','time','control','hostname'))){
    $order=$_GET['order'];
}
else {
    $order='bib';
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


$sqlquery = "SELECT l.id, u.lastname,u.firstname,u.bib,l.time,l.control,IFNULL(d.hostname,'MANUAL') FROM laps l INNER JOIN bibs b ON l.uid=b.uid INNER JOIN users u ON u.bib=b.bib LEFT OUTER JOIN devices d ON l.device=d.id ORDER BY $order $asc, u.bib asc";
//$sqlquery = "SELECT l.id, u.lastname,u.firstname,u.bib,l.time,l.control,IFNULL(d.hostname,'MANUAL') FROM laps l INNER JOIN users u ON u.bib=l.bib LEFT OUTER JOIN devices d ON l.device=d.id ORDER BY $order $asc, u.bib asc";

$link = connect();

$sqlcount="SELECT count(distinct(s.id)) AS nb,  count(distinct(s.bib)) AS nbusers  FROM ($sqlquery) as s";

$results=mysqli_query($link,$sqlcount);
$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
$nblaps=$record['nb'];
$nbusers=$record['nbusers'];
mysqli_free_result($results);
mysqli_close($link);//deconnection de mysql
$nbperpage=25;
$nbpage=ceil($nblaps/$nbperpage);
if (isset($_GET['page']) && $_GET['page']>0 && $_GET['page']<=$nbpage){
        $cpage=$_GET['page'];
}
else {
    $cpage=1;
}

?>


<?php include("ubbc-header.html");?>
<section class="container-fluid">
    <div class="row flex-column">
      <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">LAPS</h1>
      <div class="row p-1 mx-0">
        <div class="row order-1  p-1 mx-0 mx-md-1">
          <div class="btn-group px-md-2 px-0">
            <a href="ubbc-grid.php" class="btn fl-bg-electric fl-bg-hov-peach fl-txt-white px-2"><i class="fal fa-grid mx-1"></i>grid</a>
          </div>
        </div>
        <div class="row order-3 p-1 mx-0 mx-md-1">
          <form method="post" id="form_lap_id" class="" action="includes/ubbc-laps-functions.php">
            <input type="hidden" id="id_id" value="" name="id">
            <div class="input-group">
              <select id="bib_id" name="bib" class="custom-select fl-w-75" >
                <?php
                  $noBibs=bibs('bibs');
                  echo "<option value='0'>select a bib</option>";
                  foreach($noBibs as $bib=>$lastname){
                    $sbib=sprintf("%04d",$bib);
                    echo "<option value='$bib'>$sbib : $lastname</option>";
                  }
                ?>
              <input type="datetime-local" id="time_id" name="time" class="form-control fl-w-190 px-1"  min="2025-01-01 00:00" max="2025-07-31 23:59" step="1" value="<?php
              $date = new DateTime("now", new DateTimeZone('Europe/Paris') );
              echo $date->format('Y-m-d\TH:i:s');
              //echo date('Y-m-d\TH:i:s');
              ?>">
              <select class="custom-select fl-w-90" id="control_id" name="control" >
                <?php
                  $controls=controls();
                  foreach($controls as $control=>$label){
                    echo "<option value='$label'>$label</option>";
                  }
                ?>
              </select>
              <button type="submit" id="submit_id" name="lapsubmit" value="add" class="btn btn fl-bg-electric fl-txt-white fl-bg-hov-sadsea my-md-0 my-2">Add</button>
            </div>
          </form>
        </div>
        <div class="p-1 mx-1 order-2">
          <div class="btn-group px-2">
            <button type="button" class="btn fl-bd-peach fl-txt-peach fl-bg-white disabled "><i class="fal fa-undo"></i><?php echo $nblaps; ?></button>
            <button type="button" class="btn fl-bd-peach fl-txt-peach fl-bg-white disabled "><i class="fal fa-users"></i><?php echo $nbusers; ?></button>
          </div>
        </div>
        <div class="p-1 mx-1 order-4 order-md-4">
          <div class="fl-txt-prune fl-txt-20 text-center m-0"> <?php if ($nbpage>1) {echo 'page : '.$cpage;} ?></div>
        </div>
      </div>
      <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
        <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
          <tr>
            <?php echo "<th class='thin'>delete</th>";?>
            <?php echo "<th class='thin'><a href='#' id='edit_id' onclick='modify_lap(0,0,0,0);'>edit</a></th>";?>
            <?php echo "<th class='thin'><a href='ubbc-laps.php?&order=bib&asc=$asc'>BIB</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-laps.php?&order=time&asc=$asc'>time</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-laps.php?&order=lastname&asc=$asc'>Nom</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-laps.php?&order=firstname&asc=$asc'>Pr&eacute;nom</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-laps.php?&order=control&asc=$asc'>Control</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-laps.php?&order=hostname&asc=$asc'>Device</a></th>";?>
          </tr>
        </thead>
<?php

//$sqlquery = "SELECT b.uid,b.bib,u.firstname,u.lastname,u.id FROM bibs b INNER JOIN users u ON u.bib=b.bib WHERE u.edition=2025 ORDER BY $order $asc, bib asc LIMIT ".(($cpage-1)*$nbperpage).",$nbperpage";

//$sqlquery = "SELECT b.uid,b.bib,u.firstname,u.lastname,u.id FROM bibs b LEFT OUTER JOIN users u ON u.bib=b.bib WHERE u.edition=2025 or u.edition IS NULL ORDER BY $order $asc, bib asc LIMIT ".(($cpage-1)*$nbperpage).",$nbperpage";
$sqlquery =$sqlquery." LIMIT ".(($cpage-1)*$nbperpage).",$nbperpage";
/*$sqlquery = "SELECT * FROM users_xx order by bib asc LIMIT ".(($cpage-1)*$nbperpage).",$nbperpage";*/
$link = connect();
$results=mysqli_query($link,$sqlquery) ;
if (mysqli_num_rows($results) == 0) {
   echo '</table>';
   echo "<p class='m-3'>empty table, no lap to list</p>";
}
else{
  echo '<tbody>';

  while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
  {

      printf('<tr>');
      printf('<td class="thin"><a class="fl-txt-prune fl-txt-hov-blood" href="#" onclick="delete_lap(%s);"><i class="fal fa-trash mx-2"></i> </td>',$record["id"]);
      printf('<td class="thin"><a class="fl-txt-prune fl-txt-hov-sadsea" href="#" onclick="modify_lap(%s,%s,\'%s\',\'%s\');"><i class="fas fa-pen mx-2"></i></a></td>',$record["id"],$record["bib"],$record["time"],$record["control"]);
      printf('<td class="thin">%s</td>',$record["bib"]);
      printf('<td class="large text-capitalize">%s</td>',$record["time"]);
      printf('<td class="large text-capitalize">%s</td>',$record["lastname"]);
      printf('<td class="large text-capitalize">%s</td>',$record["firstname"]);
      printf('<td class="large text-uppercase">%s</td>',$record["control"]);
      printf('<td class="large text-uppercase">%s</td>',$record["hostname"]);
      printf('</tr>');
  } //fin de la boucle, le tableau contient toute la BDD
  echo "</tbody>";
  echo "<tfoot>";
  echo "</tfoot>";
  echo "</table>";
}
mysqli_free_result($results);
mysqli_close($link);//deconnection de mysql
?>
    <div class="m-auto">
<?php

if ($nbpage>1) {
  echo "<nav >";
  echo "<ul class='pagination'>";
  if ($cpage>1){
     echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-laps.php?&page=".($cpage-1)."&order=$order&asc=$asc'>précédent</a></li>";
  }
  else {
    echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>précédent</a></li>";
  }
  for($i=1;$i<=$nbpage;$i++) {
    if ($i==$cpage){
        echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>$i</a></li>";
    }
    elseif (abs($i-$cpage)<3) {
        echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-laps.php?&page=$i&ordedocument.getElementById('form_lap_id').submit();r=$order&asc=$asc'>$i</a></li>";
    }
  }
  if ($cpage<$nbpage){
     echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-laps.php?&page=".($cpage+1)."&order=$order&asc=$asc'>suivant</a></li>";
   }
   else {
    echo "<li class='page-item disabled'><a class='page-link' href='#'>suivant</a></a>";
  }
}
?>
    </div>
    <div class="row justify-content-center" >
    <a class ="mx-1 mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-live.php"><i class="fal fa-person-running mx-1"></i>live !</a>
    <a class ="mx-1 mb-2 btn btn-lg fl-bg-electric fl-txt-white fl-bg-hov-peach" href="ubbc-grid.php"><i class="fal fa-grid mx-1"></i>grid</a>
        <a class ="mx-1 mb-2 btn btn-lg fl-bg-blood fl-txt-white fl-bg-hov-r" href="ubbc-grid-finish.php"><i class="fal  fa-flag-checkered mx-1"></i>finish</a>
        <a class ="mx-1 mb-2 btn btn-lg fl-bg-anis fl-txt-white fl-bg-hov-r" href="ubbc-liverefresh.php"><i class="fal  fa-recycle mx-1"></i>refresher</a>
  </div>
  </div>
  </div>
</section>
<script>
function delete_lap(id){
  var hidden=document.createElement('input');
  hidden.type='hidden';
  hidden.name='lapsubmit';
  hidden.value="delete";
  document.getElementById("submit_id").value="delete"; //useless the submit button is not posted when using submit() instead of clicking on the button
  document.getElementById("submit_id").innerHTML="delete";
  document.getElementById('form_lap_id').appendChild(hidden);
  document.getElementById('id_id').value=id;
  document.getElementById('form_lap_id').submit();
}

function modify_lap(id,bib,datetime,control){
  if (bib==0){
    document.getElementById("submit_id").value="add";
    document.getElementById("submit_id").innerHTML="Add";
    document.getElementById("bib_id").disabled=false;
    document.getElementById("edit_id").classList.remove("fl-txt-white", "fl-txt-hov-sadsea")
  }
  else {
    document.getElementById('id_id').value=id;
    document.getElementById("bib_id").value=bib;
    document.getElementById("control_id").value=control;
    document.getElementById("bib_id").disabled=true;
    document.getElementById("time_id").value=datetime.replace(' ','T');
    document.getElementById("submit_id").value="modify";
    document.getElementById("submit_id").innerHTML="Modify";
    document.getElementById("edit_id").classList.add("fl-txt-white", "fl-txt-hov-sadsea");
  }
}
//Date.now().toISOString().slice(0, 19).replace('T', ' ');
</script>
<?php include("ubbc-footer.html");?>

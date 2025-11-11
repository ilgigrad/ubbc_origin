<?php
require_once 'includes/ubbc-functions.php';

$url1=$_SERVER['REQUEST_URI'];
//header("Refresh: 10; URL=$url1");


if (isset($_GET['order']) && in_array($_GET['order'],array('bib','id','lastname','firstname','uid','missing'))){
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

if (isset($_GET['filter'])){
    $filter=$_GET['filter'];
  }
else{
  $filter='inner';
}


switch ($filter){
  case 'left':
    $sqlquery = "SELECT if(ifnull(u.bib,0)=0,0,1) as missing, b.uid,b.bib,u.firstname,u.lastname,u.id as id FROM bibs b LEFT OUTER JOIN users u ON u.bib=b.bib and u.edition=2025 ORDER BY $order $asc, bib asc";
    $filterTitle="ALL BIBS + RELATED USERS";
    break;
  case 'right':
    $sqlquery = "SELECT if(ifnull(b.bib,0)=0,0,1) as missing, b.uid,u.bib,u.firstname,u.lastname,u.id as id FROM bibs b RIGHT OUTER JOIN users u ON u.bib=b.bib and u.edition=2025 ORDER BY $order $asc, bib asc";
    $filterTitle="ALL USERS + RELATED BIBS";
    break;
  case 'inner':
      $sqlquery = "SELECT if(ifnull(b.bib,0)=0,0,1) as missing,b.uid,b.bib,u.firstname,u.lastname,u.id as id FROM bibs b INNER JOIN users u ON u.bib=b.bib WHERE u.edition=2025 ORDER BY $order $asc, bib asc";
      $filterTitle="BIBS LINKED TO USERS";
      break;
  default:
    $sqlquery = "SELECT if(ifnull(b.bib,0)=0,0,1) as missing, b.uid,b.bib,u.firstname,u.lastname,u.id as id FROM bibs b INNER JOIN users u ON u.bib=b.bib WHERE u.edition=2025 ORDER BY $order $asc, bib asc";
    $filterTitle="BIBS LINKED TO USERS";
  }


$link = connect();

$sqlcount="SELECT count(bib) AS nb FROM bibs";
$results=mysqli_query($link,$sqlcount) ;
$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
$nbbibs=$record['nb'];

$sqlcount="SELECT count(id) AS nb FROM users WHERE edition=2025 AND birthdate IS NOT NULL";
$results=mysqli_query($link,$sqlcount) ;
$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
$nbusers=$record['nb'];

$sqlcount = "SELECT count(s.id) as nb FROM ($sqlquery) as s";
$results=mysqli_query($link,$sqlcount) ;
$record = mysqli_fetch_array($results,MYSQLI_ASSOC);
$nb=$record['nb'];

mysqli_free_result($results);
mysqli_close($link);//deconnection de mysql
$nbperpage=25;
$nbpage=ceil($nb/$nbperpage);

if (isset($_GET['page']) && $_GET['page']>0 && $_GET['page']<=$nbpage){
        $cpage=$_GET['page'];
}
else {
    $cpage=1;
}
?>

<?php include("ubbc-header.html");?>
<section class="container-fluid px-0">
    <div class="row flex-column">
      <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center"><?php echo $filterTitle; ?></h1>
      <div class="row p-1 mx-0">
        <div class="btn-group order-1 p-1">
          <a href="includes/ubbc-bibs-functions.php?reset=true" class="btn fl-bd-light fl-bg-peach fl-bg-hov-blood fl-txt-white px-2  "><i class="fal fa-recycle mx-1"></i>reset</a>
          <a href="ubbc-bibs.php?filter=inner" class="btn fl-bd-light fl-bg-electric fl-bg-hov-sadsea fl-txt-white px-2 "><i class="fal fa-check-circle mx-1"></i>ok</a>
          <a href="ubbc-bibs.php?filter=left" class="btn fl-bd-light fl-bg-electric fl-bg-hov-sadsea fl-txt-white px-2 "><i class="fal fa-tags mx-1"></i>bibs</a>
          <a href="ubbc-bibs.php?filter=right" class="btn fl-bd-light fl-bg-electric fl-bg-hov-sadsea fl-txt-white px-2 "><i class="fal fa-users mx-1"></i>users</a>
        </div>
        <div class="row order-3 order-md-2 p-1 mx-0 mx-md-1">
          <form method="post" class="" action="includes/ubbc-bibs-functions.php">
            <input type="hidden" id="uid" name="uid" value="">
            <div class="input-group">
              <div class="input-group-prepend">
                 <input type="text" class="input-group-text fl-w-60" id="oldbib" name="oldbib" value="" placeholder="bib" readonly></span>
              </div>
              <select id="newbib" name="newbib" class="custom-select disabled fl-w-70" onchange="activatesubmit();">
                  <?php
                    $noBibs=bibs('nobibs');
                    foreach($noBibs as $bib=>$lastname){
                      $sbib=sprintf("%04d",$bib);
                      echo "<option value='$bib'>$sbib : $lastname</option>";
                    }
                  ?>
                </select>
              <button type="submit" id="newbibsubmit" class="btn btn fl-bg-electric fl-txt-white fl-bg-hov-sadsea fl-w-60 disabled"  >modify</button>
            </div>
          </form>
          <div class="btn-group px-2">
            <button type="button" class="btn fl-bd-peach fl-txt-peach fl-bg-white disabled "><i class="fal fa-tags"></i><?php echo $nbbibs; ?></button>
            <button type="button" class="btn fl-bd-peach fl-txt-peach fl-bg-white disabled "><i class="fal fa-users"></i><?php echo $nbusers; ?></button>
          </div>
        </div>
        <div class="p-1 mx-1 order-2 order-md-3">
          <div class="fl-txt-prune fl-txt-20 text-center m-0"> <?php if ($nbpage>1) {echo 'page : '.$cpage;} ?></div>
        </div>
      </div>
      <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
        <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
          <tr>
            <?php echo "<th class='thin'>edit</th>";?>
            <?php echo "<th class='thin'><a href='ubbc-bibs.php?filter=$filter&order=missing&asc=$asc'>missing</a></th>";?>
            <?php echo "<th class='thin'><a href='ubbc-bibs.php?filter=$filter&order=bib&asc=$asc'>BIB</a></th>";?>
            <?php echo "<th class='thin'><a href='ubbc-bibs.php?filter=$filter&order=id&asc=$asc'>ID</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-bibs.php?filter=$filter&order=lastname&asc=$asc'>Nom</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-bibs.php?filter=$filter&order=firstname&asc=$asc'>Pr&eacute;nom</a></th>";?>
            <?php echo "<th class='large'><a href='ubbc-bibs.php?filter=$filter&order=uid&asc=$asc'>UID</a></th>";?>
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
   echo "<p class='m-3'>empty table, no bib to list</p>";
}
else{
  echo '<tbody>';

  while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
  {
      $bib=$record["bib"];
      $uid=$record["uid"];
      if ($record["missing"]==0){
          $missing="<i class='fas fa-circle fl-txt-peach mx-2'></i>";
          $edit="<i class='fas fa-pen fl-txt-lesslight mx-2'></i>";
      }
      else {
        $missing="<i class='fas fa-circle fl-txt-anis mx-2'></i>";
        $edit="<a class='fl-txt-prune fl-txt-hov-sadsea' href='#' onclick='reaffect(\"$uid\",$bib);'><i class='fas fa-pen mx-2'></i></a>";
      }


      printf('<tr>');
      printf('<td class="thin">%s</td>',$edit);
      printf('<td class="thin">%s</td>',$missing);
      printf('<td class="thin">%s</td>',$bib);
      printf('<td class="thin text-capitalize">%s</td>',$record["id"]);
      printf('<td class="large text-capitalize">%s</td>',$record["lastname"]);
      printf('<td class="large text-capitalize">%s</td>',$record["firstname"]);
      printf('<td class="large text-uppercase">%s</td>',$record["uid"]);
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
     echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-bibs.php?filter=$filter&page=".($cpage-1)."&order=$order&asc=$asc'>précédent</a></li>";
  }
  else {
    echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>précédent</a></li>";
  }
  for($i=1;$i<=$nbpage;$i++) {
    if ($i==$cpage){
        echo "<li class='page-item disabled'><a class='page-link' href='#' tabindex='-1'>$i</a></li>";
    }
    else {
        echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-bibs.php?filter=$filter&page=$i&order=$order&asc=$asc'>$i</a></li>";
    }
  }
  if ($cpage<$nbpage){
     echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-bibs.php?filter=$filter&page=".($cpage+1)."&order=$order&asc=$asc'>suivant</a></li>";
   }
   else {
    echo "<li class='page-item disabled'><a class='page-link' href='#'>suivant</a></a>";
  }
}
?>
    </div>
    <div class="row justify-content-center" >
      <a class ="mx-1 mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-admin.php">back</a>
      <a class ="mx-1 mb-2 btn btn-lg fl-bg-gray fl-txt-white fl-bg-hov-sadsea" href="ubbc-bibs.php">refresh</a>
    </div>
  </div>
</section>
<script>
function reaffect(uid,bib){
  hidden=document.getElementById("uid");
  hidden.value=uid;
  oldbib=document.getElementById('oldbib');
  oldbib.setAttribute('placeholder',bib);
  oldbib.value=bib;
  oldbib.classList.add("fl-bg-prune","fl-txt-white");
  newbib=document.getElementById('newbib');
  newbib.classList.remove("disabled")
}

function activatesubmit(){
  newbib=document.getElementById('newbib');
  if (newbib.value>0){
    document.getElementById('newbibsubmit').classList.remove("disabled");
  }
}
</script>
<?php include("ubbc-footer.html");?>

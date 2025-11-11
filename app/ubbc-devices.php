<?php include("ubbc-header.html");?>
<section class="container-fluid">
  <div class="row flex-column">
    <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">DEVICES</h1>
    <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
      <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
        <tr>
          <th class="thin">edit</th>
          <th class="thin">ID</th>
          <th class="large">hostname</th>
          <th class="large">macaddr</th>
          <th class="large">control</th>
          <th class="large">module</th>
          <th class="thin">synchro</th>
          <th class="large">updated</th>
          <th class="thin">queue reset</th>
        </tr>
    </thead>
<?php
  require_once 'includes/ubbc-functions.php';
  $url1=$_SERVER['REQUEST_URI'];
  header("Refresh: 30; URL=$url1");
  $link = connect();
  $sqlquery = "SELECT d.id,d.hostname,d.macaddr,d.control,d.module,d.is_updated,d.last_connection,d.reset FROM devices d";
  $results=mysqli_query($link,$sqlquery);
  $moduleArr=array("<i class='fal fa-stopwatch px-2'></i>race","<i class='fal fa-tag  px-2'></i>bibs");
  $synchroArr=array("<i class='fas fa-circle fl-txt-blood px-2'></i>","<i class='fas fa-circle fl-txt-anis px-2'></i>");
  $resetArr=array("","<i class='fal fa-list-alt fl-txt-blood'></i>");

  echo "<tbody>";
  if (mysqli_num_rows($results) != 0) {
    while($record = mysqli_fetch_array($results,MYSQLI_ASSOC))
    {
      $id=$record["id"];
      $hostname=$record["hostname"];
      $module=$record["module"];
      $control=$record["control"];
      $synchro=$record["is_updated"];
      $updated=$record["last_connection"];
      $reset=$record["reset"];
      $addr=$record["macaddr"];

      printf('<tr>');
      printf('<td class="thin"><button type="button" id="%s" class="btn fl-bg-prune fl-txt-white fl-bg-hov-white fl-txt-hov-sadsea" onclick="fillModal(this);"><i class="fal fa-pen px-2"></i></button></td>',$id);
      printf('<td class="thin">%d</td>',$id);
      printf('<td class="large text-uppercase" id="host-%d" data="%s">%s</td>',$id,$hostname,$hostname);
      printf('<td class="large text-uppercase">%s</td>',$addr);
      printf('<td class="large text-uppercase" id="control-%s"  data="%s">%s</td>',$id,$control,$control);
      printf('<td class="large text-uppercase" id="module-%s" data="%s">%s</td>',$id,$module,$moduleArr[$module]);
      printf('<td class="thin text-uppercase" data="%d">%s</td>',$synchro,$synchroArr[$synchro]);
      printf('<td class="large text-uppercase">%s</td>',$updated);
      printf('<td class="thin text-uppercase" id="reset-%s" data="%s">%s</td>',$id,$reset,$resetArr[$reset]);
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
</div>
  <div class="m-auto">
    <a class ="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-admin.php">back</a>
  </div>
</section>
<div class="modal fade" id="deviceModal" tabindex="-1" role="dialog" aria-labelledby="deviceModalLabel" aria-hidden="true" style="display:none;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-center fl-txt-prune" id="deviceModalLabel">Edit Device</h5>
        <button type="button" class="close" onclick="closeModal('deviceModal');">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="modalForm" method="post" action="ubbc-devices-refresh.php">
          <input type="hidden" id="id" name="id" value="0">
          <div class="form-group">
            <label for="module">Module</label>
            <select class="form-control" id="module" name="module" >
              <option value="0">Race - record Laps</option>
              <option value="1">Bibs - create tags</option>
            </select>
          </div>
          <div class="form-group">
            <label for="control">Control point</label>
            <select class="form-control" id="control" name="control" >
                  <?php
                    $controls=controls();
                    foreach($controls as $control=>$label){
                      $sc=sprintf("%02d",$control);
                      echo "<option value='$label'>$label</option>";
                    }
                  ?>
                </select>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value=false id="reset" name="reset">
            <label class="form-check-label" for="reset">Reset Queue</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value=false id="remove" name="remove">
            <label class="form-check-label fl-txt-peach" for="remove">Remove Device</label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn fl-bg-peach fl-txt-white fl-bg-hov-sadsea" onclick="closeModal('deviceModal');">Close</button>
        <button type="button" class="btn fl-bg-prune fl-txt-white fl-bg-hov-sadsea" onclick="submitModal('modalForm');">Save</button>
      </div>
    </div>
  </div>
</div>
<script>

  function fillModal(elem){
    var dId = elem.id;
    var dHostname=document.getElementById('host-'+dId).getAttribute('data');
    var dControl=document.getElementById('control-'+dId).getAttribute('data');
    var dModule=document.getElementById('module-'+dId).getAttribute('data');
    var dReset=document.getElementById('reset-'+dId).getAttribute('data');
    document.getElementById('deviceModalLabel').innerText=dHostname;
    document.getElementById('control').value=dControl;
    document.getElementById('module').value=dModule;
    document.getElementById('reset').value= dReset==1 ? false : true;
    document.getElementById('id').value=dId;
    openModal('deviceModal');
  }

  function submitModal(id){
    document.getElementById(id).submit();
    closeModal('deviceModal');
  }

  function openModal(modalid){
    var modal=document.getElementById(modalid);
    modal.classList.add('show');
    modal.setAttribute('style','display:block;');
    modal.setAttribute('aria-hidden','true');
  }

  function closeModal(modalid){
    var modal=document.getElementById(modalid);
    modal.classList.remove('show');
    modal.setAttribute('style','display:none;');
    modal.removeAttribute('aria-hidden');
  }

</script>
<?php include("ubbc-footer.html"); ?>

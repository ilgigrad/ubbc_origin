<?php
  require_once 'includes/ubbc-functions.php';
  $link = connect();
  if (!empty($_POST['id'])){
    $id=$_POST['id'];
    $module=$_POST['module'];
    $control=$_POST['control'];
    $reset=$_POST['reset']==true ? 1:0;
    $remove=$_POST['remove']==true ? 1:0;
    if ($remove){
      $sqlquery = "DELETE FROM devices WHERE id=$id";
    }
    else {
      $sqlquery = "UPDATE devices SET control='$control', module=$module,reset=$reset, is_updated=0 where id=$id";
    }
    mysqli_query($link,$sqlquery);
  }
  mysqli_free_result($results);
  mysqli_close($link);
header('Location: '.'ubbc-devices.php');
exit();
?>

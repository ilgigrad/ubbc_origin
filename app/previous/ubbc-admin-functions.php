<?php
require_once 'includes/ubbc-functions.php';
$action = 'none';
$link = connect();
mysqli_autocommit($link, true);

if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
  $sql = "CALL reset_laps()";
  $action = 'reset';
}

if (isset($_GET['start']) && $_GET['start'] == 'true') {
  $race = isset($_GET['race']) ? intval($_GET['race']) : 0;
  $sql = "CALL mass_start($race)";
  $action = 'mass';
}

if (!isset($sql)) {
  mysqli_close($link);
  header('Location: ubbc-admin.php');
  exit();
}

mysqli_query($link, $sql);
mysqli_close($link);

if ($action == 'mass') {
  header('Location: ubbc-mass-start.php');
} else {
  header('Location: ubbc-admin.php');
}
exit();
?>
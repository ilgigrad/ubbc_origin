<?php
require_once "includes/ubbc-functions.php";
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
$link=connect();
$sqlquery = "INSERT INTO iplist(ip, created_at) VALUES ('$ip', now() );";
mysqli_query($link,$sqlquery) ;
mysqli_close($link);
?>
<?php include("ubbc-header.html");?>
<?php include("ubbc-footer.html");?>

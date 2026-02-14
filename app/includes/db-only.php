<?php
require_once __DIR__ . '/ubbc-connect.php';

function ubbc_db_connect(){
    global $ubbc_host, $ubbc_user, $ubbc_pass, $ubbc_base;
    $link = mysqli_connect($ubbc_host, $ubbc_user, $ubbc_pass, $ubbc_base);
    if (mysqli_connect_errno()) {
        throw new Exception('DB connect failed: ' . mysqli_connect_error());
    }
    mysqli_set_charset($link, 'utf8');
    mysqli_query($link, "SET time_zone = 'Europe/Paris'");
    return $link;
}
?>
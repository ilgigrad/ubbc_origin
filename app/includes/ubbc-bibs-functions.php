<?php
require_once 'ubbc-functions.php';

if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    $sqlreset = "CALL bulk_create_bibs()";
    $link = connect();
    mysqli_autocommit($link, true);
    mysqli_query($link, $sqlreset);
    mysqli_close($link);
    unset($_GET['reset']);
}

if (isset($_POST['newbib']) && isset($_POST['uid'])) {
    $uid = intval($_POST['uid']);
    $newbib = intval($_POST['newbib']);

    $sqlupdate = "UPDATE users SET bib = $newbib WHERE uid = $uid";

    $link = connect();
    mysqli_autocommit($link, true);
    mysqli_query($link, $sqlupdate);
    mysqli_close($link);

    unset($_POST['uid']);
    unset($_POST['newbib']);
}

header('Location: ubbc-bibs.php');
exit();
?>

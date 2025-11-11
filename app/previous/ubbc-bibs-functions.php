<?php
  require_once 'includes/ubbc-functions.php';
  if ( isset($_GET['reset']) && $_GET['reset']=='true' ){
      $sqldelete = "TRUNCATE TABLE bibs";
      $sqlreload = "insert into bibs(bib,uid,atr) select bib, UUID(), date_format(now(),'%Y-%m-%d-%T') from users where edition=2025";
      $link = connect();
      mysqli_autocommit ( $link , true );
      mysqli_query($link,$sqldelete) ;
      mysqli_query($link,$sqlreload) ;
      mysqli_close($link);//deconnection de mysql
      unset($_GET['reset']);
    }
  if ( isset($_POST['newbib']) && isset($_POST['uid']) ){
    $uid=$_POST['uid'];
    $newbib=$_POST['newbib'];
    $sqlupdate = "UPDATE bibs SET bib=$newbib WHERE uid='$uid'";
    $link = connect();
    mysqli_autocommit ( $link , true );
    mysqli_query($link,$sqlupdate);
    $sqlalter="ALTER TABLE bibs AUTO_INCREMENT = 1";
    mysqli_query($link,$sqlalter);
    mysqli_close($link);//deconnection de mysql
    unset($_POST['uid']);
    unset($_POST['newbib']);
  }
  header('Location: '.'ubbc-bibs.php');
  exit();
?>

<?php
  require_once 'ubbc-functions.php';
  $link = connect();
  mysqli_autocommit ( $link , true );
 
  if ( isset($_POST['lapsubmit']) && $_POST['lapsubmit']=='delete' ){
      $id=$_POST['id'];
      $sql = "CALL remove_laps($id)";
    }

  if ( isset($_POST['lapsubmit']) && $_POST['lapsubmit']=='modify' ){
        $id=$_POST['id'];
        $time=$_POST['time'];
        $control=$_POST['control'];
        $sql = "UPDATE laps set time='$time', control='$control' WHERE id='$id'";
    }
  if ( isset($_POST['lapsubmit']) && $_POST['lapsubmit']=='add' ){
          $bib=$_POST['bib'];
          $time=$_POST['time'];
          $control=$_POST['control'];
          $sql = "INSERT INTO laps (time,uid,atr,control) SELECT '$time',b.uid,b.atr,'$control' FROM bibs b WHERE b.bib=$bib";

    }
    mysqli_query($link,$sql) ;
    mysqli_close($link);//deconnection de mysql
    header('Location: '.'ubbc-laps.php');
    exit();
?>

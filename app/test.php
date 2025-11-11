<html>
<body>
<?php
if (isset($_POST['name'])){

  for ($i=0; $i<sizeof($_POST['name']);$i++){
    echo $_POST['name'][$i];
  }
}
?>
<form method="post" action="test.php">
<select multiple id="id" name="name[]" value="" style="width:50px;">
   <option value="1">A</option>
   <option value="2">B</option>
   <option value="3">C</option>
   <option value="4">D</option>
   <option value="5">E</option>
   <option value="6">F</option>
</select>
<input type="submit" value="ok">
</form>
</body>
</html>

<?php
require_once 'includes/ubbc-functions.php';

if ((isset($_POST['direct']) && $_POST['direct']==1) || (isset($_GET['direct']) && $_GET['direct']==1) || $direct==1){
  $direct=1;
}
else{
  $direct=0;
}

if (isset($_GET['uid'])){
        $uid=$_GET['uid'];
 }
 elseif (isset($_POST['uid'])){
         $uid=$_POST['uid'];
  }

if (!isset($uid)) {
    $_GET['direct']=$direct;
    require 'ubbc-newuser.php';
    exit();
 }


$id=get_id_from_token($uid);
$mail=get_mail($id);
$olduser=get_user($id);
if ($olduser!=-1){
    $user=$olduser;
}
    if (isset($_POST['pseudo'])){
        $user['pseudo']=$_POST['pseudo'];}
    if (isset($_POST['lastname'])){
        $user['lastname']=$_POST['lastname'];}
    if (isset($_POST['firstname'])){
        $user['firstname']=$_POST['firstname'];}
    if (isset($_POST['city'])){
        $user['city']=$_POST['city'];}
    if (isset($_POST['nationality'])){
        $user['nationality']=$_POST['nationality'];}
    if (isset($_POST['club'])){
        $user['club']=$_POST['club'];}
    if (isset($_POST['category'])){
        $user['category']=$_POST['category'];}
    if (isset($_POST['gender'])){
        $user['gender']=$_POST['gender'];}
    if (isset($_POST['birthdate'])){
        $user['birthdate']=$_POST['birthdate'];}
    if (isset($_POST['race'])){
        $user['race']=$_POST['race'];}
$user['id']=$id;
$user['email']=$mail;
?>

<?php include("ubbc-header.html"); ?>
<section class="container-fluid">
    <h1 class="fl-txt-gray text-uppercase pt-2 text-center">nouv<span class="fl-txt-electric">&#183;eau</span><span class="fl-txt-peach">&#183;elle</span> participant<span class="fl-txt-peach">&#183;e</span></h1>
    <form  class="<?php if (isset($createerror) && strlen($createerror)>0){echo 'border fl-bd-blood';}?>" method="post" action="/ubbc-savenewuser.php">
        <input type="hidden" name="direct" value="<?php echo $direct; ?>">
        <?php printf('<input type="hidden" name="uid" value="%s"/>',$uid);
        printf('<input type="hidden" name="id" value="%s"/>',$id);
        ?>
        <div class="form-row p-2 ">
          <div class="form-group col-12 col-md-4">
            <label >pseudo ou surnom</label>
            <?php printf('<input class="form-control" type="text" name="pseudo" placeholder="Ironman" value="%s"/>',ucwords($user['pseudo'])); ?>
          </div>
          <div class="form-group col-12 col-md-4">
            <label >nom</label>
            <?php printf('<input class="form-control " type="text" name="lastname" id="id_lastname" size="30" placeholder="Starck" value="%s"/>',ucwords($user['lastname'])); ?>
          </div>
          <div class="form-group col-12 col-md-4">
            <label >prénom</label>
            <?php printf('<input class="form-control " type="text" name="firstname" id="id_firstname" placeholder="Tony" value="%s"/>',ucwords($user['firstname'])); ?>
          </div>
        </div>
        <div class="form-row p-2">
          <div class="form-group col-12 col-md-6">
            <label>ville</label>
            <?php printf('<input class="form-control" type="text" name="city" id="id_city" size="30" placeholder="Los Angeles" value="%s"/>',ucwords($user['city'])); ?>
          </div>
          <div class="form-group col-12 col-md-6">
            <label>pays</label>
            <select class="form-control" id="nationality" name="nationality">
            <?php
             echo '<option value="###">votre nationalité</option>';
             if (strtoupper(trim($user['nationality']))=="ALG") {
                 echo '<option selected="selected" value="ALG">Algérie</option>';}
            else {
                echo '<option value="ALG">Algérie</option>';}
            if (strtoupper(trim($user['nationality']))=="ALL") {
                echo '<option selected="selected" value="ALL">Allemagne</option>';}
            else {
                echo '<option value="ALL">Allemagne</option>';}
            if (strtoupper(trim($user['nationality']))=="ARG") {
                echo '<option selected="selected" value="ARG">Argentine</option>';}
            else {
                echo '<option value="ARG">Argentine</option>';}
            if (strtoupper(trim($user['nationality']))=="AUS"){
                echo '<option selected="selected" value="AUS">Australie</option>';}
            else {
                echo '<option value="AUS">Australie</option>';}
            if (strtoupper(trim($user['nationality']))=="AUT"){
                echo '<option selected="selected" value="AUT">Autriche</option>';}
            else {
                echo '<option value="AUT">Autriche</option>';}
            if (strtoupper(trim($user['nationality']))=="BEL"){
                echo '<option selected="selected" value="BEL">Belgique</option>';}
            else {
                echo '<option value="BEL">Belgique</option>';}
            if (strtoupper(trim($user['nationality']))=="BRE"){
                echo '<option selected="selected" value="BRE">Brésil</option>';}
            else {
                echo '<option value="BRE">Brésil</option>';}
            if (strtoupper(trim($user['nationality']))=="BIE"){
                echo '<option selected="selected" value="BIE">Biélorussie</option>';}
            else {
                echo '<option value="BIE">Biélorussie</option>';}
            if (strtoupper(trim($user['nationality']))=="CAM"){
                echo '<option selected="selected" value="CAM">Cambodge</option>';}
            else {
                echo '<option value="CAM">Cambodge</option>';}
            if (strtoupper(trim($user['nationality']))=="CAN"){
                echo '<option selected="selected" value="CAN">Canada</option>';}
            else {
                echo '<option value="CAN">Canada</option>';}
            if (strtoupper(trim($user['nationality']))=="CHI"){
                echo '<option selected="selected" value="CHI">Chili</option>';}
            else {
                echo '<option value="CHI">Chili</option>';}
            if (strtoupper(trim($user['nationality']))=="CHN"){
                echo '<option selected="selected" value="CHN">Chine</option>';}
            else {
                echo '<option value="CHN">Confédération Helvétique</option>';}
            if (strtoupper(trim($user['nationality']))=="CHE"){
                echo '<option selected="selected" value="CHE">Confédération Helvétique</option>';}
            else {
                echo '<option value="CHN">Chine</option>';}
            if (strtoupper(trim($user['nationality']))=="CRO"){
                echo '<option selected="selected" value="CRO">Croatie</option>';}
            else {
                echo '<option value="CRO">Croatie</option>';}
            if (strtoupper(trim($user['nationality']))=="DAN"){
                echo '<option selected="selected" value="DAN">Danemark</option>';}
            else {
                echo '<option value="DAN">Danemark</option>';}
            if (strtoupper(trim($user['nationality']))=="EGY"){
                echo '<option selected="selected" value="EGY">Egypte</option>';}
            else {
                echo '<option value="EGY">Egypte</option>';}
            if (strtoupper(trim($user['nationality']))=="ESP"){
                echo '<option selected="selected" value="ESP">Espagne</option>';}
            else {
                echo '<option value="ESP">Espagne</option>';}
            if (strtoupper(trim($user['nationality']))=="FIN"){
                echo '<option selected="selected" value="FIN">Finlande</option>';}
            else {
                echo '<option value="FIN">Finlande</option>';}
            if (strtoupper(trim($user['nationality']))=="FRA"){
                echo '<option selected="selected" value="FRA">France</option>';}
            else {
                echo '<option value="FRA">France</option>';}
            if (strtoupper(trim($user['nationality']))=="GRE"){
                echo '<option selected="selected" value="GRE">Grèce</option>';}
            else {
                echo '<option value="GRE">Grèce</option>';}
            if (strtoupper(trim($user['nationality']))=="GBR"){
                echo '<option selected="selected" value="GBR">Grande Bretagne</option>';}
            else {
                echo '<option value="GBR">Grande Bretagne</option>';}
            if (strtoupper(trim($user['nationality']))=="HON"){
                echo '<option selected="selected" value="HON">Hongrie</option>';}
            else {
                echo '<option value="HON">Hongrie</option>';}
            if (strtoupper(trim($user['nationality']))=="ITA"){
                echo '<option selected="selected" value="ITA">Italie</option>';}
            else {
                echo '<option value="ITA">Italie</option>';}
            if (strtoupper(trim($user['nationality']))=="IRL"){
                echo '<option selected="selected" value="IRL">Irlande</option>';}
            else {
                echo '<option value="IRL">Irlande</option>';}
            if (strtoupper(trim($user['nationality']))=="JAP"){
                echo '<option selected="selected" value="JAP">Japon</option>';}
            else {
                echo '<option value="JAP">Japon</option>';}
            if (strtoupper(trim($user['nationality']))=="MAR"){
                echo '<option selected="selected" value="MAR">Maroc</option>';}
            else {
                echo '<option value="MAR">Maroc</option>';}
            if (strtoupper(trim($user['nationality']))=="NED"){
                echo '<option selected="selected" value="NED">Pays Bas</option>';}
            else {
                echo '<option value="NED">Pays Bas</option>';}
            if (strtoupper(trim($user['nationality']))=="POR"){
                echo '<option selected="selected" value="POR">Portugal</option>';}
            else {
                echo '<option value="POR">Portugal</option>';}
            if (strtoupper(trim($user['nationality']))=="RUS"){
                echo '<option selected="selected" value="RUS">Russie</option>';}
            else {
                echo '<option value="RUS">Russie</option>';}
            if (strtoupper(trim($user['nationality']))=="SUE"){
                echo '<option selected="selected" value="SUE">Suède</option>';}
            else {
                echo '<option value="SUE">Suède</option>';}
            if (strtoupper(trim($user['nationality']))=="TUN"){
                echo '<option selected="selected" value="TUN">Tunisie</option>';}
            else {
                echo '<option value="TUN">Tunisie</option>';}
            if (strtoupper(trim($user['nationality']))=="TUR"){
                echo '<option selected="selected" value="TUR">Turquie</option>';}
            else {
                echo '<option value="TUR">Turquie</option>';}
            if (strtoupper(trim($user['nationality']))=="USA"){
                echo '<option selected="selected" value="USA">USA</option>';}
            else {
                echo '<option value="USA">USA</option>';}
            if (strtoupper(trim($user['nationality']))=="UKR"){
                echo '<option selected="selected" value="UKR">Ukraine</option>';}
            else {
                echo '<option value="UKR">Ukraine</option>';}
            ?>
          </select>
        </div>
      </div>
      <div class="form-row p-2 ">
        <div class="form-group col-12">
            <label >club</label>
            <?php printf('<input class="form-control" type="text" name="club" size="30" placeholder="équipe de France" value="%s"/>',strtoupper($user['club'])); ?>
        </div>
        <div class="form-row p-2 w-100">
          <div class="form-group col-12 col-md-6">
            <label >date de naissance</label>
              <?php printf('<input class="form-control" type="date" name="birthdate" placeholder="25/12/1975" value="%s"/>',$user['birthdate']); ?>
          </div>
          <div class="col-12 col-md-6">
            <label >email</label>
            <?php printf('<input class="form-control" type="email" name="email" size="50" placeholder="email" disabled value="%s"/>',strtolower($user['email'])); ?>
          </div>
        </div>
      </div>
        <div class="form-row p-2 w-100 justify-content-center">
          <div class="form-group px-3 py-1 col-12 col-md-3">
            <legend >genre</legend>
            <div class="form-check ">
              <?php printf('<input class="form-check-input" type="radio" name="gender" value="f" %s />',ischecked(substr($user['gender'],0,2),'f')); ?>
              <label class="form-check-label">femme</label>
            </div>
            <div class="form-check">
              <?php printf('<input class="form-check-input"  type="radio" name="gender" value="h" %s />',ischecked(substr($user['gender'],0,2),'h')); ?>
              <label class="form-check-label">homme</label>
            </div>
            <div class="form-check">
              <?php printf('<input class="form-check-input"   type="radio"  name="gender" value="n" %s />',ischecked(substr($user['gender'],0,2),'n')); ?>
              <label class="form-check-label">non binaire</label>
            </div>
          </div>
          <div class="py-1 px-3 col-12 col-md-3 flex-column d-flex justify-content-center" >
          <div class="form-group py-1 px-3">
            <legend>épreuve</legend>
            <div class="form-check">
	            <?php printf('<input type="radio" name="race" value="22" %s />',ischecked(substr(strval($user['race']),0,2),'22')); ?>
              <label class="form-check-label">Puebla - 10km/500d+</label>
            </div>
            <div class="form-check">
        	    <?php printf('<input type="radio" name="race" value="23" %s />',ischecked(substr(strval($user['race']),0,1),'23')); ?>
              <label class="form-check-label">Funky - 25km/1250d+</label>
            </div>
            <div class="form-check">
              <?php printf('<input type="radio" name="race" value="24" %s />',ischecked(substr(strval($user['race']),0,1),'24')); ?>
              <label class="form-check-label">Hipster - 40km/2000d+</label>
            </div>
            <div class="form-check">
              <?php printf('<input type="radio" name="race" value="25" %s />',ischecked(substr(strval($user['race']),0,1),'25')); ?>
              <label class="form-check-label">K2 - 50km/2500d+</label>
            </div>
          </div>
          <div <div class="form-group py-1 px-3 mx-auto">
            <div class="form-check">
              <input class="form-check-input " type="checkbox" value="" id="charte" name="charte" required
              onchange="this.setCustomValidity(validity.valueMissing ? 'Veuillez acceptez l'adhésion à la charte de l'UBBC' : '');">
              <label class="form-check-label" for="charte">j'accepte <a class="fl-txt-apricot fl-txt-hov-sadsea" href="ubbc-charte.php" target="_blank">la charte</a> de l'UBBC</label>
            </div>
          </div>
          <div  class="invalid-feedback d-block">
            <?php if (isset($createerror) && strlen($createerror)>0) {
                  echo "<ul class='list-unstyled'>",$createerror,"</ul>";
              } ?>
          </div>
          <input id="id_submitform" type="submit" class="mx-auto my-3 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea d-block" value="Valider" <?php if($user['email']=='not exists!'){echo 'disabled';} ?>/>
        </div>
      <div class="form-group px-3 py-1 col-12 col-md-3 border">
        <legend>catégorie</legend>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="mi" %s />',ischecked(substr($user['category'],0,2),'mi')); ?>
            <label class="form-check-label">Minime</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="ca" %s />',ischecked(substr($user['category'],0,2),'ca')); ?>
            <label class="form-check-label">Cadet</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="ju" %s />',ischecked(substr($user['category'],0,2),'ju')); ?>
            <label class="form-check-label">Junior</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="es" %s />',ischecked(substr($user['category'],0,2),'es')); ?>
            <label class="form-check-label">Espoir</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="se" %s />',ischecked(substr($user['category'],0,2),'se')); ?>
            <label class="form-check-label">Senior</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="m0" %s />',ischecked(substr($user['category'],0,2),'m1')); ?>
            <label class="form-check-label">Master 0</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="m1" %s />',ischecked(substr($user['category'],0,2),'m1')); ?>
            <label class="form-check-label">Master 1</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="m2" %s />',ischecked(substr($user['category'],0,2),'m2')); ?>
            <label class="form-check-label">Master 2</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="m3" %s />',ischecked(substr($user['category'],0,2),'m3')); ?>
            <label class="form-check-label">Master 3</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="m4" %s />',ischecked(substr($user['category'],0,2),'m3')); ?>
            <label class="form-check-label">Master 4</label>
          </div>
          <div class="form-check ">
            <?php printf('<input class="form-check-input" type="radio" name="category" value="m5" %s />',ischecked(substr($user['category'],0,2),'m3')); ?>
            <label class="form-check-label">Master 5+</label>
          </div>
      </div>
    </div>
</form>
</section>
<?php require("ubbc-footer.html"); ?>

<?php
require_once 'includes/ubbc-functions.php';
$message=date("d/m/Y-h:i:sa") . getenv("REMOTE_ADDR");
$sujet="spotify sent";
$email="david@ubbc.fr";
sendmail($email,$sujet,$message);
?>
<html>
<header>
<META http-equiv="refresh" content="0; URL=https://open.spotify.com/playlist/0MH0ZSXexRGEub5QOpipY3?si=3QwxAM2XQrqmBwappw51nQ&pi=e-00oOPyStQBif">
</header>
<body>
</body>
</html>


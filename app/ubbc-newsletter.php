<?php
require_once 'includes/ubbc-functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>validation inscription ubbc</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="flowka.css">
</head>
<body>
    
    <header>
        <div class="logo">
            <img class="site-logo" src="logoubbc3-100.jpg" alt="UBBC" alt="UBBC3.02">
        </div>
        <nav class="topnav">
            <ul class="topul">
                <li><a href="http://www.ubbc.fr" class="topa">accueil ubbc</a></li>
                <li><a href="ubbc-entrylist.php" class="topa">les inscrits</a></li>
                <li><a href="ubbc-newuser2018.php" class="topa myubbc">m'inscrire</a></li>
            </ul>
        </nav>
    </header>   
<h1> NEWSLETTER UBBC</h1><br><br>
<p>
<?php

$users=registred_users();
for ($i=0; $i<sizeof($users); $i++){
$mail=$users[$i][0];
$id=$users[$i][1];
$sujet="INFORMATION: UBBC 3.02 J-5";
$message_html = "
    <html>
        <head>
        </head>
        <body style='font-family: Arial;font-size: 14px;line-height: 1.7;color: #fcfbfa;margin:0px; padding:10px; background-color:#615375;'>
            <h2 style='color:#fed4e0;'> Informations UBBC J-5</h2><br>
            Bonjour, <br><br>
	    <p style='padding:0px 10px; color: #fcfbfa;'>l'UBBC 3.02 aura lieu dans moins de cinq jours. <br>
	    Le rendez vous pour cet &eacute;v&eacute;nement est fix&eacute;
            au 25 juillet 2018 &agrave; 18h00.<br>
	    Nous te conseillons d'arriver un peu avant cette heure afin de d&eacute;poser tes affaires
	    et te pr&eacute;parer avant le d&eacute;part. <br>
	    Nous comptons sur toi pour ne pas oublier d'apporter de quoi approvisionner
            le ravitaillement participatif - autog&eacute;r&eacute; - eco-responsable le plus garni 		    du trail mondial.<br>
	    N'oublie pas non plus de t'&eacute;quiper d'une lampe frontale si tu envisages de 		    courir ou d'attendre les autres coureurs autour de la 'base-vie' au del&agrave; de      		    22h00 <br>  
Prends &eacute;galement quelques v&#234;tements chauds pour te rhabiller apr&egrave;s la course; Malgr&eacute; la canicule annonc&eacute;e, on se refroidit vite apr&egrave;s lorsque la nuit, fut-elle &eacute;toil&eacute;, tombe.<br>           
Pour s'hydrater, on compte nombre de fontaines &agrave; eau dans le parc et quelques toilettes &agrave;  proximit&eacute; de la zone de d&eacute;part<br>
Bon Weekend<br>
            Tu trouveras pas mal d'informations quant &agrave; l'organisation de l'&eacute;v&eacute;nement sur <a style='color:#72b2c5;' href='http://www.ubbc.fr'>le site de l'UBBC</a><br>
Tu peux aussi poser des questions sur <a style='color:#72b2c5;'href= 'https://fr-fr.facebook.com/UltraBoucleButtesChaumont/'> notre page facebook</a> ou sur le fil relatif &agrave; l'UBBC du <a style='color:#72b2c5;' href= 'http://www.kikourou.net/forum/viewtopic.php?f=19&t=41021&start=120#p1005170'>forum kikourou</a><br></p>
            <br>
            <p style='padding:0px 10px; color: #fcfbfa;'>bien &agrave; toi,<br><br>
            l'&eacute;quipe UBBC 3.02</p>
        </body>
    </html>";

echo $message_html;
echo '<br><br><br>';
sendmail($mail,$message_html,$sujet);
sleep(10);
}
?>
</p>
</body>
</html>

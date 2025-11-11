<?php
session_start();

// Simuler la connexion d'un utilisateur pour cet exemple
// En pratique, vous devriez récupérer cette information après une authentification réussie
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'user' . rand(1, 100); // Remplacer par le nom d'utilisateur réel
}

// Fonction pour obtenir l'adresse IP du visiteur
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Fonction pour obtenir les informations de géolocalisation basées sur l'IP
function getGeoInfo($ip) {
    $url = "http://ip-api.com/json/{$ip}";
    $response = file_get_contents($url);
    return json_decode($response, true);
}

// Obtenir l'adresse IP du visiteur
$ip = getUserIP();

// Obtenir le nom de l'utilisateur de la session
$username = $_SESSION['username'];

// Obtenir l'agent utilisateur du navigateur
$userAgent = $_SERVER['HTTP_USER_AGENT'];

// Analyser l'agent utilisateur pour obtenir des informations sur le navigateur et le système d'exploitation
function getBrowserInfo($userAgent) {
    $browser = "Unknown Browser";
    $os = "Unknown OS";
    
    // Liste simple des navigateurs
    $browserArray = [
        '/msie/i' => 'Internet Explorer',
        '/firefox/i' => 'Firefox',
        '/safari/i' => 'Safari',
        '/chrome/i' => 'Chrome',
        '/edge/i' => 'Edge',
        '/opera/i' => 'Opera',
        '/mobile/i' => 'Mobile Browser'
    ];

    // Liste simple des systèmes d'exploitation
    $osArray = [
        '/windows nt 10/i' => 'Windows 10',
        '/windows nt 6.3/i' => 'Windows 8.1',
        '/windows nt 6.2/i' => 'Windows 8',
        '/windows nt 6.1/i' => 'Windows 7',
        '/windows nt 6.0/i' => 'Windows Vista',
        '/windows nt 5.1/i' => 'Windows XP',
        '/windows xp/i' => 'Windows XP',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i' => 'Mac OS 9',
        '/linux/i' => 'Linux',
        '/ubuntu/i' => 'Ubuntu',
        '/iphone/i' => 'iPhone',
        '/ipod/i' => 'iPod',
        '/ipad/i' => 'iPad',
        '/android/i' => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i' => 'Mobile'
    ];

    // Chercher dans l'agent utilisateur pour identifier le navigateur
    foreach ($browserArray as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $browser = $value;
        }
    }

    // Chercher dans l'agent utilisateur pour identifier le système d'exploitation
    foreach ($osArray as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $os = $value;
        }
    }

    return array(
        'browser' => $browser,
        'os' => $os
    );
}

// Obtenir les informations du navigateur et du système d'exploitation
$browserInfo = getBrowserInfo($userAgent);

// Obtenir les informations de géolocalisation
$geoInfo = getGeoInfo($ip);

// Chemin du fichier où les informations seront enregistrées
$file = 'ips.txt';

// Ouvrir le fichier en mode append
$handle = fopen($file, 'a');

// Écrire l'adresse IP, le nom de l'utilisateur, l'agent utilisateur, le navigateur, le système d'exploitation et la ville dans le fichier
fwrite($handle, "IP: $ip, User: $username, Browser: {$browserInfo['browser']}, OS: {$browserInfo['os']}, City: {$geoInfo['city']}\n");

// Fermer le fichier
fclose($handle);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .full-width-text {
            font-size: 10vw; /* 10% of the viewport width */
            font-weight: bold;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="full-width-text">MERCI !</div>
</body>
</html>
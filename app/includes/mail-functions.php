<?php
function mailjet_api($to, $subject, $message, $headers = '') {
$apiKey = '2f6ef268a8d65f02cfc1ed83763902bd';
$apiSecret = 'ea61f59b689d2f2edb1f6dcd7b2c10e2';

// Extraction de l'expéditeur à partir des headers, sinon fallback
preg_match('/From:\s*(.*)<(.*)>/i', $headers, $matches);
$fromName = $matches[1] ?? 'Organisation UBBC';
$fromEmail = $matches[2] ?? 'contact@ubbc.fr';

$data = [
'Messages' => [[
'From' => [
'Email' => $fromEmail,
'Name' => trim($fromName),
],
'To' => [[
'Email' => $to,
]],
'Subject' => $subject,
'HTMLPart' => $message,
'CustomID' => 'UBBCMail',
]]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mailjet.com/v3.1/send');
curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:$apiSecret");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode >= 200 && $httpcode < 300) {
return true;
} else {
error_log("Erreur Mailjet ($httpcode): $response");
return false;
}
}
?>
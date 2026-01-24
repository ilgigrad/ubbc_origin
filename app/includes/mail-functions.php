<?php

$apiKey = '2f6ef268a8d65f02cfc1ed83763902bd';
$apiSecret = 'ea61f59b689d2f2edb1f6dcd7b2c10e2';

require_once __DIR__ . '/../vendor/autoload.php';
use \Mailjet\Resources;

function mailjet_api($to, $subject, $message, $from = 'contact@ubbc.fr', $fromName = 'Team UBBC') {
    global $apiKey, $apiSecret;
    $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

    $body = [
        'Messages' => [[
            'From' => [
                'Email' => $from,
                'Name' => $fromName
            ],
            'To' => [[
                'Email' => $to
            ]],
            'Subject' => $subject,
            'TextPart' => strip_tags($message),
            'HTMLPart' => $message,
            'CustomID' => "UBBCMail"
        ]]
    ];

    $response = $mj->post(Resources::$Email, ['body' => $body]);
    if (!$response->success()) {
        error_log("Erreur d'envoi Mailjet : " . print_r($response->getStatus(), true));
        return false;
    }

    return true;
}
?>
?>
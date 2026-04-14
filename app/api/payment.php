<?php
declare(strict_types=1);

// /api/payment.php
// Webhook Stripe — checkout.session.completed
// Met à jour payment_at, shirt_size, shirt_model dans ubbc_subscriptions.
//
// Correspondance email :
//   client_reference_id passé dans l'URL du Payment Link :
//   https://book.stripe.com/xxx?client_reference_id=EMAIL_DU_PARTICIPANT
//   → invisible et non modifiable par l'utilisateur
//
// Variable d'environnement requise : STRIPE_WEBHOOK_SECRET

require_once __DIR__ . '/../includes/db-only.php';

header('Content-Type: application/json; charset=utf-8');

// -------------------------
// Lecture du body brut
// (doit se faire AVANT tout autre accès à php://input)
// -------------------------
$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty_body']);
    exit;
}

// -------------------------
// Vérification signature Stripe
// -------------------------
$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET') ?: '';
if ($webhookSecret === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'webhook_secret_not_configured']);
    exit;
}

$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
if (!stripe_verify_signature($rawBody, $sigHeader, $webhookSecret)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_signature']);
    exit;
}

// -------------------------
// Décodage JSON
// -------------------------
$event = json_decode($rawBody, true);
if (!is_array($event)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

// On ne traite que checkout.session.completed
if (($event['type'] ?? '') !== 'checkout.session.completed') {
    http_response_code(200);
    echo json_encode(['ok' => true, 'skipped' => true, 'type' => $event['type'] ?? '']);
    exit;
}

$session = $event['data']['object'] ?? [];

// On ne traite que les sessions effectivement payées
if (($session['payment_status'] ?? '') !== 'paid') {
    http_response_code(200);
    echo json_encode(['ok' => true, 'skipped' => true, 'reason' => 'not_paid']);
    exit;
}

// -------------------------
// Email de correspondance
// -------------------------
// customer_details.email : email saisi par le client lors du paiement Stripe.
// Note : client_reference_id inutilisable car le @ des emails n'est pas
// encodé par Mailjet et casse le paramètre URL côté Stripe.
$email = trim((string)($session['customer_details']['email'] ?? ''));

if ($email === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'no_email']);
    exit;
}

// -------------------------
// Champs t-shirt (custom_fields de la session)
// -------------------------
$shirtModel = null;
$shirtSize  = null;
foreach (($session['custom_fields'] ?? []) as $field) {
    $key   = strtolower(trim((string)($field['key'] ?? '')));
    // Stripe renvoie la valeur dans un sous-objet selon le type (text, dropdown…)
    $value = trim((string)(
        $field['text']['value']
        ?? $field['dropdown']['value']
        ?? $field['numeric']['value']
        ?? ''
    ));
    if ($key === 'modletshirt') {
        $shirtModel = $value;
    } elseif ($key === 'tailledutshirt') {
        $shirtSize = $value;
    }
}

// -------------------------
// Date de paiement
// -------------------------
$createdAt = (int)($session['created'] ?? 0);
$paymentDatetime = $createdAt > 0
    ? date('Y-m-d H:i:s', $createdAt)
    : date('Y-m-d H:i:s');

// -------------------------
// Mise à jour en base
// -------------------------
$link = ubbc_db_connect();

$emailEsc   = mysqli_real_escape_string($link, $email);
$paymentEsc = mysqli_real_escape_string($link, $paymentDatetime);

$setParts = ["payment_at = '$paymentEsc'"];

if ($shirtModel !== null) {
    $setParts[] = "shirt_model = '" . mysqli_real_escape_string($link, $shirtModel) . "'";
}
if ($shirtSize !== null) {
    $setParts[] = "shirt_size = '" . mysqli_real_escape_string($link, $shirtSize) . "'";
}

$setClause = implode(', ', $setParts);
$sql = "UPDATE ubbc_subscriptions SET $setClause WHERE email = '$emailEsc'";

$ok = mysqli_query($link, $sql);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_error', 'detail' => mysqli_error($link)]);
    mysqli_close($link);
    exit;
}

$affected = mysqli_affected_rows($link);
mysqli_close($link);

http_response_code(200);
echo json_encode([
    'ok'      => true,
    'email'   => $email,
    'updated' => $affected,
    'paid_at' => $paymentDatetime,
], JSON_UNESCAPED_UNICODE);
exit;

// -------------------------
// Vérification signature Stripe (HMAC-SHA256)
// https://docs.stripe.com/webhooks#verify-manually
// -------------------------
function stripe_verify_signature(string $payload, string $sigHeader, string $secret): bool
{
    if ($sigHeader === '' || $secret === '') {
        return false;
    }

    // Format : t=timestamp,v1=hash1,v1=hash2,...
    $parts = [];
    foreach (explode(',', $sigHeader) as $item) {
        [$k, $v] = array_pad(explode('=', $item, 2), 2, '');
        $parts[$k][] = $v;
    }

    $timestamp  = (string)($parts['t'][0] ?? '');
    $signatures = $parts['v1'] ?? [];

    if ($timestamp === '' || empty($signatures)) {
        return false;
    }

    // Tolérance de 5 minutes
    if (abs(time() - (int)$timestamp) > 300) {
        return false;
    }

    $signedPayload = $timestamp . '.' . $payload;
    $expected      = hash_hmac('sha256', $signedPayload, $secret);

    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) {
            return true;
        }
    }

    return false;
}

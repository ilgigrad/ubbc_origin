<?php
declare(strict_types=1);

// /api/subscriptions.php
// Retourne les inscriptions créées ou modifiées, hors champs paiement / t-shirt.
//
// Méthode : GET
// Auth    : header X-UBBC-TOKEN
// Params  :
//   ?since=YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS  — filtre sur updated_at (défaut : début des temps)
//   ?event=ubbc|tds                            — filtre sur l'événement
//   ?limit=N                                   — nombre de résultats (défaut 500, max 2000)

require_once __DIR__ . '/../includes/db-only.php';

header('Content-Type: application/json; charset=utf-8');

// -------------------------
// Auth
// -------------------------
$TOKEN = getenv('UBBC_INGEST_TOKEN') ?: 'CHANGE_ME';
$hdr   = $_SERVER['HTTP_X_UBBC_TOKEN'] ?? '';
if (!hash_equals($TOKEN, (string)$hdr)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// -------------------------
// Paramètres
// -------------------------
$since = isset($_GET['since']) ? trim((string)$_GET['since']) : '';
$event = isset($_GET['event']) ? strtolower(trim((string)$_GET['event'])) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
if ($limit <= 0 || $limit > 2000) {
    $limit = 500;
}

// -------------------------
// Construction de la requête
// -------------------------
$link = ubbc_db_connect();

$conditions = [];

if ($since !== '') {
    // Accepte YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
        $since .= ' 00:00:00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since)) {
        $conditions[] = "updated_at >= '" . mysqli_real_escape_string($link, $since) . "'";
    }
}

if ($event !== '' && in_array($event, ['ubbc', 'tds'], true)) {
    $conditions[] = "event = '" . mysqli_real_escape_string($link, $event) . "'";
}

$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$sql = "
    SELECT
        id, event, race,
        email, lastname, firstname,
        birthdate, gender, cat,
        country, city, club,
        itra, licence,
        participations, availability, charte,
        motivation, contribution,
        approved, refused, review_note,
        source_file, received_at, updated_at
    FROM ubbc_subscriptions
    {$where}
    ORDER BY updated_at ASC, id ASC
    LIMIT {$limit}
";

$res = mysqli_query($link, $sql);
if (!$res) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_error', 'detail' => mysqli_error($link)]);
    mysqli_close($link);
    exit;
}

$subscriptions = [];
while ($row = mysqli_fetch_assoc($res)) {
    // Casts propres
    $row['id']             = (int)$row['id'];
    $row['itra']           = $row['itra'] !== null ? (int)$row['itra'] : null;
    $row['participations'] = (int)$row['participations'];
    $row['availability']   = (int)$row['availability'];
    $row['charte']         = (int)$row['charte'];
    $row['approved']       = (int)$row['approved'];
    $row['refused']        = (int)$row['refused'];
    $subscriptions[]       = $row;
}

mysqli_free_result($res);
mysqli_close($link);

echo json_encode([
    'ok'            => true,
    'since'         => $since ?: null,
    'event'         => $event ?: null,
    'limit'         => $limit,
    'total'         => count($subscriptions),
    'subscriptions' => $subscriptions,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

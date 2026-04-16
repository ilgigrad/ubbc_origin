<?php
declare(strict_types=1);

// /api/sync_push.php
// Flush la queue ubbc_sync_queue vers l'API Django.
// Chaque ligne modifiée (via PHP ou directement en base) est poussée,
// puis retirée de la queue.
//
// Méthode : POST
// Auth    : header X-UBBC-TOKEN

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$link = ubbc_db_connect();

// -------------------------
// Lecture de la queue
// -------------------------
$res = mysqli_query($link, "SELECT subscription_id FROM ubbc_sync_queue ORDER BY queued_at ASC");
if (!$res) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_error', 'detail' => mysqli_error($link)]);
    mysqli_close($link);
    exit;
}

$ids = [];
while ($row = mysqli_fetch_assoc($res)) {
    $ids[] = (int)$row['subscription_id'];
}
mysqli_free_result($res);

if (empty($ids)) {
    mysqli_close($link);
    echo json_encode(['ok' => true, 'pushed' => 0, 'failed' => 0]);
    exit;
}

// -------------------------
// Push + suppression de la queue
// -------------------------
$pushed = 0;
$failed = 0;
$failedIds = [];

foreach ($ids as $id) {
    $ok = ubbc_push_subscription($id, $link);
    if ($ok) {
        mysqli_query($link, "DELETE FROM ubbc_sync_queue WHERE subscription_id = " . $id);
        $pushed++;
    } else {
        $failed++;
        $failedIds[] = $id;
    }
}

mysqli_close($link);

echo json_encode([
    'ok'         => $failed === 0,
    'pushed'     => $pushed,
    'failed'     => $failed,
    'failed_ids' => $failedIds,
], JSON_UNESCAPED_UNICODE);

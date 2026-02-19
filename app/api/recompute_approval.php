<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$TOKEN = getenv('UBBC_INGEST_TOKEN') ?: 'CHANGE_ME';
$hdr = $_SERVER['HTTP_X_UBBC_TOKEN'] ?? '';
if (!hash_equals($TOKEN, $hdr)) {
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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$all = isset($_GET['all']) ? (int)$_GET['all'] : 0;

if ($id > 0) {
    $sql = "SELECT id, refused, approved, review_note, gender, participations, availability, itra
            FROM ubbc_subscriptions WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
} elseif ($all === 1) {
    $sql = "SELECT id, refused, approved, review_note, gender, participations, availability, itra
            FROM ubbc_subscriptions ORDER BY id ASC";
    $stmt = mysqli_prepare($link, $sql);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'use ?id= or ?all=1']);
    exit;
}

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$upd = mysqli_prepare(
    $link,
    "UPDATE ubbc_subscriptions SET approved = ?, review_note = ? WHERE id = ?"
);

$total = 0;
$updated = 0;

while ($row = mysqli_fetch_assoc($res)) {
    $total++;

    $computed = ubbc_compute_approval($row);

    $newApproved = (int)$computed['approved'];
    $newNote = (string)$computed['review_note'];

    if (
        $newApproved !== (int)$row['approved'] ||
        $newNote !== (string)$row['review_note']
    ) {
        mysqli_stmt_bind_param($upd, "isi", $newApproved, $newNote, $row['id']);
        mysqli_stmt_execute($upd);
        $updated++;
    }
}

mysqli_free_result($res);
mysqli_stmt_close($stmt);
mysqli_stmt_close($upd);
mysqli_close($link);

echo json_encode([
    'ok' => true,
    'total' => $total,
    'updated' => $updated
]);
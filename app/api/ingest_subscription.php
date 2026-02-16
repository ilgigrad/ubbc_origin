<?php
// /api/ingest_subscription.php

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();
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

$sourceFile = $_SERVER['HTTP_X_SOURCE_FILE'] ?? '';
$raw = file_get_contents('php://input');
if (!$raw || trim($raw) === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty_body']);
    exit;
}
if ($sourceFile === '') {
    $sourceFile = 'sha1:' . sha1($raw);
}

// Parse lignes "clé: valeur"
$lines = preg_split("/\r\n|\n|\r/", $raw);
$data = [];

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    $pos = strpos($line, ':');
    if ($pos === false) continue;

    $k = trim(substr($line, 0, $pos));
    $v = trim(substr($line, $pos + 1));

    // normalise clé + valeur
    $k = html_entity_decode($k, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $k = str_replace(["’","‘"], "'", $k);
    $k = preg_replace('/\s+/', ' ', $k);

    $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $data[$k] = $v;
}

// Mapping champs FR -> DB (cast string pour éviter warnings)
$email     = strtolower(trim((string)($data['Email'] ?? '')));
$lastname  = strtolower(trim((string)($data['Nom'] ?? '')));
$firstname = strtolower(trim((string)($data['Prénom'] ?? '')));
$birthdate = trim((string)($data['Date de naissance'] ?? ''));
$gender    = trim((string)($data['Genre'] ?? ''));
$city      = strtolower(trim((string)($data['Ville'] ?? '')));
$race      = trim((string)($data['Course'] ?? ''));
$club      = strtolower(trim((string)($data['Club'] ?? '')));
$licence   = strtolower(trim((string)($data['Numéro de licence ou PPS'] ?? '')));

$contribution = trim((string)($data['Contribution ravito'] ?? ''));
$motivation   = trim((string)($data['Motivation'] ?? ''));

$charte =
    ($data["J'accepte la charte de l'UBBC"] ?? null)
    ?? ($data["J’accepte la charte de l’UBBC"] ?? null);

$charterAccepted = (
    $charte === '1'
    || strtolower(trim((string)$charte)) === 'true'
    || strtolower(trim((string)$charte)) === 'oui'
) ? 1 : 0;

// Birthdate sanity
if ($birthdate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    $birthdate = null;
}

// Champs calculés depuis raw_text
$participations = (int) ubbc_participations_count($data['Participations UBBC'] ?? null);
$availability   = (int) ubbc_available_24_31($data['Disponibilités en juillet'] ?? null);

// itra : on force int (0 si absent)
$itra = isset($data['Index ITRA / UTMB']) ? (int)$data['Index ITRA / UTMB'] : 0;

// Insert / Upsert
$stmt = mysqli_prepare($link, "
    INSERT INTO inscriptions
      (source_file, email, lastname, firstname, birthdate, gender, city, race,
       availability, contribution, motivation, charte, raw_text,
       club, participations, itra, licence)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?,
       ?, ?, ?, ?, ?,
       ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      lastname=VALUES(lastname),
      firstname=VALUES(firstname),
      birthdate=VALUES(birthdate),
      gender=VALUES(gender),
      city=VALUES(city),
      availability=VALUES(availability),
      contribution=VALUES(contribution),
      motivation=VALUES(motivation),
      charte=VALUES(charte),
      raw_text=VALUES(raw_text),
      club=VALUES(club),
      participations=VALUES(participations),
      itra=VALUES(itra),
      licence=VALUES(licence),
      received_at=CURRENT_TIMESTAMP
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'prepare_failed', 'detail' => mysqli_error($link)]);
    exit;
}

if (mysqli_stmt_bind_param(
    $stmt,
    "ssssssssississiis",
    $sourceFile,
    $email,
    $lastname,
    $firstname,
    $birthdate,     // peut être null
    $gender,
    $city,
    $race,
    $availability,  // i
    $contribution,
    $motivation,
    $charterAccepted, // i
    $raw,
    $club,
    $participations, // i
    $itra,           // i
    $licence
)){
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'bind_failed']);
    exit;
}

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'insert_failed', 'detail' => mysqli_error($link)]);
    exit;
}
mysqli_stmt_close($stmt);

// Recompute approval uniquement pour la ligne insérée/upsertée
$insertId = mysqli_insert_id($link);
if ($insertId === 0) {
    $res = mysqli_query(
        $link,
        "SELECT id FROM inscriptions WHERE source_file = '" . mysqli_real_escape_string($link, $sourceFile) . "' LIMIT 1"
    );
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $insertId = (int)$row['id'];
    }
    if ($res) mysqli_free_result($res);
}

if ($insertId > 0) {
    $res = mysqli_query(
        $link,
        "SELECT id, refused, approved, review_note, gender, participations, availability, itra
         FROM inscriptions
         WHERE id = " . (int)$insertId
    );

    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $computed = ubbc_compute_approval($row);

        $upd = mysqli_prepare($link, "UPDATE inscriptions SET approved = ?, review_note = ? WHERE id = ?");
        if ($upd) {
            $approved = (int)$computed['approved'];
            $note = (string)$computed['review_note'];
            mysqli_stmt_bind_param($upd, "isi", $approved, $note, $insertId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
    }
    if ($res) mysqli_free_result($res);
}

http_response_code(200);
echo json_encode(['ok' => true, 'status' => 'ingested', 'source_file' => $sourceFile]);
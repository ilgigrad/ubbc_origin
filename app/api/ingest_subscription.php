<?php
declare(strict_types=1);

// /api/ingest_subscription.php

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$link = ubbc_db_connect();

// --------------------
// Auth
// --------------------
$TOKEN = getenv('UBBC_INGEST_TOKEN') ?: 'CHANGE_ME';
$hdr = $_SERVER['HTTP_X_UBBC_TOKEN'] ?? '';
if (!hash_equals($TOKEN, (string)$hdr)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --------------------
// Input
// --------------------
$sourceFile = (string)($_SERVER['HTTP_X_SOURCE_FILE'] ?? '');
$raw = file_get_contents('php://input');
if (!$raw || trim($raw) === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty_body'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($sourceFile === '') {
    $sourceFile = 'sha1:' . sha1($raw);
}

// --------------------
// Parse "clé: valeur"
// --------------------
$lines = preg_split("/\r\n|\n|\r/", $raw);
$data = [];

foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '') continue;

    $pos = strpos($line, ':');
    if ($pos === false) continue;

    $k = trim(substr($line, 0, $pos));
    $v = trim(substr($line, $pos + 1));

    // decode HTML entities + normalise apostrophes côté clé
    $k = html_entity_decode($k, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $k = str_replace(["’", "‘"], "'", $k);
    $k = preg_replace('/\s+/', ' ', $k);

    // decode HTML entities côté valeur
    $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $data[$k] = $v;
}

// --------------------
// Mapping -> DB
// --------------------
$email     = strtolower((string)($data['Email'] ?? '')) ?: null;
$lastname  = strtolower((string)($data['Nom'] ?? '')) ?: null;
$firstname = strtolower((string)($data['Prénom'] ?? $data['Prenom'] ?? '')) ?: null;
$country = strtolower((string)($data['Nationalité'] ?? '')) ?: null;
$birthdate = (string)($data['Date de naissance'] ?? '');
$birthdate = $birthdate !== '' ? $birthdate : null;
if ($birthdate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    $birthdate = null;
}

$gender = (string)($data['Genre'] ?? '');
$gender = $gender !== '' ? $gender : null;

$city = strtolower((string)($data['Ville'] ?? '')) ?: null;
$race = (string)($data['Course'] ?? '');
$race = $race !== '' ? $race : null;

$club = strtolower((string)($data['Club'] ?? '')) ?: null;

$licence = strtolower((string)($data['Numéro de licence ou PPS'] ?? $data['Numero de licence ou PPS'] ?? '')) ?: null;

$contribution = (string)($data['Contribution ravito'] ?? '');
$contribution = $contribution !== '' ? $contribution : null;

$motivation = (string)($data['Motivation'] ?? '');
$motivation = $motivation !== '' ? $motivation : null;

// Charte (clé avec apostrophes droites OU typographiques)
$charte =
    $data["J'accepte la charte de l'UBBC"]
    ?? $data["J’accepte la charte de l’UBBC"]
    ?? $data["J'accepte la charte de l’UBBC"]
    ?? $data["J’accepte la charte de l'UBBC"]
    ?? null;

$charterAccepted = (
    (string)$charte === '1'
    || strtolower((string)$charte) === 'true'
    || strtolower((string)$charte) === 'oui'
) ? 1 : 0;

// ITRA
$itraRaw = $data['Index ITRA / UTMB'] ?? $data['Index ITRA'] ?? null;
$itra = null;
if ($itraRaw !== null && trim((string)$itraRaw) !== '') {
    $itra = (int)$itraRaw;
    if ($itra <= 0) $itra = null;
}

// participations (tinyint = count)
$participations = (int)ubbc_participations_count($data['Participations UBBC'] ?? null);

// availability (tinyint(1) = dispo 24-31)
$availability = (int)ubbc_available_24_31($data['Disponibilités en juillet'] ?? $data['Disponibilites en juillet'] ?? null);

// cat (tu dis : cat en DB, calculée ici)
$cat = category_from_birthdate($birthdate);

// --------------------
// INSERT / UPSERT
// --------------------
$sql = "
INSERT INTO inscriptions
  (source_file, email, lastname, firstname, birthdate, gender, city, race,
   availability, contribution, motivation, charte, raw_text,
   club, participations, itra, licence, cat,country)
VALUES
  (?, ?, ?, ?, ?, ?, ?, ?,
   ?, ?, ?, ?, ?,
   ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  email=VALUES(email),
  lastname=VALUES(lastname),
  firstname=VALUES(firstname),
  birthdate=VALUES(birthdate),
  gender=VALUES(gender),
  city=VALUES(city),
  country=VALUES(country),         
  race=VALUES(race),
  availability=VALUES(availability),
  contribution=VALUES(contribution),
  motivation=VALUES(motivation),
  charte=VALUES(charte),
  raw_text=VALUES(raw_text),
  club=VALUES(club),
  participations=VALUES(participations),
  itra=VALUES(itra),
  licence=VALUES(licence),
  cat=VALUES(cat),
  received_at=CURRENT_TIMESTAMP
";

$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'prepare_failed',
        'detail' => mysqli_error($link)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 18 params exactement
$types = "sssssssssississiiss";
$okBind = mysqli_stmt_bind_param(
    $stmt,
    $types,
    $sourceFile,
    $email,
    $lastname,
    $firstname,
    $birthdate,
    $gender,
    $city,
    $country,
    $race,
    $availability,
    $contribution,
    $motivation,
    $charterAccepted,
    $raw,
    $club,
    $participations,
    $itra,
    $licence,
    $cat
);

if (!$okBind) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'bind_failed',
        'detail' => mysqli_stmt_error($stmt)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'execute_failed',
        'detail' => mysqli_stmt_error($stmt)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_stmt_close($stmt);

// --------------------
// Récupérer l'id (insert ou update)
// --------------------
$insertId = (int)mysqli_insert_id($link);

if ($insertId === 0) {
    $sfEsc = mysqli_real_escape_string($link, $sourceFile);
    $res = mysqli_query($link, "SELECT id FROM inscriptions WHERE source_file = '{$sfEsc}' LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $insertId = (int)$row['id'];
    }
    if ($res) mysqli_free_result($res);
}

// --------------------
// Recompute approval pour cette ligne
// --------------------
if ($insertId > 0) {
    $res = mysqli_query(
        $link,
        "SELECT id, refused, approved, review_note, gender, participations, availability, itra
         FROM inscriptions
         WHERE id = " . $insertId . " LIMIT 1"
    );

    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $computed = ubbc_compute_approval($row);
        $newApproved = (int)($computed['approved'] ?? 0);
        $newNote = (string)($computed['review_note'] ?? '');

        $upd = mysqli_prepare($link, "UPDATE inscriptions SET approved = ?, review_note = ? WHERE id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, "isi", $newApproved, $newNote, $insertId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
    }
    if ($res) mysqli_free_result($res);
}

mysqli_close($link);

http_response_code(200);
echo json_encode([
    'ok' => true,
    'status' => 'ingested',
    'source_file' => $sourceFile,
    'id' => $insertId,
    'cat' => $cat,
], JSON_UNESCAPED_UNICODE);
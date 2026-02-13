<?php
// /api/ingest_subscription.php

require_once __DIR__ . '/../includes/ubbc-functions.php'; // adapte le chemin
$link = connect();

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
    // on force une identité stable pour l'idempotence
    $sourceFile = 'sha1:' . sha1($raw);
}

// Parse lignes "clé: valeur"
$lines = preg_split("/\r\n|\n|\r/", $raw);
$data = [];

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    // split uniquement sur le premier ':'
    $pos = strpos($line, ':');
    if ($pos === false) continue;

    $k = trim(substr($line, 0, $pos));
    $v = trim(substr($line, $pos + 1));

    // decode HTML entities
    $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $data[$k] = $v;
}

// Mapping champs FR -> DB
$email = $data['Email'] ?? null;
$lastname = $data['Nom'] ?? null;
$firstname = $data['Prénom'] ?? null;
$birthdate = $data['Date de naissance'] ?? null;
$gender = $data['Genre'] ?? null;
$city = $data['Ville'] ?? null;
$race = $data['Course'] ?? null;

$availability = $data['Disponibilités en juillet'] ?? null;
$contribution = $data['Contribution ravito'] ?? null;
$motivation = $data['Motivation'] ?? null;
$charter = $data["J'accepte la charte de l'UBBC"] ?? ($data["J’accepte la charte de l’UBBC"] ?? null);
$charterAccepted = ($charter === '1' || strtolower((string)$charter) === 'true') ? 1 : 0;

// Birthdate sanity
if ($birthdate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    $birthdate = null;
}

// Insert (idempotent via uniq_source_file)
$stmt = mysqli_prepare($link, "
    INSERT INTO inscriptions
      (source_file, email, lastname, firstname, birthdate, gender, city, race,
       availability_json, contribution, motivation, charter_accepted, raw_text)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?,
       ?, ?, ?, ?, ?)
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'prepare_failed', 'detail' => mysqli_error($link)]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "sssssssssssis",
    $sourceFile, $email, $lastname, $firstname, $birthdate, $gender, $city, $race,
    $availability, $contribution, $motivation, $charterAccepted, $raw
);

if (!mysqli_stmt_execute($stmt)) {
    $errno = mysqli_errno($link);
    // Duplicate unique key
    if ($errno === 1062) {
        http_response_code(409);
        echo json_encode(['ok' => true, 'status' => 'already_ingested', 'source_file' => $sourceFile]);
        exit;
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'insert_failed', 'detail' => mysqli_error($link)]);
    exit;
}

http_response_code(200);
echo json_encode(['ok' => true, 'status' => 'ingested', 'source_file' => $sourceFile]);
<?php
declare(strict_types=1);

// /api/sync_users.php
require_once __DIR__ . '/../includes/db-only.php';

header('Content-Type: application/json; charset=utf-8');

// -------------------------
// Auth token (même mécanisme que tes autres APIs)
// -------------------------
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

// -------------------------
// Optionnel: limiter à un lot
// - ?since=YYYY-MM-DD (sur submitted_at ou received_at selon ta table)
// - ?limit=500
// -------------------------
$since = isset($_GET['since']) ? trim((string)$_GET['since']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
if ($limit <= 0) $limit = 2000;

$sinceSql = '';
if ($since !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
    // adapte le nom de colonne si besoin: submitted_at / received_at
    $sinceSql = " AND s.submitted_at >= '" . mysqli_real_escape_string($link, $since . " 00:00:00") . "'";
}

// -------------------------
// Mapping champs
// IMPORTANT: adapte les colonnes ci-dessous à TA structure réelle
// - côté source: ubbc_subscriptions s.<col>
// - côté cible:  ubbc_users u.<col>
// -------------------------
$sql = "
INSERT INTO ubbc_users
(
  email,
  lastname,
  firstname,
  birthdate,
  gender,
  category,
  city,
  club,
  licence,
  itra,
  country,
  updated_at,
  created_at
)
SELECT
  LOWER(TRIM(s.email)) AS email,

  NULLIF(TRIM(s.lastname), '')  AS lastname,
  NULLIF(TRIM(s.firstname), '') AS firstname,

  /* birthdate: si format invalide -> NULL */
  CASE
    WHEN s.birthdate REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN s.birthdate
    ELSE NULL
  END AS birthdate,

  NULLIF(TRIM(s.gender), '')  AS gender,
  NULLIF(TRIM(s.cat), '')  AS category,
  NULLIF(TRIM(s.city), '')    AS city,
  NULLIF(TRIM(s.club), '')    AS club,
  NULLIF(TRIM(s.licence), '') AS licence,

  /* itra: si vide -> NULL */
  CASE
    WHEN TRIM(COALESCE(s.itra, '')) = '' THEN NULL
    ELSE CAST(s.itra AS UNSIGNED)
  END AS itra,

  NULLIF(UPPER(TRIM(s.country)), '') AS country,

  NOW() AS updated_at,
  NOW() AS created_at

FROM ubbc_subscriptions s
WHERE s.email IS NOT NULL
  AND TRIM(s.email) <> ''
  {$sinceSql}
ORDER BY s.received_at DESC
LIMIT {$limit}

ON DUPLICATE KEY UPDATE
  updated_at = NOW(),

  /* ne pas écraser si la valeur source est NULL */
  lastname  = COALESCE(VALUES(lastname),  ubbc_users.lastname),
  firstname = COALESCE(VALUES(firstname), ubbc_users.firstname),
  birthdate = COALESCE(VALUES(birthdate), ubbc_users.birthdate),
  gender    = COALESCE(VALUES(gender),    ubbc_users.gender),
  category    = COALESCE(VALUES(category),    ubbc_users.category),  
  city      = COALESCE(VALUES(city),      ubbc_users.city),
  club      = COALESCE(VALUES(club),      ubbc_users.club),
  licence   = COALESCE(VALUES(licence),   ubbc_users.licence),
  itra      = COALESCE(VALUES(itra),      ubbc_users.itra),
  country   = COALESCE(VALUES(country),   ubbc_users.country)
";

// Exécute
$ok = mysqli_query($link, $sql);
if (!$ok) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'sql_failed',
        'detail' => mysqli_error($link),
    ]);
    exit;
}

// Attention: affected_rows sur INSERT...ON DUPLICATE KEY est "spécial" (1 insert, 2 update, 0 no-op)
$affected = mysqli_affected_rows($link);

mysqli_close($link);

echo json_encode([
    'ok' => true,
    'status' => 'synced',
    'limit' => $limit,
    'since' => $since ?: null,
    'affected_rows' => $affected,
]);
<?php
declare(strict_types=1);

// /api/register_subscription.php
// Sync ubbc_bibs from ubbc_subscriptions for a given event (UBBC/TDS)
// - inserts missing bibs for eligible subscriptions
// - deletes bibs previously created by this API when subscription becomes ineligible
// - assigns bib numbers per edition (smallest available)
// - generates 5-digit code: 4 random digits + Luhn check digit

require_once __DIR__ . '/../includes/db-only.php';

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

function h(string $s): string { return $s; } // placeholder, no HTML here

function normalize_event(?string $v): string {
    $v = strtoupper(trim((string)$v));
    if ($v === '') return 'UBBC';
    return $v;
}

function luhn_check_digit(string $digits): int {
    // digits = numeric string without check digit
    $sum = 0;
    $alt = true; // for check digit computation on rightmost position
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $n = (int)$digits[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) $n -= 9;
        }
        $sum += $n;
        $alt = !$alt;
    }
    return (10 - ($sum % 10)) % 10;
}

function make_code_5(array $usedCodes): string {
    // 4 random digits + luhn
    // loop until unique in $usedCodes
    while (true) {
        $base = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $cd = luhn_check_digit($base);
        $code = $base . (string)$cd;
        if (!isset($usedCodes[$code])) return $code;
    }
}

function pick_next_edition_id(mysqli $link, int $eventId): array {
    // Prefer next edition by date >= today; fallback to most recent by year/id.
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');

    $sql1 = "SELECT id, name, date, year FROM ubbc_editions
             WHERE event_id = ? AND date IS NOT NULL AND date >= ?
             ORDER BY date ASC, id ASC
             LIMIT 1";
    $st = mysqli_prepare($link, $sql1);
    mysqli_stmt_bind_param($st, "is", $eventId, $today);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($res) mysqli_free_result($res);
    mysqli_stmt_close($st);

    if ($row) return $row;

    $sql2 = "SELECT id, name, date, year FROM ubbc_editions
             WHERE event_id = ?
             ORDER BY (year IS NULL) ASC, year DESC, id DESC
             LIMIT 1";
    $st = mysqli_prepare($link, $sql2);
    mysqli_stmt_bind_param($st, "i", $eventId);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($res) mysqli_free_result($res);
    mysqli_stmt_close($st);

    if (!$row) {
        throw new RuntimeException("no_edition_for_event");
    }
    return $row;
}

try {
    $event = normalize_event($_GET['event'] ?? ($_POST['event'] ?? 'UBBC'));
    $dryRun = (($_GET['dry_run'] ?? ($_POST['dry_run'] ?? '0')) === '1');

    // resolve event_id
    $st = mysqli_prepare($link, "SELECT id, name FROM ubbc_events WHERE UPPER(name)=UPPER(?) LIMIT 1");
    mysqli_stmt_bind_param($st, "s", $event);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $evRow = $res ? mysqli_fetch_assoc($res) : null;
    if ($res) mysqli_free_result($res);
    mysqli_stmt_close($st);

    if (!$evRow) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'unknown_event', 'event' => $event]);
        exit;
    }
    $eventId = (int)$evRow['id'];

    // next edition
    $ed = pick_next_edition_id($link, $eventId);
    $editionId = (int)$ed['id'];

    // races mapping for edition (name -> id)
    $races = [];
    $res = mysqli_query($link, "SELECT id, name FROM ubbc_races WHERE edition_id=".(int)$editionId);
    while ($res && ($r = mysqli_fetch_assoc($res))) {
        $key = mb_strtoupper(trim((string)$r['name']), 'UTF-8');
        $races[$key] = (int)$r['id'];
    }
    if ($res) mysqli_free_result($res);

    if (count($races) === 0) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'no_races_for_edition', 'edition_id' => $editionId]);
        exit;
    }

    // existing bib numbers + codes for edition
    $usedBibs = [];
    $usedCodes = [];
    $existingPairs = []; // "race_id:user_id" => bib_id
    $res = mysqli_query($link, "SELECT id, bib, code, race_id, user_id, uid FROM ubbc_bibs WHERE edition_id=".(int)$editionId);
    while ($res && ($b = mysqli_fetch_assoc($res))) {
        $bib = (int)$b['bib'];
        $usedBibs[$bib] = true;
        $code = (string)$b['code'];
        if ($code !== '') $usedCodes[$code] = true;
        $pairKey = ((int)$b['race_id']).':'.((int)$b['user_id']);
        $existingPairs[$pairKey] = (int)$b['id'];
    }
    if ($res) mysqli_free_result($res);

    // eligible subscriptions for this event
    // NOTE: "accepted" = approved, "rejected" = refused in your schema
    $sql = "
        SELECT
            s.id AS sub_id,
            s.email,
            s.race AS sub_race,
            s.gender,
            s.itra,
            s.received_at,
            u.id AS user_id
        FROM ubbc_subscriptions s
        LEFT JOIN ubbc_users u ON u.email = s.email
        WHERE UPPER(s.event)=UPPER(?)
          AND s.approved = 1
          AND s.availability = 1
          AND s.refused = 0
        ORDER BY
          (CASE WHEN s.itra IS NOT NULL AND s.itra >= 600 THEN 1 ELSE 0 END) DESC,
          (CASE WHEN UPPER(s.gender)='F' THEN 1 ELSE 0 END) DESC,
          (CASE WHEN s.itra IS NULL THEN -1 ELSE s.itra END) DESC,
          s.received_at ASC,
          s.id ASC
    ";
    $st = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($st, "s", $event);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);

    $eligible = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $eligible[] = $row;
    }
    if ($res) mysqli_free_result($res);
    mysqli_stmt_close($st);

    // helper to get smallest free bib
    $nextBib = function() use (&$usedBibs): int {
        $i = 1;
        while (isset($usedBibs[$i])) $i++;
        $usedBibs[$i] = true;
        return $i;
    };

    $report = [
        'ok' => true,
        'event' => $event,
        'event_id' => $eventId,
        'edition_id' => $editionId,
        'edition' => $ed,
        'dry_run' => $dryRun,
        'eligible_total' => count($eligible),
        'inserted' => 0,
        'kept' => 0,
        'removed' => 0,
        'skipped_no_user' => 0,
        'skipped_no_race_match' => 0,
        'details' => [
            'no_user' => [],
            'no_race_match' => [],
            'inserted' => [],
            'removed' => [],
        ],
    ];

    // Build the set of "should exist" pairs from eligible
    $shouldPairs = []; // pairKey => ['race_id'=>, 'user_id'=>, 'sub_id'=>]
    foreach ($eligible as $e) {
        $userId = (int)($e['user_id'] ?? 0);
        if ($userId <= 0) {
            $report['skipped_no_user']++;
            $report['details']['no_user'][] = [
                'sub_id' => (int)$e['sub_id'],
                'email' => (string)$e['email'],
                'race' => (string)$e['sub_race'],
            ];
            continue;
        }

        $raceKey = mb_strtoupper(trim((string)$e['sub_race']), 'UTF-8');
        $raceId = $races[$raceKey] ?? 0;
        if ($raceId <= 0) {
            $report['skipped_no_race_match']++;
            $report['details']['no_race_match'][] = [
                'sub_id' => (int)$e['sub_id'],
                'email' => (string)$e['email'],
                'race' => (string)$e['sub_race'],
                'hint' => 'No matching ubbc_races.name in this edition',
            ];
            continue;
        }

        $pairKey = $raceId . ':' . $userId;
        $shouldPairs[$pairKey] = ['race_id' => $raceId, 'user_id' => $userId, 'sub_id' => (int)$e['sub_id']];
    }

    // Insert missing bibs
    if (!$dryRun) {
        mysqli_begin_transaction($link);
    }

    $ins = null;
    if (!$dryRun) {
        $insSql = "INSERT INTO ubbc_bibs (bib, uid, user_id, race_id, edition_id, code)
                   VALUES (?, ?, ?, ?, ?, ?)";
        $ins = mysqli_prepare($link, $insSql);
        if (!$ins) {
            throw new RuntimeException('prepare_insert_failed: ' . mysqli_error($link));
        }
    }

    foreach ($shouldPairs as $pairKey => $info) {
        if (isset($existingPairs[$pairKey])) {
            $report['kept']++;
            continue;
        }

        $bib = $nextBib();
        $code = make_code_5($usedCodes);
        $usedCodes[$code] = true;

        $uid = 'sub:' . (string)$info['sub_id'];
        $userId = (int)$info['user_id'];
        $raceId = (int)$info['race_id'];

        if ($dryRun) {
            $report['inserted']++;
            $report['details']['inserted'][] = compact('bib', 'code', 'uid', 'userId', 'raceId');
            continue;
        }

        mysqli_stmt_bind_param($ins, "isiiis", $bib, $uid, $userId, $raceId, $editionId, $code);
        if (!mysqli_stmt_execute($ins)) {
            throw new RuntimeException('insert_failed: ' . mysqli_error($link));
        }

        $report['inserted']++;
        $report['details']['inserted'][] = compact('bib', 'code', 'uid', 'userId', 'raceId');
    }

    if ($ins) mysqli_stmt_close($ins);

    // Remove bibs created by this API that are no longer eligible
    // Only those with uid like 'sub:%' for this edition.
    $toRemoveIds = [];
    $res = mysqli_query($link, "SELECT id, race_id, user_id, uid FROM ubbc_bibs WHERE edition_id=".(int)$editionId." AND uid LIKE 'sub:%'");
    while ($res && ($b = mysqli_fetch_assoc($res))) {
        $pairKey = ((int)$b['race_id']).':'.((int)$b['user_id']);
        if (!isset($shouldPairs[$pairKey])) {
            $toRemoveIds[] = (int)$b['id'];
            $report['details']['removed'][] = [
                'bib_id' => (int)$b['id'],
                'race_id' => (int)$b['race_id'],
                'user_id' => (int)$b['user_id'],
                'uid' => (string)$b['uid'],
            ];
        }
    }
    if ($res) mysqli_free_result($res);

    if (count($toRemoveIds) > 0) {
        $report['removed'] = count($toRemoveIds);

        if (!$dryRun) {
            // Delete in chunks to avoid giant IN()
            $chunkSize = 200;
            for ($i = 0; $i < count($toRemoveIds); $i += $chunkSize) {
                $chunk = array_slice($toRemoveIds, $i, $chunkSize);
                $in = implode(',', array_map('intval', $chunk));
                $delSql = "DELETE FROM ubbc_bibs WHERE id IN ($in)";
                if (!mysqli_query($link, $delSql)) {
                    throw new RuntimeException('delete_failed: ' . mysqli_error($link));
                }
            }
        }
    }

    if (!$dryRun) {
        mysqli_commit($link);
    }

    echo json_encode($report, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($link) && $link instanceof mysqli) {
        // rollback if active
        try { @mysqli_rollback($link); } catch (Throwable $x) {}
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'server_error',
        'detail' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($link) && $link instanceof mysqli) {
        mysqli_close($link);
    }
}
<?php
declare(strict_types=1);

// /live/bibs_assign.php

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();

// -------------------------
// Escaping helper (sans redeclare)
// -------------------------
if (!function_exists('live_h')) {
    function live_h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// -------------------------
// URL helper (utilise helpers si dispo)
// -------------------------
if (!function_exists('ubbc_api_url')) {
    function ubbc_api_url(string $path): string {
        $path = '/' . ltrim($path, '/');

        if (function_exists('ubbc_api_base')) {
            return rtrim((string)ubbc_api_base(), '/') . $path;
        }
        if (function_exists('ubbc_base_url')) {
            return rtrim((string)ubbc_base_url(), '/') . $path;
        }

        // fallback ultra simple
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . '://' . $host . $path;
    }
}

// -------------------------
// Détection "local" pour curl SSL (tls internal Caddy)
// -------------------------
function ubbc_is_local_host(string $url): bool {
    $host = (string)(parse_url($url, PHP_URL_HOST) ?? '');
    if ($host === '') return false;
    return (substr($host, -6) === '.local') || ($host === 'localhost') || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host);
}

// -------------------------
// API calls
// -------------------------
function api_call_bibs_assign(string $event, bool $dryRun): array {
    $token = getenv('UBBC_INGEST_TOKEN') ?: 'CHANGE_ME';

    $url = ubbc_api_url('/api/register_subscription.php') . '?event=' . urlencode($event);
    if ($dryRun) $url .= '&dry_run=1';

    $ch = curl_init($url);

    $curlOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "X-UBBC-TOKEN: {$token}",
            "Accept: application/json",
        ],
        CURLOPT_TIMEOUT        => 60,
    ];

    // En dev local, Caddy "tls internal" => souvent non reconnu par curl PHP
    if (ubbc_is_local_host($url)) {
        $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
        $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
    }

    curl_setopt_array($ch, $curlOpts);

    $body = (string)curl_exec($ch);
    $err  = (string)curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err !== '') {
        return ['ok' => false, 'error' => 'curl_error', 'detail' => $err];
    }

    // warnings HTML peuvent polluer -> on coupe avant le JSON
    $jsonStart = strpos($body, '{');
    if ($jsonStart !== false) {
        $body = substr($body, $jsonStart);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'invalid_json', 'http_code' => $code, 'raw' => $body];
    }

    $data['_http_code'] = $code;
    return $data;
}

function api_call_sync_users(): array {
    $token = getenv('UBBC_INGEST_TOKEN') ?: 'CHANGE_ME';

    $url = ubbc_api_url('/api/sync_users.php');

    $ch = curl_init($url);

    $curlOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "X-UBBC-TOKEN: {$token}",
            "Accept: application/json",
        ],
        CURLOPT_TIMEOUT        => 120,
    ];

    if (ubbc_is_local_host($url)) {
        $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
        $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
    }

    curl_setopt_array($ch, $curlOpts);

    $body = (string)curl_exec($ch);
    $err  = (string)curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err !== '') {
        return ['ok' => false, 'error' => 'curl_error', 'detail' => $err];
    }

    $jsonStart = strpos($body, '{');
    if ($jsonStart !== false) {
        $body = substr($body, $jsonStart);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'invalid_json', 'http_code' => $code, 'raw' => $body];
    }

    $data['_http_code'] = $code;
    return $data;
}

// -------------------------
// Events list (DB)
// Table ubbc_events : short_name (UBBC/TDS), name (libellé)
// -------------------------
$events = [];
$resEv = mysqli_query($link, "SELECT id, short_name, name FROM ubbc_events ORDER BY id ASC");
if ($resEv) {
    while ($r = mysqli_fetch_assoc($resEv)) {
        $sn = strtoupper(trim((string)($r['short_name'] ?? '')));
        if ($sn !== '') {
            $events[] = [
                'id' => (int)($r['id'] ?? 0),
                'short_name' => $sn,
                'name' => (string)($r['name'] ?? ''),
            ];
        }
    }
    mysqli_free_result($resEv);
}
$allowed = array_values(array_unique(array_map(fn($e) => $e['short_name'], $events)));
if (!$allowed) $allowed = ['UBBC', 'TDS']; // fallback si table vide

// -------------------------
// State (GET + POST)
// -------------------------
$event = strtoupper(trim((string)($_GET['event'] ?? ($allowed[0] ?? 'UBBC'))));
if (!in_array($event, $allowed, true)) $event = ($allowed[0] ?? 'UBBC');

$dryRun = (($_GET['dry_run'] ?? '') === '1');

$result = null;
$syncResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event = strtoupper(trim((string)($_POST['event'] ?? $event)));
    if (!in_array($event, $allowed, true)) $event = ($allowed[0] ?? 'UBBC');

    $dryRun = (($_POST['dry_run'] ?? '') === '1');

    $action = (string)($_POST['action'] ?? 'assign');

    if ($action === 'sync') {
        $syncResult = api_call_sync_users();
    } elseif ($action === 'assign') {
        $result = api_call_bibs_assign($event, $dryRun);
    } elseif ($action === 'sync_assign') {
        $syncResult = api_call_sync_users();
        // même si sync échoue, on tente assign pour avoir le diag
        $result = api_call_bibs_assign($event, $dryRun);
    }
}

// -------------------------
// Render
// -------------------------
header('Content-Type: text/html; charset=utf-8');
include(__DIR__ . '/header.php');
?>

    <div class="live-container">

        <form class="live-toolbar" method="POST" action="/live/bibs_assign.php">
            <label class="muted" for="event" style="margin-right:6px;">Event</label>

            <select name="event" id="event" class="form-select form-select-sm" style="width:auto;">
                <?php foreach ($events as $e): ?>
                    <?php
                    $sn = (string)$e['short_name'];
                    $label = $sn;
                    if (trim((string)$e['name']) !== '') $label = $sn . ' — ' . (string)$e['name'];
                    ?>
                    <option value="<?php echo live_h($sn); ?>" <?php echo ($event === $sn) ? 'selected' : ''; ?>>
                        <?php echo live_h($label); ?>
                    </option>
                <?php endforeach; ?>

                <?php if (!$events): ?>
                    <option value="UBBC" <?php echo ($event === 'UBBC') ? 'selected' : ''; ?>>UBBC</option>
                    <option value="TDS"  <?php echo ($event === 'TDS')  ? 'selected' : ''; ?>>TDS</option>
                <?php endif; ?>
            </select>

            <label style="display:flex; align-items:center; gap:8px; margin-left:10px;">
                <input type="checkbox" name="dry_run" value="1" <?php echo $dryRun ? 'checked' : ''; ?>>
                <span class="muted">dry-run (ne touche pas la DB)</span>
            </label>

            <button type="submit" name="action" value="assign" class="btn btn-electric">
                Assign bibs
            </button>

            <button type="submit" name="action" value="sync" class="btn btn-prune">
                Sync users
            </button>

            <button type="submit" name="action" value="sync_assign" class="btn btn-prune">
                Sync + assign
            </button>

            <a class="btn btn-sm btn-electric" href="/live/bibs_assign.php?event=<?php echo live_h($event); ?>">
                Reset
            </a>

            <?php if (is_array($result)): ?>
                <div class="muted ms-auto">
                    HTTP <?php echo (int)($result['_http_code'] ?? 0); ?>
                </div>
            <?php elseif (is_array($syncResult)): ?>
                <div class="muted ms-auto">
                    HTTP <?php echo (int)($syncResult['_http_code'] ?? 0); ?>
                </div>
            <?php endif; ?>
        </form>

        <?php if (is_array($syncResult)): ?>
            <div class="live-card" style="padding:14px; margin-bottom:14px;">
                <?php if (($syncResult['ok'] ?? false) === true): ?>
                    <div class="row-approved" style="font-weight:700;">sync_users OK</div>
                <?php else: ?>
                    <div class="row-refused" style="font-weight:700;">sync_users ERROR</div>
                <?php endif; ?>

                <div class="muted" style="margin-top:6px;">
                    HTTP <?php echo (int)($syncResult['_http_code'] ?? 0); ?>
                </div>

                <pre class="mono" style="white-space:pre-wrap; margin:10px 0 0;"><?php
                    echo live_h(json_encode($syncResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    ?></pre>
            </div>
        <?php endif; ?>

        <?php if ($result === null): ?>
            <div class="live-card" style="padding:14px;">
                <div class="muted">
                    Choisis un event, puis lance un dry-run ou un assign.
                </div>
            </div>

        <?php elseif (!($result['ok'] ?? false)): ?>
            <div class="live-card" style="padding:14px;">
                <div class="row-refused" style="font-weight:700;">Erreur API</div>
                <pre class="mono" style="white-space:pre-wrap; margin:10px 0 0;"><?php
                    echo live_h(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    ?></pre>
            </div>

        <?php else: ?>
            <?php
            $ed      = is_array($result['edition'] ?? null) ? $result['edition'] : [];
            $details = is_array($result['details'] ?? null) ? $result['details'] : [];

            $noUser   = is_array($details['no_user'] ?? null) ? $details['no_user'] : [];
            $noRace   = is_array($details['no_race_match'] ?? null) ? $details['no_race_match'] : [];
            $inserted = is_array($details['inserted'] ?? null) ? $details['inserted'] : [];
            $removed  = is_array($details['removed'] ?? null) ? $details['removed'] : [];
            ?>

            <div class="live-card" style="padding:14px; margin-bottom:14px;">
                <div style="display:flex; gap:16px; flex-wrap:wrap;">
                    <div><strong>Event</strong> <span class="mono"><?php echo live_h((string)($result['event'] ?? '')); ?></span></div>
                    <div><strong>Edition</strong> <span class="mono"><?php echo live_h((string)($result['edition_id'] ?? '')); ?></span></div>
                    <div><strong>Nom</strong> <?php echo live_h((string)($ed['name'] ?? '')); ?></div>
                    <div><strong>Date</strong> <span class="mono"><?php echo live_h((string)($ed['date'] ?? '')); ?></span></div>
                    <div><strong>Eligible</strong> <span class="mono"><?php echo (int)($result['eligible_total'] ?? 0); ?></span></div>
                    <div class="row-approved"><strong>Inserted</strong> <span class="mono"><?php echo (int)($result['inserted'] ?? 0); ?></span></div>
                    <div class="row-pending"><strong>Kept</strong> <span class="mono"><?php echo (int)($result['kept'] ?? 0); ?></span></div>
                    <div class="row-refused"><strong>Removed</strong> <span class="mono"><?php echo (int)($result['removed'] ?? 0); ?></span></div>
                </div>
            </div>

            <!-- NO USER -->
            <div class="live-card" style="margin-bottom:14px;">
                <div style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06);">
                    <strong class="row-pending">no_user</strong>
                    <span class="muted"> — subscriptions éligibles sans user matché (sync_users rattrapera)</span>
                    <span class="muted" style="float:right;"><?php echo count($noUser); ?></span>
                </div>
                <table class="live-table">
                    <thead>
                    <tr>
                        <th>sub_id</th>
                        <th>Email</th>
                        <th>Race</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($noUser) === 0): ?>
                        <tr><td colspan="3" class="empty">—</td></tr>
                    <?php else: ?>
                        <?php foreach ($noUser as $r): ?>
                            <tr class="row-pending">
                                <td class="mono"><?php echo (int)($r['sub_id'] ?? 0); ?></td>
                                <td class="mono"><?php echo live_h((string)($r['email'] ?? '')); ?></td>
                                <td><?php echo live_h((string)($r['race'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- NO RACE MATCH -->
            <div class="live-card" style="margin-bottom:14px;">
                <div style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06);">
                    <strong class="row-refused">no_race_match</strong>
                    <span class="muted"> — race introuvable dans l’édition</span>
                    <span class="muted" style="float:right;"><?php echo count($noRace); ?></span>
                </div>
                <table class="live-table">
                    <thead>
                    <tr>
                        <th>sub_id</th>
                        <th>Email</th>
                        <th>Race</th>
                        <th>Hint</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($noRace) === 0): ?>
                        <tr><td colspan="4" class="empty">—</td></tr>
                    <?php else: ?>
                        <?php foreach ($noRace as $r): ?>
                            <tr class="row-refused">
                                <td class="mono"><?php echo (int)($r['sub_id'] ?? 0); ?></td>
                                <td class="mono"><?php echo live_h((string)($r['email'] ?? '')); ?></td>
                                <td><?php echo live_h((string)($r['race'] ?? '')); ?></td>
                                <td class="muted"><?php echo live_h((string)($r['hint'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- INSERTED -->
            <div class="live-card" style="margin-bottom:14px;">
                <div style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06);">
                    <strong class="row-approved">inserted</strong>
                    <span class="muted"> — nouveaux dossards créés</span>
                    <span class="muted" style="float:right;"><?php echo count($inserted); ?></span>
                </div>
                <table class="live-table">
                    <thead>
                    <tr>
                        <th>bib</th>
                        <th>code</th>
                        <th>uid</th>
                        <th>user_id</th>
                        <th>race_id</th>
                        <th>edition_id</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($inserted) === 0): ?>
                        <tr><td colspan="6" class="empty">—</td></tr>
                    <?php else: ?>
                        <?php foreach ($inserted as $r): ?>
                            <tr class="row-approved">
                                <td class="mono"><?php echo (int)($r['bib'] ?? 0); ?></td>
                                <td class="mono"><?php echo live_h((string)($r['code'] ?? '')); ?></td>
                                <td class="mono"><?php echo live_h((string)($r['uid'] ?? '')); ?></td>
                                <td class="mono"><?php echo (int)($r['user_id'] ?? ($r['userId'] ?? 0)); ?></td>
                                <td class="mono"><?php echo (int)($r['race_id'] ?? ($r['raceId'] ?? 0)); ?></td>
                                <td class="mono"><?php echo (int)($r['edition_id'] ?? ($r['editionId'] ?? 0)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- REMOVED -->
            <div class="live-card">
                <div style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06);">
                    <strong class="row-refused">removed</strong>
                    <span class="muted"> — bibs supprimés (uid=sub:*) car plus éligibles</span>
                    <span class="muted" style="float:right;"><?php echo count($removed); ?></span>
                </div>
                <table class="live-table">
                    <thead>
                    <tr>
                        <th>bib_id</th>
                        <th>uid</th>
                        <th>user_id</th>
                        <th>race_id</th>
                        <th>edition_id</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($removed) === 0): ?>
                        <tr><td colspan="5" class="empty">—</td></tr>
                    <?php else: ?>
                        <?php foreach ($removed as $r): ?>
                            <tr class="row-refused">
                                <td class="mono"><?php echo (int)($r['bib_id'] ?? 0); ?></td>
                                <td class="mono"><?php echo live_h((string)($r['uid'] ?? '')); ?></td>
                                <td class="mono"><?php echo (int)($r['user_id'] ?? 0); ?></td>
                                <td class="mono"><?php echo (int)($r['race_id'] ?? 0); ?></td>
                                <td class="mono"><?php echo (int)($r['edition_id'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

<?php
mysqli_close($link);
include(__DIR__ . '/footer.php');
?>
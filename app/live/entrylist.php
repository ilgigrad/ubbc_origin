<?php
declare(strict_types=1);

// /live/entrylist.php
require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();


function live_status_dot(string $status): string {
    if ($status === 'approved') return '<span class="dot dot-green" title="approved" aria-label="approved"></span>';
    if ($status === 'refused')  return '<span class="dot dot-red" title="refused" aria-label="refused"></span>';
    return '<span class="dot dot-prune" title="pending" aria-label="pending"></span>';
}

/**
 * Extrait les clés JSON depuis raw_text sur une ligne "Label: {json...}".
 * (on gère &quot; etc + apostrophes typographiques)
 */
function live_extract_json_keys_from_raw(string $raw, array $labelCandidates): array {
    $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $raw = str_replace(["’","‘"], "'", $raw);

    foreach ($labelCandidates as $label) {
        $pattern = '/^'.preg_quote($label, '/').'\s*:\s*(.+)$/mi';
        if (!preg_match($pattern, $raw, $m)) continue;

        $value = trim($m[1]);

        $start = strpos($value, '{');
        $end   = strrpos($value, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $value = substr($value, $start, $end - $start + 1);
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $json = json_decode($value, true);
        if (is_array($json)) return array_keys($json);
    }
    return [];
}

// -------------------------
// Params
// -------------------------
$searchQuery = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$showAll = (isset($_GET['showAll']) && $_GET['showAll'] === 'yes') ? 'yes' : 'no';

// pagination
$nbperpage = 25;
$cpage = (isset($_GET['page']) && (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;

// tri (whitelist stricte)
$allowedOrder = [
    'lastname'       => 'i.lastname',
    'firstname'      => 'i.firstname',
    'gender'         => 'i.gender',
    'cat'            => 'i.cat',
    'country'        => 'i.country',
    'itra'           => 'i.itra',
    'race'           => 'i.race',
    'club'           => 'i.club',
    'city'           => 'i.city',
    'licence'        => 'i.licence',
    'participations' => 'i.participations',
    'availability'   => 'i.availability',
    'received_at'    => 'i.received_at',
    // statut = tri combiné refused/approved/pending
    'status'         => "CASE WHEN i.refused=1 THEN 2 WHEN i.approved=1 THEN 1 ELSE 0 END",
];

$orderKey = $_GET['order'] ?? 'lastname';
$order = $allowedOrder[$orderKey] ?? 'i.lastname';

$asc = (isset($_GET['asc']) && in_array($_GET['asc'], ['asc','desc'], true)) ? $_GET['asc'] : 'asc';
$dasc = ($asc === 'asc') ? 'desc' : 'asc';

// -------------------------
// Search filter
// -------------------------
$searchSql = '';
if ($searchQuery !== '') {
    $q = mysqli_real_escape_string($link, $searchQuery);
    $searchSql = " AND (
        i.email LIKE '%$q%' OR
        i.lastname LIKE '%$q%' OR
        i.firstname LIKE '%$q%' OR
        i.club LIKE '%$q%' OR
        i.city LIKE '%$q%' OR
        i.race LIKE '%$q%' OR
        i.licence LIKE '%$q%' OR
        i.review_note LIKE '%$q%' OR
        i.country LIKE '%$q%' 
    )";
}

// -------------------------
// Dataset (dedupe or not)
// -------------------------
$selectCols = "
    i.id,
    i.source_file,
    i.email,
    i.lastname,
    i.firstname,
    i.birthdate,
    i.gender,
    i.cat,
    i.country,
    i.city,
    i.race,
    i.club,
    i.licence,
    i.participations,
    i.availability,
    i.itra,
    i.review_note,
    i.approved,
    i.refused,
    i.contribution,
    i.motivation,
    i.raw_text,
    i.received_at
";

$fromSql = "FROM inscriptions i WHERE 1=1";

$whereSql = $searchSql;

// count
$sqlCount = "SELECT COUNT(*) AS nb $fromSql $whereSql";
$resCount = mysqli_query($link, $sqlCount);
$rowCount = $resCount ? mysqli_fetch_array($resCount, MYSQLI_ASSOC) : null;
$nbusers = (int)($rowCount['nb'] ?? 0);
if ($resCount) mysqli_free_result($resCount);

$nbpage = max(1, (int)ceil($nbusers / $nbperpage));
if ($cpage > $nbpage) $cpage = $nbpage;

// list
$sql = "SELECT $selectCols $fromSql $whereSql
        ORDER BY $order $asc, i.received_at DESC";

if ($showAll === 'yes') {
    $sql .= " LIMIT 100000";
    $rowNumber = 1;
} else {
    $offset = ($cpage - 1) * $nbperpage;
    $sql .= " LIMIT $offset, $nbperpage";
    $rowNumber = $offset + 1;
}

$results = mysqli_query($link, $sql);

// HTML headers
header('Content-Type: text/html; charset=utf-8');
include(__DIR__ . '/header.php');

function live_link(string $order, string $asc, string $search, string $showAll, int $page = 1): string {
    return "/live/entrylist.php?order=" . urlencode($order)
        . "&asc=" . urlencode($asc)
        . "&search=" . urlencode($search)
        . "&showAll=" . urlencode($showAll)
        . "&page=" . (int)$page;
}

?>
    <div class="live-container">

        <form class="live-toolbar" method="GET" action="/live/entrylist.php">
            <input type="text" name="search" value="<?php echo live_h($searchQuery); ?>" placeholder="nom, email, club, ville, licence, note...">

            <input type="hidden" name="showAll" value="<?php echo live_h($showAll); ?>">

            
            <div class="mobile-only">
                <select name="order" class="mobile-sort" onchange="this.form.submit()">
                    <option value="received_at" <?= $orderKey==='received_at'?'selected':'' ?>>Date</option>
                    <option value="lastname" <?= $orderKey==='lastname'?'selected':'' ?>>Nom</option>
                    <option value="firstname" <?= $orderKey==='firstname'?'selected':'' ?>>Prénom</option>
                    <option value="gender" <?= $orderKey==='gender'?'selected':'' ?>>Genre</option>
                    <option value="cat" <?= $orderKey==='cat'?'selected':'' ?>>Cat</option>
                    <option value="itra" <?= $orderKey==='itra'?'selected':'' ?>>ITRA</option>
                    <option value="race" <?= $orderKey==='race'?'selected':'' ?>>Race</option>
                    <option value="club" <?= $orderKey==='club'?'selected':'' ?>>Club</option>
                    <option value="country" <?= $orderKey==='country'?'selected':'' ?>>Nationalité</option>
                    <option value="city" <?= $orderKey==='city'?'selected':'' ?>>Ville</option>
                    <option value="participations" <?= $orderKey==='participations'?'selected':'' ?>>Participations</option>
                    <option value="availability" <?= $orderKey==='availability'?'selected':'' ?>>Disponibilité</option>
                    <option value="approved" <?= $orderKey==='approved'?'selected':'' ?>>Statut</option>
                </select>
            </div>
            <select name="asc" class="mobile-sort" onchange="this.form.submit()">
                <option value="asc" <?= $asc==='asc'?'selected':'' ?>>↑ Croissant</option>
                <option value="desc" <?= $asc==='desc'?'selected':'' ?>>↓ Décroissant</option>
            </select>

            <button class="btn btn-sm btn-electric" type="submit">Rechercher</button>

            <?php if ($showAll === 'no'): ?>
                <a class="btn btn-sm btn-electric" href="<?php echo live_link($orderKey, $asc, $searchQuery, 'yes', 1); ?>">Déplier</a>
            <?php else: ?>
                <a class="btn btn-sm btn-electric" href="<?php echo live_link($orderKey, $asc, $searchQuery, 'no', 1); ?>">Replier</a>
            <?php endif; ?>

            <?php
            $isDesc = ($orderKey === 'received_at' && $asc === 'desc');
            $nextDir = $isDesc ? 'asc' : 'desc';
            $label   = $isDesc ? 'Premières inscriptions' : 'Dernières inscriptions';
            $btnClass = $isDesc ? 'btn-prune' : 'btn-electric';
            ?>

            <a class="btn btn-sm <?php echo $btnClass; ?>"
               href="<?php echo live_link('received_at', $nextDir, $searchQuery, $showAll, 1); ?>">
                <?php echo $label; ?>
            </a>

            <div class="muted ms-auto"><?php echo (int)$nbusers; ?> entrées</div>
        </form>


        <?php
        // Mobile cards (fichier séparé)
        // On réutilise $results : on doit le re-lire -> on bufferise les lignes.
        $rows = [];
        if ($results) {
            while ($r = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
                $rows[] = $r;
            }
            mysqli_free_result($results);
        }
        ?>

        <?php include(__DIR__ . '/entrylist_mobile.php'); ?>

        <div class="live-card live-desktop">
            <table class="live-table">
                <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-name"><a href="<?php echo live_link('lastname', $dasc, $searchQuery, $showAll, 1); ?>">Nom</a></th>
                    <th class="col-first"><a href="<?php echo live_link('firstname', $dasc, $searchQuery, $showAll, 1); ?>">Prénom</a></th>
                    <th class="col-g"><a href="<?php echo live_link('gender', $dasc, $searchQuery, $showAll, 1); ?>">Gender</a></th>
                    <th class="col-cat"><a href="<?php echo live_link('cat', $dasc, $searchQuery, $showAll, 1); ?>">Cat</a></th>
                    <th class="col-country"><a href="<?php echo live_link('country', $dasc, $searchQuery, $showAll, 1); ?>">Country</a></th>
                    <th class="col-itra"><a href="<?php echo live_link('itra', $dasc, $searchQuery, $showAll, 1); ?>">Itra</a></th>
                    <th class="col-race"><a href="<?php echo live_link('race', $dasc, $searchQuery, $showAll, 1); ?>">Race</a></th>
                    <th class="col-club"><a href="<?php echo live_link('club', $dasc, $searchQuery, $showAll, 1); ?>">Club</a></th>
                    <th class="col-city"><a href="<?php echo live_link('city', $dasc, $searchQuery, $showAll, 1); ?>">City</a></th>
                    <th class="col-lic"><a href="<?php echo live_link('licence', $dasc, $searchQuery, $showAll, 1); ?>">Licence</a></th>
                    <th class="col-part"><a href="<?php echo live_link('participations', $dasc, $searchQuery, $showAll, 1); ?>">Participations</a></th>
                    <th class="col-note">review_note</th>
                    <th class="col-av"><a href="<?php echo live_link('availability', $dasc, $searchQuery, $showAll, 1); ?>">Availability</a></th>
                    <th class="col-status"><a href="<?php echo live_link('status', $dasc, $searchQuery, $showAll, 1); ?>">Statut</a></th>
                </tr>
                </thead>

                <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="14" class="empty">Pas d'inscription</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $rowClass = live_row_class($r);
                        $status = live_status($r);

                        $lastname  = live_title((string)($r['lastname'] ?? ''));
                        $firstname = live_title((string)($r['firstname'] ?? ''));
                        $gender    = (string)($r['gender'] ?? '');
                        $cat       = (string)($r['cat'] ?? '');

                        $itraVal = $r['itra'];
                        $itra = ($itraVal === null || (int)$itraVal === 0) ? '—' : (string)(int)$itraVal;

                        $race = live_upper((string)($r['race'] ?? ''));
                        $club = live_title((string)($r['club'] ?? ''));
                        $city = live_title((string)($r['city'] ?? ''));
                        $country = live_upper((string)($r['country'] ?? ''));

                        $licence = (string)($r['licence'] ?? '');

                        $participationsVal = (int)($r['participations'] ?? 0);
                        $participations = ($participationsVal <= 0) ? '—' : (string)$participationsVal;

                        $availability = (int)($r['availability'] ?? 0);
                        $review_note  = (string)($r['review_note'] ?? '');

                        $rawText = (string)($r['raw_text'] ?? '');
                        $availabilityKeys = live_extract_json_keys_from_raw($rawText, [
                            "Disponibilités en juillet",
                            "Disponibilites en juillet",
                        ]);
                        $participationKeys = live_extract_json_keys_from_raw($rawText, [
                            "Participations UBBC",
                            "Participations",
                        ]);

                        $payload = [
                            'name' => trim($firstname . ' ' . $lastname),
                            'email' => (string)($r['email'] ?? ''),
                            'birthdate' => (string)($r['birthdate'] ?? ''),
                            'race' => $race,
                            'club' => $club,
                            'city' => $city,
                            'country' => $country,
                            'cat' => $cat,
                            'licence' => $licence,
                            'itra' => $itra,
                            'received_at' => (string)($r['received_at'] ?? ''),
                            'source_file' => (string)($r['source_file'] ?? ''),
                            'approved' => (int)($r['approved'] ?? 0),
                            'refused' => (int)($r['refused'] ?? 0),
                            'status' => $status,
                            'review_note' => $review_note,
                            'motivation' => (string)($r['motivation'] ?? ''),
                            'contribution' => (string)($r['contribution'] ?? ''),
                            'availability_keys' => $availabilityKeys,
                            'participation_keys' => $participationKeys,
                        ];
                        $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        ?>
                        <tr class="<?php echo live_h($rowClass); ?>">
                            <td class="col-num"><?php echo (int)$rowNumber++; ?></td>

                            <td class="col-name">
                                <a class="entry-link" href="#"
                                   data-bs-toggle="modal" data-bs-target="#entryModal"
                                   data-entry="<?php echo $dataEntry; ?>"
                                   onclick="return false;">
                                    <?php echo live_h($lastname); ?>
                                </a>
                            </td>

                            <td class="col-first">
                                <a class="entry-link" href="#"
                                   data-bs-toggle="modal" data-bs-target="#entryModal"
                                   data-entry="<?php echo $dataEntry; ?>">
                                    <?php echo live_h($firstname); ?>
                                </a>
                            </td>

                            <td class="col-g"><?php echo live_h($gender); ?></td>
                            <td class="col-cat"><?php echo live_h($cat); ?></td>
                            <td class="col-country"><?php echo live_h($country); ?></td>
                            <td class="col-itra"><?php echo live_h($itra); ?></td>
                            <td class="col-race"><?php echo live_h($race); ?></td>
                            <td class="col-club"><?php echo live_h($club); ?></td>
                            <td class="col-city"><?php echo live_h($city); ?></td>
                            <td class="col-lic"><?php echo live_h($licence !== '' ? $licence : '—'); ?></td>
                            <td class="col-part"><?php echo live_h($participations); ?></td>
                            <td class="col-note"><?php echo live_h($review_note); ?></td>

                            <td class="col-av">
                                <?php echo ($availability === 1)
                                    ? '<span class="dot dot-green" title="dispo 24–31" aria-label="dispo 24–31"></span>'
                                    : '<span class="dot dot-red" title="pas dispo 24–31" aria-label="pas dispo 24–31"></span>';
                                ?>
                            </td>

                            <td class="col-status">
                                <?php echo live_status_dot($status); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($nbpage > 1 && $showAll === 'no'): ?>
            <nav class="live-pagination" aria-label="Pagination">
                <?php
                $base = "/live/entrylist.php?order=" . urlencode($orderKey)
                    . "&asc=" . urlencode($asc)
                    . "&search=" . urlencode($searchQuery)
                    . "&showAll=" . urlencode($showAll);

                $mk = function(int $p) use ($base): string { return $base . "&page=" . $p; };

                $start = max(1, $cpage - 2);
                $end   = min($nbpage, $cpage + 2);
                if ($cpage <= 3) { $start = 1; $end = min(5, $nbpage); }
                if ($cpage >= $nbpage - 2) { $start = max(1, $nbpage - 4); $end = $nbpage; }
                ?>

                <a class="page-btn <?php echo ($cpage <= 1) ? 'disabled' : ''; ?>" href="<?php echo ($cpage <= 1) ? '#' : $mk(1); ?>">«</a>
                <a class="page-btn <?php echo ($cpage <= 1) ? 'disabled' : ''; ?>" href="<?php echo ($cpage <= 1) ? '#' : $mk($cpage - 1); ?>">‹</a>

                <?php if ($start > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i === $cpage): ?>
                        <span class="page-btn current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a class="page-btn" href="<?php echo $mk($i); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end < $nbpage): ?><span class="page-ellipsis">…</span><?php endif; ?>

                <a class="page-btn <?php echo ($cpage >= $nbpage) ? 'disabled' : ''; ?>" href="<?php echo ($cpage >= $nbpage) ? '#' : $mk($cpage + 1); ?>">›</a>
                <a class="page-btn <?php echo ($cpage >= $nbpage) ? 'disabled' : ''; ?>" href="<?php echo ($cpage >= $nbpage) ? '#' : $mk($nbpage); ?>">»</a>
            </nav>
        <?php endif; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <?php include(__DIR__ . '/entrylist_modal.php'); ?>

    </div>
<?php
mysqli_close($link);
include(__DIR__ . '/footer.php');
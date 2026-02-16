<?php
declare(strict_types=1);

// /live/entrylist.php

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();

// -------------------------
// Utils (local)
// -------------------------


/**
 * Availability = bool -> feu vert/rouge
 */
function ubbc_bool_icon(int $v, string $titleTrue, string $titleFalse): string {
    if ($v === 1) {
        return '<span class="bool-icon bool-true" title="'.ubbc_h($titleTrue).'" aria-label="'.ubbc_h($titleTrue).'"></span>';
    }
    return '<span class="bool-icon bool-false" title="'.ubbc_h($titleFalse).'" aria-label="'.ubbc_h($titleFalse).'"></span>';
}

/**
 * Status = 3 états -> feu prune/vert/rouge
 */
function ubbc_status_icon(int $refused, int $approved): string {
    if ($refused === 1) {
        return '<span class="status-icon status-refused" title="refused" aria-label="refused"></span>';
    }
    if ($approved === 1) {
        return '<span class="status-icon status-approved" title="approved" aria-label="approved"></span>';
    }
    return '<span class="status-icon status-pending" title="pending" aria-label="pending"></span>';
}

/**
 * Extrait les clés JSON d'un champ présent dans raw_text (format "Label: {json...}").
 * On gère les entités HTML (&quot; etc) + apostrophes typographiques.
 */
function ubbc_extract_json_keys_from_raw(string $raw, array $labelCandidates): array {
    $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // normalise apostrophes
    $raw = str_replace(["’","‘"], "'", $raw);

    foreach ($labelCandidates as $label) {
        // match ligne "Label: ...."
        $pattern = '/^'.preg_quote($label, '/').'\s*:\s*(.+)$/mi';
        if (!preg_match($pattern, $raw, $m)) {
            continue;
        }

        $value = trim($m[1]);

        // parfois tronqué dans une ligne -> on tente de récupérer à partir du premier { jusqu'au dernier }
        $start = strpos($value, '{');
        $end   = strrpos($value, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $value = substr($value, $start, $end - $start + 1);
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $json = json_decode($value, true);
        if (is_array($json)) {
            return array_keys($json);
        }
    }

    return [];
}

function ubbc_ul(array $items): string {
    if (count($items) === 0) {
        return '<span class="muted">—</span>';
    }
    $out = '<ul class="mini-list">';
    foreach ($items as $it) {
        $out .= '<li>'.ubbc_h((string)$it).'</li>';
    }
    $out .= '</ul>';
    return $out;
}

// -------------------------
// Params
// -------------------------
$searchQuery = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$showAll = (isset($_GET['showAll']) && $_GET['showAll'] === 'yes') ? 'yes' : 'no';

// dédoublonnage (par défaut OUI) : dernière soumission par (email, race)
$dedupe = (isset($_GET['dedupe']) && $_GET['dedupe'] === 'no') ? 'no' : 'yes';

// pagination
$nbperpage = 25;
$cpage = (isset($_GET['page']) && (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;

// tri (whitelist stricte)
$allowedOrder = [
    'received_at'    => 'received_at',
    'lastname'       => 'lastname',
    'firstname'      => 'firstname',
    'gender'         => 'gender',
    'cat'            => 'cat',
    'itra'           => 'itra',
    'race'           => 'race',
    'club'           => 'club',
    'city'           => 'city',
    'licence'        => 'licence',
    'participations' => 'participations',
    'availability'   => 'availability',
    // status = tri composite approved+refused via alias SQL
    'status'         => 'status_rank',
];
$orderKey = $_GET['order'] ?? 'received_at';
$order = $allowedOrder[$orderKey] ?? 'received_at';

$asc = (isset($_GET['asc']) && in_array($_GET['asc'], ['asc','desc'], true)) ? $_GET['asc'] : 'desc';
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
        i.review_note LIKE '%$q%'
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
    i.received_at,
    CASE
      WHEN i.refused = 1 THEN 2
      WHEN i.approved = 1 THEN 1
      ELSE 0
    END AS status_rank
";

if ($dedupe === 'yes') {
    // dernière ligne par (email,race). Les email vides ne sont pas dédoublonnés.
    $fromSql = "
        FROM inscriptions i
        JOIN (
            SELECT email, race, MAX(received_at) AS mx
            FROM inscriptions
            WHERE email IS NOT NULL AND email <> ''
            GROUP BY email, race
        ) t
          ON i.email = t.email
         AND i.race  = t.race
         AND i.received_at = t.mx
        WHERE 1=1
    ";
} else {
    $fromSql = "FROM inscriptions i WHERE 1=1";
}

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
$sql = "SELECT $selectCols $fromSql $whereSql ORDER BY $order $asc, received_at DESC";

if ($showAll === 'yes') {
    $sql .= " LIMIT 100000";
    $rowNumber = 1;
} else {
    $offset = ($cpage - 1) * $nbperpage;
    $sql .= " LIMIT $offset, $nbperpage";
    $rowNumber = $offset + 1;
}

$results = mysqli_query($link, $sql);

// headers
header('Content-Type: text/html; charset=utf-8');
include(__DIR__ . '/header.php');

// base url helper
function ubbc_link(string $order, string $asc, string $search, string $showAll, string $dedupe, int $page = 1): string {
    return "/live/entrylist.php?order=" . urlencode($order)
        . "&asc=" . urlencode($asc)
        . "&search=" . urlencode($search)
        . "&showAll=" . urlencode($showAll)
        . "&dedupe=" . urlencode($dedupe)
        . "&page=" . (int)$page;
}

?>
    <form class="live-toolbar" method="GET" action="/live/entrylist.php">
        <input type="text" name="search" value="<?php echo ubbc_h($searchQuery); ?>" placeholder="nom, email, club, ville, licence, note...">

        <input type="hidden" name="order" value="<?php echo ubbc_h($orderKey); ?>">
        <input type="hidden" name="asc" value="<?php echo ubbc_h($asc); ?>">
        <input type="hidden" name="showAll" value="<?php echo ubbc_h($showAll); ?>">
        <input type="hidden" name="dedupe" value="<?php echo ubbc_h($dedupe); ?>">

        <button class="btn btn-sm btn-electric" type="submit">Rechercher</button>

        <?php if ($showAll === 'no'): ?>
            <a class="btn btn-sm btn-electric" href="<?php echo ubbc_link($orderKey, $asc, $searchQuery, 'yes', $dedupe, 1); ?>">Déplier</a>
        <?php else: ?>
            <a class="btn btn-sm btn-electric" href="<?php echo ubbc_link($orderKey, $asc, $searchQuery, 'no', $dedupe, 1); ?>">Replier</a>
        <?php endif; ?>

        <?php if ($dedupe === 'yes'): ?>
            <a class="btn btn-sm btn-peach" href="<?php echo ubbc_link($orderKey, $asc, $searchQuery, $showAll, 'no', 1); ?>">Voir doublons</a>
        <?php else: ?>
            <a class="btn btn-sm btn-prune" href="<?php echo ubbc_link($orderKey, $asc, $searchQuery, $showAll, 'yes', 1); ?>">Masquer doublons</a>
        <?php endif; ?>

        <div class="muted ms-auto">
            <?php echo (int)$nbusers; ?> entrées
        </div>
    </form>

    <div class="live-card">
        <table class="live-table">
            <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-name"><a href="<?php echo ubbc_link('lastname', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Nom</a></th>
                <th class="col-first"><a href="<?php echo ubbc_link('firstname', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Prénom</a></th>
                <th class="col-g"><a href="<?php echo ubbc_link('gender', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Gender</a></th>
                <th class="col-cat"><a href="<?php echo ubbc_link('cat', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Cat</a></th>
                <th class="col-itra"><a href="<?php echo ubbc_link('itra', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Itra</a></th>
                <th class="col-race"><a href="<?php echo ubbc_link('race', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Race</a></th>
                <th class="col-club"><a href="<?php echo ubbc_link('club', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Club</a></th>
                <th class="col-city"><a href="<?php echo ubbc_link('city', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">City</a></th>
                <th class="col-lic"><a href="<?php echo ubbc_link('licence', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Licence</a></th>
                <th class="col-part"><a href="<?php echo ubbc_link('participations', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Participations</a></th>
                <th class="col-note">review_note</th>
                <th class="col-av"><a href="<?php echo ubbc_link('availability', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Availability</a></th>
                <th class="col-status"><a href="<?php echo ubbc_link('status', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Status</a></th>
                <th class="col-rec"><a href="<?php echo ubbc_link('received_at', $dasc, $searchQuery, $showAll, $dedupe, 1); ?>">Received at</a></th>
            </tr>
            </thead>

            <tbody>
            <?php if (!$results || mysqli_num_rows($results) === 0): ?>
                <tr><td colspan="15" class="empty">Pas d'inscription</td></tr>
            <?php else: ?>
                <?php while ($r = mysqli_fetch_array($results, MYSQLI_ASSOC)): ?>
                    <?php
                    $rowClass = ubbc_status_class($r);

                        $lastname  = ubbc_title((string)($r['lastname'] ?? ''));
                        $firstname = ubbc_title((string)($r['firstname'] ?? ''));
                        $gender    = (string)($r['gender'] ?? '');
                    $cat       = (string)($r['cat'] ?? '');

                    $itraVal = $r['itra'];
                    $itra = ($itraVal === null || $itraVal === '' || (int)$itraVal === 0) ? '' : (string)(int)$itraVal;

                    $availability   = (int)($r['availability'] ?? 0);
                    $approved       = (int)($r['approved'] ?? 0);
                    $refused        = (int)($r['refused'] ?? 0);
                    $participationsVal = $r['participations'];
                    $participations = ($participationsVal === null || $participationsVal === '' || (int)$participationsVal === 0) ? '' : (string)(int)$participationsVal;
                    $review_note    = (string)($r['review_note'] ?? '');
                    $itra = ubbc_dash_if_zero($itra);
                    $participations = ubbc_dash_if_zero($participations);
                    $race = ubbc_upper((string)($r['race'] ?? ''));
                    $club = ubbc_title((string)($r['club'] ?? ''));
                    $city = ubbc_title((string)($r['city'] ?? ''));

                    // modale: listes "clés uniquement" depuis raw_text
                    $rawText = (string)($r['raw_text'] ?? '');
                    $availabilityKeys = ubbc_extract_json_keys_from_raw($rawText, [
                        "Disponibilités en juillet",
                        "Disponibilites en juillet"
                    ]);
                    $participationKeys = ubbc_extract_json_keys_from_raw($rawText, [
                        "Participations UBBC",
                        "Participations"
                    ]);

                    $payload = [
                        'firstname' => trim($firstname),
                        'lastname' => trim($lastname),
                        'email' => (string)($r['email'] ?? ''),
                        'birthdate' => (string)($r['birthdate'] ?? ''),
                        'race' => $race,
                        'club' => $club,
                        'city' => $city,
                        'licence' => (string)($r['licence'] ?? ''),
                        'itra' => $itra,
                        'received_at' => (string)($r['received_at'] ?? ''),
                        'source_file' => (string)($r['source_file'] ?? ''),
                        'approved' => $approved,
                        'refused' => $refused,
                        'review_note' => $review_note,
                        'motivation' => (string)($r['motivation'] ?? ''),
                        'contribution' => (string)($r['contribution'] ?? ''),
                        'availability_keys' => $availabilityKeys,
                        'participation_keys' => $participationKeys,
                    ];
                    $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    ?>
                    <tr class="<?php echo ubbc_h($rowClass); ?>">
                        <td class="col-num"><?php echo (int)$rowNumber++; ?></td>

                        <td class="col-name">
                            <a class="entry-link" href="#"
                               data-bs-toggle="modal" data-bs-target="#entryModal"
                               data-entry="<?php echo $dataEntry; ?>"
                               onclick="return false;">
                                <?php echo ubbc_h($lastname); ?>
                            </a>
                        </td>

                        <td class="col-first">
                            <a class="entry-link" href="#"
                               data-bs-toggle="modal" data-bs-target="#entryModal"
                               data-entry="<?php echo $dataEntry; ?>"
                               onclick="return false;">
                                <?php echo ubbc_h($firstname); ?>
                            </a>
                        </td>

                        <td class="col-g"><?php echo ubbc_h($gender); ?></td>
                        <td class="col-cat"><?php echo ubbc_h($cat); ?></td>
                        <td class="col-itra"><?php echo ubbc_h($itra); ?></td>
                        <td class="col-race"><?php echo ubbc_h((string)($r['race'] ?? '')); ?></td>
                        <td class="col-club"><?php echo ubbc_h((string)($r['club'] ?? '')); ?></td>
                        <td class="col-city"><?php echo ubbc_h((string)($r['city'] ?? '')); ?></td>
                        <td class="col-lic"><?php echo ubbc_h((string)($r['licence'] ?? '')); ?></td>
                        <td class="col-part"><?php echo ubbc_h($participations); ?></td>

                        <td class="col-note"><?php echo ubbc_h($review_note); ?></td>

                        <td class="col-av">
                            <?php echo ubbc_bool_icon($availability, "dispo 24-31", "pas dispo 24-31"); ?>
                        </td>

                        <td class="col-status">
                            <?php echo ubbc_status_icon($refused, $approved); ?>
                        </td>

                        <td class="col-rec"><?php echo ubbc_h((string)($r['received_at'] ?? '')); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php
if ($results) mysqli_free_result($results);
mysqli_close($link);
?>

<?php if ($nbpage > 1 && $showAll === 'no'): ?>
    <nav class="live-pagination" aria-label="Pagination">
        <?php
        $base = "/live/entrylist.php?order=" . urlencode($orderKey)
            . "&asc=" . urlencode($asc)
            . "&search=" . urlencode($searchQuery)
            . "&showAll=" . urlencode($showAll)
            . "&dedupe=" . urlencode($dedupe);

        $mk = function(int $p) use ($base): string { return $base . "&page=" . $p; };

        // window
        $start = max(1, $cpage - 2);
        $end   = min($nbpage, $cpage + 2);
        if ($cpage <= 3) { $start = 1; $end = min(5, $nbpage); }
        if ($cpage >= $nbpage - 2) { $start = max(1, $nbpage - 4); $end = $nbpage; }
        ?>

        <a class="page-btn <?php echo ($cpage <= 1) ? 'disabled' : ''; ?>" href="<?php echo ($cpage <= 1) ? '#' : $mk(1); ?>">«</a>
        <a class="page-btn <?php echo ($cpage <= 1) ? 'disabled' : ''; ?>" href="<?php echo ($cpage <= 1) ? '#' : $mk($cpage - 1); ?>">‹</a>

        <?php if ($start > 1): ?>
            <span class="page-ellipsis">…</span>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $cpage): ?>
                <span class="page-btn current"><?php echo $i; ?></span>
            <?php else: ?>
                <a class="page-btn" href="<?php echo $mk($i); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $nbpage): ?>
            <span class="page-ellipsis">…</span>
        <?php endif; ?>

        <a class="page-btn <?php echo ($cpage >= $nbpage) ? 'disabled' : ''; ?>" href="<?php echo ($cpage >= $nbpage) ? '#' : $mk($cpage + 1); ?>">›</a>
        <a class="page-btn <?php echo ($cpage >= $nbpage) ? 'disabled' : ''; ?>" href="<?php echo ($cpage >= $nbpage) ? '#' : $mk($nbpage); ?>">»</a>
    </nav>
<?php endif; ?>

    <!-- Modal -->


<?php include(__DIR__ . '/footer.php'); ?>
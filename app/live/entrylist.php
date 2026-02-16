<?php
declare(strict_types=1);

// /live/entrylist.php
require_once __DIR__ . '/../includes/db-only.php';

$link = ubbc_db_connect();

// -------------------------
// Helpers (local)
// -------------------------


// -------------------------
// Params
// -------------------------
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$orderKey = isset($_GET['order']) ? (string)$_GET['order'] : 'received_at';
$asc = (isset($_GET['asc']) && in_array($_GET['asc'], ['asc', 'desc'], true)) ? $_GET['asc'] : 'desc';
$dasc = ($asc === 'asc') ? 'desc' : 'asc';

$view = isset($_GET['view']) ? (string)$_GET['view'] : 'latest';
if (!in_array($view, ['latest','all','duplicates'], true)) $view = 'latest';

$perPage = 25;
$page = (isset($_GET['page']) && (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;

$allowedOrder = [
    'received_at'    => 'i.received_at',
    'lastname'       => 'i.lastname',
    'firstname'      => 'i.firstname',
    'gender'         => 'i.gender',
    'itra'           => 'i.itra',
    'race'           => 'i.race',
    'club'           => 'i.club',
    'city'           => 'i.city',
    'licence'        => 'i.licence',
    'participations' => 'i.participations',
    'availability'   => 'i.availability',
    'approved'       => 'i.approved',
    'refused'        => 'i.refused',
];
$order = $allowedOrder[$orderKey] ?? 'i.received_at';

$searchSql = '';
if ($search !== '') {
    $q = mysqli_real_escape_string($link, $search);
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

$selectCols = "
    i.id, i.source_file, i.email,
    i.lastname, i.firstname, i.birthdate, i.gender,
    i.city, i.race, i.club, i.licence,
    i.participations, i.availability, i.itra,
    i.review_note, i.approved, i.refused,
    i.contribution, i.motivation, i.raw_text, i.received_at
";

if ($view === 'latest') {
    $fromSql = "
        FROM inscriptions i
        JOIN (
            SELECT email, race, MAX(received_at) AS mx
            FROM inscriptions
            WHERE email IS NOT NULL AND email <> ''
            GROUP BY email, race
        ) t ON t.email = i.email AND t.race = i.race AND t.mx = i.received_at
        WHERE 1=1
    ";
    $orderSql = " ORDER BY $order $asc, i.received_at DESC ";
} elseif ($view === 'duplicates') {
    $fromSql = "
        FROM inscriptions i
        JOIN (
            SELECT email, race
            FROM inscriptions
            WHERE email IS NOT NULL AND email <> ''
            GROUP BY email, race
            HAVING COUNT(*) > 1
        ) d ON d.email = i.email AND d.race = i.race
        WHERE 1=1
    ";
    $orderSql = " ORDER BY i.email ASC, i.race ASC, i.received_at DESC ";
} else {
    $fromSql = "FROM inscriptions i WHERE 1=1";
    $orderSql = " ORDER BY $order $asc, i.received_at DESC ";
}

$sqlCount = "SELECT COUNT(*) AS nb $fromSql $searchSql";
$resCount = mysqli_query($link, $sqlCount);
$nb = 0;
if ($resCount) {
    $row = mysqli_fetch_assoc($resCount);
    $nb = (int)($row['nb'] ?? 0);
    mysqli_free_result($resCount);
}

$nbPage = max(1, (int)ceil($nb / $perPage));
if ($page > $nbPage) $page = $nbPage;

$sql = "SELECT $selectCols $fromSql $searchSql $orderSql";

$rowNumber = 1;
if ($view !== 'all') {
    $offset = ($page - 1) * $perPage;
    $sql .= " LIMIT $offset, $perPage";
    $rowNumber = $offset + 1;
} else {
    $sql .= " LIMIT 100000";
}

$results = mysqli_query($link, $sql);

function link_to(array $p): string {
    $base = [
        'search' => $_GET['search'] ?? '',
        'order'  => $_GET['order'] ?? 'received_at',
        'asc'    => $_GET['asc'] ?? 'desc',
        'view'   => $_GET['view'] ?? 'latest',
        'page'   => $_GET['page'] ?? 1,
    ];
    $q = array_merge($base, $p);
    return '/live/entrylist.php?' . http_build_query($q);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>UBBC Live • Entrylist</title>

    <link rel="icon" href="/static/images/icon-reboot.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/static/css/live.css" rel="stylesheet">
</head>
<body>

<header class="live-header">
    <div class="brand">
        <img src="/static/images/icon-reboot.png" alt="UBBC">
        <div>
            <div class="title">UBBC • Entrylist</div>
            <div class="subtitle">
                <span class="pill pill-prune"><?php echo h($view); ?></span>
                <span class="muted ms-2"><?php echo (int)$nb; ?> entrées</span>
            </div>
        </div>
    </div>

    <nav class="nav-simple">
        <a class="nav-link-simple" href="/live/entrylist.php">Entrylist</a>
    </nav>
</header>

<main class="live-container">

    <form class="live-toolbar" method="GET" action="/live/entrylist.php">
        <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="nom, email, club, ville, licence, note...">

        <input type="hidden" name="order" value="<?php echo h($orderKey); ?>">
        <input type="hidden" name="asc" value="<?php echo h($asc); ?>">
        <input type="hidden" name="view" value="<?php echo h($view); ?>">
        <input type="hidden" name="page" value="1">

        <button class="btn btn-sm btn-electric" type="submit">Rechercher</button>

        <div class="toolbar-split"></div>

        <a class="btn btn-sm <?php echo ($view==='latest')?'btn-prune':'btn-outline-prune'; ?>"
           href="<?php echo h(link_to(['view'=>'latest','page'=>1])); ?>">
            Dernière soumission
        </a>

        <a class="btn btn-sm <?php echo ($view==='all')?'btn-prune':'btn-outline-prune'; ?>"
           href="<?php echo h(link_to(['view'=>'all','page'=>1])); ?>">
            Tout
        </a>

        <a class="btn btn-sm <?php echo ($view==='duplicates')?'btn-peach':'btn-outline-peach'; ?>"
           href="<?php echo h(link_to(['view'=>'duplicates','page'=>1])); ?>">
            Doublons
        </a>

        <div class="ms-auto legend-status">
            <span class="legend-item legend-approved">approved</span>
            <span class="legend-sep">/</span>
            <span class="legend-item legend-pending">pending</span>
            <span class="legend-sep">/</span>
            <span class="legend-item legend-refused">refused</span>
        </div>
    </form>

    <section class="live-card live-table-wrap">
        <table class="live-table">
            <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-name"><a href="<?php echo h(link_to(['order'=>'lastname','asc'=>$dasc,'page'=>1])); ?>">Nom</a></th>
                <th class="col-first"><a href="<?php echo h(link_to(['order'=>'firstname','asc'=>$dasc,'page'=>1])); ?>">Prénom</a></th>
                <th class="col-g"><a href="<?php echo h(link_to(['order'=>'gender','asc'=>$dasc,'page'=>1])); ?>">Gender</a></th>
                <th class="col-cat">Cat</th>
                <th class="col-itra"><a href="<?php echo h(link_to(['order'=>'itra','asc'=>$dasc,'page'=>1])); ?>">Itra</a></th>
                <th class="col-race"><a href="<?php echo h(link_to(['order'=>'race','asc'=>$dasc,'page'=>1])); ?>">Race</a></th>
                <th class="col-club"><a href="<?php echo h(link_to(['order'=>'club','asc'=>$dasc,'page'=>1])); ?>">Club</a></th>
                <th class="col-city"><a href="<?php echo h(link_to(['order'=>'city','asc'=>$dasc,'page'=>1])); ?>">City</a></th>
                <th class="col-lic"><a href="<?php echo h(link_to(['order'=>'licence','asc'=>$dasc,'page'=>1])); ?>">Licence</a></th>
                <th class="col-part"><a href="<?php echo h(link_to(['order'=>'participations','asc'=>$dasc,'page'=>1])); ?>">Participations</a></th>
                <th class="col-av"><a href="<?php echo h(link_to(['order'=>'availability','asc'=>$dasc,'page'=>1])); ?>">Availability</a></th>
                <th class="col-note">review_note</th>
                <th class="col-appr"><a href="<?php echo h(link_to(['order'=>'approved','asc'=>$dasc,'page'=>1])); ?>">approved</a></th>
                <th class="col-ref"><a href="<?php echo h(link_to(['order'=>'refused','asc'=>$dasc,'page'=>1])); ?>">refused</a></th>
                <th class="col-rec"><a href="<?php echo h(link_to(['order'=>'received_at','asc'=>$dasc,'page'=>1])); ?>">Received at</a></th>
            </tr>
            </thead>

            <tbody>
            <?php if (!$results || mysqli_num_rows($results) === 0): ?>
                <tr><td colspan="16" class="empty">Pas d'inscription</td></tr>
            <?php else: ?>
                <?php while ($r = mysqli_fetch_assoc($results)): ?>
                    <?php
                    $rowClass = status_class($r);

                    $lastname  = title_case($r['lastname'] ?? '');
                    $firstname = title_case($r['firstname'] ?? '');
                    $city      = title_case($r['city'] ?? '');
                    $club      = title_case($r['club'] ?? '');

                    $race = upper($r['race'] ?? '');
                    $cat = category_from_birthdate($r['birthdate'] ?? null);

                    $itra = dash_if_zero($r['itra'] ?? null);
                    $parts = dash_if_zero($r['participations'] ?? null);

                    $availability = (int)($r['availability'] ?? 0);
                    $approved     = (int)($r['approved'] ?? 0);
                    $refused      = (int)($r['refused'] ?? 0);

                    $reviewNote = (string)($r['review_note'] ?? '');

                    $rawText = (string)($r['raw_text'] ?? '');
                    $availabilityKeys = extract_json_keys_from_raw($rawText, ["Disponibilités en juillet","Disponibilites en juillet"]);
                    $participationKeys = extract_json_keys_from_raw($rawText, ["Participations UBBC","Participations"]);

                    $payload = [
                        'name' => trim($firstname.' '.$lastname),
                        'email' => (string)($r['email'] ?? ''),
                        'birthdate' => (string)($r['birthdate'] ?? ''),
                        'race' => $race,
                        'club' => $club,
                        'city' => $city,
                        'licence' => (string)($r['licence'] ?? ''),
                        'itra' => ($itra === '—') ? '' : $itra,
                        'participations' => ($parts === '—') ? '' : $parts,
                        'received_at' => (string)($r['received_at'] ?? ''),
                        'source_file' => (string)($r['source_file'] ?? ''),
                        'approved' => $approved,
                        'refused' => $refused,
                        'availability' => $availability,
                        'review_note' => $reviewNote,
                        'motivation' => (string)($r['motivation'] ?? ''),
                        'contribution' => (string)($r['contribution'] ?? ''),
                        'availability_keys' => $availabilityKeys,
                        'participation_keys' => $participationKeys,
                    ];
                    $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    ?>

                    <tr class="<?php echo h($rowClass); ?>">
                        <td class="col-num"><?php echo (int)$rowNumber++; ?></td>

                        <td class="col-name">
                            <a class="entry-link" href="#"
                               data-bs-toggle="modal" data-bs-target="#entryModal"
                               data-entry="<?php echo $dataEntry; ?>"
                               onclick="return false;">
                                <?php echo h($lastname); ?>
                            </a>
                        </td>

                        <td class="col-first">
                            <a class="entry-link" href="#"
                               data-bs-toggle="modal" data-bs-target="#entryModal"
                               data-entry="<?php echo $dataEntry; ?>"
                               onclick="return false;">
                                <?php echo h($firstname); ?>
                            </a>
                        </td>

                        <td class="col-g"><?php echo h((string)($r['gender'] ?? '')); ?></td>
                        <td class="col-cat"><?php echo h($cat); ?></td>
                        <td class="col-itra"><?php echo h($itra); ?></td>
                        <td class="col-race"><?php echo h($race); ?></td>
                        <td class="col-club"><?php echo h($club); ?></td>
                        <td class="col-city"><?php echo h($city); ?></td>
                        <td class="col-lic"><?php echo h((string)($r['licence'] ?? '')); ?></td>
                        <td class="col-part"><?php echo h($parts); ?></td>

                        <td class="col-av">
                            <?php echo bool_icon($availability, "dispo 24-31", "pas dispo 24-31"); ?>
                        </td>

                        <td class="col-note"><?php echo h($reviewNote); ?></td>

                        <td class="col-appr">
                            <?php echo bool_icon($approved, "approved", "pending"); ?>
                        </td>

                        <td class="col-ref">
                            <?php echo bool_icon($refused, "refused", "not refused"); ?>
                        </td>

                        <td class="col-rec"><?php echo h((string)($r['received_at'] ?? '')); ?></td>
                    </tr>

                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- MOBILE CARDS -->
    <section class="entry-cards">
        <?php
        if ($results) mysqli_free_result($results);
        $results2 = mysqli_query($link, $sql);
        $cardNumber = ($view !== 'all') ? (($page - 1) * $perPage + 1) : 1;
        ?>

        <div class="card-legend">
            <div class="card-legend-item">
                <span class="bool-icon bool-true"></span>
                <span class="txt">Availability (24-31)</span>
            </div>
            <div class="card-legend-item">
                <span class="bool-icon bool-true"></span>
                <span class="txt">Approved</span>
            </div>
            <div class="card-legend-item">
                <span class="bool-icon bool-false"></span>
                <span class="txt">Refused</span>
            </div>
            <div class="card-legend-hint muted">pastilles: vert = oui, rouge = non</div>
        </div>

        <?php if (!$results2 || mysqli_num_rows($results2) === 0): ?>
            <div class="card-empty">Pas d'inscription</div>
        <?php else: ?>
            <?php while ($r = mysqli_fetch_assoc($results2)): ?>
                <?php
                $rowClass = status_class($r);

                $lastname  = title_case($r['lastname'] ?? '');
                $firstname = title_case($r['firstname'] ?? '');
                $city      = title_case($r['city'] ?? '');
                $club      = title_case($r['club'] ?? '');
                $race      = upper($r['race'] ?? '');
                $cat       = category_from_birthdate($r['birthdate'] ?? null);

                $itra = dash_if_zero($r['itra'] ?? null);
                $parts = dash_if_zero($r['participations'] ?? null);

                $availability = (int)($r['availability'] ?? 0);
                $approved     = (int)($r['approved'] ?? 0);
                $refused      = (int)($r['refused'] ?? 0);
                $reviewNote   = (string)($r['review_note'] ?? '');

                $rawText = (string)($r['raw_text'] ?? '');
                $availabilityKeys = extract_json_keys_from_raw($rawText, ["Disponibilités en juillet","Disponibilites en juillet"]);
                $participationKeys = extract_json_keys_from_raw($rawText, ["Participations UBBC","Participations"]);

                $payload = [
                    'name' => trim($firstname.' '.$lastname),
                    'email' => (string)($r['email'] ?? ''),
                    'birthdate' => (string)($r['birthdate'] ?? ''),
                    'race' => $race,
                    'club' => $club,
                    'city' => $city,
                    'licence' => (string)($r['licence'] ?? ''),
                    'itra' => ($itra === '—') ? '' : $itra,
                    'participations' => ($parts === '—') ? '' : $parts,
                    'received_at' => (string)($r['received_at'] ?? ''),
                    'source_file' => (string)($r['source_file'] ?? ''),
                    'approved' => $approved,
                    'refused' => $refused,
                    'availability' => $availability,
                    'review_note' => $reviewNote,
                    'motivation' => (string)($r['motivation'] ?? ''),
                    'contribution' => (string)($r['contribution'] ?? ''),
                    'availability_keys' => $availabilityKeys,
                    'participation_keys' => $participationKeys,
                ];
                $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                ?>

                <article class="entry-card <?php echo h($rowClass); ?>">
                    <div class="entry-card-top">
                        <div class="entry-card-num">#<?php echo (int)$cardNumber++; ?></div>
                        <div class="entry-card-badges">
                            <span class="badge-dot"><?php echo bool_icon($availability, "availability 24-31", "availability 24-31"); ?></span>
                            <span class="badge-dot"><?php echo bool_icon($approved, "approved", "approved"); ?></span>
                            <span class="badge-dot"><?php echo bool_icon($refused, "refused", "refused"); ?></span>
                        </div>
                    </div>

                    <div class="entry-card-name">
                        <a class="entry-link" href="#"
                           data-bs-toggle="modal" data-bs-target="#entryModal"
                           data-entry="<?php echo $dataEntry; ?>"
                           onclick="return false;">
                            <?php echo h($firstname.' '.$lastname); ?>
                        </a>
                    </div>

                    <div class="entry-card-grid">
                        <div><span class="k">Race</span><span class="v"><?php echo h($race ?: '—'); ?></span></div>
                        <div><span class="k">Cat</span><span class="v"><?php echo h($cat); ?></span></div>
                        <div><span class="k">Itra</span><span class="v"><?php echo h($itra); ?></span></div>
                        <div><span class="k">Parts</span><span class="v"><?php echo h($parts); ?></span></div>
                        <div><span class="k">City</span><span class="v"><?php echo h($city ?: '—'); ?></span></div>
                        <div><span class="k">Club</span><span class="v"><?php echo h($club ?: '—'); ?></span></div>
                        <div class="span-2"><span class="k">Licence</span><span class="v mono"><?php echo h((string)($r['licence'] ?? '—')); ?></span></div>
                        <div class="span-2"><span class="k">Received</span><span class="v mono"><?php echo h((string)($r['received_at'] ?? '')); ?></span></div>
                        <div class="span-2"><span class="k">Note</span><span class="v"><?php echo h($reviewNote ?: '—'); ?></span></div>
                    </div>
                </article>

            <?php endwhile; ?>
        <?php endif; ?>

        <?php if ($results2) mysqli_free_result($results2); ?>
    </section>

    <?php if ($nbPage > 1 && $view !== 'all'): ?>
        <nav class="live-pagination" aria-label="Pagination">
            <?php
            $mk = function(int $p) use ($search, $orderKey, $asc, $view): string {
                return '/live/entrylist.php?' . http_build_query([
                        'search' => $search,
                        'order' => $orderKey,
                        'asc' => $asc,
                        'view' => $view,
                        'page' => $p
                    ]);
            };

            $start = max(1, $page - 2);
            $end   = min($nbPage, $page + 2);
            if ($page <= 3) { $start = 1; $end = min(5, $nbPage); }
            if ($page >= $nbPage - 2) { $start = max(1, $nbPage - 4); $end = $nbPage; }
            ?>

            <a class="page-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>" href="<?php echo ($page <= 1) ? '#' : h($mk(1)); ?>">«</a>
            <a class="page-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>" href="<?php echo ($page <= 1) ? '#' : h($mk($page-1)); ?>">‹</a>

            <?php if ($start > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="page-btn current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a class="page-btn" href="<?php echo h($mk($i)); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end < $nbPage): ?><span class="page-ellipsis">…</span><?php endif; ?>

            <a class="page-btn <?php echo ($page >= $nbPage) ? 'disabled' : ''; ?>" href="<?php echo ($page >= $nbPage) ? '#' : h($mk($page+1)); ?>">›</a>
            <a class="page-btn <?php echo ($page >= $nbPage) ? 'disabled' : ''; ?>" href="<?php echo ($page >= $nbPage) ? '#' : h($mk($nbPage)); ?>">»</a>
        </nav>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/entrylist_modal.php'; ?>

<footer class="live-footer"></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
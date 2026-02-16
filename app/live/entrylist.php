<?php
declare(strict_types=1);

// live/entrylist.php
require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();

// ---------------------------
// Params
// ---------------------------
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$view = isset($_GET['view']) ? (string)$_GET['view'] : 'all';        // all | approved | pending | refused
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'received_at'; // received_at | lastname | firstname | race | itra | participations
$dir  = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$dir = ($dir === 'asc') ? 'asc' : 'desc';

// whitelist sort
$sortMap = [
    'received_at'     => 'i.received_at',
    'lastname'        => 'i.lastname',
    'firstname'       => 'i.firstname',
    'race'            => 'i.race',
    'itra'            => 'i.itra',
    'participations'  => 'i.participations',
];
$orderBy = $sortMap[$sort] ?? $sortMap['received_at'];

// ---------------------------
// WHERE
// ---------------------------
$where = [];
$params = [];
$types  = '';

if ($q !== '') {
    // recherche simple
    $where[] = "(i.lastname LIKE ? OR i.firstname LIKE ? OR i.email LIKE ? OR i.city LIKE ? OR i.club LIKE ? OR i.race LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like,$like,$like,$like,$like,$like]);
    $types .= 'ssssss';
}

switch ($view) {
    case 'approved':
        $where[] = "i.refused = 0 AND i.approved = 1";
        break;
    case 'refused':
        $where[] = "i.refused = 1";
        break;
    case 'pending':
        $where[] = "i.refused = 0 AND i.approved = 0";
        break;
    default:
        $view = 'all';
        break;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---------------------------
// Count
// ---------------------------
$sqlCount = "SELECT COUNT(*) AS c FROM inscriptions i {$whereSql}";
$stmtCount = mysqli_prepare($link, $sqlCount);
if (!$stmtCount) {
    http_response_code(500);
    echo "DB error (prepare count): " . h(mysqli_error($link));
    exit;
}
if ($types !== '') {
    mysqli_stmt_bind_param($stmtCount, $types, ...$params);
}
mysqli_stmt_execute($stmtCount);
$resCount = mysqli_stmt_get_result($stmtCount);
$totalRows = (int)(mysqli_fetch_assoc($resCount)['c'] ?? 0);
mysqli_free_result($resCount);
mysqli_stmt_close($stmtCount);

$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ---------------------------
// Fetch page
// ---------------------------
$sql = "
SELECT
  i.id,
  i.source_file,
  i.email,
  i.lastname,
  i.firstname,
  i.birthdate,
  i.gender,
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
FROM inscriptions i
{$whereSql}
ORDER BY {$orderBy} {$dir}, i.id {$dir}
LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
    http_response_code(500);
    echo "DB error (prepare list): " . h(mysqli_error($link));
    exit;
}

$typesList = $types . 'ii';
$paramsList = array_merge($params, [$perPage, $offset]);
mysqli_stmt_bind_param($stmt, $typesList, ...$paramsList);

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
}

mysqli_free_result($res);
mysqli_stmt_close($stmt);


// ---------------------------
// URL builder
// ---------------------------
function ubbc_url(array $overrides = []): string {
    $base = [
        'q'    => $_GET['q']    ?? '',
        'view' => $_GET['view'] ?? 'all',
        'sort' => $_GET['sort'] ?? 'received_at',
        'dir'  => $_GET['dir']  ?? 'desc',
        'page' => $_GET['page'] ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    // clean
    if (($merged['q'] ?? '') === '') unset($merged['q']);
    return 'entrylist.php?' . http_build_query($merged);
}

function ubbc_sort_link(string $key): string {
    $currentSort = (string)($_GET['sort'] ?? 'received_at');
    $currentDir  = strtolower((string)($_GET['dir'] ?? 'desc'));
    $newDir = 'asc';
    if ($currentSort === $key) {
        $newDir = ($currentDir === 'asc') ? 'desc' : 'asc';
    }
    return ubbc_url(['sort' => $key, 'dir' => $newDir, 'page' => 1]);
}

// ---------------------------
// Render
// ---------------------------
include __DIR__ . '/header.php';
?>

    <div class="live-container">

        <div class="live-toolbar">
            <form method="get" action="entrylist.php" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Rechercher (nom, email, club, ville, course)">
                <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
                <input type="hidden" name="dir" value="<?php echo h($dir); ?>">

                <select name="view" class="form-select" style="max-width:220px;">
                    <option value="all" <?php echo ($view==='all')?'selected':''; ?>>Tous</option>
                    <option value="approved" <?php echo ($view==='approved')?'selected':''; ?>>Approved</option>
                    <option value="pending" <?php echo ($view==='pending')?'selected':''; ?>>Pending</option>
                    <option value="refused" <?php echo ($view==='refused')?'selected':''; ?>>Refused</option>
                </select>

                <button class="btn btn-primary" type="submit">Filtrer</button>
                <a class="btn btn-outline-secondary" href="entrylist.php">Reset</a>
            </form>

            <div style="margin-left:auto; font-size:14px; color:#6b7280;">
                <?php echo (int)$totalRows; ?> inscriptions
            </div>
        </div>

        <!-- Légende statut -->
        <div style="display:flex; gap:12px; flex-wrap:wrap; margin: 0 0 10px; align-items:center;">
            <span class="status status-approved">approved</span>
            <span class="status status-pending">pending</span>
            <span class="status status-refused">refused</span>
        </div>

        <div class="live-card">

            <!-- TABLE (desktop) -->
            <div class="live-table-wrap">
                <table class="live-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th><a class="entry-link" href="<?php echo h(ubbc_sort_link('lastname')); ?>">Nom</a></th>
                        <th><a class="entry-link" href="<?php echo h(ubbc_sort_link('firstname')); ?>">Prénom</a></th>
                        <th>Gender</th>
                        <th class="cat">Cat</th>
                        <th class="col-index"><a class="entry-link" href="<?php echo h(ubbc_sort_link('itra')); ?>">Itra</a></th>
                        <th><a class="entry-link" href="<?php echo h(ubbc_sort_link('race')); ?>">Race</a></th>
                        <th>Club</th>
                        <th>City</th>
                        <th>Licence</th>
                        <th><a class="entry-link" href="<?php echo h(ubbc_sort_link('participations')); ?>">Participations</a></th>
                        <th>Availability</th>
                        <th>review_note</th>
                        <th>Statut</th>
                        <th>Received at</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 0;
                    foreach ($rows as $r):
                        $i++;
                        $num = $offset + $i;

                        $status = ubbc_status($r);
                        $rowClass = ubbc_row_class($r);

                        $lastname  = title_case((string)($r['lastname'] ?? ''));
                        $firstname = title_case((string)($r['firstname'] ?? ''));
                        $city      = title_case((string)($r['city'] ?? ''));
                        $club      = title_case((string)($r['club'] ?? ''));

                        $race = strtoupper((string)($r['race'] ?? ''));
                        $cat = category_from_birthdate((string)($r['birthdate'] ?? ''));
                        $itra = (int)($r['itra'] ?? 0);
                        $itraDisplay = ($itra > 0) ? (string)$itra : '';

                        $parts = (int)($r['participations'] ?? 0);
                        $partsDisplay = ($parts > 0) ? (string)$parts : '';

                        $availability = (int)($r['availability'] ?? 0); // 0/1
                        $availabilityIcon = $availability ? '🟢' : '🔴'; // si tu veux remplacer par CSS, on le fera ensuite

                        $note = (string)($r['review_note'] ?? '');
                        $received = (string)($r['received_at'] ?? '');

                        // Modal data
                        $raw = (string)($r['raw_text'] ?? '');
                        $availKeys = extract_json_keys_from_raw($raw, ['Disponibilités en juillet', 'Disponibilites en juillet']);
                        $partKeys  = extract_json_keys_from_raw($raw, ['Participations UBBC', 'Participations']);

                        $entry = [
                            'id' => (int)$r['id'],
                            'source_file' => (string)($r['source_file'] ?? ''),
                            'email' => (string)($r['email'] ?? ''),
                            'lastname' => $lastname,
                            'firstname' => $firstname,
                            'birthdate' => (string)($r['birthdate'] ?? ''),
                            'gender' => (string)($r['gender'] ?? ''),
                            'city' => $city,
                            'club' => $club,
                            'race' => $race,
                            'licence' => (string)($r['licence'] ?? ''),
                            'participations' => $parts,
                            'availability' => $availability,
                            'itra' => $itra,
                            'review_note' => $note,
                            'approved' => (int)($r['approved'] ?? 0),
                            'refused' => (int)($r['refused'] ?? 0),
                            'motivation' => (string)($r['motivation'] ?? ''),
                            'contribution' => (string)($r['contribution'] ?? ''),
                            'received_at' => $received,
                            'avail_keys' => $availKeys,
                            'part_keys' => $partKeys,
                            'raw_text' => $raw,
                        ];
                        ?>
                        <tr class="<?php echo h($rowClass); ?>">
                            <td><?php echo (int)$num; ?></td>

                            <td>
                                <a href="#"
                                   class="entry-link js-open-entry"
                                   data-entry="<?php echo h(json_encode($entry, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?>">
                                    <?php echo h($lastname); ?>
                                </a>
                            </td>

                            <td>
                                <a href="#"
                                   class="entry-link js-open-entry"
                                   data-entry="<?php echo h(json_encode($entry, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?>">
                                    <?php echo h($firstname); ?>
                                </a>
                            </td>

                            <td><?php echo h((string)($r['gender'] ?? '')); ?></td>
                            <td class="cat"><?php echo h($cat); ?></td>
                            <td class="col-index"><?php echo h($itraDisplay); ?></td>
                            <td><?php echo h($race); ?></td>
                            <td><?php echo h($club); ?></td>
                            <td><?php echo h($city); ?></td>
                            <td><?php echo h((string)($r['licence'] ?? '')); ?></td>
                            <td style="text-align:center;"><?php echo h($partsDisplay); ?></td>
                            <td style="text-align:center;"><?php echo $availabilityIcon; ?></td>
                            <td><?php echo h($note); ?></td>
                            <td><?php echo ubbc_status_badge($r); ?></td>
                            <td><?php echo h($received); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- CARDS (mobile) -->
            <div class="live-cards">
                <?php foreach ($rows as $r):
                    $status = ubbc_status(
                        (int)($r['approved'] ?? 0),
                        (int)($r['refused'] ?? 0)
                    );
                    $rowClass = ubbc_row_class($r);

                    $lastname  = title_case((string)($r['lastname'] ?? ''));
                    $firstname = title_case((string)($r['firstname'] ?? ''));
                    $city      = title_case((string)($r['city'] ?? ''));
                    $club      = title_case((string)($r['club'] ?? ''));
                    $race      = strtoupper((string)($r['race'] ?? ''));

                    $cat = category_from_birthdate((string)($r['birthdate'] ?? ''));
                    $itra = (int)($r['itra'] ?? 0);
                    $itraDisplay = ($itra > 0) ? (string)$itra : '';
                    $parts = (int)($r['participations'] ?? 0);
                    $partsDisplay = ($parts > 0) ? (string)$parts : '';
                    $availability = (int)($r['availability'] ?? 0);
                    $availabilityIcon = $availability ? '🟢' : '🔴';

                    $note = (string)($r['review_note'] ?? '');
                    $received = (string)($r['received_at'] ?? '');
                    $raw = (string)($r['raw_text'] ?? '');
                    $availKeys = extract_json_keys_from_raw($raw, ['Disponibilités en juillet', 'Disponibilites en juillet']);
                    $partKeys  = extract_json_keys_from_raw($raw, ['Participations UBBC', 'Participations']);

                    $entry = [
                        'id' => (int)$r['id'],
                        'source_file' => (string)($r['source_file'] ?? ''),
                        'email' => (string)($r['email'] ?? ''),
                        'lastname' => $lastname,
                        'firstname' => $firstname,
                        'birthdate' => (string)($r['birthdate'] ?? ''),
                        'gender' => (string)($r['gender'] ?? ''),
                        'city' => $city,
                        'club' => $club,
                        'race' => $race,
                        'licence' => (string)($r['licence'] ?? ''),
                        'participations' => $parts,
                        'availability' => $availability,
                        'itra' => $itra,
                        'review_note' => $note,
                        'approved' => (int)($r['approved'] ?? 0),
                        'refused' => (int)($r['refused'] ?? 0),
                        'motivation' => (string)($r['motivation'] ?? ''),
                        'contribution' => (string)($r['contribution'] ?? ''),
                        'received_at' => $received,
                        'avail_keys' => $availKeys,
                        'part_keys' => $partKeys,
                        'raw_text' => $raw,
                    ];
                    ?>
                    <div class="live-card-item <?php echo h($rowClass); ?>">
                        <div class="live-card-top">
                            <div class="live-card-name">
                                <a href="#"
                                   class="entry-link js-open-entry"
                                   data-entry="<?php echo h(json_encode($entry, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?>">
                                    <?php echo h($lastname . ' ' . $firstname); ?>
                                </a>
                                <div class="live-card-sub">
                                    <?php echo h($race); ?> · <?php echo h($city); ?> · <?php echo h($club); ?>
                                </div>
                            </div>
                            <div class="live-card-status">
                                <?php echo ubbc_status_badge($r); ?>
                            </div>
                        </div>

                        <div class="live-card-grid">
                            <div><span class="k">Gender</span><span class="v"><?php echo h((string)($r['gender'] ?? '')); ?></span></div>
                            <div><span class="k">Cat</span><span class="v"><?php echo h($cat); ?></span></div>
                            <div><span class="k">Itra</span><span class="v"><?php echo h($itraDisplay); ?></span></div>
                            <div><span class="k">Parts</span><span class="v"><?php echo h($partsDisplay); ?></span></div>
                            <div><span class="k">Avail</span><span class="v"><?php echo $availabilityIcon; ?></span></div>
                            <div><span class="k">Licence</span><span class="v"><?php echo h((string)($r['licence'] ?? '')); ?></span></div>
                        </div>

                        <?php if ($note !== ''): ?>
                            <div class="live-card-note"><?php echo h($note); ?></div>
                        <?php endif; ?>

                        <div class="live-card-foot"><?php echo h($received); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="live-pagination">
                <?php
                $prev = max(1, $page - 1);
                $next = min($totalPages, $page + 1);

                $window = 3;
                $start = max(1, $page - $window);
                $end   = min($totalPages, $page + $window);

                if ($page > 1) {
                    echo '<a class="btn btn-outline-primary" href="' . h(ubbc_url(['page' => $prev])) . '">‹</a>';
                } else {
                    echo '<span class="current">‹</span>';
                }

                if ($start > 1) {
                    echo '<a href="' . h(ubbc_url(['page' => 1])) . '">1</a>';
                    if ($start > 2) echo '<span>…</span>';
                }

                for ($p = $start; $p <= $end; $p++) {
                    if ($p === $page) {
                        echo '<span class="current">' . $p . '</span>';
                    } else {
                        echo '<a href="' . h(ubbc_url(['page' => $p])) . '">' . $p . '</a>';
                    }
                }

                if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<span>…</span>';
                    echo '<a href="' . h(ubbc_url(['page' => $totalPages])) . '">' . $totalPages . '</a>';
                }

                if ($page < $totalPages) {
                    echo '<a class="btn btn-outline-primary" href="' . h(ubbc_url(['page' => $next])) . '">›</a>';
                } else {
                    echo '<span class="current">›</span>';
                }
                ?>
            </div>
        <?php endif; ?>

    </div>

<?php
// modal + JS
include __DIR__ . '/entrylist_modal.php';
include __DIR__ . '/footer.php';
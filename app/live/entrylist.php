<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();

// ---------------------------
// Params
// ---------------------------
$q    = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$view = isset($_GET['view']) ? (string)$_GET['view'] : 'all';          // all|approved|pending|refused
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'received_at';  // received_at|lastname|firstname|race|itra|participations
$dir  = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));

$perPage = 50;
$offset  = ($page - 1) * $perPage;

$dir = ($dir === 'asc') ? 'asc' : 'desc';

$sortMap = [
    'received_at'    => 'i.received_at',
    'lastname'       => 'i.lastname',
    'firstname'      => 'i.firstname',
    'race'           => 'i.race',
    'itra'           => 'i.itra',
    'participations' => 'i.participations',
];
$orderBy = $sortMap[$sort] ?? $sortMap['received_at'];

// ---------------------------
// WHERE
// ---------------------------
$where  = [];
$params = [];
$types  = '';

if ($q !== '') {
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
if (!$stmtCount) { http_response_code(500); echo "DB error: " . h(mysqli_error($link)); exit; }

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
// Fetch
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
if (!$stmt) { http_response_code(500); echo "DB error: " . h(mysqli_error($link)); exit; }

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
// URL helpers
// ---------------------------
function ubbc_url(array $overrides = []): string {
    $base = [
        'q'    => $_GET['q']    ?? '',
        'view' => $_GET['view'] ?? 'all',
        'sort' => $_GET['sort'] ?? 'received_at',
        'dir'  => $_GET['dir']  ?? 'desc',
        'page' => $_GET['page'] ?? 1,
    ];
    $m = array_merge($base, $overrides);
    if (($m['q'] ?? '') === '') unset($m['q']);
    return 'entrylist.php?' . http_build_query($m);
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

include __DIR__ . '/header.php';
?>

    <div class="live-container">

        <div class="live-toolbar">
            <form method="get" action="entrylist.php" class="live-toolbar-form">
                <input class="live-search" type="text" name="q" value="<?php echo h($q); ?>" placeholder="Recherche : nom, email, club, ville, course">

                <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
                <input type="hidden" name="dir"  value="<?php echo h($dir); ?>">

                <select name="view" class="live-select">
                    <option value="all"      <?php echo ($view==='all')?'selected':''; ?>>Tous</option>
                    <option value="approved" <?php echo ($view==='approved')?'selected':''; ?>>Approved</option>
                    <option value="pending"  <?php echo ($view==='pending')?'selected':''; ?>>Pending</option>
                    <option value="refused"  <?php echo ($view==='refused')?'selected':''; ?>>Refused</option>
                </select>

                <button class="live-btn" type="submit">Filtrer</button>
                <a class="live-btn live-btn-ghost" href="entrylist.php">Reset</a>
            </form>

            <div class="live-count"><?php echo (int)$totalRows; ?> inscriptions</div>
        </div>

        <div class="status-legend">
            <span class="status-pill status-approved">approved</span>
            <span class="status-pill status-pending">pending</span>
            <span class="status-pill status-refused">refused</span>
        </div>

        <!-- TABLE DESKTOP -->
        <div class="only-desktop">
            <div class="live-table-wrap">
                <table class="live-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th><a class="th-link" href="<?php echo h(ubbc_sort_link('lastname')); ?>">Nom</a></th>
                        <th><a class="th-link" href="<?php echo h(ubbc_sort_link('firstname')); ?>">Prénom</a></th>
                        <th>Gender</th>
                        <th>Cat</th>
                        <th><a class="th-link" href="<?php echo h(ubbc_sort_link('itra')); ?>">Itra</a></th>
                        <th><a class="th-link" href="<?php echo h(ubbc_sort_link('race')); ?>">Race</a></th>
                        <th>Club</th>
                        <th>City</th>
                        <th>Licence</th>
                        <th><a class="th-link" href="<?php echo h(ubbc_sort_link('participations')); ?>">Participations</a></th>
                        <th>Availability</th>
                        <th>review_note</th>
                        <th>Statut</th>
                        <th><a class="th-link" href="<?php echo h(ubbc_sort_link('received_at')); ?>">Received at</a></th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $i = 0;
                    foreach ($rows as $r):
                        $i++;
                        $num = $offset + $i;

                        $approved = (string)($r['approved'] ?? '0');
                        $refused  = (string)($r['refused'] ?? '0');
                        $status   = ubbc_status($approved, $refused); // helpers.php
                        $rowClass = 'row-' . $status;

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

                        $raw = (string)($r['raw_text'] ?? '');
                        $availKeys = extract_json_keys_from_raw($raw, ['Disponibilités en juillet','Disponibilites en juillet']);
                        $partKeys  = extract_json_keys_from_raw($raw, ['Participations UBBC','Participations']);

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
                            'review_note' => (string)($r['review_note'] ?? ''),
                            'approved' => (int)($r['approved'] ?? 0),
                            'refused' => (int)($r['refused'] ?? 0),
                            'motivation' => (string)($r['motivation'] ?? ''),
                            'contribution' => (string)($r['contribution'] ?? ''),
                            'received_at' => (string)($r['received_at'] ?? ''),
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
                            <td><?php echo h($cat); ?></td>
                            <td class="col-center"><?php echo h($itraDisplay); ?></td>
                            <td><?php echo h($race); ?></td>
                            <td><?php echo h($club); ?></td>
                            <td><?php echo h($city); ?></td>
                            <td><?php echo h((string)($r['licence'] ?? '')); ?></td>
                            <td class="col-center"><?php echo h($partsDisplay); ?></td>

                            <td class="col-center">
                                <span class="dot <?php echo $availability ? 'dot-green' : 'dot-red'; ?>" title="Availability"></span>
                            </td>

                            <td><?php echo h((string)($r['review_note'] ?? '')); ?></td>

                            <td class="col-center">
              <span class="dot
                <?php echo ($status === 'approved') ? 'dot-green' : (($status === 'pending') ? 'dot-prune' : 'dot-red'); ?>
              " title="<?php echo h($status); ?>"></span>
                            </td>

                            <td><?php echo h((string)($r['received_at'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CARDS MOBILE -->
        <div class="only-mobile">
            <div class="cards">
                <?php foreach ($rows as $r):

                    $approved = (string)($r['approved'] ?? '0');
                    $refused  = (string)($r['refused'] ?? '0');
                    $status   = ubbc_status($approved, $refused);
                    $rowClass = 'row-' . $status;

                    $lastname  = title_case((string)($r['lastname'] ?? ''));
                    $firstname = title_case((string)($r['firstname'] ?? ''));
                    $city      = title_case((string)($r['city'] ?? ''));
                    $club      = title_case((string)($r['club'] ?? ''));
                    $race      = strtoupper((string)($r['race'] ?? ''));

                    $cat = category_from_birthdate((string)($r['birthdate'] ?? ''));
                    $itra = (int)($r['itra'] ?? 0);
                    $parts = (int)($r['participations'] ?? 0);
                    $availability = (int)($r['availability'] ?? 0);

                    $raw = (string)($r['raw_text'] ?? '');
                    $availKeys = extract_json_keys_from_raw($raw, ['Disponibilités en juillet','Disponibilites en juillet']);
                    $partKeys  = extract_json_keys_from_raw($raw, ['Participations UBBC','Participations']);

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
                        'review_note' => (string)($r['review_note'] ?? ''),
                        'approved' => (int)($r['approved'] ?? 0),
                        'refused' => (int)($r['refused'] ?? 0),
                        'motivation' => (string)($r['motivation'] ?? ''),
                        'contribution' => (string)($r['contribution'] ?? ''),
                        'received_at' => (string)($r['received_at'] ?? ''),
                        'avail_keys' => $availKeys,
                        'part_keys' => $partKeys,
                        'raw_text' => $raw,
                    ];

                    $statusDotClass = ($status === 'approved') ? 'dot-green' : (($status === 'pending') ? 'dot-prune' : 'dot-red');
                    ?>
                    <div class="card-item <?php echo h($rowClass); ?>">
                        <div class="card-top">
                            <div class="card-title">
                                <a href="#"
                                   class="entry-link js-open-entry"
                                   data-entry="<?php echo h(json_encode($entry, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?>">
                                    <?php echo h($lastname . ' ' . $firstname); ?>
                                </a>
                                <div class="card-sub"><?php echo h($race); ?></div>
                            </div>

                            <div class="card-dots">
                                <span class="dot <?php echo $availability ? 'dot-green' : 'dot-red'; ?>" title="Availability"></span>
                                <span class="dot <?php echo h($statusDotClass); ?>" title="<?php echo h($status); ?>"></span>
                            </div>
                        </div>

                        <div class="card-grid">
                            <div><span class="k">Cat</span><span class="v"><?php echo h($cat); ?></span></div>
                            <div><span class="k">Itra</span><span class="v"><?php echo ($itra > 0) ? h((string)$itra) : '—'; ?></span></div>
                            <div><span class="k">Parts</span><span class="v"><?php echo ($parts > 0) ? h((string)$parts) : '—'; ?></span></div>
                            <div><span class="k">City</span><span class="v"><?php echo h($city); ?></span></div>
                            <div><span class="k">Club</span><span class="v"><?php echo h($club); ?></span></div>
                            <div><span class="k">Licence</span><span class="v"><?php echo h((string)($r['licence'] ?? '')); ?></span></div>
                        </div>

                        <?php if (!empty($r['review_note'])): ?>
                            <div class="card-note"><?php echo h((string)$r['review_note']); ?></div>
                        <?php endif; ?>

                        <div class="card-foot"><?php echo h((string)($r['received_at'] ?? '')); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $prev = max(1, $page - 1);
                $next = min($totalPages, $page + 1);

                $window = 3;
                $start  = max(1, $page - $window);
                $end    = min($totalPages, $page + $window);

                $btn = function(string $label, ?string $href, bool $current=false) {
                    if ($current) return '<span class="p-current">'.$label.'</span>';
                    if ($href === null) return '<span class="p-disabled">'.$label.'</span>';
                    return '<a class="p-btn" href="'.h($href).'">'.$label.'</a>';
                };

                echo $btn('‹', ($page > 1) ? ubbc_url(['page'=>$prev]) : null);

                if ($start > 1) {
                    echo $btn('1', ubbc_url(['page'=>1]));
                    if ($start > 2) echo '<span class="p-ellipsis">…</span>';
                }

                for ($p=$start; $p<=$end; $p++) {
                    echo $btn((string)$p, $p===$page ? null : ubbc_url(['page'=>$p]), $p===$page);
                }

                if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<span class="p-ellipsis">…</span>';
                    echo $btn((string)$totalPages, ubbc_url(['page'=>$totalPages]));
                }

                echo $btn('›', ($page < $totalPages) ? ubbc_url(['page'=>$next]) : null);
                ?>
            </div>
        <?php endif; ?>

    </div>

<?php
include __DIR__ . '/entrylist_modal.php';
include __DIR__ . '/footer.php';
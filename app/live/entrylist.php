<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();

/**
 * Params
 */
$q    = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$view = isset($_GET['view']) ? (string)$_GET['view'] : 'all';  // all|approved|pending|refused
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'received_at';
$dir  = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));

$perPage = 50;
$offset  = ($page - 1) * $perPage;

/**
 * URL helpers
 */
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

function th_sort(string $label, string $key): string {
    $href = ubbc_sort_link($key);
    return '<a class="th-link" href="'.h($href).'">'.h($label).'</a>';
}

/**
 * WHERE
 */
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

/**
 * Computed fields in SQL: season, age, category label, cat rank, status rank
 * - season: Sep-Dec => year+1, Jan-Aug => year
 * - age = season - birth_year
 * - category label from age
 * - cat_rank for sorting
 * - status_rank: approved(0) pending(1) refused(2)   (tu peux inverser si tu préfères)
 */
$seasonExpr = "(
  CASE
    WHEN MONTH(CURDATE()) BETWEEN 9 AND 12 THEN YEAR(CURDATE()) + 1
    ELSE YEAR(CURDATE())
  END
)";

$ageExpr = "(
  CASE
    WHEN i.birthdate IS NULL THEN NULL
    ELSE {$seasonExpr} - YEAR(i.birthdate)
  END
)";

$catLabelExpr = "(
  CASE
    WHEN {$ageExpr} IS NULL THEN ''
    WHEN {$ageExpr} BETWEEN 13 AND 14 THEN 'MI'
    WHEN {$ageExpr} BETWEEN 15 AND 16 THEN 'CA'
    WHEN {$ageExpr} BETWEEN 17 AND 18 THEN 'JU'
    WHEN {$ageExpr} BETWEEN 19 AND 22 THEN 'ES'
    WHEN {$ageExpr} BETWEEN 23 AND 34 THEN 'SE'
    WHEN {$ageExpr} BETWEEN 35 AND 39 THEN 'M0'
    WHEN {$ageExpr} BETWEEN 40 AND 44 THEN 'M1'
    WHEN {$ageExpr} BETWEEN 45 AND 49 THEN 'M2'
    WHEN {$ageExpr} BETWEEN 50 AND 54 THEN 'M3'
    WHEN {$ageExpr} BETWEEN 55 AND 59 THEN 'M4'
    WHEN {$ageExpr} BETWEEN 60 AND 64 THEN 'M5'
    WHEN {$ageExpr} BETWEEN 65 AND 69 THEN 'M6'
    WHEN {$ageExpr} BETWEEN 70 AND 74 THEN 'M7'
    WHEN {$ageExpr} BETWEEN 75 AND 79 THEN 'M8'
    ELSE 'Hors cat'
  END
)";

$catRankExpr = "(
  CASE
    WHEN {$ageExpr} IS NULL THEN 999
    WHEN {$ageExpr} BETWEEN 13 AND 14 THEN 10
    WHEN {$ageExpr} BETWEEN 15 AND 16 THEN 20
    WHEN {$ageExpr} BETWEEN 17 AND 18 THEN 30
    WHEN {$ageExpr} BETWEEN 19 AND 22 THEN 40
    WHEN {$ageExpr} BETWEEN 23 AND 34 THEN 50
    WHEN {$ageExpr} BETWEEN 35 AND 39 THEN 60
    WHEN {$ageExpr} BETWEEN 40 AND 44 THEN 70
    WHEN {$ageExpr} BETWEEN 45 AND 49 THEN 80
    WHEN {$ageExpr} BETWEEN 50 AND 54 THEN 90
    WHEN {$ageExpr} BETWEEN 55 AND 59 THEN 100
    WHEN {$ageExpr} BETWEEN 60 AND 64 THEN 110
    WHEN {$ageExpr} BETWEEN 65 AND 69 THEN 120
    WHEN {$ageExpr} BETWEEN 70 AND 74 THEN 130
    WHEN {$ageExpr} BETWEEN 75 AND 79 THEN 140
    ELSE 998
  END
)";

$statusRankExpr = "(
  CASE
    WHEN i.refused = 1 THEN 2
    WHEN i.approved = 1 THEN 0
    ELSE 1
  END
)";

/**
 * ORDER BY mapping (requested columns)
 */
$sortMap = [
    'lastname'       => 'i.lastname',
    'firstname'      => 'i.firstname',
    'gender'         => 'i.gender',
    'cat'            => 'cat_rank',
    'itra'           => 'i.itra',
    'race'           => 'i.race',
    'club'           => 'i.club',
    'city'           => 'i.city',
    'participations' => 'i.participations',
    'availability'   => 'i.availability',
    'status'         => 'status_rank',
    'received_at'    => 'i.received_at',
];

$sort = array_key_exists($sort, $sortMap) ? $sort : 'received_at';
$orderBy = $sortMap[$sort];

/**
 * Count
 */
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

/**
 * Fetch
 */
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
  i.received_at,
  {$catLabelExpr} AS cat_label,
  {$catRankExpr} AS cat_rank,
  {$statusRankExpr} AS status_rank
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

include __DIR__ . '/header.php';
?>

    <div class="live-container">

        <div class="live-toolbar">
            <form method="get" action="entrylist.php" class="live-toolbar-form">
                <input class="live-search" type="text" name="q" value="<?php echo h($q); ?>" placeholder="Recherche : nom, email, club, ville, course">

                <select name="view" class="live-select">
                    <option value="all"      <?php echo ($view==='all')?'selected':''; ?>>Tous</option>
                    <option value="approved" <?php echo ($view==='approved')?'selected':''; ?>>Approved</option>
                    <option value="pending"  <?php echo ($view==='pending')?'selected':''; ?>>Pending</option>
                    <option value="refused"  <?php echo ($view==='refused')?'selected':''; ?>>Refused</option>
                </select>

                <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
                <input type="hidden" name="dir"  value="<?php echo h($dir); ?>">

                <button class="live-btn" type="submit">Filtrer</button>
                <a class="live-btn live-btn-ghost" href="entrylist.php">Reset</a>
            </form>

            <div class="live-count"><?php echo (int)$totalRows; ?> inscriptions</div>
        </div>

        <!-- TABLE (desktop) -->
        <div class="only-desktop">
            <div class="live-table-wrap">
                <table class="live-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo th_sort('Nom', 'lastname'); ?></th>
                        <th><?php echo th_sort('Prénom', 'firstname'); ?></th>
                        <th><?php echo th_sort('Gender', 'gender'); ?></th>
                        <th><?php echo th_sort('Cat', 'cat'); ?></th>
                        <th><?php echo th_sort('Itra', 'itra'); ?></th>
                        <th><?php echo th_sort('Race', 'race'); ?></th>
                        <th><?php echo th_sort('Club', 'club'); ?></th>
                        <th><?php echo th_sort('City', 'city'); ?></th>
                        <th>Licence</th>
                        <th><?php echo th_sort('Participations', 'participations'); ?></th>
                        <th>review_note</th>
                        <th class="col-center"><?php echo th_sort('Availability', 'availability'); ?></th>
                        <th class="col-center"><?php echo th_sort('Statut', 'status'); ?></th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $i = 0;
                    foreach ($rows as $r):
                        $i++;
                        $num = $offset + $i;

                        $approved = (int)($r['approved'] ?? 0);
                        $refused  = (int)($r['refused'] ?? 0);
                        $status   = ubbc_status((string)$approved, (string)$refused); // helpers.php => approved|pending|refused
                        $rowClass = 'row-' . $status;

                        $lastname  = title_case((string)($r['lastname'] ?? ''));
                        $firstname = title_case((string)($r['firstname'] ?? ''));
                        $city      = title_case((string)($r['city'] ?? ''));
                        $club      = title_case((string)($r['club'] ?? ''));
                        $race      = strtoupper((string)($r['race'] ?? ''));

                        $cat = (string)($r['cat_label'] ?? '');

                        $itra = (int)($r['itra'] ?? 0);
                        $itraDisplay = ($itra > 0) ? (string)$itra : '—';

                        $parts = (int)($r['participations'] ?? 0);
                        $partsDisplay = ($parts > 0) ? (string)$parts : '—';

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
                            'approved' => $approved,
                            'refused' => $refused,
                            'motivation' => (string)($r['motivation'] ?? ''),
                            'contribution' => (string)($r['contribution'] ?? ''),
                            'received_at' => (string)($r['received_at'] ?? ''),
                            'avail_keys' => $availKeys,
                            'part_keys' => $partKeys,
                            'raw_text' => $raw,
                            'cat' => $cat,
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
                            <td><?php echo h((string)($r['review_note'] ?? '')); ?></td>

                            <td class="col-center">
                                <span class="dot <?php echo $availability ? 'dot-green' : 'dot-red'; ?>" title="Availability"></span>
                            </td>

                            <td class="col-center">
              <span class="dot
                <?php echo ($status === 'approved') ? 'dot-green' : (($status === 'pending') ? 'dot-prune' : 'dot-red'); ?>
              " title="<?php echo h($status); ?>"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- MOBILE (cards only) -->
        <div class="only-mobile">
            <div class="cards">
                <?php foreach ($rows as $r):

                    $approved = (int)($r['approved'] ?? 0);
                    $refused  = (int)($r['refused'] ?? 0);
                    $status   = ubbc_status((string)$approved, (string)$refused);
                    $rowClass = 'row-' . $status;

                    $lastname  = title_case((string)($r['lastname'] ?? ''));
                    $firstname = title_case((string)($r['firstname'] ?? ''));
                    $city      = title_case((string)($r['city'] ?? ''));
                    $club      = title_case((string)($r['club'] ?? ''));
                    $race      = strtoupper((string)($r['race'] ?? ''));
                    $cat       = (string)($r['cat_label'] ?? '');

                    $itra  = (int)($r['itra'] ?? 0);
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
                        'approved' => $approved,
                        'refused' => $refused,
                        'motivation' => (string)($r['motivation'] ?? ''),
                        'contribution' => (string)($r['contribution'] ?? ''),
                        'received_at' => (string)($r['received_at'] ?? ''),
                        'avail_keys' => $availKeys,
                        'part_keys' => $partKeys,
                        'raw_text' => $raw,
                        'cat' => $cat,
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
                            <div><span class="k">Gender</span><span class="v"><?php echo h((string)($r['gender'] ?? '')); ?></span></div>
                            <div><span class="k">Cat</span><span class="v"><?php echo h($cat ?: '—'); ?></span></div>
                            <div><span class="k">Itra</span><span class="v"><?php echo ($itra > 0) ? h((string)$itra) : '—'; ?></span></div>
                            <div><span class="k">Parts</span><span class="v"><?php echo ($parts > 0) ? h((string)$parts) : '—'; ?></span></div>
                            <div><span class="k">City</span><span class="v"><?php echo h($city); ?></span></div>
                            <div><span class="k">Club</span><span class="v"><?php echo h($club); ?></span></div>
                        </div>

                        <?php if (!empty($r['review_note'])): ?>
                            <div class="card-note"><?php echo h((string)$r['review_note']); ?></div>
                        <?php endif; ?>
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
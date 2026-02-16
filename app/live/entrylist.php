<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();

// ---------- Params (pagination + tri) ----------
$perPage = isset($_GET['per_page']) ? max(10, min(200, (int)$_GET['per_page'])) : 50;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'lastname';
$dir  = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'asc';
$dir  = ($dir === 'desc') ? 'DESC' : 'ASC';

$allowedSorts = [
    'lastname'        => "i.lastname",
    'firstname'       => "i.firstname",
    'gender'          => "i.gender",
    // cat = tri approximé via birthdate (cohérent avec la logique de catégorie)
    'cat'             => "i.birthdate",
    'itra'            => "i.itra",
    'race'            => "i.race",
    'club'            => "i.club",
    'city'            => "i.city",
    'participations'  => "i.participations",
    'availability'    => "i.availability",
    // statut : pending(0) / approved(1) / refused(2)
    'status'          => "CASE WHEN i.refused=1 THEN 2 WHEN i.approved=1 THEN 1 ELSE 0 END",
];

$orderBy = $allowedSorts[$sort] ?? $allowedSorts['lastname'];

// ---------- Helpers view ----------
function q(array $extra = []): string {
    $params = array_merge($_GET, $extra);
    foreach ($params as $k => $v) {
        if ($v === null) unset($params[$k]);
    }
    return '?' . http_build_query($params);
}

function sort_link(string $key): string {
    $currentSort = (string)($_GET['sort'] ?? 'lastname');
    $currentDir  = strtolower((string)($_GET['dir'] ?? 'asc'));
    $nextDir = 'asc';
    if ($currentSort === $key && $currentDir === 'asc') $nextDir = 'desc';

    return q(['sort' => $key, 'dir' => $nextDir, 'page' => 1]);
}

function sort_caret(string $key): string {
    $currentSort = (string)($_GET['sort'] ?? 'lastname');
    $currentDir  = strtolower((string)($_GET['dir'] ?? 'asc'));
    if ($currentSort !== $key) return '';
    return ($currentDir === 'desc') ? ' ▼' : ' ▲';
}

// ---------- Count ----------
$countSql = "SELECT COUNT(*) AS c FROM inscriptions i";
$countRes = mysqli_query($link, $countSql);
$total = 0;
if ($countRes && $row = mysqli_fetch_assoc($countRes)) {
    $total = (int)$row['c'];
}
if ($countRes) mysqli_free_result($countRes);

$totalPages = max(1, (int)ceil($total / $perPage));

// ---------- Data ----------
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
  i.contribution,
  i.motivation,
  i.raw_text,
  i.review_note,
  i.approved,
  i.refused,
  i.received_at
FROM inscriptions i
ORDER BY {$orderBy} {$dir}
LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
    http_response_code(500);
    echo "DB error: " . h(mysqli_error($link));
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $perPage, $offset);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// ---------- Render ----------
include __DIR__ . '/header.php';
?>
    <link rel="stylesheet" href="/static/css/live.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <div class="live-wrap container-fluid py-3">

        <div class="live-topbar">
            <div class="legend">
                <span class="legend-item status-approved">approved</span>
                <span class="legend-item status-pending">pending</span>
                <span class="legend-item status-refused">refused</span>
            </div>

            <div class="pager">
                <a class="pager-btn" href="<?= h(q(['page' => max(1, $page - 1)])) ?>" <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>Précédent</a>
                <span class="pager-info"><?= h((string)$page) ?> / <?= h((string)$totalPages) ?> (<?= h((string)$total) ?>)</span>
                <a class="pager-btn" href="<?= h(q(['page' => min($totalPages, $page + 1)])) ?>" <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>>Suivant</a>
            </div>
        </div>

        <!-- DESKTOP TABLE -->
        <div class="entry-table-wrap d-none d-md-block">
            <table class="table table-sm align-middle entry-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th><a class="th-link" href="<?= h(sort_link('lastname')) ?>">Nom<?= h(sort_caret('lastname')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('firstname')) ?>">Prénom<?= h(sort_caret('firstname')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('gender')) ?>">Gender<?= h(sort_caret('gender')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('cat')) ?>">Cat<?= h(sort_caret('cat')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('itra')) ?>">Itra<?= h(sort_caret('itra')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('race')) ?>">Race<?= h(sort_caret('race')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('club')) ?>">Club<?= h(sort_caret('club')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('city')) ?>">City<?= h(sort_caret('city')) ?></a></th>
                    <th>Licence</th>
                    <th><a class="th-link" href="<?= h(sort_link('participations')) ?>">Participations<?= h(sort_caret('participations')) ?></a></th>
                    <th>Review note</th>
                    <th><a class="th-link" href="<?= h(sort_link('availability')) ?>">Availability<?= h(sort_caret('availability')) ?></a></th>
                    <th><a class="th-link" href="<?= h(sort_link('status')) ?>">Statut<?= h(sort_caret('status')) ?></a></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = $offset;
                foreach ($rows as $r):
                    $i++;

                    $rowClass = status_class($r);
                    $status = ubbc_status((string)($r['approved'] ?? '0'), (string)($r['refused'] ?? '0'));

                    $cat = category_from_birthdate((string)($r['birthdate'] ?? ''));

                    $lastname  = title_case((string)($r['lastname'] ?? ''));
                    $firstname = title_case((string)($r['firstname'] ?? ''));
                    $city      = title_case((string)($r['city'] ?? ''));
                    $club      = title_case((string)($r['club'] ?? ''));
                    $race      = upper((string)($r['race'] ?? ''));

                    $itra = dash_if_zero($r['itra'] ?? null);
                    $parts = dash_if_zero($r['participations'] ?? null);

                    $avail = (int)($r['availability'] ?? 0);
                    $availIcon = bool_icon($avail, 'available 24-31', 'not available 24-31');

                    $raw = (string)($r['raw_text'] ?? '');
                    $availKeys = extract_json_keys_from_raw($raw, ['Disponibilités en juillet', 'Disponibilites en juillet']);
                    $partKeys  = extract_json_keys_from_raw($raw, ['Participations UBBC']);

                    $modalPayload = [
                        'id' => (int)$r['id'],
                        'status' => $status,
                        'email' => (string)($r['email'] ?? ''),
                        'lastname' => $lastname,
                        'firstname' => $firstname,
                        'birthdate' => (string)($r['birthdate'] ?? ''),
                        'gender' => (string)($r['gender'] ?? ''),
                        'cat' => $cat,
                        'itra' => $itra,
                        'race' => $race,
                        'club' => $club,
                        'city' => $city,
                        'licence' => (string)($r['licence'] ?? ''),
                        'participations' => $parts,
                        'availability' => $avail,
                        'review_note' => (string)($r['review_note'] ?? ''),
                        'contribution' => (string)($r['contribution'] ?? ''),
                        'motivation' => (string)($r['motivation'] ?? ''),
                        'source_file' => (string)($r['source_file'] ?? ''),
                        'received_at' => (string)($r['received_at'] ?? ''),
                        'raw_availability_keys' => $availKeys,
                        'raw_participations_keys' => $partKeys,
                    ];
                    $modalJson = htmlspecialchars(json_encode($modalPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    $nameLinkClass = "name-link name-link-" . $status;
                    ?>
                    <tr class="<?= h($rowClass) ?>">
                        <td class="col-num"><?= h((string)$i) ?></td>

                        <td>
                            <a href="#" class="<?= h($nameLinkClass) ?>" data-modal='<?= $modalJson ?>' data-bs-toggle="modal" data-bs-target="#entryModal">
                                <?= h($lastname) ?>
                            </a>
                        </td>

                        <td>
                            <a href="#" class="<?= h($nameLinkClass) ?>" data-modal='<?= $modalJson ?>' data-bs-toggle="modal" data-bs-target="#entryModal">
                                <?= h($firstname) ?>
                            </a>
                        </td>

                        <td><?= h((string)($r['gender'] ?? '')) ?></td>
                        <td><?= h($cat) ?></td>
                        <td><?= h($itra) ?></td>
                        <td><?= h($race) ?></td>
                        <td><?= h($club) ?></td>
                        <td><?= h($city) ?></td>
                        <td><?= h((string)($r['licence'] ?? '')) ?></td>
                        <td><?= h($parts) ?></td>
                        <td class="review"><?= h((string)($r['review_note'] ?? '')) ?></td>
                        <td class="col-icon"><?= $availIcon ?></td>
                        <td class="col-icon"><span class="status-dot status-<?= h($status) ?>" title="<?= h($status) ?>"></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MOBILE CARDS -->
        <div class="entry-cards d-md-none">
            <?php foreach ($rows as $r):
                $rowClass = status_class($r);
                $status = ubbc_status((string)($r['approved'] ?? '0'), (string)($r['refused'] ?? '0'));
                $cat = category_from_birthdate((string)($r['birthdate'] ?? ''));

                $lastname  = title_case((string)($r['lastname'] ?? ''));
                $firstname = title_case((string)($r['firstname'] ?? ''));
                $city      = title_case((string)($r['city'] ?? ''));
                $club      = title_case((string)($r['club'] ?? ''));
                $race      = upper((string)($r['race'] ?? ''));

                $itra = dash_if_zero($r['itra'] ?? null);
                $parts = dash_if_zero($r['participations'] ?? null);

                $avail = (int)($r['availability'] ?? 0);
                $availIcon = bool_icon($avail, 'available 24-31', 'not available 24-31');

                $raw = (string)($r['raw_text'] ?? '');
                $availKeys = extract_json_keys_from_raw($raw, ['Disponibilités en juillet', 'Disponibilites en juillet']);
                $partKeys  = extract_json_keys_from_raw($raw, ['Participations UBBC']);

                $modalPayload = [
                    'id' => (int)$r['id'],
                    'status' => $status,
                    'email' => (string)($r['email'] ?? ''),
                    'lastname' => $lastname,
                    'firstname' => $firstname,
                    'birthdate' => (string)($r['birthdate'] ?? ''),
                    'gender' => (string)($r['gender'] ?? ''),
                    'cat' => $cat,
                    'itra' => $itra,
                    'race' => $race,
                    'club' => $club,
                    'city' => $city,
                    'licence' => (string)($r['licence'] ?? ''),
                    'participations' => $parts,
                    'availability' => $avail,
                    'review_note' => (string)($r['review_note'] ?? ''),
                    'contribution' => (string)($r['contribution'] ?? ''),
                    'motivation' => (string)($r['motivation'] ?? ''),
                    'source_file' => (string)($r['source_file'] ?? ''),
                    'received_at' => (string)($r['received_at'] ?? ''),
                    'raw_availability_keys' => $availKeys,
                    'raw_participations_keys' => $partKeys,
                ];
                $modalJson = htmlspecialchars(json_encode($modalPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $nameLinkClass = "name-link name-link-" . $status;
                ?>
                <div class="entry-card <?= h($rowClass) ?>">
                    <div class="entry-card-head">
                        <span class="status-dot status-<?= h($status) ?>" title="<?= h($status) ?>"></span>

                        <a href="#" class="<?= h($nameLinkClass) ?>" data-modal='<?= $modalJson ?>' data-bs-toggle="modal" data-bs-target="#entryModal">
                            <?= h($lastname) ?> <?= h($firstname) ?>
                        </a>
                    </div>

                    <div class="entry-card-body">
                        <div class="kv"><span>Gender</span><b><?= h((string)($r['gender'] ?? '')) ?></b></div>
                        <div class="kv"><span>Cat</span><b><?= h($cat) ?></b></div>
                        <div class="kv"><span>Itra</span><b><?= h($itra) ?></b></div>
                        <div class="kv"><span>Race</span><b><?= h($race) ?></b></div>
                        <div class="kv"><span>Club</span><b><?= h($club) ?></b></div>
                        <div class="kv"><span>City</span><b><?= h($city) ?></b></div>
                        <div class="kv"><span>Licence</span><b><?= h((string)($r['licence'] ?? '')) ?></b></div>
                        <div class="kv"><span>Participations</span><b><?= h($parts) ?></b></div>
                        <div class="kv"><span>Availability</span><b><?= $availIcon ?></b></div>
                        <div class="kv full"><span>Review</span><b><?= h((string)($r['review_note'] ?? '')) ?></b></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="live-bottombar">
            <div class="pager">
                <a class="pager-btn" href="<?= h(q(['page' => max(1, $page - 1)])) ?>" <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>Précédent</a>
                <span class="pager-info"><?= h((string)$page) ?> / <?= h((string)$totalPages) ?> (<?= h((string)$total) ?>)</span>
                <a class="pager-btn" href="<?= h(q(['page' => min($totalPages, $page + 1)])) ?>" <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>>Suivant</a>
            </div>
        </div>

    </div>

<?php include __DIR__ . '/entrylist_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const modalEl = document.getElementById('entryModal');
            if (!modalEl) return;

            modalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const raw = trigger.getAttribute('data-modal');
                if (!raw) return;

                let data = null;
                try { data = JSON.parse(raw); } catch (e) { return; }

                const setText = (id, v) => {
                    const el = modalEl.querySelector('#' + id);
                    if (!el) return;
                    el.textContent = (v === null || v === undefined) ? '' : String(v);
                };

                setText('m_status', data.status || '');
                setText('m_email', data.email || '');
                setText('m_lastname', data.lastname || '');
                setText('m_firstname', data.firstname || '');
                setText('m_birthdate', data.birthdate || '');
                setText('m_gender', data.gender || '');
                setText('m_cat', data.cat || '');
                setText('m_itra', data.itra || '');
                setText('m_race', data.race || '');
                setText('m_club', data.club || '');
                setText('m_city', data.city || '');
                setText('m_licence', data.licence || '');
                setText('m_participations', data.participations || '');
                setText('m_review_note', data.review_note || '');
                setText('m_contribution', data.contribution || '');
                setText('m_motivation', data.motivation || '');
                setText('m_source_file', data.source_file || '');
                setText('m_received_at', data.received_at || '');

                // availability icon
                const avail = modalEl.querySelector('#m_availability');
                if (avail) {
                    avail.innerHTML = data.availability == 1
                        ? '<span class="bool-icon bool-true" title="available 24-31"></span>'
                        : '<span class="bool-icon bool-false" title="not available 24-31"></span>';
                }

                const fillList = (id, arr) => {
                    const ul = modalEl.querySelector('#' + id);
                    if (!ul) return;
                    ul.innerHTML = '';
                    if (!arr || !Array.isArray(arr) || arr.length === 0) {
                        ul.innerHTML = '<li class="muted">—</li>';
                        return;
                    }
                    arr.forEach(k => {
                        const li = document.createElement('li');
                        li.textContent = String(k);
                        ul.appendChild(li);
                    });
                };

                fillList('m_avail_keys', data.raw_availability_keys || []);
                fillList('m_part_keys', data.raw_participations_keys || []);

                // status color in modal header
                const badge = modalEl.querySelector('.modal-status-dot');
                if (badge) {
                    badge.classList.remove('status-approved', 'status-pending', 'status-refused');
                    badge.classList.add('status-' + (data.status || 'pending'));
                }
            });
        })();
    </script>

<?php
include __DIR__ . '/footer.php';
mysqli_close($link);
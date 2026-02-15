<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/helpers.php';
// /live/entrylist.php
require_once __DIR__ . '/../includes/db-only.php';
$link = ubbc_db_connect();

header('Content-Type: text/html; charset=utf-8');

// -------- params
$searchQuery = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$showAll = (isset($_GET['showAll']) && $_GET['showAll'] === 'yes') ? 'yes' : 'no';

// par défaut on dédoublonne (dernière soumission par email+race)
$dedupe = (isset($_GET['dedupe']) && $_GET['dedupe'] === 'no') ? 'no' : 'yes';

// pagination
$nbperpage = 25;

// tri (whitelist stricte)
$allowedOrder = [
    'received_at' => 'received_at',
    'lastname'    => 'lastname',
    'firstname'   => 'firstname',
    'email'       => 'email',
    'gender'      => 'gender',
    'club'        => 'club',
    'city'        => 'city',
    'race'        => 'race',
    'approved'    => 'approved',
];

$order = $allowedOrder[$_GET['order'] ?? 'received_at'] ?? 'received_at';
$asc = (isset($_GET['asc']) && in_array($_GET['asc'], ['asc','desc'], true)) ? $_GET['asc'] : 'desc';
$dasc = ($asc === 'asc') ? 'desc' : 'asc';

// page
$cpage = (isset($_GET['page']) && (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;

// -------- base SQL (search)
$searchSql = '';
if ($searchQuery !== '') {
    $q = mysqli_real_escape_string($link, $searchQuery);
    $searchSql = " AND (
        email LIKE '%$q%' OR
        lastname LIKE '%$q%' OR
        firstname LIKE '%$q%' OR
        club LIKE '%$q%' OR
        city LIKE '%$q%' OR
        race LIKE '%$q%'
    )";
}

// -------- dataset : dédoublonné ou complet
// DEDUPE = dernière ligne par (email, race). Si email est NULL/'' -> on garde tel quel via id.
if ($dedupe === 'yes') {
    $fromSql = "
        FROM inscriptions i
        JOIN (
            SELECT email, race, MAX(received_at) AS mx
            FROM inscriptions
            WHERE email IS NOT NULL AND email <> ''
            $searchSql
            GROUP BY email, race
        ) t
          ON i.email = t.email
         AND i.race  = t.race
         AND i.received_at = t.mx
        WHERE 1=1
    ";
    // on réapplique le search sur i (utile si certains champs changent)
    $whereSql = " $searchSql ";
} else {
    $fromSql = "FROM inscriptions i WHERE 1=1";
    $whereSql = " $searchSql ";
}

// -------- count
$sqlCount = "SELECT COUNT(i.id) AS nb $fromSql $whereSql";
$res = mysqli_query($link, $sqlCount);
$record = mysqli_fetch_array($res, MYSQLI_ASSOC);
$nbusers = (int)($record['nb'] ?? 0);
mysqli_free_result($res);

$nbpage = max(1, (int)ceil($nbusers / $nbperpage));
if ($cpage > $nbpage) $cpage = $nbpage;
$rowNumber = ($cpage - 1) * $nbperpage + 1;
// -------- list query
$sql = "
    SELECT i.*
    $fromSql
    $whereSql
    ORDER BY $order $asc, received_at DESC
";

if ($showAll === 'yes') {
    $sql .= " LIMIT 100000";
} else {
    $offset = ($cpage - 1) * $nbperpage;
    $sql .= " LIMIT $offset, $nbperpage";
}

$results = mysqli_query($link, $sql);

// -------- page
include(__DIR__ . '/header.php');
?>
    <section class="container-fluid">
        <div class="row flex-column">
            <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">Liste des inscriptions (live)</h1>

            <form class="text-center mb-3" method="GET" action="/live/entrylist.php">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="nom, email, club, ville...">
                <button type="submit" class="btn fl-bg-prune text-white"><i class="fas fa-search"></i> Search</button>
                <a href="/live/entrylist.php" class="btn fl-bg-peach text-white">Clear</a>

                <input type="hidden" name="showAll" value="<?php echo $showAll; ?>">
                <input type="hidden" name="dedupe" value="<?php echo $dedupe; ?>">
            </form>

            <div class="text-center mb-2">
                <?php if ($dedupe === 'yes'): ?>
                    <a class="btn btn-sm fl-bg-peach text-white" href="/live/entrylist.php?dedupe=no&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>">Voir doublons</a>
                <?php else: ?>
                    <a class="btn btn-sm fl-bg-prune text-white" href="/live/entrylist.php?dedupe=yes&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>">Masquer doublons</a>
                <?php endif; ?>
            </div>

            <table class="mx-auto table table-striped table-hover table-bordered table-sm vw-100 table-responsive-lg">
                <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
                <tr>
                    <th class="thin">#</th>
                    <th class="thin">Cat</th>
                    <th class="medium"><a href="/live/entrylist.php?order=received_at&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Reçu</a></th>
                    <th class="medium"><a href="/live/entrylist.php?order=approved&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Statut</a></th>
                    <th class="large"><a href="/live/entrylist.php?order=lastname&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Nom</a></th>
                    <th class="large"><a href="/live/entrylist.php?order=firstname&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Prénom</a></th>
                    <th class="thin"><a href="/live/entrylist.php?order=gender&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Genre</a></th>
                    <th class="large"><a href="/live/entrylist.php?order=club&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Club</a></th>
                    <th class="large"><a href="/live/entrylist.php?order=city&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Ville</a></th>
                    <th class="medium"><a href="/live/entrylist.php?order=race&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Course</a></th>
                    <th class="large"><a href="/live/entrylist.php?order=email&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Email</a></th>
                    <th class="col-index">Index</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (!$results || mysqli_num_rows($results) === 0) {
                    echo "<tr><td colspan='9' class='text-center p-3'>Pas d'inscription</td></tr>";
                } else {
                    while ($r = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
                        $status = $r['approved'] ?? $r['approval'] ?? '';
                        if ($status === '' || $status === null) $status = 'reçu';

                        $color = '';
                        if (is_string($status) && stripos($status, 'refus') !== false) {
                            $color = 'class="fl-txt-blood"';
                        }

                        $approval = $r['approval'] ?? null;
                        $rowClass = ubbc_is_refused($approval) ? 'row-refused' : '';

                        $approved = $r['approved'] ?? 'reçu'; // ton statut principal
                        $cat = ubbc_category($r['birthdate'] ?? null);

// payload pour la modale
                        $payload = [
                            'name' => trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')),
                            'email' => $r['email'] ?? '',
                            'race' => $r['race'] ?? '',
                            'received_at' => $r['received_at'] ?? '',
                            'approved' => $approved,
                            'approval' => $approval ?? '',
                            'participations' => $r['participations'] ?? '',
                            'availability' => $r['availability'] ?? '',
                            'contribution' => $r['contribution'] ?? '',
                            'motivation' => $r['motivation'] ?? '',
                        ];

                        $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                        echo "<tr class='{$rowClass}'>";

                        echo "<td>".(int)$rowNumber++."</td>";
                        echo "<td>".htmlspecialchars((string)($r['received_at'] ?? ''), ENT_QUOTES)."</td>";
                        echo "<td>".htmlspecialchars((string)$approved, ENT_QUOTES)."</td>";

                        $lastname = htmlspecialchars((string)($r['lastname'] ?? ''), ENT_QUOTES);
                        $firstname = htmlspecialchars((string)($r['firstname'] ?? ''), ENT_QUOTES);

                        echo "<td>
  <a class='entry-link' href='#' data-bs-toggle='modal' data-bs-target='#entryModal' data-entry='{$dataEntry}' onclick='return false;'>
    {$lastname}
  </a>
</td>";

                        echo "<td>{$firstname}</td>";
                        echo "<td>".htmlspecialchars($cat, ENT_QUOTES)."</td>";
                        echo "<td>".htmlspecialchars((string)($r['gender'] ?? ''), ENT_QUOTES)."</td>";
                        echo "<td>".htmlspecialchars((string)($r['club'] ?? ''), ENT_QUOTES)."</td>";
                        echo "<td>".htmlspecialchars((string)($r['city'] ?? ''), ENT_QUOTES)."</td>";
                        echo "<td>".htmlspecialchars((string)($r['race'] ?? ''), ENT_QUOTES)."</td>";
                        echo "<td class='col-index'>".htmlspecialchars((string)($r['index'] ?? ''), ENT_QUOTES)."</td>";
                        echo "<td>".htmlspecialchars((string)($r['email'] ?? ''), ENT_QUOTES)."</td>";

                        echo "</tr>";
                    }
                }
                mysqli_free_result($results);
                mysqli_close($link);
                ?>
                </tbody>
            </table>

            <div class="m-auto">
                <?php
                if ($nbpage > 1 && $showAll === 'no') {
                    echo "<nav><ul class='pagination'>";

                    $base = "/live/entrylist.php?order=$order&asc=$asc&search=" . urlencode($searchQuery) . "&showAll=$showAll&dedupe=$dedupe";

                    // first
                    if ($cpage > 1) {
                        echo "<li class='page-item'><a class='page-link fl-txt-prune' href='{$base}&page=1'><i class='fas fa-angle-double-left'></i></a></li>";
                    } else {
                        echo "<li class='page-item disabled'><span class='page-link'><i class='fas fa-angle-double-left'></i></span></li>";
                    }

                    $start = max(1, $cpage - 2);
                    $end = min($nbpage, $cpage + 2);

                    if ($cpage <= 3) { $start = 1; $end = min(5, $nbpage); }
                    if ($cpage >= $nbpage - 2) { $start = max(1, $nbpage - 4); $end = $nbpage; }

                    if ($start > 2) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";

                    for ($i = $start; $i <= $end; $i++) {
                        if ($i === $cpage) {
                            echo "<li class='page-item active'><span class='page-link'>$i</span></li>";
                        } else {
                            echo "<li class='page-item'><a class='page-link fl-txt-prune' href='{$base}&page=$i'>$i</a></li>";
                        }
                    }

                    if ($end < $nbpage - 1) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";

                    // last
                    if ($cpage < $nbpage) {
                        echo "<li class='page-item'><a class='page-link fl-txt-prune' href='{$base}&page=$nbpage'><i class='fas fa-angle-double-right'></i></a></li>";
                    } else {
                        echo "<li class='page-item disabled'><span class='page-link'><i class='fas fa-angle-double-right'></i></span></li>";
                    }

                    // show all
                    echo "<li class='page-item'><a class='page-link fl-txt-prune' href='/live/entrylist.php?showAll=yes&search=" . urlencode($searchQuery) . "&dedupe=$dedupe'>Déplier</a></li>";

                    echo "</ul></nav>";
                } elseif ($showAll === 'yes') {
                    echo "<nav><ul class='pagination'>";
                    echo "<li class='page-item'><a class='page-link fl-txt-prune' href='/live/entrylist.php?showAll=no&search=" . urlencode($searchQuery) . "&dedupe=$dedupe'>Replier</a></li>";
                    echo "</ul></nav>";
                }
                ?>
            </div>

        </div>

    <div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="entryModalTitle">Inscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><strong>Email :</strong> <span id="m_email"></span></div>
                    <div class="mb-2"><strong>Course :</strong> <span id="m_race"></span></div>
                    <div class="mb-2"><strong>Reçu :</strong> <span id="m_received_at"></span></div>
                    <div class="mb-3"><strong>Approved :</strong> <span id="m_approved"></span></div>

                    <hr>

                    <div class="mb-3">
                        <div><strong>Approval :</strong></div>
                        <div id="m_approval" style="white-space:pre-wrap"></div>
                    </div>

                    <div class="mb-3">
                        <div><strong>Participations :</strong></div>
                        <div id="m_participations" style="white-space:pre-wrap"></div>
                    </div>

                    <div class="mb-3">
                        <div><strong>Availability :</strong></div>
                        <div id="m_availability" style="white-space:pre-wrap"></div>
                    </div>

                    <div class="mb-3">
                        <div><strong>Contribution :</strong></div>
                        <div id="m_contribution" style="white-space:pre-wrap"></div>
                    </div>

                    <div class="mb-3">
                        <div><strong>Motivation :</strong></div>
                        <div id="m_motivation" style="white-space:pre-wrap"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('click', function(e){
            const a = e.target.closest('a[data-entry]');
            if(!a) return;

            const data = JSON.parse(a.getAttribute('data-entry') || '{}');

            document.getElementById('entryModalTitle').textContent = data.name || 'Inscription';
            document.getElementById('m_email').textContent = data.email || '';
            document.getElementById('m_race').textContent = data.race || '';
            document.getElementById('m_received_at').textContent = data.received_at || '';
            document.getElementById('m_approved').textContent = data.approved || '';
            document.getElementById('m_approval').textContent = data.approval || '';
            document.getElementById('m_participations').textContent = data.participations || '';
            document.getElementById('m_availability').textContent = data.availability || '';
            document.getElementById('m_contribution').textContent = data.contribution || '';
            document.getElementById('m_motivation').textContent = data.motivation || '';
        });
    </script>
<?php include(__DIR__ . '/footer.php'); ?>
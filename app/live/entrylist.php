<?php
declare(strict_types=1);

// /live/entrylist.php

require_once __DIR__ . '/../includes/db-only.php';
require_once __DIR__ . '/../includes/helpers.php';

$link = ubbc_db_connect();

// --- params
$searchQuery = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$showAll = (isset($_GET['showAll']) && $_GET['showAll'] === 'yes') ? 'yes' : 'no';

// dédoublonnage (par défaut OUI) : dernière soumission par (email, race)
$dedupe = (isset($_GET['dedupe']) && $_GET['dedupe'] === 'no') ? 'no' : 'yes';

// pagination
$nbperpage = 25;
$cpage = (isset($_GET['page']) && (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;

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
    'index'       => '`index`',
];
$order = $allowedOrder[$_GET['order'] ?? 'received_at'] ?? 'received_at';
$asc = (isset($_GET['asc']) && in_array($_GET['asc'], ['asc','desc'], true)) ? $_GET['asc'] : 'desc';
$dasc = ($asc === 'asc') ? 'desc' : 'asc';

// --- search filter (escaped)
$searchSql = '';
if ($searchQuery !== '') {
    $q = mysqli_real_escape_string($link, $searchQuery);
    $searchSql = " AND (
        i.email LIKE '%$q%' OR
        i.lastname LIKE '%$q%' OR
        i.firstname LIKE '%$q%' OR
        i.club LIKE '%$q%' OR
        i.city LIKE '%$q%' OR
        i.race LIKE '%$q%'
    )";
}

// --- dataset (dedupe or not)
// Note: review_note = nouveau nom; fallback vers approval si pas encore migré
$selectCols = "
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
    i.participations,
    i.availability,
    i.contribution,
    i.motivation,
    i.raw_text,
    i.received_at,
    i.approved,
    i.`index`,
    i.licence,
    COALESCE(i.review_note, i.approval) AS review_note
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

// --- count
$sqlCount = "SELECT COUNT(*) AS nb $fromSql $whereSql";
$resCount = mysqli_query($link, $sqlCount);
$rowCount = $resCount ? mysqli_fetch_array($resCount, MYSQLI_ASSOC) : null;
$nbusers = (int)($rowCount['nb'] ?? 0);
if ($resCount) mysqli_free_result($resCount);

$nbpage = max(1, (int)ceil($nbusers / $nbperpage));
if ($cpage > $nbpage) $cpage = $nbpage;

// --- list query
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

// headers must be sent before any output
header('Content-Type: text/html; charset=utf-8');

include(__DIR__ . '/header.php');

// base url for pagination/sorting
$base = "/live/entrylist.php?order=" . urlencode($_GET['order'] ?? 'received_at')
    . "&asc=" . urlencode($asc)
    . "&search=" . urlencode($searchQuery)
    . "&showAll=" . urlencode($showAll)
    . "&dedupe=" . urlencode($dedupe);

?>
    <form class="live-toolbar" method="GET" action="/live/entrylist.php">
        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES); ?>" placeholder="nom, email, club, ville...">

        <input type="hidden" name="order" value="<?php echo htmlspecialchars($_GET['order'] ?? 'received_at', ENT_QUOTES); ?>">
        <input type="hidden" name="asc" value="<?php echo htmlspecialchars($asc, ENT_QUOTES); ?>">
        <input type="hidden" name="showAll" value="<?php echo htmlspecialchars($showAll, ENT_QUOTES); ?>">
        <input type="hidden" name="dedupe" value="<?php echo htmlspecialchars($dedupe, ENT_QUOTES); ?>">

        <button class="btn btn-dark btn-sm" type="submit">Search</button>
        <a class="btn btn-outline-dark btn-sm" href="/live/entrylist.php">Clear</a>

        <?php if ($dedupe === 'yes'): ?>
            <a class="btn btn-outline-dark btn-sm" href="/live/entrylist.php?dedupe=no&search=<?php echo urlencode($searchQuery); ?>">Voir doublons</a>
        <?php else: ?>
            <a class="btn btn-outline-dark btn-sm" href="/live/entrylist.php?dedupe=yes&search=<?php echo urlencode($searchQuery); ?>">Masquer doublons</a>
        <?php endif; ?>

        <?php if ($showAll === 'no'): ?>
            <a class="btn btn-outline-dark btn-sm" href="/live/entrylist.php?showAll=yes&dedupe=<?php echo urlencode($dedupe); ?>&search=<?php echo urlencode($searchQuery); ?>">Déplier</a>
        <?php else: ?>
            <a class="btn btn-outline-dark btn-sm" href="/live/entrylist.php?showAll=no&dedupe=<?php echo urlencode($dedupe); ?>&search=<?php echo urlencode($searchQuery); ?>">Replier</a>
        <?php endif; ?>
    </form>

    <div class="live-card">
        <table class="live-table">
            <thead>
            <tr>
                <th>#</th>
                <th><a href="/live/entrylist.php?order=received_at&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Reçu</a></th>
                <th><a href="/live/entrylist.php?order=lastname&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Nom</a></th>
                <th><a href="/live/entrylist.php?order=firstname&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Prénom</a></th>
                <th>Cat</th>
                <th><a href="/live/entrylist.php?order=gender&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Genre</a></th>
                <th><a href="/live/entrylist.php?order=club&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Club</a></th>
                <th><a href="/live/entrylist.php?order=city&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Ville</a></th>
                <th><a href="/live/entrylist.php?order=race&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Course</a></th>
                <th><a href="/live/entrylist.php?order=email&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Email</a></th>
                <th class="col-index"><a href="/live/entrylist.php?order=index&asc=<?php echo $dasc; ?>&search=<?php echo urlencode($searchQuery); ?>&showAll=<?php echo $showAll; ?>&dedupe=<?php echo $dedupe; ?>">Index</a></th>
            </tr>
            </thead>
            <tbody>
            <?php
            if (!$results || mysqli_num_rows($results) === 0) {
                echo "<tr><td colspan='11' style='text-align:center;padding:18px'>Pas d'inscription</td></tr>";
            } else {
                while ($r = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
                    $approvedVal = $r['approved']; // bool/int/null
                    $rowClass = ((string)$approvedVal !== '' && $approvedVal !== null && (int)$approvedVal === 0) ? 'row-refused' : '';

                    $payload = [
                        'name' => trim((string)($r['firstname'] ?? '') . ' ' . (string)($r['lastname'] ?? '')),
                        'email' => $r['email'] ?? '',
                        'race' => $r['race'] ?? '',
                        'received_at' => $r['received_at'] ?? '',
                        'approved' => $approvedVal,
                        'review_note' => $r['review_note'] ?? '',
                        'participations' => $r['participations'] ?? '',
                        'availability' => $r['availability'] ?? '',
                        'contribution' => $r['contribution'] ?? '',
                        'motivation' => $r['motivation'] ?? '',
                    ];
                    $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    $lastname = htmlspecialchars((string)($r['lastname'] ?? ''), ENT_QUOTES);
                    $firstname = htmlspecialchars((string)($r['firstname'] ?? ''), ENT_QUOTES);

                    echo "<tr class='{$rowClass}'>";
                    echo "<td>" . (int)$rowNumber++ . "</td>";
                    echo "<td>" . htmlspecialchars((string)($r['received_at'] ?? ''), ENT_QUOTES) . "</td>";

                    // Nom = déclencheur modale
                    echo "<td><a class='entry-link' href='#' data-bs-toggle='modal' data-bs-target='#entryModal' data-entry='{$dataEntry}' onclick='return false;'>{$lastname}</a></td>";

                    echo "<td>{$firstname}</td>";
                    echo "<td>" . htmlspecialchars(ubbc_category($r['birthdate'] ?? null), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($r['gender'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($r['club'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($r['city'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($r['race'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($r['email'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td class='col-index'>" . htmlspecialchars((string)($r['index'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "</tr>";
                }
            }
            if ($results) mysqli_free_result($results);
            mysqli_close($link);
            ?>
            </tbody>
        </table>
    </div>

<?php if ($nbpage > 1 && $showAll === 'no'): ?>
    <div class="live-pagination">
        <?php
        $baseNoPage = "/live/entrylist.php?order=" . urlencode($_GET['order'] ?? 'received_at')
            . "&asc=" . urlencode($asc)
            . "&search=" . urlencode($searchQuery)
            . "&showAll=no"
            . "&dedupe=" . urlencode($dedupe);

        $start = max(1, $cpage - 2);
        $end = min($nbpage, $cpage + 2);

        if ($cpage <= 3) { $start = 1; $end = min(5, $nbpage); }
        if ($cpage >= $nbpage - 2) { $start = max(1, $nbpage - 4); $end = $nbpage; }

        if ($cpage > 1) echo "<a href='{$baseNoPage}&page=1'>&laquo;</a>";

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $cpage) echo "<span class='current'>{$i}</span>";
            else echo "<a href='{$baseNoPage}&page={$i}'>{$i}</a>";
        }

        if ($cpage < $nbpage) echo "<a href='{$baseNoPage}&page={$nbpage}'>&raquo;</a>";
        ?>
    </div>
<?php endif; ?>

    <!-- Modal -->
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

                    <hr>

                    <div class="mb-3">
                        <div><strong>Comment :</strong></div>
                        <div id="m_review_note" style="white-space:pre-wrap"></div>
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

    <!-- Bootstrap JS (si pas déjà chargé dans le header) -->
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

            document.getElementById('m_review_note').textContent = data.review_note || '';
            document.getElementById('m_participations').textContent = data.participations || '';
            document.getElementById('m_availability').textContent = data.availability || '';
            document.getElementById('m_contribution').textContent = data.contribution || '';
            document.getElementById('m_motivation').textContent = data.motivation || '';
        });
    </script>

<?php include(__DIR__ . '/footer.php'); ?>
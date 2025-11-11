<?php
require_once 'includes/ubbc-functions.php';
$link = connect();

// Check if search query is set
$searchQuery = "";
if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
}

// Check if 'déplier' or 'replier' button is clicked
$showAll = isset($_GET['showAll']) ? $_GET['showAll'] : 'no';

// Calculate the total number of users based on the search query
$sqlquery = "SELECT COUNT(id) as nb FROM users WHERE edition=2025 AND id!='test' AND birthdate IS NOT null";
if ($searchQuery != "") {
    $sqlquery .= " AND (pseudo LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%' OR lastname LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%' OR firstname LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%' OR club LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%')";
}
$results = mysqli_query($link, $sqlquery);
$record = mysqli_fetch_array($results, MYSQLI_ASSOC);
$nbusers = $record['nb'];
$nbperpage = 25;
$nbpage = ceil($nbusers / $nbperpage);

if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbpage) {
    $cpage = $_GET['page'];
} else {
    $cpage = 1;
}

if (isset($_GET['order']) && in_array($_GET['order'], array('bib', 'status', 'lastname', 'gender', 'category', 'club', 'city', 'nationality', 'race'))) {
    $order = $_GET['order'];
} else {
    $order = 'bib';
}

if (isset($_GET['asc']) && in_array($_GET['asc'], array('asc', 'desc'))) {
    $asc = $_GET['asc'];
    $dasc = ($asc == 'asc') ? 'desc' : 'asc';
} else {
    $asc = 'asc';
    $dasc = 'asc';
}
?>
<?php include("ubbc-header.html"); ?>
<section class="container-fluid">
    <div class="row flex-column">
        <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">Liste des inscrit&#183;e&#183;s UBBC 2025</h1>

        <!-- Search form -->
        <form class="text-center mb-3" method="GET" action="ubbc-entrylist.php">
            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="name, pseudo or club">
            <button type="submit" class="btn  fl-bg-prune text-white"><i class="fas fa-search"></i> Search</button>
            <a href="ubbc-entrylist.php" class="btn fl-bg-peach text-white">Clear</a>
            <input type="hidden" name="showAll" value="<?php echo $showAll; ?>">
        </form>

        <table class="mx-auto table table-striped table-hover table-bordered table-sm vw-100 table-responsive-lg">
            <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
                <tr>
                    <th class="thin"><a href='ubbc-entrylist.php?order=bib&asc=<?php echo $dasc ?>'>BIB</a></th>
                    <th class="medium"><a href='ubbc-entrylist.php?order=pseudo&asc=<?php echo $dasc ?>'>Statut</a></th>
                    <th class="large"><a href='ubbc-entrylist.php?order=lastname&asc=<?php echo $dasc ?>'>Nom</a></th>
                    <th class="large"><a href='ubbc-entrylist.php?order=firstname&asc=<?php echo $dasc ?>'>Prénom</a></th>
                    <th class="thin"><a href='ubbc-entrylist.php?order=gender&asc=<?php echo $dasc ?>'>Genre</a></th>
                    <th class="thin"><a href='ubbc-entrylist.php?order=category&asc=<?php echo $dasc ?>'>Catégorie</a></th>
                    <th class="large"><a href='ubbc-entrylist.php?order=club&asc=<?php echo $dasc ?>'>Team / Club</a></th>
                    <th class="large"><a href='ubbc-entrylist.php?order=city&asc=<?php echo $dasc ?>'>Ville</a></th>
                    <th class="thin"><a href='ubbc-entrylist.php?order=nationality&asc=<?php echo $dasc ?>'>Pays</a></th>
                    <th class="medium"><a href='ubbc-entrylist.php?order=race&asc=<?php echo $dasc ?>'>Race</a></th>
                </tr>
            </thead>
<?php
// Fetch the filtered list of users based on the search query
$sqlquery = "SELECT * FROM users WHERE edition=2025";
if ($searchQuery != "") {
    $sqlquery .= " AND (pseudo LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%' OR lastname LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%' OR firstname LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%' OR club LIKE '%" . mysqli_real_escape_string($link, $searchQuery) . "%')";
}
$sqlquery .= " ORDER BY " . $order . " " . $asc . ", gender ASC, bib ASC";

if ($showAll === 'yes') {
    $sqlquery .= " LIMIT 100000"; // large number to show all
} else {
    $sqlquery .= " LIMIT " . (($cpage - 1) * $nbperpage) . ",$nbperpage";
}

$race = array();
$race[22] = 'Puebla';
$race[23] = 'Funky';
$race[24] = 'Hipster';
$race[25] = 'K2';
$results = mysqli_query($link, $sqlquery);

if (mysqli_num_rows($results) == 0) {
    echo '</table>';
    echo "<p class='m-3'>Pas de coureur inscrit</p>";
    exit;
}
echo '<tbody>';
while ($record = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
    if ($record['bib'] >200) {
        $status = '<span class="fl-txt-blood">attente</span>'; 
        $color =  'class="fl-txt-blood"';
    } else {
        $color = '';
        $status = ($record['gender'] === 'f') ? 'inscrite' : 'inscrit';}
    printf('<tr %s>',$color);
    printf('<td class="thin">%s</td>', $record["bib"]);
    printf('<td class="medium">%s</td>',$status);
    printf('<td class="large text-capitalize">%s</td>', $record["lastname"]);
    printf('<td class="large text-capitalize">%s</td>', $record["firstname"]);
    printf('<td class="thin text-uppercase">%s</td>', $record["gender"]);
    printf('<td class="thin text-uppercase">%s</td>', $record["category"]);
    printf('<td class="large text-capitalize">%s</td>', $record["club"]);
    printf('<td class="large text-capitalize">%s</td>', $record["city"]);
    printf('<td class="thin text-uppercase">%s</td>', $record["nationality"]);
    printf('<td class="medium text-capitalize">%s</td>', $race[$record["race"]]);
    printf('</tr>');
}
mysqli_free_result($results);
mysqli_close($link);
?>
            </tbody>
            <tfoot>
            </tfoot>
        </table>
        <div class="m-auto">
<?php
if ($nbpage > 1 && $showAll === 'no') {
    echo "<nav><ul class='pagination'>";

    // Icône première page
    if ($cpage > 1) {
        echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-entrylist.php?page=1&order=$order&asc=$asc&search=" . urlencode($searchQuery) . "&showAll=$showAll'><i class='fas fa-angle-double-left'></i></a></li>";
    } else {
        echo "<li class='page-item disabled'><span class='page-link'><i class='fas fa-angle-double-left'></i></span></li>";
    }

    // Détermination de la plage autour de la page courante
    $start = max(1, $cpage - 2);
    $end = min($nbpage, $cpage + 2);

    // Ajustements si trop proche du début ou de la fin
    if ($cpage <= 3) {
        $start = 1;
        $end = min(5, $nbpage);
    } elseif ($cpage >= $nbpage - 2) {
        $start = max(1, $nbpage - 4);
        $end = $nbpage;
    }

    // Ellipsis après la première page
    if ($start > 2) {
        echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
    }

    // Boucle sur la fenêtre affichée
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $cpage) {
            echo "<li class='page-item active'><span class='page-link'>$i</span></li>";
        } else {
            echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-entrylist.php?page=$i&order=$order&asc=$asc&search=" . urlencode($searchQuery) . "&showAll=$showAll'>$i</a></li>";
        }
    }

    // Ellipsis avant la dernière page
    if ($end < $nbpage - 1) {
        echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
    }

    // Icône dernière page
    if ($cpage < $nbpage) {
        echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-entrylist.php?page=$nbpage&order=$order&asc=$asc&search=" . urlencode($searchQuery) . "&showAll=$showAll'><i class='fas fa-angle-double-right'></i></a></li>";
    } else {
        echo "<li class='page-item disabled'><span class='page-link'><i class='fas fa-angle-double-right'></i></span></li>";
    }

    // Bouton "Déplier"
    echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-entrylist.php?showAll=yes&search=" . urlencode($searchQuery) . "'>Déplier</a></li>";

    echo "</ul></nav>";
} elseif ($showAll === 'yes') {
    echo "<nav><ul class='pagination'>";
    echo "<li class='page-item'><a class='page-link fl-txt-prune' href='ubbc-entrylist.php?page=$cpage&order=$order&asc=$asc&search=" . urlencode($searchQuery) . "&showAll=no'>Replier</a></li>";
    echo "</ul></nav>";
}
?>
        </div>
        <a class="mx-auto mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-newuser.php">Inscrire un&#183;e coureu&#183;r&#183;se</a>
    </div>
</section>
<?php include("ubbc-footer.html"); ?>

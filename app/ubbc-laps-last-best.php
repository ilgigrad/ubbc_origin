
<?php
require_once 'includes/ubbc-functions.php';

if (isset($_GET['order']) && in_array($_GET['order'], array('bib', 'lastname', 'firstname', 'time', 'control', 'hostname'))) {
    $order = $_GET['order'];
} else {
    $order = 'bib';
}

if (isset($_GET['page']) && isset($_GET['asc']) && in_array($_GET['asc'], array('asc', 'desc'))) {
    $asc = $_GET['asc'];
} elseif (isset($_GET['asc']) && $_GET['asc'] == 'asc') {
    $asc = 'desc';
} else {
    $asc = 'asc';
}

$link = connect();
$search = '';
$whereClause = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = mysqli_real_escape_string($link, trim($_GET['search']));
    $whereClause = "WHERE u.lastname LIKE '%$search%' OR u.firstname LIKE '%$search%' OR u.bib = '$search'";
}

$sqlquery = "SELECT l.id, u.lastname, u.firstname, u.bib, l.time, l.control, IFNULL(d.hostname, 'MANUAL')
            FROM laps l
            INNER JOIN bibs b ON l.uid = b.uid
            INNER JOIN users u ON u.bib = b.bib
            LEFT OUTER JOIN devices d ON l.device = d.id
            $whereClause
            ORDER BY $order $asc, u.bib asc";

$sqlcount = "SELECT count(distinct(s.id)) AS nb, count(distinct(s.bib)) AS nbusers
             FROM ($sqlquery) as s";

$results = mysqli_query($link, $sqlcount);
$record = mysqli_fetch_array($results, MYSQLI_ASSOC);
$nblaps = $record['nb'];
$nbusers = $record['nbusers'];
mysqli_free_result($results);
mysqli_close($link); // deconnection de mysql

$nbperpage = 25;
$nbpage = ceil($nblaps / $nbperpage);

if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbpage) {
    $cpage = $_GET['page'];
} else {
    $cpage = 1;
}
?>

<?php include("ubbc-header.html"); ?>
<section class="container-fluid">
    <div class="row flex-column">
        <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">LAPS</h1>
        <div class="row p-1 mx-0">
            <div class="row order-1 p-1 mx-0 mx-md-1">
                <div class="btn-group px-md-2 px-0">
                    <a href="ubbc-grid.php" class="btn fl-bg-electric fl-bg-hov-peach fl-txt-white px-2"><i class="fal fa-grid mx-1"></i>grid</a>
                </div>
            </div>
            <div class="row order-3 p-1 mx-0 mx-md-1">
                <form method="post" id="form_lap_id" class="" action="includes/ubbc-laps-functions.php">
                    <input type="hidden" id="id_id" value="" name="id">
                    <div class="input-group">
                        <select id="bib_id" name="bib" class="custom-select fl-w-75">
                            <?php
                            $noBibs = bibs('bibs');
                            echo "<option value='0'>select a bib</option>";
                            foreach ($noBibs as $bib => $lastname) {
                                $sbib = sprintf("%04d", $bib);
                                echo "<option value='$bib'>$sbib : $lastname</option>";
                            }
                            ?>
                        <input type="datetime-local" id="time_id" name="time" class="form-control fl-w-190 px-1" min="2025-01-01 00:00" max="2025-07-31 23:59" step="1" value="<?php
                        $date = new DateTime("now", new DateTimeZone('Europe/Paris'));
                        echo $date->format('Y-m-d\TH:i:s');
                        //echo date('Y-m-d\TH:i:s');
                        ?>">
                        <select class="custom-select fl-w-90" id="control_id" name="control">
                            <?php
                            $controls = controls();
                            foreach ($controls as $control => $label) {
                                echo "<option value='$label'>$label</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" id="submit_id" name="lapsubmit" value="add" class="btn btn fl-bg-electric fl-txt-white fl-bg-hov-sadsea my-md-0 my-2">Add</button>
                    </div>
                </form>
            </div>
            <div class="row order-3 p-1 mx-0 mx-md-1">
                <form method="get" action="ubbc-laps.php">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or bib" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn fl-bg-prune fl-txt-white fl-bg-hov-peach"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
            </div>
            <div class="p-1 mx-1 order-2">
                <div class="btn-group px-2">
                    <button type="button" class="btn fl-bd-peach fl-txt-peach fl-bg-white disabled"><i class="fal fa-undo"></i><?php echo $nblaps; ?></button>
                    <button type="button" class="btn fl-bd-peach fl-txt-peach fl-bg-white disabled"><i class="fal fa-users"></i><?php echo $nbusers; ?></button>
                </div>
            </div>
            <div class="p-1 mx-1 order-4 order-md-4">
                <div class="fl-txt-prune fl-txt-20 text-center m-0"> <?php if ($nbpage > 1) { echo 'page : ' . $cpage; } ?></div>
            </div>
        </div>
        <table class="mx-auto table table-stripped table-hover table-bordered table-sm vw-100 table-responsive-lg">
            <thead class="thead-light fl-bg-apricot fl-txt-prune fl-txt-hov-sadsea">
                <tr>
                    <?php echo "<th class='thin'>delete</th>"; ?>
                    <?php echo "<th class='thin'><a href='#' id='edit_id' onclick='modify_lap(0,0,0,0);'>edit</a></th>"; ?>
                    <?php echo "<th class='thin'><a href='ubbc-laps.php?&order=bib&asc=$asc'>BIB</a></th>"; ?>
                    <?php echo "<th class='large'><a href='ubbc-laps.php?&order=time&asc=$asc'>time</a></th>"; ?>
                    <?php echo "<th class='large'><a href='ubbc-laps.php?&order=lastname&asc=$asc'>Nom</a></th>"; ?>
                    <?php echo "<th class='large'><a href='ubbc-laps.php?&order=firstname&asc=$asc'>Pr&eacute;nom</a></th>"; ?>
                    <?php echo "<th class='large'><a href='ubbc-laps.php?&order=control&asc=$asc'>Control</a></th>"; ?>
                    <?php echo "<th class='large'><a href='ubbc-laps.php?&order=hostname&asc=$asc'>Device</a></th>"; ?>
                </tr>
            </thead>
<?php

$sqlquery .= " LIMIT " . (($cpage - 1) * $nbperpage) . ", $nbperpage";
$link = connect();
$results = mysqli_query($link, $sqlquery);

if (mysqli_num_rows($results) == 0) {
    echo '</table>';
    echo "<p class='m-3'>empty table, no lap to list</p>";
} else {
    echo '<tbody>';

    while ($record = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        printf('<tr>');
        printf('<td class="thin"><a class="fl-txt-prune fl-txt-hov-blood" href="#" onclick="delete_lap(%s);"><i class="fal fa-trash mx-2"></i> </td>', $record["id"]);
        printf('<td class="thin"><a class="fl-txt-prune fl-txt-hov-sadsea" href="#" onclick="modify_lap(%s,%s,\'%s\',\'%s\');"><i class="fas fa-pen mx-2"></i></a></td>', $record["id"], $record["bib"], $record["time"], $record["control"]);
        printf('<td class="thin">%s</td>', $record["bib"]);
        printf('<td class="large text-capitalize">%s</td>', $record["time"]);
        printf('<td class="large text-capitalize">%s</td>', $record["lastname"]);
        printf('<td class="large text-capitalize">%s</td>', $record["firstname"]);
        printf('<td class="large text-uppercase">%s</td>', $record["control"]);
        printf('<td class="large text-uppercase">%s</td>', $record["hostname"]);
        printf('</tr>');
    }
    echo "</tbody>";
    echo "<tfoot>";
    echo "</tfoot>";
    echo "</table>";
}

mysqli_free_result($results);
mysqli_close($link); // deconnection de mysql
?>
        <div class="row mx-auto px-0">
            <div class="btn-toolbar" role="toolbar">
                <div class="btn-group m-3" role="group" aria-label="First group">
                    <?php
                    for ($i = 1; $i <= $nbpage; $i++) {
                        if ($i == $cpage) {
                            echo "<a class='btn fl-txt-white fl-bg-peach'>$i</a>";
                        } else {
                            echo "<a class='btn fl-txt-peach fl-bd-peach fl-bg-white' href='ubbc-laps.php?page=$i&order=$order&asc=$asc'>$i</a>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include("ubbc-footer.html"); ?>

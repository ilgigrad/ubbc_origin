<?php
require_once 'includes/ubbc-functions.php';
?>
<?php
include("ubbc-header.html"); ?>

<section class="container-fluid mt-1">
    <div class="row flex-column">
        <div class="row order-1 justify-content-center ubbc-sticky top">
            <h1 id="runner-name" class="lead text-center fl-txt-blood fw-bold mt-2">FINISH</h1>
        </div>

        <?php
        $sqlquery = "
            SELECT u.bib, u.firstname, u.lastname, r.label as race_label
            FROM users u
            LEFT JOIN races r ON u.race = r.id
            WHERE u.edition = 2025
            ORDER BY u.bib ASC
        ";
        $link = connect();
        $results = mysqli_query($link, $sqlquery);
        $count = mysqli_num_rows($results);

        if ($count <= 50) {
            echo '<div class="row order-2 p-1 mx-auto grid-5-10">';
        } elseif ($count <= 75) {
            echo '<div class="row order-2 p-1 mx-auto grid-5-15">';
        } elseif ($count <= 100) {
            echo '<div class="row order-2 p-1 mx-auto grid-5-20">';
        } elseif ($count <= 125) {
            echo '<div class="row order-2 p-1 mx-auto grid-5-25">';
        } elseif ($count <= 150) {
            echo '<div class="row order-2 p-1 mx-auto grid-5-30">';
        } elseif ($count <= 200) {
            echo '<div class="row order-2 p-1 mx-auto grid-5-40">';
        } else {
            echo '<div class="row order-2 p-1 mx-auto grid-8-40">';
        }

        while ($record = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
            $bib = $record["bib"];
            $first = htmlspecialchars($record["firstname"]);
            $last = htmlspecialchars($record["lastname"]);
            $race = htmlspecialchars($record["race_label"]);
            printf(
                '<button id="bib-%1$s" data-bib="%1$s" data-firstname="%2$s" data-lastname="%3$s" data-race="%4$s" class="btn ml-1 mb-1 fl-bg-white fl-txt-blood fl-bd-blood fl-txt-hov-white fl-bg-hov-blood" onclick="toggleFinish(%1$s)">%1$s</button>',
                $bib, $first, $last, $race
            );
        }
        mysqli_free_result($results);
        mysqli_close($link);
        ?>
        </div>
    <div class="row order-3 justify-content-center ubbc-sticky bottom">
        <a class="mx-1 mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-live.php"><i class="fal fa-person-running mx-1"></i>live !</a>
        <a class="mx-1 mb-2 btn btn-lg fl-bg-peach fl-txt-white fl-bg-hov-sadsea" href="ubbc-laps.php"><i class="fal fa-recycle mx-1"></i>laps</a>
        <a class="mx-1 mb-2 btn btn-lg fl-bg-electric fl-txt-white fl-bg-hov-sadsea" href="ubbc-grid.php"><i class="fal fa-flag-checkered mx-1"></i>lap grid</a>
    </div>
    </div>
</section>

<script src="static/js/ubbc_grid_finish_behavior.js"></script>
<script src="static/js/sticky.js"></script>
<?php
include("ubbc-footer.html");
?>

<?php

function to_local_time($datetime_string, $format = 'Y-m-d H:i:s') {
    $utc = new DateTimeZone('UTC');
    $paris = new DateTimeZone('Europe/Paris');
    $date = new DateTime($datetime_string, $utc);
    $date->setTimezone($paris);
    return $date->format($format);
}
require_once 'includes/ubbc-functions.php';
include("ubbc-header.html");
?>
<section class="container-fluid mt-1">
    <div class="row flex-column">
        <div class="row p-1 mx-0 flex-column">
            <div class="row order-1 justify-content-center ubbc-sticky top">
                <h1 id="runner-name" class="lead text-center fw-bold pt-2">NEW LAP</h1>
            </div>
            <?php
            $link = connect();
            $sqlquery = "
                SELECT u.bib, u.firstname, u.lastname, r.label AS race
                FROM users u
                LEFT JOIN races r ON u.race = r.id
                WHERE u.edition=2025 and u.bib<=200 and u.bib>=1
                ORDER BY u.bib ASC";
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
                echo '<div class="row order-2 p-1 mx-auto grid-8-25">';
            } else {
                echo '<div class="row order-2 p-1 mx-auto grid-8-40">';
            }

            while ($record = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
                printf(
                    '<button id="bib-%s" class="btn fl-txt-gray mb-1 ml-1"
                        data-firstname="%s" data-lastname="%s" data-bib="%s" data-race="%s"
                        onclick="toggleBib(%s)">%s</button>',
                    $record['bib'],
                    htmlspecialchars($record['firstname']),
                    htmlspecialchars($record['lastname']),
                    $record['bib'],
                    htmlspecialchars($record['race']),
                    $record['bib'],
                    $record['bib']
                );
            }

            mysqli_free_result($results);
            mysqli_close($link);
            ?>
        </div>
        <div class="row order-3 justify-content-center ubbc-sticky bottom">
            <a class="mx-1 mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-live.php"><i class="fal fa-person-running mx-1"></i>live !</a>
            <a class="mx-1 mb-2 btn btn-lg fl-bg-peach fl-txt-white fl-bg-hov-r" href="ubbc-laps.php"><i class="fal fa-recycle mx-1"></i>laps</a>
            <a class="mx-1 mb-2 btn btn-lg fl-bg-blood fl-txt-white fl-bg-hov-r" href="ubbc-grid-finish.php"><i class="fal fa-flag-checkered mx-1"></i>finish</a>
        </div>
    </div>
</section>
<script src="static/js/sticky.js"></script>
<script src="static/js/ubbc_grid_behavior.js"></script>
<?php
include("ubbc-footer.html&ubbc-sticky-bottom"); ?>

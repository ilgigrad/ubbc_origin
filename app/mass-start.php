
<?php
include('ubbc-header.html');
require_once 'includes/ubbc-functions.php';
?>
<section class="container-fluid">
    <div class="row flex-column">
        <h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center m-auto">MASS START</h1>
        <div class="row justify-content-center">
            <?php
            $link = connect();
            $query = "SELECT id, label, started_at FROM races WHERE id IN (22,23,24,25) ORDER BY id DESC";
            $result = mysqli_query($link, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $id = $row['id'];
                $label = strtoupper($row['label']);
                $started = $row['started_at'] ? (new DateTime($row['started_at']))->format('H:i:s') : null;

                echo '<div class="col-12 text-center mb-3">';
                printf(
                    '<a class="btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="includes/ubbc-admin-functions.php?start=true&race=%d">%s</a><br/>',
                    $id,
                    $label
                );
                if ($started) {
                    printf('<small class="text-muted fst-italic">Départ à %s</small>', $started);
                }
                echo '</div>';
            }
            mysqli_close($link);
            ?>
        </div>
        <div class="row order-3 justify-content-center mt-4">
            <a class="mx-1 mb-2 btn btn-lg fl-bg-blood fl-txt-white fl-bg-hov-sadsea" href="ubbc-admin"><i class="fal fa-person-running mx-1"></i>admin</a>
            <a class="mx-1 mb-2 btn btn-lg fl-bg-peach fl-txt-white fl-bg-hov-sadsea" href="ubbc-grid"><i class="fal fa-recycle mx-1"></i>grid</a>
            <a class="mx-1 mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-live"><i class="fal fa-flag-checkered mx-1"></i>live</a>
        </div>
    </div>
</section>
<?php include('ubbc-footer.html'); ?>

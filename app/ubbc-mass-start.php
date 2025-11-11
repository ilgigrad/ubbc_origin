
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
                $started = $row['started_at'];
                $started_display = $started ? (new DateTime($started))->format('H:i:s') : null;

                $btnClass = $started
                    ? "fl-bg-peach fl-bg-hov-sadsea"
                    : "fl-bg-prune fl-bg-hov-sadsea";

                echo '<div class="col-12 text-center mb-3">';
                if ($started) {
                    printf(
                        '<button class="btn btn-lg %s fl-txt-white" onclick="confirmRestart(%d)">%s</button><br/>',
                        $btnClass,
                        $id,
                        $label
                    );
                    printf('<small class="text-muted fst-italic">Départ à %s</small>', $started_display);
                } else {
                    printf(
                        '<a class="btn btn-lg %s fl-txt-white" href="includes/ubbc-admin-functions.php?start=true&race=%d">%s</a><br/>',
                        $btnClass,
                        $id,
                        $label
                    );
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

<!-- Modal HTML -->
<div id="confirmModal" class="modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:1050; align-items:center; justify-content:center;">
    <div class="modal-content" style="background:white; border-radius:10px; padding:20px; max-width:400px; width:90%; box-shadow:0 0 10px rgba(0,0,0,0.3);">
        <p class="fw-bold text-center mb-3">La course a déjà commencé.<br>Voulez-vous vraiment relancer le départ ?</p>
        <div class="text-center">
            <a id="modalConfirmBtn" class="btn fl-bg-blood fl-txt-white fl-bg-hov-sadsea mx-2">Oui</a>
            <button onclick="closeModal()" class="btn fl-bg-prune fl-txt-white fl-bg-hov-sadsea mx-2">Non</button>
        </div>
    </div>
</div>

<script>
    let selectedRace = null;

    function confirmRestart(raceId) {
        selectedRace = raceId;
        const modal = document.getElementById("confirmModal");
        modal.style.display = "flex";
        modal.style.position = "fixed";
    }

    function closeModal() {
        selectedRace = null;
        document.getElementById("confirmModal").style.display = "none";
    }

    document.getElementById("modalConfirmBtn").addEventListener("click", function() {
        if (selectedRace !== null) {
            window.location.href = `includes/ubbc-admin-functions.php?start=true&race=${selectedRace}`;
        }
    });
</script>

<?php include('ubbc-footer.html'); ?>

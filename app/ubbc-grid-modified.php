<?php
require_once 'includes/ubbc-functions.php';

if (isset($_GET['bib']) && !isset($_GET['redirected'])) {
    $link = connect();
    $bib = $_GET['bib'];
    $date = new DateTime("now", new DateTimeZone('Europe/Paris'));
    $time = $date->format('Y-m-d\TH:i:s');
    $control = "START";
    $sql = "INSERT INTO laps (time, uid, atr, control) SELECT '$time', b.uid, b.atr, '$control' FROM bibs b WHERE b.bib = $bib";
    mysqli_query($link, $sql);
    $sql = "SELECT u.firstname as first, u.lastname as last FROM users u WHERE u.bib = $bib";
    $result = mysqli_query($link, $sql);
    $record = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $firstname = $record['first'];
    $lastname = $record['last'];
    mysqli_close($link);
    $url = "ubbc-grid.php?&firstname={$firstname}&lastname={$lastname}&bib={$bib}&redirected=1";
    header("Location: $url");
    exit();
}
?>

<?php include("ubbc-header.html"); ?>
<section class="container-fluid">
    <div class="row flex-column">
        <div class="row p-1 mx-0 flex-column">
            <div class="row order-1 justify-content-center flex-row">
                <?php
                if (isset($_GET['firstname']) && isset($_GET['lastname']) && isset($_GET['bib'])) {
                    $firstname = $_GET['firstname'];
                    $lastname = $_GET['lastname'];
                    $bib = $_GET['bib'];
                    printf('<h2 class="text-capitalize">[%s] %s %s</h2>', $bib, $firstname, $lastname);
                    printf('<script>document.addEventListener("DOMContentLoaded", function() { displayPopup("%s", "%s", "%s"); });</script>', $firstname, $lastname, $bib);
                }
                else {
              printf('<h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">NEW LAP</h1>');
                }
                ?>
            </div>

            <?php
            $sqlquery = "SELECT u.bib as bib FROM users u WHERE u.edition=2025 ORDER BY u.bib ASC";
            $link = connect();
            $results = mysqli_query($link, $sqlquery);
            $count = mysqli_num_rows($results);
            if ($count <= 50) {
                printf('<div class="row order-2 p-1 mx-auto grid-5-10">');
            } elseif ($count <= 75) {
                printf('<div class="row order-2 p-1 mx-auto grid-5-15">');
            } elseif ($count <= 100) {
                printf('<div class="row order-2 p-1 mx-auto grid-5-20">');
            } elseif ($count <= 125) {
                printf('<div class="row order-2 p-1 mx-auto grid-5-25">');
            } else {
                printf('<div class="row order-2 p-1 mx-auto grid-5-30">');
            }
            while ($record = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
                printf('<a id="bib-%s" class="d-block border rounded strong lead fl-bg-white fl-bd-electric fl-txt-electric fl-bd-hov-sadsea fl-bg-hov-sadsea fl-txt-hov-white m-1 p-2" href="ubbc-grid.php?&bib=%s">%s</a>', $record["bib"], $record["bib"], $record["bib"]);
            }
            mysqli_free_result($results);
            mysqli_close($link);
            ?>
        </div>
        <div class="row order-3 justify-content-center">
            <a class="mx-1 mb-2 btn btn-lg fl-bg-prune fl-txt-white fl-bg-hov-sadsea" href="ubbc-live.php"><i class="fal fa-person-running mx-1"></i>live !</a>
            <a class="mx-1 mb-2 btn btn-lg fl-bg-peach fl-txt-white fl-bg-hov-r" href="ubbc-laps.php"><i class="fal fa-recycle mx-1"></i>laps</a>
            <a class="mx-1 mb-2 btn btn-lg fl-bg-blood fl-txt-white fl-bg-hov-r" href="ubbc-grid-finish.php"><i class="fal fa-flag-checkered mx-1"></i>finish</a>
        </div>
    </div>
</section>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Scroll to the previous position if it exists
    if (sessionStorage.getItem("scrollPosition")) {
        window.scrollTo(0, sessionStorage.getItem("scrollPosition"));
        sessionStorage.removeItem("scrollPosition");
    }

    // Display popup if data is stored
    if (sessionStorage.getItem("firstname") && sessionStorage.getItem("lastname") && sessionStorage.getItem("clickedBib")) {
        displayPopup(sessionStorage.getItem("firstname"), sessionStorage.getItem("lastname"), sessionStorage.getItem("clickedBib"));
        sessionStorage.removeItem("firstname");
        sessionStorage.removeItem("lastname");
        sessionStorage.removeItem("clickedBib");
    }

    // Add click event listener to all links
    document.querySelectorAll('a[href^="ubbc-grid.php"]').forEach(function(link) {
        link.addEventListener("click", function(event) {
            sessionStorage.setItem("scrollPosition", window.scrollY);
            var bib = event.target.id.split('-')[1];
            sessionStorage.setItem("clickedBib", bib);
        });
    });
});

function displayPopup(firstname, lastname, bib) {
    var button = document.getElementById("bib-" + bib);
    var popup = document.createElement("div");
    popup.className = "popup";
    popup.innerHTML = bib + " - " + firstname + " " + lastname;
    document.body.appendChild(popup);

    var rect = button.getBoundingClientRect();
    popup.style.position = "absolute";
    popup.style.top = rect.top + window.scrollY - popup.offsetHeight + "px";
    popup.style.left = rect.left + window.scrollX + "px";

    setTimeout(function() {
        popup.remove();
    }, 1500);
}
</script>

<style>
.popup {
    background-color: #72b2c5;
    color: #fff;
    padding: 10px;
    border-radius: 5px;
    z-index: 1000;
    text-align: center;
    text-transform: capitalize;
}
</style>
<?php include("ubbc-footer.html"); ?>

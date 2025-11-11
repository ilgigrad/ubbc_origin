<?php include("ubbc-header.html"); ?>
<div class="carousel-container">
    <div id="topCarousel" class="carousel slide" data-ride="carousel" data-interval="3000">
        <div class="carousel-inner">
            <?php 
                $slides = [
                    ["img" => "static/images/ubbcbkgrd1.jpg", "title" => "PARIS", "subtitle" => "9 JUILLET 2025", "btn" => "INSCRIVEZ-VOUS"],
                    ["img" => "static/images/ubbcbkgrd2.jpg", "title" => "NUIT D'ÉTÉ", "subtitle" => "18H00 - MINUIT", "btn" => "REJOIGNEZ-NOUS"],
                    ["img" => "static/images/ubbcbkgrd3.jpg", "title" => "CÔTES & DESCENTES", "subtitle" => "AU COEUR DU PARC", "btn" => "PARTICIPEZ"],
                    ["img" => "static/images/ubbcbkgrd4.jpg", "title" => "LA BOUCLE ULTIME", "subtitle" => "AMBIANCE FESTIVE", "btn" => "S'INSCRIRE"]
                ];

                foreach ($slides as $index => $slide) {
                    echo '
                    <div class="carousel-item ' . ($index === 0 ? 'active' : '') . '">
                        <img src="' . $slide["img"] . '" class="d-block w-100" alt="' . $slide["title"] . '">
                        <div class="carousel-caption">
                            <h2 class="display-2 text-uppercase">' . $slide["title"] . '</h2>
                            <h3 class="display-4">' . $slide["subtitle"] . '</h3>
                            <a class="btn btn-lg btn-primary" href="ubbc-newuser.php">' . $slide["btn"] . '</a>
                        </div>
                    </div>';
                }
            ?>
        </div>
        <a class="carousel-control-prev" href="#topCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </a>
        <a class="carousel-control-next" href="#topCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </a>
    </div>
</div>

<main>
    <section class="jumbotron text-center">
        <h1 class="display-1">ULTRA BOUCLE DES BUTTES CHAUMONT</h1>
        <p class="lead">Combien de tours oserez-vous défier ?</p>
        <p class="lead">Prochaine édition : <strong>9 juillet 2025 à 18h</strong></p>
        <a href="ubbc-newuser.php" class="btn btn-lg btn-success">Participer</a>
    </section>

    <section class="container text-center">
        <h2 class="my-4">L'ESPRIT UBBC</h2>
        <div class="row">
            <div class="col-md-4">
                <h3>🏃‍♂️ Off 2 Ouf</h3>
                <p>Pas de dossards, pas de règles strictes, juste des amis et des kilomètres à avaler dans la bonne humeur. Qui dit mieux ?</p>
            </div>
            <div class="col-md-4">
                <h3>🌳 Buttes Chaumont 360°</h3>
                <p>Parcours emblématique, vues à couper le souffle, et... des côtes pour les amateurs de sueur. Préparez vos mollets !</p>
            </div>
            <div class="col-md-4">
                <h3>🎉 La boucle ultime</h3>
                <p>250 m de dénivelé en 5 km. Faites chauffer vos jambes et votre mental pour un défi épique. Et après, bière au ravito.</p>
            </div>
        </div>
    </section>

    <section class="container text-center my-5">
        <h2>Les épreuves</h2>
        <div class="row">
            <?php 
                $events = [
                    ["name" => "UBBC Solo", "details" => "Venez, courez, profitez. Le nombre de tours, c'est vous qui décidez."],
                    ["name" => "UBBC Puebla", "details" => "10 km, 500 m de D+. Presque une Tour Eiffel, mais sans ascenseur."],
                    ["name" => "UBBC Funky", "details" => "25 km, 1250 m de D+. Fun et (un peu) épuisant. Parfait pour un dimanche soir."],
                    ["name" => "UBBC Hipster", "details" => "40 km, 2000 m de D+. On parle de trail, mais dans Paris."],
                    ["name" => "UBBC K2", "details" => "50 km, 2500 m de D+. Si vous terminez, préparez-vous à devenir une légende."]
                ];

                foreach ($events as $event) {
                    echo '
                    <div class="col-md-4 my-3">
                        <h3>' . $event["name"] . '</h3>
                        <p>' . $event["details"] . '</p>
                    </div>';
                }
            ?>
        </div>
    </section>

    <section class="text-center bg-dark text-white py-5">
        <h2>Matériel obligatoire</h2>
        <p>Frontale, tenue adaptée, bonne humeur et envie de vous dépasser.</p>
    </section>

    <section class="container text-center my-5">
        <h2>Plans et parcours</h2>
        <p>Téléchargez les traces GPX, explorez les parcours sur OpenRunner, et préparez votre stratégie.</p>
    </section>

    <footer class="bg-dark text-white text-center py-4">
        <p>© UBBC 2025 – Le défi qui fait transpirer Paris.</p>
    </footer>
</main>
<?php include("ubbc-footer.html"); ?>
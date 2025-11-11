<?php
include("ubbc-header.html");
?>
<h1>Documentation de l'application UBBC</h1>

<p>Application de gestion de courses en boucle avec dossards, départs, passages et arrivées.</p>

<h2>Navigation rapide</h2>
<p class="d-flex flex-wrap gap-2">
    <a href="https://ubbc.fr/ubbc-grid" class="btn mr-1 fl-bg-electric fl-txt-white fl-bg-hov-prune">Grille Départ</a>
    <a href="https://ubbc.fr/ubbc-grid-finish" class="btn mr-1 fl-bg-blood fl-txt-white fl-bg-hov-peach">Grille Arrivée</a>
    <a href="https://ubbc.fr/ubbc-mass-start" class="btn mr-1 fl-bg-prune fl-txt-white fl-bg-hov-sadsea">Mass Start</a>
    <a href="https://ubbc.fr/ubbc-live" class="btn mr-1 fl-bg-sadsea fl-txt-white fl-bg-hov-blood">Suivi Live</a>
    <a href="https://ubbc.fr/ubbc-liverefresh" class="btn mr-1 fl-bg-electric fl-txt-white fl-bg-hov-apricot">Live Refresh</a>
    <a href="https://ubbc.fr/ubbc-laps" class="btn mr-1 fl-bg-apricot fl-txt-white fl-bg-hov-blood">Laps</a>
    <a href="https://ubbc.fr/ubbc-bibs" class="btn mr-1 fl-bg-peach fl-txt-white fl-bg-hov-electric">Dossards</a>
    <a href="https://ubbc.fr/ubbc-reset-laps" class="btn fl-bg-blood fl-txt-white fl-bg-hov-electric">Reset Laps</a>
</p>

<article class="mb-4">
    <h2><a href="ubbc-grid" class="fl-txt-electric fl-txt-hov-peach">ubbc-grid</a></h2>
    <p>Permet d’enregistrer les tours pour chaque coureur via une grille interactive.</p>
    <ul>
        <li>Un bouton par dossard, coloré selon l’état du dernier tour.</li>
        <li><strong>Clique</strong> : ajoute un nouveau tour.</li>
        <li><strong>Reclique sous 15 minutes</strong> : annule le tour précédent.</li>
        <li>Affichage du <strong>prénom</strong>, de la <strong>course</strong> et du <strong>dossard</strong> en haut de page.</li>
    </ul>

    <p class="fl-txt-gray fw-bold">Codes couleurs :</p>
    <ul class="list-unstyled">
        <li><span class="btn m-1 fl-w-30 fl-h-30 fl-bg-prune fl-bd-prune"></span> : Aucun tour enregistré</li>
        <li><span class="btn m-1 fl-w-30 fl-h-30 fl-bg-blood fl-bd-blood"></span> : Tour récent (&lt; 5 min) ou dernier tour est un STOP</li>
        <li><span class="btn m-1 fl-w-30 fl-h-30 fl-bg-apricot fl-bd-apricot"></span> : Tour entre 5 et 15 minutes</li>
        <li><span class="btn m-1 fl-w-30 fl-h-30 fl-bg-electric fl-bd-electric"></span> : Dernier tour &gt; 15 minutes</li>
        <li><span class="btn m-1 fl-w-30 fl-h-30 fl-bg-gray fl-bd-gray"></span> : Dernier tour annulé &lt; 5 minutes</li>
    </ul>
</article>

<article>
<h2><a href="ubbc-grid-finish">ubbc-grid-finish</a></h2>
<p>Permet de marquer un coureur comme arrivé (contrôle STOP).</p>
<ul>
  <li>Clique = enregistrement du tour avec statut STOP.</li>
  <li>Le bouton devient rouge indéfiniment (sauf annulation).</li>
</ul>
</article>

<article class="mb-4">
    <h2>
        <a href="ubbc-live" class="fl-txt-electric fl-txt-hov-peach">ubbc-live</a>
    </h2>
    <p>Affiche les tours récents en temps réel pour les spectateurs et les animateurs.</p>
    <ul>
        <li>Mise à jour automatique sans rafraîchissement de la page.</li>
        <li>Cliquer sur un numéro de dossard permet d’afficher les détails d’un coureur.</li>
        <li>Pour que les statistiques soient mises à jour, le <strong>refresher</strong> doit être lancé.</li>
        <li>Il suffit d’ouvrir <a href="ubbc-liverefresh" class="fl-txt-electric fl-txt-hov-peach">le refresher</a> dans un onglet.</li>
        <li>Pendant la course, le refresher est lancé automatiquement — inutile de l’ouvrir manuellement.</li>
    </ul>
</article>

<article>
<h2><a href="ubbc-mass-start">ubbc-mass-start</a></h2>
<p>Départ groupé pour une course.</p>
<ul>
  <li>Clique = départ + tour pour chaque coureur de la course.</li>
  <li>Si la course est déjà partie : modale de confirmation.</li>
</ul>
</article>

<article class="mb-4">
    <h2>
        <a href="ubbc-laps" class="fl-txt-electric fl-txt-hov-peach">ubbc-laps</a>
    </h2>
    <p>Affiche tous les tours enregistrés pour tous les coureurs.</p>
    <ul>
        <li>Chaque ligne affiche la date, l’heure, le dossard, la course et le statut (START ou STOP).</li>
        <li><strong>START</strong> marque le début d’un tour, <strong>STOP</strong> marque la fin de la course.</li>
        <li>Possibilité de modifier l’heure d’un tour directement depuis la table.</li>
        <li>Possibilité d’annuler un tour (état <em>annulé</em>) ou de rétablir un tour annulé.</li>
        <li>Un lien vers <a href="ubbc-reset-laps" class="fl-txt-prune fl-txt-hov-peach">reset-laps</a> permet de supprimer tous les tours (avec archivage automatique).</li>
    </ul>
</article>

<article>
<h2><a href="ubbc-bibs">ubbc-bibs</a></h2>
<p>Affiche les informations des dossards/coureurs.</p>
<ul>
  <li>Liste des inscrits avec numéro, prénom, course.</li>
    <li>association des numéros de dossard et des puces avec les coureurs</li>
</ul>
</article>

<article class="mb-5">
    <h2 class="fl-txt-prune">Tutoriel : déroulement complet d’une course avec l’application UBBC</h2>
    <ol class="fs-5">
        <li>
            <strong>Initialiser les dossards</strong><br>
            Accéder à <a href="https://ubbc.fr/ubbc-bibs" class="fl-txt-peach fl-txt-hov-electric">ubbc-bibs</a>.<br>
            Cette page ne sert pas à créer ou modifier les coureurs, mais à <strong>associer un numéro de dossard et éventuellement une puce</strong> à chaque coureur existant.<br>
            Pour attribuer automatiquement un dossard (et une puce le cas échéant) à tous les coureurs inscrits, cliquer sur le bouton <strong>Reset</strong>.<br>
            Il est également possible de modifier manuellement un dossard ou une puce ligne par ligne, puis de cliquer sur <strong>Valider</strong>.
        </li>
        <li>
            <strong>Réinitialiser tous les tours</strong><br>
            Aller sur <a href="https://ubbc.fr/ubbc-reset-laps" class="fl-txt-blood fl-txt-hov-apricot">ubbc-reset-laps</a>.<br>
            Cliquer sur <strong>Réinitialiser tous les tours</strong> pour purger les enregistrements précédents.<br>
            Une archive est automatiquement créée.
        </li>

        <li>
            <strong>Tester manuellement quelques tours</strong><br>
            Ouvrir <a href="https://ubbc.fr/ubbc-grid" class="fl-txt-electric fl-txt-hov-prune">ubbc-grid</a>.<br>
            Cliquer sur les dossards (ex : 101, 102, 103) pour enregistrer un tour. <br>
            Cliquer à nouveau sur un dossard pour annuler le tour si moins de 15 minutes se sont écoulées.<br>
            Les couleurs indiquent l’état des tours :
            <ul>
                <li><span class="btn fl-bg-prune fl-bd-prune m-1"></span> : aucun tour</li>
                <li><span class="btn fl-bg-blood fl-bd-blood m-1"></span> : dernier tour &lt; 5 min ou STOP</li>
                <li><span class="btn fl-bg-apricot fl-bd-apricot m-1"></span> : entre 5 et 15 min</li>
                <li><span class="btn fl-bg-electric fl-bd-electric m-1"></span> : &gt; 15 min</li>
                <li><span class="btn fl-bg-gray fl-bd-gray m-1"></span> : tour annulé &lt; 5 min</li>
            </ul>
        </li>

        <li>
            <strong>Lancer le départ d’une course</strong><br>
            Aller sur <a href="https://ubbc.fr/ubbc-mass-start" class="fl-txt-prune fl-txt-hov-sadsea">ubbc-mass-start</a>.<br>
            Cliquer sur le bouton de la course souhaitée (ex. <strong>PUEBLA</strong>).<br>
            Tous les coureurs de cette course reçoivent un tour START.<br>
            Si la course a déjà démarré, une confirmation s’affiche.
        </li>

        <li>
            <strong>Ajouter des tours supplémentaires</strong><br>
            Revenir sur <a href="https://ubbc.fr/ubbc-grid" class="fl-txt-electric fl-txt-hov-peach">ubbc-grid</a>.<br>
            Cliquer sur d'autres dossards (ex : 104, 105).<br>
            Annuler, modifier ou relancer un tour si nécessaire.
        </li>

        <li>
            <strong>Terminer la course</strong><br>
            Aller sur <a href="https://ubbc.fr/ubbc-grid-finish" class="fl-txt-blood fl-txt-hov-electric">ubbc-grid-finish</a>.<br>
            Cliquer sur les dossards pour enregistrer un tour avec statut <strong>STOP</strong>.<br>
            Cliquer de nouveau pour annuler l’arrivée si besoin. Le bouton redevient blanc.
        </li>

        <li>
            <strong>Activer le rafraîchissement automatique</strong><br>
            Ouvrir <a href="https://ubbc.fr/ubbc-liverefresh" class="fl-txt-electric fl-txt-hov-apricot">ubbc-liverefresh</a> dans un onglet.<br>
            Cette page met à jour les statistiques en arrière-plan pour le live.
        </li>

        <li>
            <strong>Afficher les résultats en temps réel</strong><br>
            Aller sur <a href="https://ubbc.fr/ubbc-live" class="fl-txt-sadsea fl-txt-hov-peach">ubbc-live</a>.<br>
            Les derniers tours s’affichent en temps réel.<br>
            Cliquer sur un dossard pour consulter son historique de tours.
        </li>

        <li>
            <strong>Contrôler les données</strong><br>
            Aller sur <a href="https://ubbc.fr/ubbc-laps" class="fl-txt-apricot fl-txt-hov-blood">ubbc-laps</a>.<br>
            Modifier un horaire, annuler un tour, ou filtrer par dossard.<br>
            Tri, pagination, et contrôle total sur les enregistrements.
        </li>
    </ol>
</article>

<?php include("ubbc-footer.html"); ?>

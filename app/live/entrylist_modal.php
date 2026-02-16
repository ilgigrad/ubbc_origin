<?php
// live/entrylist_modal.php
// Bootstrap 5 modal. Nécessite bootstrap.bundle.js (ou bootstrap JS + Popper) chargé dans le header.
?>

<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="entryModalTitle">Inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <div class="modal-grid">
                    <div><span class="mk">Email</span><span class="mv" id="m_email"></span></div>
                    <div><span class="mk">Birthdate</span><span class="mv" id="m_birthdate"></span></div>
                    <div><span class="mk">Source file</span><span class="mv" id="m_source"></span></div>
                    <div><span class="mk">Received at</span><span class="mv" id="m_received"></span></div>
                    <div><span class="mk">Approved</span><span class="mv" id="m_approved"></span></div>
                    <div><span class="mk">Refused</span><span class="mv" id="m_refused"></span></div>
                </div>

                <hr>

                <div class="modal-block">
                    <div class="m-title">Motivation</div>
                    <div class="m-text" id="m_motivation"></div>
                </div>

                <div class="modal-block">
                    <div class="m-title">Contribution</div>
                    <div class="m-text" id="m_contribution"></div>
                </div>

                <div class="modal-block">
                    <div class="m-title">Raw text</div>

                    <div class="m-subtitle">Availabilities (clés)</div>
                    <ul class="m-list" id="m_avail_keys"></ul>

                    <div class="m-subtitle">Participations (clés)</div>
                    <ul class="m-list" id="m_part_keys"></ul>

                    <details class="m-details">
                        <summary>Afficher le raw_text complet</summary>
                        <pre class="m-raw" id="m_raw"></pre>
                    </details>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="live-btn" data-bs-dismiss="modal">Fermer</button>
            </div>

        </div>
    </div>
</div>

<script>
    (function () {
        function byId(id){ return document.getElementById(id); }

        function setText(id, v) {
            const el = byId(id);
            if (!el) return;
            el.textContent = (v === null || v === undefined) ? '' : String(v);
        }

        function setBoolText(id, v) {
            setText(id, (v === 1 || v === true || v === "1") ? 'OUI' : 'NON');
        }

        function fillList(id, arr) {
            const ul = byId(id);
            if (!ul) return;
            ul.innerHTML = '';
            if (!arr || !arr.length) {
                const li = document.createElement('li');
                li.textContent = '—';
                ul.appendChild(li);
                return;
            }
            arr.forEach(function(k){
                const li = document.createElement('li');
                li.textContent = k;
                ul.appendChild(li);
            });
        }

        function openModal(entry) {
            setText('entryModalTitle', (entry.lastname || '') + ' ' + (entry.firstname || ''));

            setText('m_email', entry.email || '');
            setText('m_birthdate', entry.birthdate || '');
            setText('m_source', entry.source_file || '');
            setText('m_received', entry.received_at || '');
            setBoolText('m_approved', entry.approved);
            setBoolText('m_refused', entry.refused);

            setText('m_motivation', entry.motivation || '');
            setText('m_contribution', entry.contribution || '');

            fillList('m_avail_keys', entry.avail_keys || []);
            fillList('m_part_keys', entry.part_keys || []);
            setText('m_raw', entry.raw_text || '');

            const modalEl = document.getElementById('entryModal');
            if (!modalEl) return;

            if (!window.bootstrap || !bootstrap.Modal) {
                // Bootstrap JS non chargé -> pas de modal
                alert("Bootstrap JS n'est pas chargé : impossible d'ouvrir la modale.");
                return;
            }
            const m = bootstrap.Modal.getOrCreateInstance(modalEl);
            m.show();
        }

        document.addEventListener('click', function(e){
            const a = e.target.closest('.js-open-entry');
            if (!a) return;
            e.preventDefault();

            const json = a.getAttribute('data-entry');
            if (!json) return;

            try {
                const entry = JSON.parse(json);
                openModal(entry);
            } catch (err) {
                console.error('Bad modal payload', err);
            }
        });
    })();
</script>
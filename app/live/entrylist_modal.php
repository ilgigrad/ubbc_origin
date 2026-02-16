<?php
// live/entrylist_modal.php
?>

<div class="ubbc-modal" id="ubbcModal" aria-hidden="true">
    <div class="ubbc-modal__backdrop" data-close="1"></div>

    <div class="ubbc-modal__panel" role="dialog" aria-modal="true" aria-labelledby="ubbcModalTitle">
        <div class="ubbc-modal__header">
            <div class="ubbc-modal__title" id="ubbcModalTitle">Inscription</div>
            <button class="ubbc-modal__close" type="button" data-close="1">×</button>
        </div>

        <div class="ubbc-modal__body">
            <div class="modal-grid">
                <div><span class="mk">Email</span><span class="mv" id="m_email"></span></div>
                <div><span class="mk">Birthdate</span><span class="mv" id="m_birthdate"></span></div>
                <div><span class="mk">Course</span><span class="mv" id="m_race"></span></div>
                <div><span class="mk">Cat</span><span class="mv" id="m_cat"></span></div>
                <div><span class="mk">Itra</span><span class="mv" id="m_itra"></span></div>
                <div><span class="mk">Participations</span><span class="mv" id="m_parts"></span></div>
                <div><span class="mk">Availability</span><span class="mv" id="m_avail"></span></div>
                <div><span class="mk">Statut</span><span class="mv" id="m_status"></span></div>
                <div><span class="mk">Source file</span><span class="mv" id="m_source"></span></div>
                <div><span class="mk">Received at</span><span class="mv" id="m_received"></span></div>
            </div>

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
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('ubbcModal');
        const title = document.getElementById('ubbcModalTitle');

        function byId(id){ return document.getElementById(id); }
        function setText(id, v){
            const el = byId(id);
            if (!el) return;
            el.textContent = (v === null || v === undefined) ? '' : String(v);
        }
        function fillList(id, arr){
            const ul = byId(id);
            if (!ul) return;
            ul.innerHTML = '';
            if (!arr || !arr.length) {
                const li = document.createElement('li');
                li.textContent = '—';
                ul.appendChild(li);
                return;
            }
            arr.forEach(k => {
                const li = document.createElement('li');
                li.textContent = k;
                ul.appendChild(li);
            });
        }

        function statusLabel(entry){
            if (entry.refused === 1 || entry.refused === true || entry.refused === "1") return 'refused';
            if (entry.approved === 1 || entry.approved === true || entry.approved === "1") return 'approved';
            return 'pending';
        }

        function open(entry){
            const full = (entry.lastname || '') + ' ' + (entry.firstname || '');
            title.textContent = full.trim() || 'Inscription';

            setText('m_email', entry.email || '');
            setText('m_birthdate', entry.birthdate || '');
            setText('m_race', entry.race || '');
            setText('m_cat', entry.cat || '');
            setText('m_itra', (entry.itra && entry.itra > 0) ? entry.itra : '—');
            setText('m_parts', (entry.participations && entry.participations > 0) ? entry.participations : '—');
            setText('m_avail', (entry.availability === 1 || entry.availability === true || entry.availability === "1") ? 'OUI' : 'NON');
            setText('m_status', statusLabel(entry));
            setText('m_source', entry.source_file || '');
            setText('m_received', entry.received_at || '');

            setText('m_motivation', entry.motivation || '');
            setText('m_contribution', entry.contribution || '');

            fillList('m_avail_keys', entry.avail_keys || []);
            fillList('m_part_keys', entry.part_keys || []);
            setText('m_raw', entry.raw_text || '');

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function close(){
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        document.addEventListener('click', function(e){
            const opener = e.target.closest('.js-open-entry');
            if (opener) {
                e.preventDefault();
                const json = opener.getAttribute('data-entry');
                if (!json) return;
                try {
                    open(JSON.parse(json));
                } catch (err) {
                    console.error('Bad modal payload', err);
                }
                return;
            }

            if (e.target && e.target.getAttribute && e.target.getAttribute('data-close') === '1') {
                e.preventDefault();
                close();
                return;
            }
        });

        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
        });
    })();
</script>
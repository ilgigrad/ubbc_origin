<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="entryModalTitle">Inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <div class="grid-2">
                    <div><strong>Email</strong><div id="m_email" class="mono"></div></div>
                    <div><strong>Birthdate</strong><div id="m_birthdate" class="mono"></div></div>

                    <div><strong>Race</strong><div id="m_race"></div></div>
                    <div><strong>Club</strong><div id="m_club"></div></div>

                    <div><strong>City</strong><div id="m_city"></div></div>
                    <div><strong>Licence</strong><div id="m_licence" class="mono"></div></div>

                    <div><strong>Itra</strong><div id="m_itra" class="mono"></div></div>
                    <div><strong>Received at</strong><div id="m_received_at" class="mono"></div></div>
                </div>

                <hr>

                <div class="grid-2">
                    <div><strong>Approved</strong><div id="m_approved"></div></div>
                    <div><strong>Refused</strong><div id="m_refused"></div></div>
                </div>

                <div class="mt-3">
                    <strong>review_note</strong>
                    <div id="m_review_note" style="white-space:pre-wrap"></div>
                </div>

                <hr>

                <div class="mt-3">
                    <strong>Motivation</strong>
                    <div id="m_motivation" style="white-space:pre-wrap"></div>
                </div>

                <div class="mt-3">
                    <strong>Contribution</strong>
                    <div id="m_contribution" style="white-space:pre-wrap"></div>
                </div>

                <hr>

                <div class="grid-2">
                    <div>
                        <strong>Raw text → availability keys</strong>
                        <div id="m_availability_keys"></div>
                    </div>
                    <div>
                        <strong>Raw text → participations keys</strong>
                        <div id="m_participation_keys"></div>
                    </div>
                </div>

                <div class="mt-3">
                    <strong>Source file</strong>
                    <div id="m_source_file" class="mono"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function esc(s){
        return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }
    function renderList(keys){
        if(!keys || !Array.isArray(keys) || keys.length === 0) return '<span class="muted">—</span>';
        return '<ul class="mini-list">' + keys.map(k => '<li>'+esc(k)+'</li>').join('') + '</ul>';
    }
    function boolLabel(v){
        return (Number(v) === 1) ? '<span class="bool-pill on">OUI</span>' : '<span class="bool-pill off">NON</span>';
    }

    document.addEventListener('click', function(e){
        const a = e.target.closest('a[data-entry]');
        if(!a) return;

        const data = JSON.parse(a.getAttribute('data-entry') || '{}');

        document.getElementById('entryModalTitle').textContent = data.name || 'Inscription';

        document.getElementById('m_email').textContent = data.email || '';
        document.getElementById('m_birthdate').textContent = data.birthdate || '';

        document.getElementById('m_race').textContent = data.race || '';
        document.getElementById('m_club').textContent = data.club || '';
        document.getElementById('m_city').textContent = data.city || '';
        document.getElementById('m_licence').textContent = data.licence || '';
        document.getElementById('m_itra').textContent = data.itra || '';
        document.getElementById('m_received_at').textContent = data.received_at || '';

        document.getElementById('m_approved').innerHTML = boolLabel(data.approved);
        document.getElementById('m_refused').innerHTML  = boolLabel(data.refused);

        document.getElementById('m_review_note').textContent = data.review_note || '';
        document.getElementById('m_motivation').textContent = data.motivation || '';
        document.getElementById('m_contribution').textContent = data.contribution || '';

        document.getElementById('m_availability_keys').innerHTML = renderList(data.availability_keys);
        document.getElementById('m_participation_keys').innerHTML = renderList(data.participation_keys);

        document.getElementById('m_source_file').textContent = data.source_file || '';
    });
</script>
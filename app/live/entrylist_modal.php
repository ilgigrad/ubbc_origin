<?php
declare(strict_types=1);
?>
<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content entry-modal">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <span class="modal-status-dot status-pending modal-status-dot" title="status"></span>
                    <div class="modal-title">
                        <span id="m_lastname"></span> <span id="m_firstname"></span>
                        <small class="ms-2" id="m_status"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <div class="grid">
                    <div class="box">
                        <h6>Identité</h6>
                        <div class="line"><span>Email</span><b id="m_email"></b></div>
                        <div class="line"><span>Date de naissance</span><b id="m_birthdate"></b></div>
                        <div class="line"><span>Gender</span><b id="m_gender"></b></div>
                        <div class="line"><span>Cat</span><b id="m_cat"></b></div>
                    </div>

                    <div class="box">
                        <h6>Course</h6>
                        <div class="line"><span>Race</span><b id="m_race"></b></div>
                        <div class="line"><span>Itra</span><b id="m_itra"></b></div>
                        <div class="line"><span>Club</span><b id="m_club"></b></div>
                        <div class="line"><span>City</span><b id="m_city"></b></div>
                        <div class="line"><span>Licence</span><b id="m_licence"></b></div>
                    </div>

                    <div class="box">
                        <h6>UBBC</h6>
                        <div class="line"><span>Participations</span><b id="m_participations"></b></div>
                        <div class="line"><span>Availability 24-31</span><b id="m_availability"></b></div>
                        <div class="line"><span>Review note</span><b id="m_review_note"></b></div>
                    </div>

                    <div class="box">
                        <h6>Infos</h6>
                        <div class="line"><span>Source file</span><b id="m_source_file" class="mono"></b></div>
                        <div class="line"><span>Received at</span><b id="m_received_at" class="mono"></b></div>
                    </div>
                </div>

                <div class="box mt-3">
                    <h6>Motivation</h6>
                    <div class="text" id="m_motivation"></div>
                </div>

                <div class="box mt-3">
                    <h6>Contribution</h6>
                    <div class="text" id="m_contribution"></div>
                </div>

                <div class="grid mt-3">
                    <div class="box">
                        <h6>Raw text → availability (clés)</h6>
                        <ul class="keylist" id="m_avail_keys"></ul>
                    </div>
                    <div class="box">
                        <h6>Raw text → participations (clés)</h6>
                        <ul class="keylist" id="m_part_keys"></ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
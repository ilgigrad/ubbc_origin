<?php
// /live/entrylist_mobile.php
// Attend: $rows (array), $rowNumber (int), et les fonctions live_* déclarées dans entrylist.php

?>
<div class="live-mobile">
    <div class="mobile-list">
        <?php if (count($rows) === 0): ?>
            <div class="mobile-card empty">Pas d'inscription</div>
        <?php else: ?>
            <?php
            $n = $rowNumber;
            foreach ($rows as $r):
                $rowClass = live_row_class($r);
                $status = live_status($r);

                $lastname  = live_title((string)($r['lastname'] ?? ''));
                $firstname = live_title((string)($r['firstname'] ?? ''));
                $gender    = (string)($r['gender'] ?? '');
                $cat       = (string)($r['cat'] ?? '');

                $itraVal = $r['itra'];
                $itra = ($itraVal === null || (int)$itraVal === 0) ? '—' : (string)(int)$itraVal;

                $race = live_upper((string)($r['race'] ?? ''));
                $club = live_title((string)($r['club'] ?? ''));
                $city = live_title((string)($r['city'] ?? ''));

                $licence = (string)($r['licence'] ?? '');

                $participationsVal = (int)($r['participations'] ?? 0);
                $participations = ($participationsVal <= 0) ? '—' : (string)$participationsVal;

                $availability = (int)($r['availability'] ?? 0);
                $review_note  = (string)($r['review_note'] ?? '');

                $rawText = (string)($r['raw_text'] ?? '');
                $availabilityKeys = live_extract_json_keys_from_raw($rawText, [
                    "Disponibilités en juillet",
                    "Disponibilites en juillet",
                ]);
                $participationKeys = live_extract_json_keys_from_raw($rawText, [
                    "Participations UBBC",
                    "Participations",
                ]);

                $payload = [
                    'name' => trim($firstname . ' ' . $lastname),
                    'email' => (string)($r['email'] ?? ''),
                    'birthdate' => (string)($r['birthdate'] ?? ''),
                    'race' => $race,
                    'club' => $club,
                    'city' => $city,
                    'licence' => $licence,
                    'itra' => $itra,
                    'received_at' => (string)($r['received_at'] ?? ''),
                    'source_file' => (string)($r['source_file'] ?? ''),
                    'approved' => (int)($r['approved'] ?? 0),
                    'refused' => (int)($r['refused'] ?? 0),
                    'status' => $status,
                    'review_note' => $review_note,
                    'motivation' => (string)($r['motivation'] ?? ''),
                    'contribution' => (string)($r['contribution'] ?? ''),
                    'availability_keys' => $availabilityKeys,
                    'participation_keys' => $participationKeys,
                ];
                $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                ?>
                <div class="mobile-card <?php echo live_h($rowClass); ?>">
                    <div class="mobile-top">
                        <div class="mobile-name">
                            <div class="muted">#<?php echo (int)$n++; ?></div>
                            <a class="entry-link" href="#"
                               data-bs-toggle="modal" data-bs-target="#entryModal"
                               data-entry="<?php echo $dataEntry; ?>">
                               <?php echo live_h($lastname . ' ' . $firstname); ?></a>
                            <div class="mobile-dots">
                                <?php echo live_status_dot($status); ?>
                                <?php echo ($availability === 1)
                                    ? '<span class="dot dot-green" title="dispo 24–31" aria-label="dispo 24–31"></span>'
                                    : '<span class="dot dot-red" title="pas dispo 24–31" aria-label="pas dispo 24–31"></span>'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mobile-meta">
                        <div><div class="k">Gender</div><div class="v"><?php echo live_h($gender ?: '—'); ?></div></div>
                        <div><div class="k">Cat</div><div class="v"><?php echo live_h($cat ?: '—'); ?></div></div>

                        <div><div class="k">Itra</div><div class="v"><?php echo live_h($itra); ?></div></div>
                        <div><div class="k">Race</div><div class="v"><?php echo live_h($race ?: '—'); ?></div></div>

                        <div><div class="k">Club</div><div class="v"><?php echo live_h($club ?: '—'); ?></div></div>
                        <div><div class="k">City</div><div class="v"><?php echo live_h($city ?: '—'); ?></div></div>

                        <div><div class="k">Licence</div><div class="v"><?php echo live_h($licence ?: '—'); ?></div></div>
                        <div><div class="k">Participations</div><div class="v"><?php echo live_h($participations); ?></div></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
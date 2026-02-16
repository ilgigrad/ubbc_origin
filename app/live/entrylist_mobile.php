<?php
declare(strict_types=1);

/**
 * Attend:
 * - $rows (array)
 * - $rowNumber (int) (numéro de ligne de départ)
 * - fonctions déjà dispo dans entrylist.php:
 *   ubbc_h(), ubbc_status_class(), ubbc_bool_icon()
 *   + tes helpers: ubbc_category(), etc.
 *
 * Et les variables: $orderKey, $asc, $searchQuery, $showAll, $dedupe
 * + la fonction ubbc_link() définie dans entrylist.php
 */

if (!function_exists('ubbc_h')) {
    function ubbc_h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('ubbc_extract_json_keys_from_raw')) {
    function ubbc_extract_json_keys_from_raw(string $raw, array $labelCandidates): array {
        $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $raw = str_replace(["’","‘"], "'", $raw);

        foreach ($labelCandidates as $label) {
            $pattern = '/^'.preg_quote($label, '/').'\s*:\s*(.+)$/mi';
            if (!preg_match($pattern, $raw, $m)) continue;

            $value = trim($m[1]);
            $start = strpos($value, '{');
            $end   = strrpos($value, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $value = substr($value, $start, $end - $start + 1);
            }
            $json = json_decode($value, true);
            if (is_array($json)) return array_keys($json);
        }
        return [];
    }
}

$rn = (int)($rowNumber ?? 1);
?>

<?php if (empty($rows)): ?>
    <div class="live-card">
        <div class="empty">Pas d'inscription</div>
    </div>
<?php else: ?>

    <div class="mobile-list">
        <?php foreach ($rows as $r): ?>
            <?php
            $rowClass = ubbc_status_class($r);

            $lastname  = (string)($r['lastname'] ?? '');
            $firstname = (string)($r['firstname'] ?? '');
            $gender    = (string)($r['gender'] ?? '');
            $cat       = ubbc_category($r['birthdate'] ?? null);

            $itra = $r['itra'];
            $itra = ($itra === null || $itra === '' || (int)$itra === 0) ? '' : (string)(int)$itra;

            $race = strtoupper((string)($r['race'] ?? ''));
            $club = (string)($r['club'] ?? '');
            $city = (string)($r['city'] ?? '');
            $lic  = (string)($r['licence'] ?? '');

            $participations = (int)($r['participations'] ?? 0);
            $availability   = (int)($r['availability'] ?? 0);

            $approved = (int)($r['approved'] ?? 0);
            $refused  = (int)($r['refused'] ?? 0);

            $review_note = (string)($r['review_note'] ?? '');

            $rawText = (string)($r['raw_text'] ?? '');
            $availabilityKeys = ubbc_extract_json_keys_from_raw($rawText, ["Disponibilités en juillet","Disponibilites en juillet"]);
            $participationKeys = ubbc_extract_json_keys_from_raw($rawText, ["Participations UBBC","Participations"]);

            $payload = [
                'name' => trim($firstname . ' ' . $lastname),
                'email' => (string)($r['email'] ?? ''),
                'birthdate' => (string)($r['birthdate'] ?? ''),
                'race' => (string)($r['race'] ?? ''),
                'club' => (string)($r['club'] ?? ''),
                'city' => (string)($r['city'] ?? ''),
                'licence' => (string)($r['licence'] ?? ''),
                'itra' => $itra,
                'received_at' => (string)($r['received_at'] ?? ''),
                'source_file' => (string)($r['source_file'] ?? ''),
                'approved' => $approved,
                'refused' => $refused,
                'review_note' => $review_note,
                'motivation' => (string)($r['motivation'] ?? ''),
                'contribution' => (string)($r['contribution'] ?? ''),
                'availability_keys' => $availabilityKeys,
                'participation_keys' => $participationKeys,
            ];
            $dataEntry = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // affichage "vide" au lieu de 0
            $partLabel = ($participations > 0) ? (string)$participations : '—';
            $itraLabel  = ($itra !== '') ? $itra : '—';
            ?>

            <div class="mobile-card <?php echo ubbc_h($rowClass); ?>">
                <div class="mobile-head">
                    <div class="mobile-num">#<?php echo $rn++; ?></div>

                    <div class="mobile-title">
                        <a class="entry-link" href="#"
                           data-bs-toggle="modal" data-bs-target="#entryModal"
                           data-entry="<?php echo $dataEntry; ?>"
                           onclick="return false;">
                            <?php echo ubbc_h(ucwords($lastname)); ?>
                        </a>
                        <span class="sep">·</span>
                        <a class="entry-link" href="#"
                           data-bs-toggle="modal" data-bs-target="#entryModal"
                           data-entry="<?php echo $dataEntry; ?>"
                           onclick="return false;">
                            <?php echo ubbc_h(ucwords($firstname)); ?>
                        </a>
                    </div>

                    <div class="mobile-icons">
            <span class="badge-av" title="Dispo 24-31">
              <?php echo ubbc_bool_icon($availability, "dispo 24-31", "pas dispo 24-31"); ?>
            </span>
                        <span class="badge-statut" title="Statut">
              <?php
              // statut: pending prune / approved vert / refused rouge
              $stat = 'pending';
              if ($refused === 1) $stat = 'refused';
              else if ($approved === 1) $stat = 'approved';
              ?>
              <span class="status-dot status-<?php echo $stat; ?>"></span>
            </span>
                    </div>
                </div>

                <div class="mobile-body">
                    <div class="kv">
                        <div><span class="k">Gender</span><span class="v"><?php echo ubbc_h($gender ?: '—'); ?></span></div>
                        <div><span class="k">Cat</span><span class="v"><?php echo ubbc_h((string)$cat ?: '—'); ?></span></div>
                        <div><span class="k">ITRA</span><span class="v mono"><?php echo ubbc_h($itraLabel); ?></span></div>
                        <div><span class="k">Part.</span><span class="v"><?php echo ubbc_h($partLabel); ?></span></div>
                    </div>

                    <div class="kv">
                        <div><span class="k">Race</span><span class="v"><?php echo ubbc_h($race); ?></span></div>
                        <div><span class="k">Club</span><span class="v"><?php echo ubbc_h(ucwords($club)); ?></span></div>
                        <div><span class="k">City</span><span class="v"><?php echo ubbc_h(ucwords($city)); ?></span></div>
                        <div><span class="k">Licence</span><span class="v mono"><?php echo ubbc_h($lic ?: '—'); ?></span></div>
                    </div>

                    <?php if ($review_note !== ''): ?>
                        <div class="mobile-note">
                            <span class="k">Note</span>
                            <span class="v"><?php echo ubbc_h($review_note); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>
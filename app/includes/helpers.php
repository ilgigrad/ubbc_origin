<?php
declare(strict_types=1);



/**
 * Parse un "json dans un texte" (ou texte brut), et compte les participations
 * attendu : un objet JSON {"2024":"2024","2023":"2023"} etc.
 */
function ubbc_participations_count($raw): int
{
    if ($raw === null) return 0;

    $s = trim((string)$raw);
    if ($s === '') return 0;

    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // tentative JSON
    $json = json_decode($s, true);
    if (is_array($json)) {
        // object JSON => count(keys)
        return count($json);
    }

    // fallback : si c'est un texte "2023, 2024" etc.
    // on compte des années sur 4 chiffres
    if (preg_match_all('/\b(19|20)\d{2}\b/', $s, $m)) {
        return count(array_unique($m[0]));
    }

    return 0;
}

/**
 * Availability = 1 si le JSON contient la clef dispos_juillet-25-31
 */
function ubbc_available_24_31($raw): int
{
    if ($raw === null) return 0;

    $s = trim((string)$raw);
    if ($s === '') return 0;

    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // JSON object ?
    $json = json_decode($s, true);
    if (is_array($json)) {
        return array_key_exists('dispos_juillet-25-31', $json) ? 1 : 0;
    }

    // fallback string
    return (strpos($s, 'dispos_juillet-25-31') !== false) ? 1 : 0;
}

/**
 * Calcul approval à partir d'une ligne DB (inscriptions)
 *
 * Règles :
 *  - gender = F -> approved=1 (+ femme)
 *  - participations > 3 -> approved=1 (+ senator)
 *  - participations > 1 -> approved=1 (+ loyal)
 *  - itra > 650 -> approved=1 (+ index)
 *  - refused = 1 => approved=0 (quoi qu'il arrive)
 *
 * IMPORTANT: on "recalcule" la note auto, on ne concatène pas indéfiniment.
 */

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function title_case(?string $s): string {
    $s = trim((string)$s);
    if ($s === '') return '';
    $s = mb_strtolower($s, 'UTF-8');
    return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
}

function upper(?string $s): string {
    $s = trim((string)$s);
    return $s === '' ? '' : mb_strtoupper($s, 'UTF-8');
}

function dash_if_zero($v): string {
    if ($v === null) return '—';
    if ($v === '') return '—';
    $n = (int)$v;
    return ($n <= 0) ? '—' : (string)$n;
}

function season_year(?int $nowTs = null): int {
    $ts = $nowTs ?? time();
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts);
    // Entre septembre et décembre, saison = année en cours +1
    // Entre janvier et aout, saison = année en cours
    return ($m >= 9) ? ($y + 1) : $y;
}

function category_from_birthdate(?string $birthdate): string {
    if (!$birthdate) return '—';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) return '—';
    $y = (int)substr($birthdate, 0, 4);
    if ($y <= 0) return '—';

    $age = season_year() - $y;

    // Jeunes
    if ($age >= 13 && $age <= 14) return 'MI';
    if ($age >= 15 && $age <= 16) return 'CA';
    if ($age >= 17 && $age <= 18) return 'JU';
    if ($age >= 19 && $age <= 22) return 'ES';
    if ($age >= 23 && $age <= 34) return 'SE';

    // Masters
    if ($age >= 35 && $age <= 39) return 'M0';
    if ($age >= 40 && $age <= 44) return 'M1';
    if ($age >= 45 && $age <= 49) return 'M2';
    if ($age >= 50 && $age <= 54) return 'M3';
    if ($age >= 55 && $age <= 59) return 'M4';
    if ($age >= 60 && $age <= 64) return 'M5';
    if ($age >= 65 && $age <= 69) return 'M6';
    if ($age >= 70 && $age <= 74) return 'M7';
    if ($age >= 75 && $age <= 79) return 'M8';

    return '—';
}

function status_class(array $r): string {
    $refused  = (int)($r['refused'] ?? 0);
    $approved = (int)($r['approved'] ?? 0);
    if ($refused === 1) return 'row-refused';
    if ($approved === 1) return 'row-approved';
    return 'row-pending';
}

function bool_icon(int $v, string $labelTrue, string $labelFalse): string {
    if ($v === 1) {
        return '<span class="bool-icon bool-true" title="'.h($labelTrue).'" aria-label="'.h($labelTrue).'"></span>';
    }
    return '<span class="bool-icon bool-false" title="'.h($labelFalse).'" aria-label="'.h($labelFalse).'"></span>';
}

/**
 * Extraction des clés JSON depuis raw_text:
 * - on repère la ligne "Label: { ... }"
 * - decode entités HTML
 * - json_decode => keys
 */
function extract_json_keys_from_raw(string $raw, array $labelCandidates): array {
    $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $raw = str_replace(["’","‘"], "'", $raw);

    foreach ($labelCandidates as $label) {
        $pattern = '/^'.preg_quote($label, '/').'\s*:\s*(.+)$/mi';
        if (!preg_match($pattern, $raw, $m)) continue;

        $value = trim($m[1]);

        // tenter de récupérer un JSON object complet dans la valeur
        $start = strpos($value, '{');
        $end   = strrpos($value, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $value = substr($value, $start, $end - $start + 1);
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $json = json_decode($value, true);
        if (is_array($json)) return array_keys($json);
        return [];
    }

    return [];
}

function ubbc_title(?string $s): string {
    $s = trim((string)$s);
    if ($s === '') return '';
    $s = mb_strtolower($s, 'UTF-8');
    return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
}

function ubbc_upper(?string $s): string {
    $s = trim((string)$s);
    return $s === '' ? '' : mb_strtoupper($s, 'UTF-8');
}

function ubbc_dash_if_zero($v): string {
    if ($v === null) return '—';
    $n = (int)$v;
    return ($n <= 0) ? '—' : (string)$n;
}
function ubbc_compute_approval(array $row): array
{
    $refused = (int)($row['refused'] ?? 0);

    $gender = strtoupper(trim((string)($row['gender'] ?? '')));
    $participations = (int)($row['participations'] ?? 0);
    $availability = (int)($row['availability'] ?? 0); // pas utilisé dans tes règles actuelles, mais dispo
    $itra = (int)($row['itra'] ?? 0);

    $approved = 0;
    $tags = [];

    if ($gender === 'F') {
        $approved = 1;
        $tags[] = 'femme';
    }

    if ($participations > 3) {
        $approved = 1;
        $tags[] = 'senator';
    } elseif ($participations > 1) {
        $approved = 1;
        $tags[] = 'loyal';
    }

    if ($itra > 650) {
        $approved = 1;
        $tags[] = 'index';
    }

    // règle hard
    if ($refused === 1) {
        $approved = 0;
        $tags[] = 'refused';
    }

    // note auto compacte, sans doublons
    $tags = array_values(array_unique($tags));
    $reviewNote = implode(' ', $tags);

    return [
        'approved' => $approved,
        'review_note' => $reviewNote,
    ];
}
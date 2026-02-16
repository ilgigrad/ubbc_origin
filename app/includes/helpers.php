<?php
declare(strict_types=1);

/**
 * Catégorie à partir de l'année de naissance
 * Saison :
 *  - Sep → Déc : année + 1
 *  - Jan → Août : année
 */
function ubbc_category(?string $birthdate): string
{
    if (!$birthdate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        return '-';
    }

    $year = (int)substr($birthdate, 0, 4);

    $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
    $currentYear = (int)$now->format('Y');
    $month = (int)$now->format('n');

    $season = ($month >= 9) ? $currentYear + 1 : $currentYear;
    $age = $season - $year;

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

    // Jeunes / sénior
    if ($age >= 13 && $age <= 14) return 'MI';
    if ($age >= 15 && $age <= 16) return 'CA';
    if ($age >= 17 && $age <= 18) return 'JU';
    if ($age >= 19 && $age <= 22) return 'ES';
    if ($age >= 23 && $age <= 34) return 'SE';

    return '-';
}

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
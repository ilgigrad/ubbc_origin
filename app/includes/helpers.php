<?php
function ubbc_category(?string $birthdate): string

{
if (!$birthdate || !preg_match('/^\d{4}/', $birthdate)) {
return '-';
}

$year = (int)substr($birthdate, 0, 4);

$now = new DateTime();
$currentYear = (int)$now->format('Y');
$month = (int)$now->format('n');

// Saison
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

function ubbc_available_24_31(?string $rawValue): int
{
    if (!$rawValue) return 0;

    $v = html_entity_decode($rawValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // tente JSON
    $json = json_decode($v, true);
    if (is_array($json)) {
        return array_key_exists('dispos_juillet-25-31', $json) ? 1 : 0;
    }

    // fallback brut
    return (strpos($v, 'dispos_juillet-25-31') !== false) ? 1 : 0;
}

function ubbc_participations_count(?string $rawValue): int
{
    if (!$rawValue) return 0;

    $v = trim(html_entity_decode($rawValue, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($v === '') return 0;

    // JSON object/array ?
    $json = json_decode($v, true);
    if (is_array($json)) {
        return count($json);
    }

    // fallback "2022, 2023" ou "2022;2023" etc.
    $parts = preg_split('/[,\n;|]+/', $v);
    $parts = array_values(array_filter(array_map('trim', $parts), fn($x) => $x !== ''));
    return count($parts);
}

declare(strict_types=1);

function ubbc_compute_approval(array $row): array
{
    $refused = (int)($row['refused'] ?? 0);
    $approved = (int)($row['approved'] ?? 0);
    $note = trim((string)($row['review_note'] ?? ''));

    // Refused bloque tout
    if ($refused === 1) {
        return [
            'approved' => 0,
            'review_note' => $note
        ];
    }

    $gender = strtoupper(trim((string)($row['gender'] ?? '')));
    $participations = (int)($row['participations'] ?? 0);
    $itra = (int)($row['itra'] ?? 0);

    $add = function(string $tag) use (&$note) {
        if (stripos($note, $tag) !== false) {
            return;
        }
        $note = ($note === '') ? $tag : $note . ' / ' . $tag;
    };

    if ($gender === 'F') {
        $approved = 1;
        $add('femme');
    }

    if ($participations > 3) {
        $approved = 1;
        $add('senator');
    }

    if ($participations > 1) {
        $approved = 1;
        $add('loyal');
    }

    if ($itra > 650) {
        $approved = 1;
        $add('itra');
    }

    return [
        'approved' => $approved,
        'review_note' => $note
    ];


?>
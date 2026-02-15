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


?>
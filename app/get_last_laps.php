<?php
require_once 'includes/ubbc-functions.php';

header('Content-Type: application/json');

$link = connect();

// Requête pour obtenir le dernier tour par bib (START ou STOP non annulé ou annulé mais dernier)
$sql = "
    SELECT b.bib, l.time, l.is_canceled, l.control
    FROM laps l
    JOIN bibs b ON l.uid = b.uid
    JOIN (
        SELECT l2.uid, MAX(l2.time) AS max_time
        FROM laps l2
        GROUP BY l2.uid
    ) last_laps ON last_laps.uid = l.uid AND last_laps.max_time = l.time
";

$result = mysqli_query($link, $sql);

$laps = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Convertir le timestamp de Europe/Paris vers UTC
    $dt = new DateTime($row['time'], new DateTimeZone('Europe/Paris'));
    $dt->setTimezone(new DateTimeZone('UTC'));
    $timestampUtc = $dt->getTimestamp() * 1000;

    $laps[] = [
        'bib' => $row['bib'],
        'timestamp' => $timestampUtc,
        'canceled' => (bool)$row['is_canceled'],
        'control' => $row['control']
    ];
}

mysqli_free_result($result);
mysqli_close($link);

echo json_encode($laps);
?>

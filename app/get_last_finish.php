<?php
require_once 'includes/ubbc-functions.php';

$link = connect();
header("Content-Type: application/json");

$sql = "
    SELECT u.bib, l.time, l.is_canceled
    FROM laps l
    INNER JOIN bibs b ON l.uid = b.uid
    INNER JOIN users u ON b.bib = u.bib
    WHERE l.control = 'STOP'
      AND l.time = (
        SELECT MAX(time)
        FROM laps l2
        WHERE l2.uid = l.uid AND l2.control = 'STOP'
      )
";

$result = mysqli_query($link, $sql);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Conversion explicite Europe/Paris → UTC
    $dt = new DateTime($row['time'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Europe/Paris'));
    $utcTimestamp = $dt->getTimestamp() * 1000;

    $data[] = [
        "bib" => $row["bib"],
        "timestamp" => $utcTimestamp,
        "is_canceled" => (bool)$row["is_canceled"]
    ];
}

echo json_encode($data);
mysqli_free_result($result);
mysqli_close($link);
?>
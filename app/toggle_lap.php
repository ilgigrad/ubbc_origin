<?php
require_once 'includes/ubbc-functions.php';
$mysqli = connect();
mysqli_query($mysqli, "SET time_zone = '+02:00'");

$bib = intval($_GET['bib']);
$response = [];

$bibRes = $mysqli->query("
    SELECT b.uid, u.firstname, u.lastname, r.label AS race
    FROM bibs b
    JOIN users u ON b.bib = u.bib
    JOIN races r ON u.race = r.id
    WHERE b.bib = $bib
    LIMIT 1
");

if (!$bibRes || $bibRes->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Bib not found']);
    exit;
}

$bibData = $bibRes->fetch_assoc();
$uid = $bibData['uid'];
$firstname = $bibData['firstname'];
$lastname = $bibData['lastname'];
$race = $bibData['race'];

$latestLap = $mysqli->query("
    SELECT id, time, is_canceled, control
    FROM laps
    WHERE uid = '$uid'
    ORDER BY time DESC
    LIMIT 1
");

$now = (new DateTime("now", new DateTimeZone("Europe/Paris")))->format("Y-m-d H:i:s");

if ($latestLap && $latestLap->num_rows > 0) {
    $row = $latestLap->fetch_assoc();
    $lastTime = strtotime($row['time']);
    $nowTime = strtotime($now);

    $delta = $nowTime - $lastTime;

    // Si le dernier tour est < 15 min et non annulé, on annule
    if ($delta < 15 * 60 && !$row['is_canceled']) {
        $lapId = intval($row['id']);
        $mysqli->query("UPDATE laps SET is_canceled = TRUE WHERE id = $lapId");
        echo json_encode([
            "action" => "removed",
            "bib" => $bib,
            "firstname" => $firstname,
            "lastname" => $lastname,
            "race" => $race,
            "control" => $row['control']
        ]);
        exit;
    }
}

// Sinon, on crée un nouveau tour
$control = 'START';
$stmt = $mysqli->prepare("INSERT INTO laps (uid, time, control, is_canceled) VALUES (?, ?, ?, FALSE)");
$stmt->bind_param("sss", $uid, $now, $control);
$stmt->execute();

echo json_encode([
    "action" => "added",
    "bib" => $bib,
    "firstname" => $firstname,
    "lastname" => $lastname,
    "race" => $race,
    "control" => $control
]);

$stmt->close();
$mysqli->close();
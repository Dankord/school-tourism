<?php

require_once __DIR__.'/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    exit("forbidden");
}

$destination = trim($_POST['destination-name'] ?? '');
$duration = $_POST['trip-duration'] ?? '';
$type = $_POST['trip-type'] ?? '';
$satisfaction = $_POST['satisfaction'] ?? '';
$understanding = $_POST['understanding'] ?? '';
$maintenance = $_POST['maintenance'] ?? '';
$recommendation = $_POST['recommendation'] ?? '';
$suggestion = $_POST['suggestion'] ?? '';

if (!$destination || !$duration || !$type || !$satisfaction || !$understanding || !$maintenance || !$recommendation) {
    die("Missing require fields");
}

$submissionCode = generateSubmissionCode();

function generateSubmissionCode($prefix = 'GR1') {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
}

$insert = $connection->prepare("
    INSERT INTO responses
    (submission_id, destination_name, trip_duration, trip_type, satisfaction, understanding, maintenance, recommendation, suggestion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insert->bind_param(
    "sssssssss",
    $submissionCode,
    $destination,
    $duration,
    $type,
    $satisfaction,
    $understanding,
    $maintenance,
    $recommendation,
    $suggestion
);

if (!$insert->execute()) {
    die("Insert failed: " . $insert->error);
}

// echo "Success in POST";
header("Location: form-success.html?code=" . urlencode($submissionCode));
exit;

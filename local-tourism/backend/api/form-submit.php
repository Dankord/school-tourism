<?php

require_once __DIR__.'/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
    ]);
    exit;
}

$destination = trim($_POST['destination-name'] ?? '');
$province = $_POST['province'] ?? '';
$date = $_POST['trip-date'] ?? '';
$type = $_POST['trip-type'] ?? '';
$satisfaction = $_POST['satisfaction'] ?? '';
$understanding = $_POST['understanding'] ?? '';
$maintenance = $_POST['maintenance'] ?? '';
$transportation = $_POST['transportation'] ?? '';
$affordable = $_POST['affordable'] ?? '';
$facilities = $_POST['facilities'] ?? '';
$recommendation = $_POST['recommendation'] ?? '';
$issues = $_POST['issues'] ?? '';
$suggestion = $_POST['suggestion'] ?? '';

if (!$destination || !$province || !$date || !$type || !$satisfaction || !$understanding || !$maintenance || !$transportation || !$affordable || !$facilities || !$recommendation) {
    die("Missing require fields");
}

$submissionCode = generateSubmissionCode();

function generateSubmissionCode($prefix = 'GR1') {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
}

$insert = $connection->prepare("
    INSERT INTO responses
    (submission_id, destination_name, province, trip_date, trip_type, satisfaction, understanding, maintenance, transportation, affordable, facilities, recommendation, issues, suggestion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insert->bind_param(
    "ssssssssssssss",
    $submissionCode,
    $destination,
    $province,
    $date,
    $type,
    $satisfaction,
    $understanding,
    $maintenance,
    $transportation,
    $affordable,
    $facilities,
    $recommendation,
    $issues,
    $suggestion
);

if (!$insert->execute()) {
    die("Insert failed: " . $insert->error);
}

// echo "Success in POST";
header("Location: ../../frontend/pages/form-success.html?code=" . urlencode($submissionCode));
exit;

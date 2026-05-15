<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/../config/config.php';

const SURVEY_FORM_PATH = '../../frontend/pages/survey-form.html';
const RATE_LIMIT_MIN_INTERVAL = 300;
const RATE_LIMIT_MAX_PER_HOUR = 3;
const SUBMISSION_MIN_SECONDS = 5;

function redirectWithError(string $code): void {
    header('Location: ' . SURVEY_FORM_PATH . '?error=' . urlencode($code));
    exit;
}

function generateSubmissionCode($prefix = 'GR1') {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function clientIpAddress(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    }
    return $ip;
}

function rateLimitFile(string $ip): string {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . '/rate-' . hash('sha256', $ip) . '.json';
}

function loadRateEntries(string $file, int $now): array {
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    $decoded = $raw ? json_decode($raw, true) : null;
    if (!is_array($decoded)) return [];
    return array_values(array_filter($decoded, fn($ts) => is_int($ts) && ($now - $ts) < 3600));
}

function checkRateLimit(string $ip): void {
    $now = time();
    $file = rateLimitFile($ip);
    $entries = loadRateEntries($file, $now);
    error_log(sprintf('[survey] checkRateLimit ip=%s file=%s entries=%d exists=%d writable=%d',
        $ip, $file, count($entries), is_file($file) ? 1 : 0, is_writable(dirname($file)) ? 1 : 0));
    if (!empty($entries) && ($now - max($entries)) < RATE_LIMIT_MIN_INTERVAL) {
        redirectWithError('rate_fast');
    }
    if (count($entries) >= RATE_LIMIT_MAX_PER_HOUR) {
        redirectWithError('rate_hour');
    }
}

function recordRateLimit(string $ip): void {
    $file = rateLimitFile($ip);
    $now = time();
    $entries = loadRateEntries($file, $now);
    $entries[] = $now;
    $bytes = @file_put_contents($file, json_encode($entries), LOCK_EX);
    if ($bytes === false) {
        $err = error_get_last();
        error_log('[survey] recordRateLimit WRITE FAILED file=' . $file . ' err=' . ($err['message'] ?? 'unknown'));
    } else {
        error_log(sprintf('[survey] recordRateLimit ip=%s entries=%d bytes=%d', $ip, count($entries), $bytes));
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
    ]);
    exit;
}

if (!empty(trim((string) ($_POST['website'] ?? '')))) {
    redirectWithError('spam');
}

$formLoadedAt = (int) ($_POST['form_loaded_at'] ?? 0);
if ($formLoadedAt > 0 && (time() - intdiv($formLoadedAt, 1000)) < SUBMISSION_MIN_SECONDS) {
    redirectWithError('too_fast');
}

$clientIp = clientIpAddress();
checkRateLimit($clientIp);

$captchaInput = trim((string) ($_POST['captcha_answer'] ?? ''));
$captchaExpected = isset($_SESSION['captcha_answer']) ? (string) $_SESSION['captcha_answer'] : '';
error_log(sprintf('[survey] captcha session_id=%s expected=%s input=%s session_keys=%s',
    session_id() ?: '(none)',
    $captchaExpected === '' ? '(empty)' : $captchaExpected,
    $captchaInput === '' ? '(empty)' : $captchaInput,
    implode(',', array_keys($_SESSION))
));
if ($captchaExpected === '' || $captchaInput === '' || $captchaInput !== $captchaExpected) {
    error_log('[survey] captcha REJECTED');
    unset($_SESSION['captcha_answer'], $_SESSION['captcha_issued_at']);
    redirectWithError('captcha');
}
error_log('[survey] captcha PASSED');
unset($_SESSION['captcha_answer'], $_SESSION['captcha_issued_at']);

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
    redirectWithError('missing');
}

$submissionCode = generateSubmissionCode();

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
    redirectWithError('server');
}

recordRateLimit($clientIp);

header("Location: ../../frontend/pages/form-success.html?code=" . urlencode($submissionCode));
exit;

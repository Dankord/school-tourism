<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
    ]);
    exit;
}

$a = random_int(1, 9);
$b = random_int(1, 9);
$op = random_int(0, 2);

switch ($op) {
    case 0:
        $answer = $a + $b;
        $question = "$a + $b";
        break;
    case 1:
        if ($a < $b) { [$a, $b] = [$b, $a]; }
        $answer = $a - $b;
        $question = "$a - $b";
        break;
    default:
        $answer = $a * $b;
        $question = "$a x $b";
        break;
}

$_SESSION['captcha_answer'] = (string) $answer;
$_SESSION['captcha_issued_at'] = time();

error_log(sprintf('[survey] captcha ISSUED session_id=%s question=%s answer=%s',
    session_id() ?: '(none)', $question, (string) $answer));

echo json_encode([
    'success' => true,
    'question' => $question . ' = ?',
]);

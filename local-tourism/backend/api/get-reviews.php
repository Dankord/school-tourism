<?php

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
    ]);
    exit;
}

// total responses
$totalRespondentQuery = "
    SELECT
        COUNT(*) AS total_responses
    FROM responses
";

// responses grouped by province for progress bbaar
$provinceSummaryQuery = "
    SELECT
        LOWER(TRIM(province)) AS province,
        COUNT(*) AS total_responses
    FROM responses
    GROUP BY province
    ORDER BY total_responses DESC
";

// most answered destinations
$topDestinationQuery = "
    SELECT
        destination_name,
        COUNT(*) AS total_responses,
        ROUND(AVG(CAST(satisfaction AS DECIMAL(10,2))), 2) AS average_satisfaction
    FROM responses
    GROUP BY destination_name
    ORDER BY total_responses DESC, average_satisfaction DESC
    LIMIT 6
";

$totalRespondentResult = $connection->query($totalRespondentQuery);
$provinceSummaryResult = $connection->query($provinceSummaryQuery);
$topDestinationResult = $connection->query($topDestinationQuery);

if (!$totalRespondentResult || !$provinceSummaryResult || !$topDestinationResult) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch responses',
    ]);
    exit;
}

$summaryRow = $totalRespondentResult->fetch_assoc();
$totalResponses = (int) ($summaryRow['total_responses'] ?? 0);

$provinceDefaults = [
    'luzon' => 0,
    'visayas' => 0,
    'mindanao' => 0,
    'others' => 0,
];

$provinceRows = [];
while ($row = $provinceSummaryResult->fetch_assoc()) {
    $provinceName = trim((string) ($row['province'] ?? ''));
    $count = (int) ($row['total_responses'] ?? 0);
    if (isset($provinceDefaults[$provinceName])) {
        $provinceDefaults[$provinceName] += $count;
    } else {
        $provinceDefaults['others'] += $count;
    }
    $provinceRows[] = [
        'province' => $provinceName,
        'respondents' => $count,
    ];
}

$provinceProgress = [];
foreach ($provinceDefaults as $province => $respondents) {
    $percentage = $totalResponses > 0 ? round(($respondents / $totalResponses) * 100, 2) : 0;
    $provinceProgress[] = [
        'province' => $province,
        'respondents' => $respondents,
        'percentage' => $percentage,
    ];
}

$topDestinations = [];
while ($row = $topDestinationResult->fetch_assoc()) {
    $topDestinations[] = [
        'destination_name' => $row['destination_name'],
    ];
}

echo json_encode([
    'success' => true,
    'summary' => [
        'total_responses' => $totalResponses,
    ],
    'top_destinations' => $topDestinations,
    'province_progress' => $provinceProgress,
    'province_raw' => $provinceRows,
]);

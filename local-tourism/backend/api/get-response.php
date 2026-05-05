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
// get all the total
$totalRespondent = "
    SELECT
        COUNT(*) AS total_responses
    FROM responses
";

$fetchDestinations = "
    SELECT
        destination_name,
        COUNT(*) AS total_students,
        SUM(CASE WHEN LOWER(recommendation) = 'yes' THEN 1 ELSE 0 END) AS recommend_yes_count,
        AVG(CAST(satisfaction AS DECIMAL(10,2))) AS average_satisfaction,
        AVG(CAST(maintenance AS DECIMAL(10,2))) AS average_maintenance,
        AVG(CAST(understanding AS DECIMAL(10,2))) AS average_understanding
    FROM responses
    GROUP BY destination_name
    ORDER BY total_students DESC, destination_name ASC
";

// set the data 
$totalRespondent = $connection->query($totalRespondent);
$destinationResult = $connection->query($fetchDestinations);

// catcher if this fail, all fail, help me.
if (!$totalRespondent || !$destinationResult) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch responses',
    ]);
    exit;
}

$summaryRow = $totalRespondent->fetch_assoc();
$totalResponses = (int) ($summaryRow['total_responses'] ?? 0);

$destinations = [];

while ($row = $destinationResult->fetch_assoc()) {
    $students = (int) $row['total_students'];
    $recommendYes = (int) $row['recommend_yes_count'];
    $recommendationPercent = $students > 0
        ? round(($recommendYes / $students) * 100, 2)
        : 0;
    $averageSatisfaction = (float) ($row['average_satisfaction'] ?? 0);
    $averageMaintenance = (float) ($row['average_maintenance'] ?? 0);
    $averageUnderstanding = (float) ($row['average_understanding'] ?? 0);

    $destinations[] = [
        'name' => $row['destination_name'],
        'students' => $students,
        'recommendation_percent' => $recommendationPercent,
        'average_satisfaction' => round($averageSatisfaction, 2),
        'average_maintenance' => round($averageMaintenance, 2),
        'average_understanding' => round($averageUnderstanding, 2),
    ];
}

echo json_encode([
    'success' => true,
    'summary' => [
        'total_responses' => $totalResponses,
    ],
    'destinations' => $destinations,
]);


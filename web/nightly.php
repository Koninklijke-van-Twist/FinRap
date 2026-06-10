<?php

set_time_limit(18000);
ini_set('max_execution_time', '18000');
ignore_user_abort(true);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';
require_once __DIR__ . '/odata.php';
require_once __DIR__ . '/finrap_data.php';
header('Content-Type: application/json; charset=utf-8');

$startedAt = hrtime(true);
$results = finrap_run_nightly_reports();
finrap_refresh_report_indexes_for_nightly_results($results);
$projects = [];

foreach ($results as $result) {
    if (!is_array($result) || !($result['ok'] ?? false)) {
        continue;
    }

    $projects[] = [
        'company' => (string) ($result['company'] ?? ''),
        'project_no' => (string) ($result['project_no'] ?? ''),
        'report_id' => (string) ($result['report_id'] ?? ''),
        'duration_ms' => (int) ($result['duration_ms'] ?? 0),
    ];
}

$errors = [];
foreach ($results as $result) {
    if (!is_array($result) || ($result['ok'] ?? false)) {
        continue;
    }

    $errors[] = [
        'company' => (string) ($result['company'] ?? ''),
        'project_no' => (string) ($result['project_no'] ?? ''),
        'duration_ms' => (int) ($result['duration_ms'] ?? 0),
        'error' => (string) ($result['error'] ?? ''),
    ];
}

echo json_encode([
    'ok' => true,
    'generated_at' => gmdate('c'),
    'total_duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
    'projects' => $projects,
    'errors' => $errors,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

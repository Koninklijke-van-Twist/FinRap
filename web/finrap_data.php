<?php

/**
 * Includes/requires
 */
require_once __DIR__ . '/finance_calculations.php';
require_once __DIR__ . '/project_finance.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * Constants
 */
const FINRAP_FORMATTED_TASK_NOS_ONLY = true;
const FINRAP_GLOBAL_TOTAL_TASK_NO = '000-000-000';
const FINRAP_PROJECT_ROOT_TOTAL_TASK_CODE = '000';

const FINRAP_BUDGET_HOURS_ENTITY_SET = 'JobBaselineLines';
const FINRAP_BUDGET_HOURS_FIELD = 'Quantity';
const FINRAP_BUDGET_HOURS_FILTER_BASELINE_FIELD = 'Baseline_Version_in_Filter';
const FINRAP_BUDGET_COST_FIELD = 'Total_Cost';
const FINRAP_BUDGET_REVENUE_FIELD = 'Total_Price_LCY';
const FINRAP_BUDGET_REVENUE_TYPE = 'GB-rekening';
const FINRAP_BUDGET_REVENUE_NO = '800000';
const FINRAP_PROJECT_TASK_ENTITY_SET = 'ProjectTaken';
const FINRAP_PROJECT_TASK_ENTITY_SET_FALLBACK = 'ProjectenJobTaskLines';
const FINRAP_PROJECT_TASK_CONTRACT_FIELD = 'LVS_Contract_Total_Price_2';
const FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD = 'LVS_Job_Change_Order_No';
const FINRAP_PROJECT_TASK_BASELINE_COST_FIELD = 'LVS_Baseline_Total_Cost';
const FINRAP_PROJECT_TASK_PURCHASES_FIELD = 'LVS_Registered_Purchases_Amt';
const FINRAP_PROJECT_TASK_INVOICED_PRICE_FIELD = 'Contract_Invoiced_Price';
const FINRAP_BUDGET_HOURS_FILTER_TYPE_FIELD = 'Type';
const FINRAP_BUDGET_HOURS_FILTER_RESOURCE_TYPE_FIELD = 'Resource_Type';
const FINRAP_BUDGET_HOURS_FILTER_UOM_FIELD = 'Unit_of_Measure_Code';
const FINRAP_BUDGET_HOURS_FILTER_TYPE_VALUE = 'Resource';
const FINRAP_BUDGET_HOURS_FILTER_RESOURCE_TYPE_VALUE = 'Person';
const FINRAP_BUDGET_HOURS_FILTER_UOM_VALUE = 'HR';

const FINRAP_ESTIMATED_HOURS_ENTITY_SET = '';
const FINRAP_ESTIMATED_HOURS_FIELD = '';
const FINRAP_REPORT_LIST_PAGE_SIZE = 25;
const FINRAP_REPORT_COMMENT_MAX_LENGTH = 2000;

/**
 * Functies
 */
function finrap_write_json_file(string $path, array $payload, int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): bool
{
    $json = json_encode($payload, $jsonFlags);
    if (!is_string($json)) {
        return false;
    }

    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
        return false;
    }

    $tmpPath = $path . '.tmp';
    if (file_put_contents($tmpPath, $json, LOCK_EX) === false) {
        @unlink($tmpPath);

        return false;
    }

    if (!@rename($tmpPath, $path)) {
        @unlink($tmpPath);

        return false;
    }

    return true;
}

function finrap_cache_dir(): string
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'finrap';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function finrap_normalize_company(string $company): string
{
    return preg_replace('/[^a-z0-9_-]/i', '_', strtolower(trim($company)));
}

function finrap_normalize_project_no(string $projectNo): string
{
    return strtolower(trim($projectNo));
}

function finrap_cache_path(string $company, string $projectNo, string $yearMonth): string
{
    $safeCompany = finrap_normalize_company($company);
    $safeProject = preg_replace('/[^a-z0-9_-]/i', '_', finrap_normalize_project_no($projectNo));
    $safeMonth = preg_replace('/[^0-9-]/', '', trim($yearMonth));

    return finrap_cache_dir() . DIRECTORY_SEPARATOR . $safeCompany . '_' . $safeProject . '_' . $safeMonth . '.json';
}

function finrap_generate_report_id(): string
{
    try {
        return gmdate('Ymd_His') . '_' . bin2hex(random_bytes(3));
    } catch (Throwable $ignoredRandomError) {
        return gmdate('Ymd_His') . '_' . substr(uniqid('', true), -6);
    }
}

function finrap_email_local_part(string $email): string
{
    $email = strtolower(trim($email));
    if ($email === '') {
        return '';
    }

    $atPos = strpos($email, '@');

    return $atPos === false ? $email : substr($email, 0, $atPos);
}

function finrap_report_cache_path(string $company, string $projectNo, string $reportId): string
{
    $safeCompany = finrap_normalize_company($company);
    $safeProject = preg_replace('/[^a-z0-9_-]/i', '_', finrap_normalize_project_no($projectNo));
    $safeReportId = preg_replace('/[^a-z0-9_-]/i', '', strtolower(trim($reportId)));

    return finrap_cache_dir() . DIRECTORY_SEPARATOR . $safeCompany . '_' . $safeProject . '_ts_' . $safeReportId . '.json';
}

function finrap_report_snapshot_file_prefix(string $company, string $projectNo): string
{
    $safeCompany = finrap_normalize_company($company);
    $safeProject = preg_replace('/[^a-z0-9_-]/i', '_', finrap_normalize_project_no($projectNo));

    return $safeCompany . '_' . $safeProject . '_ts_';
}

function finrap_report_index_path(string $company, string $projectNo): string
{
    $safeCompany = finrap_normalize_company($company);
    $safeProject = preg_replace('/[^a-z0-9_-]/i', '_', finrap_normalize_project_no($projectNo));

    return finrap_cache_dir() . DIRECTORY_SEPARATOR . $safeCompany . '_' . $safeProject . '_reports-index.json';
}

function finrap_report_index_entry_from_payload(string $company, string $projectNo, string $reportId, array $payload): array
{
    $modal = is_array($payload['project_modal'] ?? null) ? $payload['project_modal'] : [];
    $taskRows = is_array($modal['task_rows'] ?? null) ? $modal['task_rows'] : [];
    $overrides = finrap_load_report_overrides($company, $projectNo, $reportId);
    $eacOverrides = is_array($overrides['eac_by_task'] ?? null) ? $overrides['eac_by_task'] : [];
    $taskRows = finrap_apply_eac_overrides_to_task_rows($taskRows, $eacOverrides);
    $summaryTotals = finrap_get_report_summary_totals($taskRows);
    $bookedCost = finance_to_float($summaryTotals['Booked_Cost'] ?? 0.0);
    $budgetCost = finance_to_float($summaryTotals['Budget_Cost'] ?? 0.0);
    $eacTotal = finance_to_float($summaryTotals['EAC'] ?? 0.0);

    return [
        'report_id' => $reportId,
        'fetched_at' => (string) ($payload['fetched_at'] ?? ''),
        'auto_report' => finrap_is_auto_report($payload),
        'created_by' => finrap_email_local_part((string) ($payload['created_by_email'] ?? '')),
        'installments_received' => round(finance_to_float($modal['installments_received'] ?? 0.0), 2),
        'poc_baseline' => round(finrap_calculate_poc_percent($bookedCost, $budgetCost), 2),
        'poc_eac' => round(finrap_calculate_poc_percent($bookedCost, $eacTotal), 2),
    ];
}

function finrap_sort_report_index_entries(array $entries): array
{
    usort($entries, static function (array $left, array $right): int {
        return strcmp((string) ($right['fetched_at'] ?? ''), (string) ($left['fetched_at'] ?? ''));
    });

    return $entries;
}

function finrap_load_report_index(string $company, string $projectNo): ?array
{
    $path = finrap_report_index_path($company, $projectNo);
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !is_array($decoded['reports'] ?? null)) {
        return null;
    }

    $decoded['reports'] = finrap_sort_report_index_entries($decoded['reports']);

    return $decoded;
}

function finrap_save_report_index(string $company, string $projectNo, array $reports): bool
{
    $path = finrap_report_index_path($company, $projectNo);
    $payload = [
        'version' => 1,
        'updated_at' => gmdate('c'),
        'reports' => finrap_sort_report_index_entries(array_values($reports)),
    ];

    return finrap_write_json_file($path, $payload);
}

function finrap_count_report_snapshot_files(string $company, string $projectNo): int
{
    $dir = finrap_cache_dir();
    $prefix = finrap_report_snapshot_file_prefix($company, $projectNo);
    $entries = @scandir($dir);
    if (!is_array($entries)) {
        return 0;
    }

    $count = 0;
    foreach ($entries as $entry) {
        if (!str_starts_with($entry, $prefix) || !str_ends_with($entry, '.json') || finrap_is_report_sidecar_json_file($entry)) {
            continue;
        }

        $reportId = substr($entry, strlen($prefix), -5);
        if (preg_match('/^[a-z0-9_-]+$/i', $reportId)) {
            $count++;
        }
    }

    return $count;
}

function finrap_rebuild_report_index(string $company, string $projectNo): bool
{
    $dir = finrap_cache_dir();
    $prefix = finrap_report_snapshot_file_prefix($company, $projectNo);
    $entries = @scandir($dir);
    if (!is_array($entries)) {
        return finrap_save_report_index($company, $projectNo, []);
    }

    $reports = [];
    foreach ($entries as $entry) {
        if (!str_starts_with($entry, $prefix) || !str_ends_with($entry, '.json') || finrap_is_report_sidecar_json_file($entry)) {
            continue;
        }

        $reportId = substr($entry, strlen($prefix), -5);
        if (!preg_match('/^[a-z0-9_-]+$/i', $reportId)) {
            continue;
        }

        $payload = finrap_load_report_snapshot($company, $projectNo, $reportId);
        if (!is_array($payload)) {
            continue;
        }

        $reports[] = finrap_report_index_entry_from_payload($company, $projectNo, $reportId, $payload);
    }

    return finrap_save_report_index($company, $projectNo, $reports);
}

function finrap_upsert_report_index_entry(string $company, string $projectNo, string $reportId, array $payload): bool
{
    $reportId = trim($reportId);
    if ($reportId === '') {
        return false;
    }

    $index = finrap_load_report_index($company, $projectNo);
    $reports = is_array($index['reports'] ?? null) ? $index['reports'] : [];
    $normalizedReportId = strtolower($reportId);
    $reportsById = [];

    foreach ($reports as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $entryReportId = strtolower(trim((string) ($entry['report_id'] ?? '')));
        if ($entryReportId === '') {
            continue;
        }

        $reportsById[$entryReportId] = $entry;
    }

    $reportsById[$normalizedReportId] = finrap_report_index_entry_from_payload($company, $projectNo, $reportId, $payload);

    return finrap_save_report_index($company, $projectNo, array_values($reportsById));
}

function finrap_refresh_report_index_entry(string $company, string $projectNo, string $reportId): bool
{
    $reportId = trim($reportId);
    if ($reportId === '') {
        return false;
    }

    $payload = finrap_load_report_snapshot($company, $projectNo, $reportId);
    if (!is_array($payload)) {
        return false;
    }

    return finrap_upsert_report_index_entry($company, $projectNo, $reportId, $payload);
}

function finrap_dashboard_cache_path(string $company, string $projectNo): string
{
    $safeCompany = finrap_normalize_company($company);
    $safeProject = preg_replace('/[^a-z0-9_-]/i', '_', finrap_normalize_project_no($projectNo));

    return finrap_cache_dir() . DIRECTORY_SEPARATOR . $safeCompany . '_' . $safeProject . '_dashboard-cache.json';
}

function finrap_load_dashboard_cache(string $company, string $projectNo): ?array
{
    $path = finrap_dashboard_cache_path($company, $projectNo);
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : null;
}

function finrap_is_dashboard_cache_current(string $company, string $projectNo, ?array $cache): bool
{
    if (!is_array($cache) || !is_array($cache['dashboard'] ?? null)) {
        return false;
    }

    $index = finrap_load_report_index($company, $projectNo);
    if (!is_array($index)) {
        return false;
    }

    $latestReportId = finrap_find_latest_report_id($company, $projectNo);
    if ($latestReportId === null) {
        return false;
    }

    return strcasecmp((string) ($cache['source_latest_report_id'] ?? ''), $latestReportId) === 0
        && (string) ($cache['source_index_updated_at'] ?? '') === (string) ($index['updated_at'] ?? '');
}

function finrap_save_dashboard_cache(string $company, string $projectNo, array $dashboard, array $index): bool
{
    $latestReportId = finrap_find_latest_report_id($company, $projectNo);
    $payload = [
        'version' => 1,
        'updated_at' => gmdate('c'),
        'source_latest_report_id' => $latestReportId ?? '',
        'source_index_updated_at' => (string) ($index['updated_at'] ?? ''),
        'dashboard' => $dashboard,
    ];

    return finrap_write_json_file(finrap_dashboard_cache_path($company, $projectNo), $payload);
}

function finrap_delete_dashboard_cache(string $company, string $projectNo): bool
{
    $path = finrap_dashboard_cache_path($company, $projectNo);
    if (!is_file($path)) {
        return true;
    }

    return @unlink($path);
}

function finrap_report_index_entries_by_report_id(?array $index): array
{
    if (!is_array($index)) {
        return [];
    }

    $entriesByReportId = [];
    foreach ($index['reports'] ?? [] as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $reportId = strtolower(trim((string) ($entry['report_id'] ?? '')));
        if ($reportId === '') {
            continue;
        }

        $entriesByReportId[$reportId] = $entry;
    }

    return $entriesByReportId;
}

function finrap_project_report_entries_from_index(?array $index): array
{
    if (!is_array($index)) {
        return [];
    }

    $entries = [];
    foreach ($index['reports'] ?? [] as $reportEntry) {
        if (!is_array($reportEntry)) {
            continue;
        }

        $reportId = trim((string) ($reportEntry['report_id'] ?? ''));
        if ($reportId === '') {
            continue;
        }

        $entries[] = [
            'report_id' => $reportId,
            'fetched_at' => (string) ($reportEntry['fetched_at'] ?? ''),
            'auto_report' => (bool) ($reportEntry['auto_report'] ?? false),
        ];
    }

    usort($entries, static function (array $left, array $right): int {
        return strcmp((string) ($left['fetched_at'] ?? ''), (string) ($right['fetched_at'] ?? ''));
    });

    return $entries;
}

function finrap_build_dashboard_poc_points_from_index(string $company, string $projectNo, ?array $index = null): array
{
    if ($index === null) {
        $index = finrap_load_report_index($company, $projectNo);
    }

    $entries = finrap_project_report_entries_from_index($index);
    $selected = finrap_select_dashboard_report_entries($entries, false);
    $indexEntries = finrap_report_index_entries_by_report_id($index);
    $points = [];

    foreach ($selected as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $reportId = trim((string) ($entry['report_id'] ?? ''));
        if ($reportId === '') {
            continue;
        }

        $chartDate = finrap_report_fetched_date((string) ($entry['fetched_at'] ?? ''));
        if ($chartDate === null) {
            continue;
        }

        $indexEntry = $indexEntries[strtolower($reportId)] ?? null;
        if (is_array($indexEntry) && isset($indexEntry['poc_baseline'], $indexEntry['poc_eac'])) {
            $points[] = [
                'date' => $chartDate,
                'report_id' => $reportId,
                'auto_report' => (bool) ($entry['auto_report'] ?? false),
                'poc_baseline' => round(finance_to_float($indexEntry['poc_baseline'] ?? 0.0), 2),
                'poc_eac' => round(finance_to_float($indexEntry['poc_eac'] ?? 0.0), 2),
            ];
            continue;
        }

        $metrics = finrap_compute_report_poc_metrics($company, $projectNo, $reportId);
        if (!is_array($metrics)) {
            continue;
        }

        $points[] = [
            'date' => $chartDate,
            'report_id' => $reportId,
            'auto_report' => (bool) ($entry['auto_report'] ?? false),
            'poc_baseline' => round(finance_to_float($metrics['poc_baseline'] ?? 0.0), 2),
            'poc_eac' => round(finance_to_float($metrics['poc_eac'] ?? 0.0), 2),
        ];
    }

    return $points;
}

function finrap_build_project_dashboard_core(string $company, string $projectNo, ?array $index = null): array
{
    if ($index === null) {
        finrap_reconcile_report_index($company, $projectNo);
        $index = finrap_load_report_index($company, $projectNo);
    }

    $points = finrap_build_dashboard_poc_points_from_index($company, $projectNo, $index);
    $yMaxPercent = 100.0;
    foreach ($points as $point) {
        if (!is_array($point)) {
            continue;
        }

        $yMaxPercent = max(
            $yMaxPercent,
            finance_to_float($point['poc_baseline'] ?? 0.0),
            finance_to_float($point['poc_eac'] ?? 0.0)
        );
    }

    return [
        'latest_report_id' => finrap_find_latest_report_id($company, $projectNo) ?? '',
        'points' => $points,
        'y_max_percent' => $yMaxPercent,
        'cost_breakdown' => finrap_build_latest_cost_breakdown($company, $projectNo),
        'eac_breakdown' => finrap_build_latest_cost_breakdown($company, $projectNo, 'EAC'),
        'invoiced_breakdown' => finrap_build_latest_cost_breakdown($company, $projectNo, 'Invoiced_Amount'),
        'installments_history' => finrap_build_installments_received_history($company, $projectNo, $index),
    ];
}

function finrap_refresh_dashboard_cache(string $company, string $projectNo): bool
{
    finrap_reconcile_report_index($company, $projectNo);
    $index = finrap_load_report_index($company, $projectNo);
    if (!is_array($index)) {
        return finrap_delete_dashboard_cache($company, $projectNo);
    }

    $dashboard = finrap_build_project_dashboard_core($company, $projectNo, $index);

    return finrap_save_dashboard_cache($company, $projectNo, $dashboard, $index);
}

function finrap_refresh_dashboard_cache_if_latest(string $company, string $projectNo, string $reportId): void
{
    if (!finrap_is_latest_report($company, $projectNo, $reportId)) {
        return;
    }

    finrap_refresh_dashboard_cache($company, $projectNo);
}

function finrap_remove_report_index_entry(string $company, string $projectNo, string $reportId): bool
{
    $reportId = trim($reportId);
    if ($reportId === '') {
        return false;
    }

    $index = finrap_load_report_index($company, $projectNo);
    if (!is_array($index)) {
        return true;
    }

    $reports = is_array($index['reports'] ?? null) ? $index['reports'] : [];
    $filtered = array_values(array_filter($reports, static function (array $entry) use ($reportId): bool {
        return strcasecmp(trim((string) ($entry['report_id'] ?? '')), $reportId) !== 0;
    }));

    if (count($filtered) === count($reports)) {
        return true;
    }

    return finrap_save_report_index($company, $projectNo, $filtered);
}

function finrap_reconcile_report_index(string $company, string $projectNo): bool
{
    $index = finrap_load_report_index($company, $projectNo);
    $fileCount = finrap_count_report_snapshot_files($company, $projectNo);
    $indexCount = is_array($index['reports'] ?? null) ? count($index['reports']) : 0;
    $needsMetricRebuild = false;

    if (is_array($index['reports'] ?? null)) {
        foreach ($index['reports'] as $entry) {
            if (!is_array($entry) || !array_key_exists('poc_baseline', $entry)) {
                $needsMetricRebuild = true;
                break;
            }
        }
    }

    if ($index === null || $indexCount !== $fileCount || $needsMetricRebuild) {
        return finrap_rebuild_report_index($company, $projectNo);
    }

    return true;
}

function finrap_refresh_report_indexes_for_nightly_results(array $results): void
{
    $seen = [];
    foreach ($results as $result) {
        if (!is_array($result) || !($result['ok'] ?? false)) {
            continue;
        }

        $company = trim((string) ($result['company'] ?? ''));
        $projectNo = trim((string) ($result['project_no'] ?? ''));
        if ($company === '' || $projectNo === '') {
            continue;
        }

        $key = finrap_normalize_company($company) . '|' . finrap_normalize_project_no($projectNo);
        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        finrap_reconcile_report_index($company, $projectNo);
        finrap_refresh_dashboard_cache($company, $projectNo);
    }
}

function finrap_is_report_sidecar_json_file(string $filename): bool
{
    return str_ends_with($filename, '-overrides.json') || str_ends_with($filename, '-comments.json');
}

function finrap_report_overrides_path(string $company, string $projectNo, string $reportId): string
{
    $reportPath = finrap_report_cache_path($company, $projectNo, $reportId);
    $overridePath = preg_replace('/\.json$/', '-overrides.json', $reportPath);

    return is_string($overridePath) && $overridePath !== '' ? $overridePath : $reportPath . '-overrides.json';
}

function finrap_load_report_overrides(string $company, string $projectNo, string $reportId): array
{
    $path = finrap_report_overrides_path($company, $projectNo, $reportId);
    if (!is_file($path)) {
        return [];
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function finrap_save_report_overrides(string $company, string $projectNo, string $reportId, array $overrides): bool
{
    $path = finrap_report_overrides_path($company, $projectNo, $reportId);
    $eacByTask = is_array($overrides['eac_by_task'] ?? null) ? $overrides['eac_by_task'] : [];

    $payload = [
        'eac_by_task' => (object) $eacByTask,
        'updated_at' => gmdate('c'),
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        return false;
    }

    $saved = file_put_contents($path, $json, LOCK_EX) !== false;
    if ($saved) {
        finrap_refresh_report_index_entry($company, $projectNo, $reportId);
        finrap_refresh_dashboard_cache_if_latest($company, $projectNo, $reportId);
    }

    return $saved;
}

function finrap_report_has_overrides(array $overrides): bool
{
    $eacByTask = is_array($overrides['eac_by_task'] ?? null) ? $overrides['eac_by_task'] : [];

    return $eacByTask !== [];
}

function finrap_find_latest_report_id(string $company, string $projectNo): ?string
{
    $index = finrap_load_report_index($company, $projectNo);
    if (!is_array($index)) {
        finrap_reconcile_report_index($company, $projectNo);
        $index = finrap_load_report_index($company, $projectNo);
    }

    $reports = is_array($index['reports'] ?? null) ? $index['reports'] : [];
    if ($reports === []) {
        return null;
    }

    $reportId = trim((string) ($reports[0]['report_id'] ?? ''));

    return $reportId !== '' ? $reportId : null;
}

function finrap_is_latest_report(string $company, string $projectNo, string $reportId): bool
{
    $latestReportId = finrap_find_latest_report_id($company, $projectNo);
    if ($latestReportId === null) {
        return false;
    }

    return strcasecmp($latestReportId, trim($reportId)) === 0;
}

function finrap_can_edit_report_overrides(string $company, string $projectNo, string $reportId): bool
{
    $reportId = trim($reportId);
    if ($reportId === '') {
        return false;
    }

    return finrap_is_latest_report($company, $projectNo, $reportId);
}

function finrap_copy_report_overrides(string $company, string $projectNo, string $sourceReportId, string $targetReportId): bool
{
    $sourceReportId = trim($sourceReportId);
    $targetReportId = trim($targetReportId);
    if ($sourceReportId === '' || $targetReportId === '' || strcasecmp($sourceReportId, $targetReportId) === 0) {
        return false;
    }

    $sourceOverrides = finrap_load_report_overrides($company, $projectNo, $sourceReportId);
    if (!finrap_report_has_overrides($sourceOverrides)) {
        return false;
    }

    $payload = [
        'eac_by_task' => is_array($sourceOverrides['eac_by_task'] ?? null) ? $sourceOverrides['eac_by_task'] : [],
        'copied_from_report_id' => $sourceReportId,
        'copied_at' => gmdate('c'),
    ];

    return finrap_save_report_overrides($company, $projectNo, $targetReportId, $payload);
}

function finrap_inherit_overrides_from_previous_report(string $company, string $projectNo, string $newReportId): bool
{
    $newReportId = trim($newReportId);
    if ($newReportId === '') {
        return false;
    }

    $reports = finrap_list_report_snapshots($company, $projectNo);
    $previousReportId = null;
    foreach ($reports as $reportEntry) {
        if (!is_array($reportEntry)) {
            continue;
        }

        $reportId = trim((string) ($reportEntry['report_id'] ?? ''));
        if ($reportId === '' || strcasecmp($reportId, $newReportId) === 0) {
            continue;
        }

        $previousReportId = $reportId;
        break;
    }

    if ($previousReportId === null) {
        return false;
    }

    $copied = finrap_copy_report_overrides($company, $projectNo, $previousReportId, $newReportId);
    if ($copied) {
        finrap_refresh_report_index_entry($company, $projectNo, $newReportId);
        finrap_refresh_dashboard_cache($company, $projectNo);
    }

    return $copied;
}

function finrap_project_comments_db_path(string $company, string $projectNo): string
{
    $safeCompany = finrap_normalize_company($company);
    $safeProject = preg_replace('/[^a-z0-9_-]/i', '_', finrap_normalize_project_no($projectNo));

    return finrap_cache_dir() . DIRECTORY_SEPARATOR . $safeCompany . '_' . $safeProject . '_comments.sqlite';
}

function finrap_project_comments_pdo(string $company, string $projectNo): ?PDO
{
    if (!extension_loaded('pdo_sqlite')) {
        return null;
    }

    $path = finrap_project_comments_db_path($company, $projectNo);
    try {
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        finrap_ensure_project_comments_schema($pdo);
        finrap_migrate_legacy_json_report_comments($pdo, $company, $projectNo);

        return $pdo;
    } catch (Throwable) {
        return null;
    }
}

function finrap_ensure_project_comments_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS report_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            report_id TEXT NOT NULL,
            email TEXT NOT NULL COLLATE NOCASE,
            text TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_report_comments_report_id ON report_comments(report_id)');
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_meta (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )'
    );
}

function finrap_normalize_report_comment_row(array $row): array
{
    $createdAt = trim((string) ($row['created_at'] ?? ''));
    $updatedAt = trim((string) ($row['updated_at'] ?? ''));

    return [
        'id' => (string) ($row['id'] ?? ''),
        'email' => strtolower(trim((string) ($row['email'] ?? ''))),
        'text' => (string) ($row['text'] ?? ''),
        'created_at' => $createdAt,
        'updated_at' => $updatedAt !== '' ? $updatedAt : $createdAt,
        'is_edited' => $updatedAt !== '' && $createdAt !== '' && $updatedAt !== $createdAt,
    ];
}

function finrap_trim_report_comment_text(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    if (mb_strlen($text) > FINRAP_REPORT_COMMENT_MAX_LENGTH) {
        return mb_substr($text, 0, FINRAP_REPORT_COMMENT_MAX_LENGTH);
    }

    return $text;
}

function finrap_migrate_legacy_json_report_comments(PDO $pdo, string $company, string $projectNo): void
{
    $stmt = $pdo->prepare('SELECT value FROM schema_meta WHERE key = :key LIMIT 1');
    $stmt->execute(['key' => 'legacy_json_imported']);
    $row = $stmt->fetch();
    if (is_array($row) && trim((string) ($row['value'] ?? '')) === '1') {
        return;
    }

    $dir = finrap_cache_dir();
    $prefix = finrap_report_snapshot_file_prefix($company, $projectNo);
    $entries = @scandir($dir);
    if (!is_array($entries)) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO report_comments (report_id, email, text, created_at, updated_at)
         VALUES (:report_id, :email, :text, :created_at, :updated_at)'
    );
    $exists = $pdo->prepare(
        'SELECT 1 FROM report_comments
         WHERE report_id = :report_id
           AND email = :email COLLATE NOCASE
           AND created_at = :created_at
           AND text = :text
         LIMIT 1'
    );

    foreach ($entries as $entry) {
        if (!is_string($entry) || !str_starts_with($entry, $prefix) || !str_ends_with($entry, '-comments.json')) {
            continue;
        }

        $reportId = substr($entry, strlen($prefix), -strlen('-comments.json'));
        if ($reportId === '' || !preg_match('/^[a-z0-9_-]+$/i', $reportId)) {
            continue;
        }

        $raw = @file_get_contents($dir . DIRECTORY_SEPARATOR . $entry);
        if (!is_string($raw) || trim($raw) === '') {
            continue;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            continue;
        }

        $messages = is_array($decoded['messages'] ?? null) ? $decoded['messages'] : [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $text = finrap_trim_report_comment_text((string) ($message['text'] ?? ''));
            $email = strtolower(trim((string) ($message['email'] ?? '')));
            $createdAt = trim((string) ($message['created_at'] ?? ''));
            if ($text === '' || $email === '' || $createdAt === '') {
                continue;
            }

            $exists->execute([
                'report_id' => $reportId,
                'email' => $email,
                'created_at' => $createdAt,
                'text' => $text,
            ]);
            if ($exists->fetch()) {
                continue;
            }

            $insert->execute([
                'report_id' => $reportId,
                'email' => $email,
                'text' => $text,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    $pdo->prepare('INSERT OR REPLACE INTO schema_meta (key, value) VALUES (:key, :value)')
        ->execute(['key' => 'legacy_json_imported', 'value' => '1']);
}

function finrap_load_report_comments(string $company, string $projectNo, string $reportId): array
{
    $reportId = trim($reportId);
    if ($reportId === '') {
        return [];
    }

    $pdo = finrap_project_comments_pdo($company, $projectNo);
    if ($pdo === null) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT id, email, text, created_at, updated_at
         FROM report_comments
         WHERE report_id = :report_id
         ORDER BY created_at ASC, id ASC'
    );
    $stmt->execute(['report_id' => $reportId]);
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) {
        return [];
    }

    return array_map(static fn(array $row): array => finrap_normalize_report_comment_row($row), $rows);
}

function finrap_report_comment_counts(string $company, string $projectNo, array $reportIds): array
{
    $reportIds = array_values(array_unique(array_filter(array_map(
        static fn($reportId): string => trim((string) $reportId),
        $reportIds
    ))));
    if ($reportIds === []) {
        return [];
    }

    $pdo = finrap_project_comments_pdo($company, $projectNo);
    if ($pdo === null) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
    $stmt = $pdo->prepare(
        'SELECT report_id, COUNT(*) AS comment_count
         FROM report_comments
         WHERE report_id IN (' . $placeholders . ')
         GROUP BY report_id'
    );
    $stmt->execute($reportIds);
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) {
        return [];
    }

    $counts = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $reportId = trim((string) ($row['report_id'] ?? ''));
        if ($reportId === '') {
            continue;
        }

        $counts[$reportId] = (int) ($row['comment_count'] ?? 0);
    }

    return $counts;
}

function finrap_latest_report_comments_for_ids(string $company, string $projectNo, array $reportIds): array
{
    $reportIds = array_values(array_unique(array_filter(array_map(
        static fn($reportId): string => trim((string) $reportId),
        $reportIds
    ))));
    if ($reportIds === []) {
        return [];
    }

    $pdo = finrap_project_comments_pdo($company, $projectNo);
    if ($pdo === null) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
    $stmt = $pdo->prepare(
        'SELECT c.id, c.report_id, c.email, c.text, c.created_at, c.updated_at
         FROM report_comments c
         INNER JOIN (
             SELECT report_id, MAX(id) AS max_id
             FROM report_comments
             WHERE report_id IN (' . $placeholders . ')
             GROUP BY report_id
         ) latest ON c.id = latest.max_id'
    );
    $stmt->execute($reportIds);
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) {
        return [];
    }

    $latestByReportId = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $reportId = trim((string) ($row['report_id'] ?? ''));
        if ($reportId === '') {
            continue;
        }

        $latestByReportId[$reportId] = finrap_normalize_report_comment_row($row);
    }

    return $latestByReportId;
}

function finrap_count_report_comments(string $company, string $projectNo, string $reportId): int
{
    $counts = finrap_report_comment_counts($company, $projectNo, [trim($reportId)]);

    return (int) ($counts[trim($reportId)] ?? 0);
}

function finrap_add_report_comment(string $company, string $projectNo, string $reportId, string $email, string $text): ?array
{
    $reportId = trim($reportId);
    $email = strtolower(trim($email));
    $text = finrap_trim_report_comment_text($text);
    if ($reportId === '' || $email === '' || $text === '') {
        return null;
    }

    $reportPath = finrap_report_cache_path($company, $projectNo, $reportId);
    if (!is_file($reportPath)) {
        return null;
    }

    $pdo = finrap_project_comments_pdo($company, $projectNo);
    if ($pdo === null) {
        return null;
    }

    $createdAt = gmdate('c');
    $stmt = $pdo->prepare(
        'INSERT INTO report_comments (report_id, email, text, created_at, updated_at)
         VALUES (:report_id, :email, :text, :created_at, :updated_at)'
    );
    $stmt->execute([
        'report_id' => $reportId,
        'email' => $email,
        'text' => $text,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    $id = (int) $pdo->lastInsertId();
    if ($id <= 0) {
        return null;
    }

    return finrap_normalize_report_comment_row([
        'id' => (string) $id,
        'email' => $email,
        'text' => $text,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

function finrap_update_report_comment(
    string $company,
    string $projectNo,
    string $reportId,
    int $commentId,
    string $email,
    string $text
): ?array {
    $reportId = trim($reportId);
    $email = strtolower(trim($email));
    $text = finrap_trim_report_comment_text($text);
    if ($reportId === '' || $email === '' || $text === '' || $commentId <= 0) {
        return null;
    }

    $reportPath = finrap_report_cache_path($company, $projectNo, $reportId);
    if (!is_file($reportPath)) {
        return null;
    }

    $pdo = finrap_project_comments_pdo($company, $projectNo);
    if ($pdo === null) {
        return null;
    }

    $existingStmt = $pdo->prepare(
        'SELECT id, email, text, created_at, updated_at
         FROM report_comments
         WHERE id = :id AND report_id = :report_id
         LIMIT 1'
    );
    $existingStmt->execute([
        'id' => $commentId,
        'report_id' => $reportId,
    ]);
    $existing = $existingStmt->fetch();
    if (!is_array($existing)) {
        return null;
    }

    if (strcasecmp((string) ($existing['email'] ?? ''), $email) !== 0) {
        return null;
    }

    $updatedAt = gmdate('c');
    $updateStmt = $pdo->prepare(
        'UPDATE report_comments
         SET text = :text, updated_at = :updated_at
         WHERE id = :id AND report_id = :report_id AND email = :email'
    );
    $updateStmt->execute([
        'text' => $text,
        'updated_at' => $updatedAt,
        'id' => $commentId,
        'report_id' => $reportId,
        'email' => $email,
    ]);

    if ($updateStmt->rowCount() < 1) {
        return null;
    }

    return finrap_normalize_report_comment_row([
        'id' => (string) $commentId,
        'email' => $email,
        'text' => $text,
        'created_at' => (string) ($existing['created_at'] ?? ''),
        'updated_at' => $updatedAt,
    ]);
}

function finrap_delete_report_comments(string $company, string $projectNo, string $reportId): bool
{
    $reportId = trim($reportId);
    if ($reportId === '') {
        return true;
    }

    $pdo = finrap_project_comments_pdo($company, $projectNo);
    if ($pdo === null) {
        return true;
    }

    $stmt = $pdo->prepare('DELETE FROM report_comments WHERE report_id = :report_id');
    $stmt->execute(['report_id' => $reportId]);

    return true;
}

function finrap_delete_report_overrides(string $company, string $projectNo, string $reportId): bool
{
    $path = finrap_report_overrides_path($company, $projectNo, $reportId);
    if (!is_file($path)) {
        return true;
    }

    return @unlink($path);
}

function finrap_calculate_poc_percent(float $bookedCost, float $eac): float
{
    $epsilon = 0.000001;
    if (abs($eac) < $epsilon) {
        return 0.0;
    }

    return ($bookedCost / $eac) * 100.0;
}

function finrap_load(string $company, string $projectNo, string $yearMonth): ?array
{
    $path = finrap_cache_path($company, $projectNo, $yearMonth);
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function finrap_save(string $company, string $projectNo, string $yearMonth, array $data): bool
{
    $path = finrap_cache_path($company, $projectNo, $yearMonth);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        return false;
    }

    return file_put_contents($path, $json, LOCK_EX) !== false;
}

function finrap_save_report_snapshot(string $company, string $projectNo, array $data, ?string $reportId = null): ?string
{
    $finalReportId = trim((string) ($reportId ?? ''));
    if ($finalReportId === '') {
        $finalReportId = finrap_generate_report_id();
    }

    $path = finrap_report_cache_path($company, $projectNo, $finalReportId);
    $payload = $data;
    $payload['report_id'] = $finalReportId;
    if (!isset($payload['fetched_at']) || trim((string) ($payload['fetched_at'] ?? '')) === '') {
        $payload['fetched_at'] = gmdate('c');
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        return null;
    }

    $ok = file_put_contents($path, $json, LOCK_EX) !== false;
    if ($ok) {
        finrap_upsert_report_index_entry($company, $projectNo, $finalReportId, $payload);
        finrap_refresh_dashboard_cache($company, $projectNo);
    }

    return $ok ? $finalReportId : null;
}

function finrap_load_report_snapshot(string $company, string $projectNo, string $reportId): ?array
{
    $path = finrap_report_cache_path($company, $projectNo, $reportId);
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function finrap_delete_report_snapshot(string $company, string $projectNo, string $reportId): bool
{
    $path = finrap_report_cache_path($company, $projectNo, $reportId);
    if (!is_file($path)) {
        return false;
    }

    $deleted = @unlink($path);
    if ($deleted) {
        finrap_delete_report_overrides($company, $projectNo, $reportId);
        finrap_delete_report_comments($company, $projectNo, $reportId);
        finrap_remove_report_index_entry($company, $projectNo, $reportId);
        finrap_refresh_dashboard_cache($company, $projectNo);
    }

    return $deleted;
}

function finrap_ensure_report_index(string $company, string $projectNo, bool $reconcile = true): ?array
{
    if ($reconcile) {
        finrap_reconcile_report_index($company, $projectNo);
    } elseif (!is_file(finrap_report_index_path($company, $projectNo))) {
        finrap_reconcile_report_index($company, $projectNo);
    }

    return finrap_load_report_index($company, $projectNo);
}

function finrap_report_snapshot_rows_from_index(?array $index, bool $includeAutoReports = true): array
{
    if (!is_array($index)) {
        return [];
    }

    $entries = is_array($index['reports'] ?? null) ? $index['reports'] : [];
    $rows = array_map(static function (array $entry): array {
        return [
            'report_id' => (string) ($entry['report_id'] ?? ''),
            'fetched_at' => (string) ($entry['fetched_at'] ?? ''),
            'auto_report' => (bool) ($entry['auto_report'] ?? false),
            'created_by' => (string) ($entry['created_by'] ?? ''),
        ];
    }, $entries);

    if (!$includeAutoReports) {
        $rows = array_values(array_filter($rows, static function (array $entry): bool {
            return !($entry['auto_report'] ?? false);
        }));
    }

    return $rows;
}

function finrap_count_report_snapshots(string $company, string $projectNo, bool $includeAutoReports = true, bool $reconcile = true): int
{
    $index = finrap_ensure_report_index($company, $projectNo, $reconcile);

    return count(finrap_report_snapshot_rows_from_index($index, $includeAutoReports));
}

function finrap_list_report_snapshots(
    string $company,
    string $projectNo,
    ?int $limit = null,
    int $offset = 0,
    bool $includeAutoReports = true,
    bool $reconcile = true
): array {
    $index = finrap_ensure_report_index($company, $projectNo, $reconcile);
    $rows = finrap_report_snapshot_rows_from_index($index, $includeAutoReports);

    if ($limit === null) {
        return $rows;
    }

    return array_slice($rows, max(0, $offset), max(0, $limit));
}

function finrap_report_list_page(
    string $company,
    string $projectNo,
    int $limit,
    int $offset,
    bool $includeAutoReports = true,
    bool $reconcile = true
): array {
    $index = finrap_ensure_report_index($company, $projectNo, $reconcile);
    $rows = finrap_report_snapshot_rows_from_index($index, $includeAutoReports);
    $totalCount = count($rows);
    $offset = max(0, $offset);
    $limit = max(1, $limit);
    $reports = array_slice($rows, $offset, $limit);
    $reportIds = array_values(array_filter(array_map(
        static fn(array $row): string => trim((string) ($row['report_id'] ?? '')),
        $reports
    )));
    $commentCounts = finrap_report_comment_counts($company, $projectNo, $reportIds);
    $latestComments = finrap_latest_report_comments_for_ids($company, $projectNo, $reportIds);
    $reports = array_map(static function (array $row) use ($commentCounts, $latestComments): array {
        $reportId = trim((string) ($row['report_id'] ?? ''));
        $row['comment_count'] = $reportId !== '' ? (int) ($commentCounts[$reportId] ?? 0) : 0;
        $row['latest_comment'] = $reportId !== '' && isset($latestComments[$reportId])
            ? $latestComments[$reportId]
            : null;

        return $row;
    }, $reports);

    return [
        'reports' => $reports,
        'total_count' => $totalCount,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + count($reports)) < $totalCount,
    ];
}

function finrap_is_auto_report(array $payload): bool
{
    return ($payload['auto_report'] ?? false) === true;
}

function finrap_list_nightly_report_targets(): array
{
    $dir = finrap_cache_dir();
    $entries = @scandir($dir);
    if (!is_array($entries)) {
        return [];
    }

    $projectsByKey = [];
    foreach ($entries as $entry) {
        if (!is_string($entry) || !str_contains($entry, '_ts_') || !str_ends_with($entry, '.json') || finrap_is_report_sidecar_json_file($entry)) {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            continue;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            continue;
        }

        $company = trim((string) ($payload['company'] ?? ''));
        $projectNo = trim((string) ($payload['project_no'] ?? ''));
        if ($company === '' || $projectNo === '') {
            continue;
        }

        $projectKey = strtolower($company) . '|' . strtolower($projectNo);
        if (!isset($projectsByKey[$projectKey])) {
            $projectsByKey[$projectKey] = [
                'company' => $company,
                'project_no' => $projectNo,
                'has_manual_report' => false,
            ];
        }

        if (!finrap_is_auto_report($payload)) {
            $projectsByKey[$projectKey]['has_manual_report'] = true;
        }
    }

    $targets = [];
    foreach ($projectsByKey as $projectEntry) {
        if (!is_array($projectEntry) || !(bool) ($projectEntry['has_manual_report'] ?? false)) {
            continue;
        }

        $targets[] = [
            'company' => (string) ($projectEntry['company'] ?? ''),
            'project_no' => (string) ($projectEntry['project_no'] ?? ''),
        ];
    }

    usort($targets, static function (array $left, array $right): int {
        $leftKey = strtolower((string) ($left['company'] ?? '')) . '|' . strtolower((string) ($left['project_no'] ?? ''));
        $rightKey = strtolower((string) ($right['company'] ?? '')) . '|' . strtolower((string) ($right['project_no'] ?? ''));

        return strcmp($leftKey, $rightKey);
    });

    return $targets;
}

function finrap_run_nightly_reports(): array
{
    $targets = finrap_list_nightly_report_targets();
    $yearMonth = gmdate('Y-m');
    $results = [];

    foreach ($targets as $target) {
        if (!is_array($target)) {
            continue;
        }

        $company = trim((string) ($target['company'] ?? ''));
        $projectNo = trim((string) ($target['project_no'] ?? ''));
        if ($company === '' || $projectNo === '') {
            continue;
        }

        if (!finrap_should_generate_nightly_report($company, $projectNo)) {
            continue;
        }

        $startedAt = hrtime(true);
        try {
            $report = finrap_generate_month_for_project($company, $projectNo, $yearMonth);
            $resolvedProjectNo = trim((string) ($report['project_no'] ?? $projectNo));
            $report['auto_report'] = true;
            $reportId = finrap_save_report_snapshot($company, $resolvedProjectNo, $report);
            if (!is_string($reportId) || $reportId === '') {
                throw new RuntimeException('Rapport opslaan mislukt.');
            }

            finrap_inherit_overrides_from_previous_report($company, $resolvedProjectNo, $reportId);

            $results[] = [
                'ok' => true,
                'company' => $company,
                'project_no' => $resolvedProjectNo,
                'report_id' => $reportId,
                'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            ];
        } catch (Throwable $error) {
            $results[] = [
                'ok' => false,
                'company' => $company,
                'project_no' => $projectNo,
                'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
                'error' => $error->getMessage(),
            ];
        }
    }

    return $results;
}

function finrap_report_timezone(): DateTimeZone
{
    return new DateTimeZone('Europe/Amsterdam');
}

function finrap_report_fetched_datetime(string $fetchedAt): ?DateTimeImmutable
{
    $value = trim($fetchedAt);
    if ($value === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($value))->setTimezone(finrap_report_timezone());
    } catch (Throwable $ignoredDateParseError) {
        return null;
    }
}

function finrap_report_fetched_date(string $fetchedAt): ?string
{
    $dateTime = finrap_report_fetched_datetime($fetchedAt);

    return $dateTime instanceof DateTimeImmutable ? $dateTime->format('Y-m-d') : null;
}

function finrap_parse_project_ending_date(?array $project): ?DateTimeImmutable
{
    if (!is_array($project)) {
        return null;
    }

    $raw = trim((string) ($project['Ending_Date'] ?? ''));
    if ($raw === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($raw, finrap_report_timezone()))->setTime(0, 0, 0);
    } catch (Throwable $ignoredDateParseError) {
        return null;
    }
}

function finrap_get_project_ending_date(string $company, string $projectNo): ?DateTimeImmutable
{
    $reports = finrap_list_report_snapshots($company, $projectNo);
    foreach ($reports as $reportEntry) {
        if (!is_array($reportEntry)) {
            continue;
        }

        $reportId = trim((string) ($reportEntry['report_id'] ?? ''));
        if ($reportId === '') {
            continue;
        }

        $payload = finrap_load_report_snapshot($company, $projectNo, $reportId);
        $project = is_array($payload['project'] ?? null) ? $payload['project'] : null;
        $endingDate = finrap_parse_project_ending_date($project);
        if ($endingDate instanceof DateTimeImmutable) {
            return $endingDate;
        }
    }

    try {
        $project = finrap_fetch_project($company, $projectNo, 3600);
    } catch (Throwable $ignoredProjectLoadError) {
        return null;
    }

    return finrap_parse_project_ending_date(is_array($project) ? $project : null);
}

function finrap_is_ending_date_passed(?DateTimeImmutable $endingDate): bool
{
    if (!$endingDate instanceof DateTimeImmutable) {
        return false;
    }

    $today = new DateTimeImmutable('today', finrap_report_timezone());

    return $endingDate < $today;
}

function finrap_has_recent_manual_report_after_ending(string $company, string $projectNo, DateTimeImmutable $endingDate): bool
{
    $cutoff = new DateTimeImmutable('-1 month', finrap_report_timezone());
    $reports = finrap_list_report_snapshots($company, $projectNo);

    foreach ($reports as $reportEntry) {
        if (!is_array($reportEntry) || (bool) ($reportEntry['auto_report'] ?? false)) {
            continue;
        }

        $fetchedAt = finrap_report_fetched_datetime((string) ($reportEntry['fetched_at'] ?? ''));
        if (!$fetchedAt instanceof DateTimeImmutable) {
            continue;
        }

        $fetchedDate = $fetchedAt->setTime(0, 0, 0);
        if ($fetchedDate <= $endingDate || $fetchedDate < $cutoff) {
            continue;
        }

        return true;
    }

    return false;
}

function finrap_should_generate_nightly_report(string $company, string $projectNo): bool
{
    $endingDate = finrap_get_project_ending_date($company, $projectNo);
    if (!finrap_is_ending_date_passed($endingDate)) {
        return true;
    }

    return finrap_has_recent_manual_report_after_ending($company, $projectNo, $endingDate);
}

function finrap_list_project_report_entries(string $company, string $projectNo): array
{
    $index = finrap_ensure_report_index($company, $projectNo);

    return finrap_project_report_entries_from_index($index);
}

function finrap_select_dashboard_report_entries(array $entries, bool $debugAllReports): array
{
    if ($debugAllReports) {
        return $entries;
    }

    $latestAutoByDay = [];
    foreach ($entries as $entry) {
        if (!is_array($entry) || !(bool) ($entry['auto_report'] ?? false)) {
            continue;
        }

        $day = finrap_report_fetched_date((string) ($entry['fetched_at'] ?? ''));
        if ($day === null) {
            continue;
        }

        if (
            !isset($latestAutoByDay[$day])
            || strcmp((string) ($entry['fetched_at'] ?? ''), (string) ($latestAutoByDay[$day]['fetched_at'] ?? '')) > 0
        ) {
            $latestAutoByDay[$day] = $entry;
        }
    }

    $selected = array_values($latestAutoByDay);
    usort($selected, static function (array $left, array $right): int {
        return strcmp((string) ($left['fetched_at'] ?? ''), (string) ($right['fetched_at'] ?? ''));
    });

    return $selected;
}

function finrap_compute_report_poc_metrics(string $company, string $projectNo, string $reportId): ?array
{
    $reportId = trim($reportId);
    if ($reportId === '') {
        return null;
    }

    $payload = finrap_load_report_snapshot($company, $projectNo, $reportId);
    if (!is_array($payload)) {
        return null;
    }

    $modal = is_array($payload['project_modal'] ?? null) ? $payload['project_modal'] : [];
    $taskRows = is_array($modal['task_rows'] ?? null) ? $modal['task_rows'] : [];
    $overrides = finrap_load_report_overrides($company, $projectNo, $reportId);
    $eacOverrides = is_array($overrides['eac_by_task'] ?? null) ? $overrides['eac_by_task'] : [];
    $taskRows = finrap_apply_eac_overrides_to_task_rows($taskRows, $eacOverrides);
    $summaryTotals = finrap_get_report_summary_totals($taskRows);

    $bookedCost = finance_to_float($summaryTotals['Booked_Cost'] ?? 0.0);
    $budgetCost = finance_to_float($summaryTotals['Budget_Cost'] ?? 0.0);
    $eacTotal = finance_to_float($summaryTotals['EAC'] ?? 0.0);

    return [
        'poc_baseline' => finrap_calculate_poc_percent($bookedCost, $budgetCost),
        'poc_eac' => finrap_calculate_poc_percent($bookedCost, $eacTotal),
    ];
}

function finrap_build_project_dashboard(string $company, string $projectNo, bool $debugAllReports = false): array
{
    if (!$debugAllReports) {
        $cache = finrap_load_dashboard_cache($company, $projectNo);
        if (finrap_is_dashboard_cache_current($company, $projectNo, $cache) && is_array($cache['dashboard'] ?? null)) {
            return array_merge($cache['dashboard'], [
                'company' => $company,
                'project_no' => $projectNo,
                'debug_all_reports' => false,
            ]);
        }

        finrap_refresh_dashboard_cache($company, $projectNo);
        $cache = finrap_load_dashboard_cache($company, $projectNo);
        if (is_array($cache['dashboard'] ?? null)) {
            return array_merge($cache['dashboard'], [
                'company' => $company,
                'project_no' => $projectNo,
                'debug_all_reports' => false,
            ]);
        }
    }

    $entries = finrap_list_project_report_entries($company, $projectNo);
    $selected = finrap_select_dashboard_report_entries($entries, $debugAllReports);
    $points = [];
    $seriesStartDate = null;

    foreach ($selected as $index => $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $reportId = trim((string) ($entry['report_id'] ?? ''));
        if ($reportId === '') {
            continue;
        }

        if ($debugAllReports) {
            if (!$seriesStartDate instanceof DateTimeImmutable) {
                $firstDay = finrap_report_fetched_date((string) ($entry['fetched_at'] ?? ''));
                $seriesStartDate = $firstDay !== null
                    ? new DateTimeImmutable($firstDay, finrap_report_timezone())
                    : new DateTimeImmutable('today', finrap_report_timezone());
            }

            $chartDate = $seriesStartDate->modify('+' . (int) $index . ' days')->format('Y-m-d');
        } else {
            $chartDate = finrap_report_fetched_date((string) ($entry['fetched_at'] ?? ''));
            if ($chartDate === null) {
                continue;
            }
        }

        $metrics = finrap_compute_report_poc_metrics($company, $projectNo, $reportId);
        if (!is_array($metrics)) {
            continue;
        }

        $points[] = [
            'date' => $chartDate,
            'report_id' => $reportId,
            'auto_report' => (bool) ($entry['auto_report'] ?? false),
            'poc_baseline' => round(finance_to_float($metrics['poc_baseline'] ?? 0.0), 2),
            'poc_eac' => round(finance_to_float($metrics['poc_eac'] ?? 0.0), 2),
        ];
    }

    $yMaxPercent = 100.0;
    foreach ($points as $point) {
        if (!is_array($point)) {
            continue;
        }

        $yMaxPercent = max(
            $yMaxPercent,
            finance_to_float($point['poc_baseline'] ?? 0.0),
            finance_to_float($point['poc_eac'] ?? 0.0)
        );
    }

    return [
        'company' => $company,
        'project_no' => $projectNo,
        'debug_all_reports' => $debugAllReports,
        'latest_report_id' => finrap_find_latest_report_id($company, $projectNo),
        'points' => $points,
        'y_max_percent' => $yMaxPercent,
        'cost_breakdown' => finrap_build_latest_cost_breakdown($company, $projectNo),
        'eac_breakdown' => finrap_build_latest_cost_breakdown($company, $projectNo, 'EAC'),
        'invoiced_breakdown' => finrap_build_latest_cost_breakdown($company, $projectNo, 'Invoiced_Amount'),
        'installments_history' => finrap_build_installments_received_history($company, $projectNo),
    ];
}

function finrap_is_bc_blank_date(string $date): bool
{
    $value = trim($date);

    return $value === '' || str_starts_with($value, '0001-01-01');
}

function finrap_amounts_match_for_termijn(float $left, float $right, float $epsilon = 0.01): bool
{
    return abs($left - $right) < $epsilon || abs(abs($left) - abs($right)) < $epsilon;
}

function finrap_find_customer_ledger_match_for_termijn(
    float $termijnAmount,
    array $customerRows,
    array &$usedEntryNos
): ?array {
    foreach ($customerRows as $customerRow) {
        if (!is_array($customerRow)) {
            continue;
        }

        $entryNo = (int) ($customerRow['Entry_No'] ?? 0);
        if ($entryNo > 0 && isset($usedEntryNos[$entryNo])) {
            continue;
        }

        $salesLcy = finance_to_float($customerRow['Sales_LCY'] ?? 0.0);
        if (!finrap_amounts_match_for_termijn($salesLcy, $termijnAmount)) {
            continue;
        }

        if ($entryNo > 0) {
            $usedEntryNos[$entryNo] = true;
        }

        return $customerRow;
    }

    return null;
}

function finrap_enrich_termijn_lines_with_customer_ledger(array $termijnLines, array $customerRows): array
{
    $usedEntryNos = [];

    foreach ($termijnLines as &$termijnLine) {
        if (!is_array($termijnLine)) {
            continue;
        }

        $termijnLine['status'] = 'not_invoiced';
        $termijnLine['ledger_description'] = '';
        $termijnLine['posting_date'] = '';
        $termijnLine['due_date'] = '';
        $termijnLine['closed_at_date'] = '';

        $ledgerMatch = finrap_find_customer_ledger_match_for_termijn(
            finance_to_float($termijnLine['amount'] ?? 0.0),
            $customerRows,
            $usedEntryNos
        );
        if ($ledgerMatch === null) {
            continue;
        }

        $termijnLine['status'] = 'invoiced';
        $termijnLine['ledger_description'] = trim((string) ($ledgerMatch['Description'] ?? ''));

        $postingDate = trim((string) ($ledgerMatch['Posting_Date'] ?? ''));
        if (!finrap_is_bc_blank_date($postingDate)) {
            $termijnLine['posting_date'] = $postingDate;
        }

        $dueDate = trim((string) ($ledgerMatch['Due_Date'] ?? ''));
        if (!finrap_is_bc_blank_date($dueDate)) {
            $termijnLine['due_date'] = $dueDate;
        }

        $closedAtDate = trim((string) ($ledgerMatch['Closed_at_Date'] ?? ''));
        if (!finrap_is_bc_blank_date($closedAtDate)) {
            $termijnLine['status'] = 'paid';
            $termijnLine['closed_at_date'] = $closedAtDate;
        }
    }
    unset($termijnLine);

    return $termijnLines;
}

function finrap_sort_termijn_lines_by_change_order(array $termijnLines): array
{
    usort($termijnLines, static function (array $left, array $right): int {
        $changeOrderLeft = trim((string) ($left['change_order_no'] ?? ''));
        $changeOrderRight = trim((string) ($right['change_order_no'] ?? ''));

        $groupLeft = $changeOrderLeft === '' ? 0 : 1;
        $groupRight = $changeOrderRight === '' ? 0 : 1;
        if ($groupLeft !== $groupRight) {
            return $groupLeft <=> $groupRight;
        }

        if ($groupLeft === 1) {
            $changeOrderCompare = strnatcasecmp($changeOrderLeft, $changeOrderRight);
            if ($changeOrderCompare !== 0) {
                return $changeOrderCompare;
            }
        }

        return ((int) ($left['line_no'] ?? 0)) <=> ((int) ($right['line_no'] ?? 0));
    });

    return $termijnLines;
}

function finrap_build_installments_received_history(string $company, string $projectNo, ?array $index = null): array
{
    if ($index === null) {
        $index = finrap_ensure_report_index($company, $projectNo);
    }

    $reports = is_array($index['reports'] ?? null) ? $index['reports'] : [];
    if ($reports === []) {
        return [];
    }

    $pointsByDay = [];
    foreach (array_reverse($reports) as $reportEntry) {
        if (!is_array($reportEntry)) {
            continue;
        }

        $reportId = trim((string) ($reportEntry['report_id'] ?? ''));
        if ($reportId === '') {
            continue;
        }

        $chartDate = finrap_report_fetched_date((string) ($reportEntry['fetched_at'] ?? ''));
        if ($chartDate === null) {
            continue;
        }

        $pointsByDay[$chartDate] = [
            'date' => $chartDate,
            'report_id' => $reportId,
            'auto_report' => (bool) ($reportEntry['auto_report'] ?? false),
            'amount' => round(finance_to_float($reportEntry['installments_received'] ?? 0.0), 2),
        ];
    }

    ksort($pointsByDay);

    return array_values($pointsByDay);
}

function finrap_is_major_total_task_code(string $taskCode): bool
{
    return preg_match('/^\d{3}-000-000$/', trim($taskCode)) === 1;
}

function finrap_is_minor_total_task_code(string $taskCode): bool
{
    $code = trim($taskCode);
    if (!preg_match('/^\d{3}-\d{3}-000$/', $code)) {
        return false;
    }

    return !finrap_is_major_total_task_code($code);
}

function finrap_major_total_code_for_minor(string $minorCode): string
{
    if (!finrap_is_minor_total_task_code($minorCode)) {
        return '';
    }

    $parts = explode('-', trim($minorCode));

    return $parts[0] . '-000-000';
}

function finrap_load_latest_report_task_rows(string $company, string $projectNo): ?array
{
    $reportId = finrap_find_latest_report_id($company, $projectNo);
    if ($reportId === null) {
        return null;
    }

    $payload = finrap_load_report_snapshot($company, $projectNo, $reportId);
    if (!is_array($payload)) {
        return null;
    }

    $modal = is_array($payload['project_modal'] ?? null) ? $payload['project_modal'] : [];
    $taskRows = is_array($modal['task_rows'] ?? null) ? $modal['task_rows'] : [];
    $overrides = finrap_load_report_overrides($company, $projectNo, $reportId);
    $eacOverrides = is_array($overrides['eac_by_task'] ?? null) ? $overrides['eac_by_task'] : [];
    $taskRows = finrap_apply_eac_overrides_to_task_rows($taskRows, $eacOverrides);

    return [
        'report_id' => $reportId,
        'fetched_at' => (string) ($payload['fetched_at'] ?? ''),
        'auto_report' => finrap_is_auto_report($payload),
        'task_rows' => $taskRows,
    ];
}

function finrap_build_latest_cost_breakdown(string $company, string $projectNo, string $costField = 'Booked_Cost'): array
{
    $latest = finrap_load_latest_report_task_rows($company, $projectNo);
    if (!is_array($latest)) {
        return [
            'report_id' => '',
            'fetched_at' => '',
            'auto_report' => false,
            'cost_field' => $costField,
            'major_totals' => [],
            'total_amount' => 0.0,
        ];
    }

    $epsilon = 0.000001;
    $majorTotalsByCode = [];
    $minorTotals = [];

    foreach ($latest['task_rows'] as $taskRow) {
        if (!is_array($taskRow) || !(bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $taskCode = trim((string) ($taskRow['Cost_Group_Code'] ?? ''));
        if ($taskCode === '') {
            continue;
        }

        $amount = finance_to_float($taskRow[$costField] ?? 0.0);
        if (abs($amount) < $epsilon) {
            continue;
        }

        if (finrap_is_major_total_task_code($taskCode)) {
            $majorTotalsByCode[strtolower($taskCode)] = [
                'code' => $taskCode,
                'description' => trim((string) ($taskRow['Cost_Group_Description'] ?? '')),
                'amount' => $amount,
                'subtotals' => [],
            ];
            continue;
        }

        if (finrap_is_minor_total_task_code($taskCode)) {
            $minorTotals[] = [
                'code' => $taskCode,
                'description' => trim((string) ($taskRow['Cost_Group_Description'] ?? '')),
                'amount' => $amount,
                'parent_code' => finrap_major_total_code_for_minor($taskCode),
            ];
        }
    }

    foreach ($minorTotals as $minorTotal) {
        if (!is_array($minorTotal)) {
            continue;
        }

        $parentCode = trim((string) ($minorTotal['parent_code'] ?? ''));
        $parentKey = strtolower($parentCode);
        if ($parentCode === '' || !isset($majorTotalsByCode[$parentKey])) {
            continue;
        }

        $majorTotalsByCode[$parentKey]['subtotals'][] = [
            'code' => (string) ($minorTotal['code'] ?? ''),
            'description' => (string) ($minorTotal['description'] ?? ''),
            'amount' => finance_to_float($minorTotal['amount'] ?? 0.0),
        ];
    }

    $majorTotals = array_values($majorTotalsByCode);
    usort($majorTotals, static function (array $left, array $right): int {
        return strnatcasecmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
    });

    foreach ($majorTotals as &$majorTotal) {
        if (!is_array($majorTotal) || !is_array($majorTotal['subtotals'] ?? null)) {
            continue;
        }

        usort($majorTotal['subtotals'], static function (array $left, array $right): int {
            return strnatcasecmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
        });
    }
    unset($majorTotal);

    $totalAmount = 0.0;
    foreach ($majorTotals as $majorTotal) {
        if (!is_array($majorTotal)) {
            continue;
        }

        $totalAmount = finance_add_amount($totalAmount, finance_to_float($majorTotal['amount'] ?? 0.0));
    }

    return [
        'report_id' => (string) ($latest['report_id'] ?? ''),
        'fetched_at' => (string) ($latest['fetched_at'] ?? ''),
        'auto_report' => (bool) ($latest['auto_report'] ?? false),
        'cost_field' => $costField,
        'major_totals' => $majorTotals,
        'total_amount' => $totalAmount,
    ];
}

function finrap_list_cached_months(string $company, string $projectNo): array
{
    $dir = finrap_cache_dir();
    $safeCompany = finrap_normalize_company($company);
    $safeProject = preg_replace('/[^a-z0-9_-]/i', '_', finrap_normalize_project_no($projectNo));
    $prefix = $safeCompany . '_' . $safeProject . '_';

    $entries = @scandir($dir);
    if (!is_array($entries)) {
        return [];
    }

    $result = [];
    foreach ($entries as $entry) {
        if (!str_starts_with($entry, $prefix) || !str_ends_with($entry, '.json')) {
            continue;
        }

        $yearMonth = substr($entry, strlen($prefix), -5);
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            continue;
        }

        $payload = finrap_load($company, $projectNo, $yearMonth);
        $result[] = [
            'year_month' => $yearMonth,
            'fetched_at' => is_array($payload) ? (string) ($payload['fetched_at'] ?? '') : '',
        ];
    }

    usort($result, static function (array $left, array $right): int {
        return strcmp((string) ($right['year_month'] ?? ''), (string) ($left['year_month'] ?? ''));
    });

    return $result;
}

function finrap_company_entity_url_with_query(string $baseUrl, string $environment, string $company, string $entitySet, array $query): string
{
    $safeCompany = str_replace("'", "''", trim($company));
    $companySegment = "Company('" . rawurlencode($safeCompany) . "')";
    $url = rtrim($baseUrl, '/') . '/' . rawurlencode($environment) . '/ODataV4/' . $companySegment . '/' . rawurlencode($entitySet);

    if ($query !== []) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    return $url;
}

function finrap_odata_ttl_for_month(string $yearMonth): int
{
    $day = 86400;
    $week = 604800;
    $year = 31536000;

    $target = DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
    if (!$target instanceof DateTimeImmutable) {
        return $day;
    }

    $currentMonth = (new DateTimeImmutable('first day of this month'))->format('Y-m');
    if ($yearMonth === $currentMonth) {
        return $day;
    }

    $previousMonth = (new DateTimeImmutable('first day of last month'))->format('Y-m');
    if ($yearMonth === $previousMonth) {
        return $week;
    }

    return $year;
}

function finrap_is_formatted_task_no(string $taskNo): bool
{
    return preg_match('/^\d{3}-\d{3}-\d{3}$/', trim($taskNo)) === 1;
}

function finrap_task_no_to_numeric(string $taskNo): ?int
{
    $value = trim($taskNo);
    if (!finrap_is_formatted_task_no($value)) {
        return null;
    }

    $parts = explode('-', $value);
    return ((int) $parts[0] * 1000000) + ((int) $parts[1] * 1000) + (int) $parts[2];
}

function finrap_parse_totaling_range(string $totaling): ?array
{
    $text = trim($totaling);
    if ($text === '') {
        return null;
    }

    if (!preg_match('/^(\d{3}-\d{3}-\d{3})\.\.(\d{3}-\d{3}-\d{3})$/', $text, $matches)) {
        return null;
    }

    $fromNumeric = finrap_task_no_to_numeric($matches[1]);
    $toNumeric = finrap_task_no_to_numeric($matches[2]);
    if ($fromNumeric === null || $toNumeric === null || $fromNumeric > $toNumeric) {
        return null;
    }

    return [
        'from_numeric' => $fromNumeric,
        'to_numeric' => $toNumeric,
    ];
}

function finrap_task_no_in_range(string $taskNo, array $range): bool
{
    $taskNumeric = finrap_task_no_to_numeric($taskNo);
    if ($taskNumeric === null) {
        return false;
    }

    return $taskNumeric >= (int) ($range['from_numeric'] ?? 0)
        && $taskNumeric <= (int) ($range['to_numeric'] ?? -1);
}

function finrap_is_total_task_type(string $taskType): bool
{
    return str_contains(strtolower(trim($taskType)), 'totaal');
}

function finrap_fetch_budget_hours_total(
    string $baseUrl,
    string $environment,
    string $company,
    array $auth,
    string $projectFilter,
    array $allowedTaskKeys,
    int $ttl
): ?float {
    if (trim(FINRAP_BUDGET_HOURS_ENTITY_SET) === '' || trim(FINRAP_BUDGET_HOURS_FIELD) === '') {
        return null;
    }

    $hoursFilter = $projectFilter
        . ' and ' . FINRAP_BUDGET_HOURS_FILTER_BASELINE_FIELD . ' eq true'
        . " and " . FINRAP_BUDGET_HOURS_FILTER_TYPE_FIELD . " eq '" . str_replace("'", "''", FINRAP_BUDGET_HOURS_FILTER_TYPE_VALUE) . "'"
        . " and " . FINRAP_BUDGET_HOURS_FILTER_RESOURCE_TYPE_FIELD . " eq '" . str_replace("'", "''", FINRAP_BUDGET_HOURS_FILTER_RESOURCE_TYPE_VALUE) . "'"
        . " and " . FINRAP_BUDGET_HOURS_FILTER_UOM_FIELD . " eq '" . str_replace("'", "''", FINRAP_BUDGET_HOURS_FILTER_UOM_VALUE) . "'";

    try {
        $hoursUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, FINRAP_BUDGET_HOURS_ENTITY_SET, [
            '$select' => 'Job_Task_No,' . FINRAP_BUDGET_HOURS_FIELD,
            '$filter' => $hoursFilter,
        ]);
        $hoursRows = odata_get_all($hoursUrl, $auth, $ttl);
    } catch (Throwable $ignoredBudgetHoursLoadError) {
        return null;
    }

    $allowedLookup = [];
    foreach ($allowedTaskKeys as $allowedTaskKey) {
        if (!is_string($allowedTaskKey) || trim($allowedTaskKey) === '') {
            continue;
        }
        $allowedLookup[strtolower(trim($allowedTaskKey))] = true;
    }

    $totalHours = 0.0;
    $hasRows = false;
    foreach ($hoursRows as $hoursRow) {
        if (!is_array($hoursRow)) {
            continue;
        }

        $taskNo = trim((string) ($hoursRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if ($allowedLookup !== [] && !isset($allowedLookup[$taskKey])) {
            continue;
        }

        $totalHours = finance_add_amount($totalHours, finance_to_float($hoursRow[FINRAP_BUDGET_HOURS_FIELD] ?? 0.0));
        $hasRows = true;
    }

    if ($hasRows) {
        return $totalHours;
    }

    return null;
}

function finrap_baseline_row_counts_as_budget_revenue(array $baselineRow): bool
{
    if (trim(FINRAP_BUDGET_REVENUE_TYPE) === '' || trim(FINRAP_BUDGET_REVENUE_NO) === '') {
        return false;
    }

    $type = trim((string) ($baselineRow['Type'] ?? ''));
    $no = trim((string) ($baselineRow['No'] ?? ''));

    return strcasecmp($type, FINRAP_BUDGET_REVENUE_TYPE) === 0
        && $no === FINRAP_BUDGET_REVENUE_NO;
}

function finrap_fetch_filtered_baseline_rows(
    string $baseUrl,
    string $environment,
    string $company,
    array $auth,
    string $projectFilter,
    int $ttl
): array {
    if (trim(FINRAP_BUDGET_HOURS_ENTITY_SET) === '') {
        return [];
    }

    $selectFields = ['Job_Task_No', 'Type', 'No'];
    if (trim(FINRAP_BUDGET_REVENUE_FIELD) !== '') {
        $selectFields[] = FINRAP_BUDGET_REVENUE_FIELD;
    }

    if (count($selectFields) === 1) {
        return [];
    }

    $baselineFilter = $projectFilter
        . ' and ' . FINRAP_BUDGET_HOURS_FILTER_BASELINE_FIELD . ' eq true';

    if (trim(FINRAP_BUDGET_REVENUE_TYPE) !== '') {
        $baselineFilter .= " and Type eq '" . str_replace("'", "''", FINRAP_BUDGET_REVENUE_TYPE) . "'";
    }

    if (trim(FINRAP_BUDGET_REVENUE_NO) !== '') {
        $baselineFilter .= " and No eq '" . str_replace("'", "''", FINRAP_BUDGET_REVENUE_NO) . "'";
    }

    try {
        $baselineUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, FINRAP_BUDGET_HOURS_ENTITY_SET, [
            '$select' => implode(',', $selectFields),
            '$filter' => $baselineFilter,
        ]);

        return odata_get_all($baselineUrl, $auth, $ttl);
    } catch (Throwable $ignoredBaselineLoadError) {
        return [];
    }
}

function finrap_parse_baseline_amounts_by_task(array $baselineRows, array $allowedTaskKeys): array
{
    $allowedLookup = [];
    foreach ($allowedTaskKeys as $allowedTaskKey) {
        if (!is_string($allowedTaskKey) || trim($allowedTaskKey) === '') {
            continue;
        }
        $allowedLookup[strtolower(trim($allowedTaskKey))] = true;
    }

    $costsByTask = [];
    $revenueByTask = [];
    foreach ($baselineRows as $baselineRow) {
        if (!is_array($baselineRow)) {
            continue;
        }

        $taskNo = trim((string) ($baselineRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if ($allowedLookup !== [] && !isset($allowedLookup[$taskKey])) {
            continue;
        }

        if (trim(FINRAP_BUDGET_REVENUE_FIELD) !== '' && finrap_baseline_row_counts_as_budget_revenue($baselineRow)) {
            $revenueByTask[$taskKey] = finance_add_amount(
                (float) ($revenueByTask[$taskKey] ?? 0.0),
                finance_to_float($baselineRow[FINRAP_BUDGET_REVENUE_FIELD] ?? 0.0)
            );
        }
    }

    return [
        'costs' => $costsByTask,
        'revenue' => $revenueByTask,
    ];
}

function finrap_project_task_entity_select_fields(string $entitySet): array
{
    $selectFields = [
        'Job_No',
        'Job_Task_No',
        FINRAP_PROJECT_TASK_CONTRACT_FIELD,
        FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD,
    ];

    if (strcasecmp(trim($entitySet), FINRAP_PROJECT_TASK_ENTITY_SET) === 0) {
        $selectFields[] = FINRAP_PROJECT_TASK_BASELINE_COST_FIELD;
        $selectFields[] = FINRAP_PROJECT_TASK_PURCHASES_FIELD;
        $selectFields[] = FINRAP_PROJECT_TASK_INVOICED_PRICE_FIELD;
    }

    return $selectFields;
}

function finrap_project_task_row_matches_project(array $projectTaskRow, string $projectNo): bool
{
    $expectedProjectNo = trim($projectNo);
    if ($expectedProjectNo === '') {
        return true;
    }

    $rowProjectNo = trim((string) ($projectTaskRow['Job_No'] ?? ''));
    if ($rowProjectNo === '') {
        return false;
    }

    return strcasecmp($rowProjectNo, $expectedProjectNo) === 0;
}

function finrap_parse_project_task_contract_by_task(
    array $projectTaskRows,
    array $allowedTaskKeys,
    string $projectNo
): array {
    $allowedLookup = [];
    foreach ($allowedTaskKeys as $allowedTaskKey) {
        if (!is_string($allowedTaskKey) || trim($allowedTaskKey) === '') {
            continue;
        }
        $allowedLookup[strtolower(trim($allowedTaskKey))] = true;
    }

    $contractByTask = [];
    foreach ($projectTaskRows as $projectTaskRow) {
        if (!is_array($projectTaskRow)) {
            continue;
        }

        if (!finrap_project_task_row_matches_project($projectTaskRow, $projectNo)) {
            continue;
        }

        $taskNo = trim((string) ($projectTaskRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if ($allowedLookup !== [] && !isset($allowedLookup[$taskKey])) {
            continue;
        }

        $contractByTask[$taskKey] = finance_add_amount(
            (float) ($contractByTask[$taskKey] ?? 0.0),
            finance_to_float($projectTaskRow[FINRAP_PROJECT_TASK_CONTRACT_FIELD] ?? 0.0)
        );
    }

    return $contractByTask;
}

function finrap_parse_project_task_amounts_by_task(
    array $projectTaskRows,
    array $allowedTaskKeys,
    string $projectNo,
    string $fieldName
): array {
    $fieldName = trim($fieldName);
    if ($fieldName === '') {
        return [];
    }

    $allowedLookup = [];
    foreach ($allowedTaskKeys as $allowedTaskKey) {
        if (!is_string($allowedTaskKey) || trim($allowedTaskKey) === '') {
            continue;
        }
        $allowedLookup[strtolower(trim($allowedTaskKey))] = true;
    }

    $amountsByTask = [];
    foreach ($projectTaskRows as $projectTaskRow) {
        if (!is_array($projectTaskRow)) {
            continue;
        }

        if (!finrap_project_task_row_matches_project($projectTaskRow, $projectNo)) {
            continue;
        }

        $taskNo = trim((string) ($projectTaskRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if ($allowedLookup !== [] && !isset($allowedLookup[$taskKey])) {
            continue;
        }

        $amountsByTask[$taskKey] = finance_add_amount(
            (float) ($amountsByTask[$taskKey] ?? 0.0),
            finance_to_float($projectTaskRow[$fieldName] ?? 0.0)
        );
    }

    return $amountsByTask;
}

function finrap_parse_project_task_baseline_costs_by_task(
    array $projectTaskRows,
    array $allowedTaskKeys,
    string $projectNo = ''
): array {
    return finrap_parse_project_task_amounts_by_task(
        $projectTaskRows,
        $allowedTaskKeys,
        $projectNo,
        FINRAP_PROJECT_TASK_BASELINE_COST_FIELD
    );
}

function finrap_task_row_has_change_order(array $row): bool
{
    return trim((string) ($row[FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD] ?? $row['Job_Change_Order_No'] ?? '')) !== '';
}

function finrap_parse_project_task_change_orders_by_task(array $projectTaskRows, string $projectNo = ''): array
{
    $changeOrderByTask = [];
    foreach ($projectTaskRows as $projectTaskRow) {
        if (!is_array($projectTaskRow)) {
            continue;
        }

        if (!finrap_project_task_row_matches_project($projectTaskRow, $projectNo)) {
            continue;
        }

        $taskNo = trim((string) ($projectTaskRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $changeOrderByTask[strtolower($taskNo)] = trim((string) ($projectTaskRow[FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD] ?? ''));
    }

    return $changeOrderByTask;
}

function finrap_task_row_matches_change_order_group(string $taskChangeOrder, string $groupChangeOrder): bool
{
    $groupChangeOrder = trim($groupChangeOrder);
    $taskChangeOrder = trim($taskChangeOrder);

    if ($groupChangeOrder === '') {
        return $taskChangeOrder === '';
    }

    return strcasecmp($taskChangeOrder, $groupChangeOrder) === 0;
}

function finrap_is_project_root_total_task_code(string $taskCode): bool
{
    return strcasecmp(trim($taskCode), FINRAP_PROJECT_ROOT_TOTAL_TASK_CODE) === 0;
}

function finrap_find_project_root_total_row(array $taskRows): ?array
{
    $legacyGlobalTotalRow = null;
    $majorTotalRows = [];

    foreach ($taskRows as $taskRow) {
        if (!is_array($taskRow) || !(bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $taskCode = trim((string) ($taskRow['Cost_Group_Code'] ?? ''));
        if ($taskCode === '') {
            continue;
        }

        if (finrap_is_project_root_total_task_code($taskCode)) {
            return $taskRow;
        }

        if (strcasecmp($taskCode, FINRAP_GLOBAL_TOTAL_TASK_NO) === 0) {
            $legacyGlobalTotalRow = $taskRow;
            continue;
        }

        if (finrap_is_major_total_task_code($taskCode)) {
            $majorTotalRows[] = $taskRow;
        }
    }

    if ($legacyGlobalTotalRow !== null) {
        return $legacyGlobalTotalRow;
    }

    if (count($majorTotalRows) === 1) {
        return $majorTotalRows[0];
    }

    return null;
}

function finrap_collect_prj_header_task_keys(array $taskRows, array $changeOrderByTask): array
{
    $rootTotalRow = finrap_find_project_root_total_row($taskRows);
    $range = null;
    if ($rootTotalRow !== null) {
        $range = finrap_parse_totaling_range((string) ($rootTotalRow['Totaling'] ?? ''));
    }

    $allowedKeys = [];
    foreach ($taskRows as $taskRow) {
        if (!is_array($taskRow) || !finrap_is_detail_task_row($taskRow)) {
            continue;
        }

        $taskCode = trim((string) ($taskRow['Cost_Group_Code'] ?? ''));
        if ($taskCode === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskCode)) {
            continue;
        }

        if ($range !== null && !finrap_task_no_in_range($taskCode, $range)) {
            continue;
        }

        $taskKey = strtolower($taskCode);
        if (trim((string) ($changeOrderByTask[$taskKey] ?? '')) !== '') {
            continue;
        }

        $allowedKeys[$taskKey] = true;
    }

    if ($allowedKeys !== []) {
        return array_keys($allowedKeys);
    }

    if ($rootTotalRow === null || $range === null) {
        return finrap_collect_prj_header_task_keys_without_total_row($taskRows, $changeOrderByTask);
    }

    return [];
}

function finrap_collect_prj_header_task_keys_without_total_row(array $taskRows, array $changeOrderByTask): array
{
    $allowedKeys = [];
    foreach ($taskRows as $taskRow) {
        if (!is_array($taskRow) || !finrap_is_detail_task_row($taskRow)) {
            continue;
        }

        $taskCode = trim((string) ($taskRow['Cost_Group_Code'] ?? ''));
        if ($taskCode === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskCode)) {
            continue;
        }

        $taskKey = strtolower($taskCode);
        if (trim((string) ($changeOrderByTask[$taskKey] ?? '')) !== '') {
            continue;
        }

        $allowedKeys[$taskKey] = true;
    }

    return array_keys($allowedKeys);
}

function finrap_task_metrics_from_total_row(?array $totalRow): ?array
{
    if ($totalRow === null) {
        return null;
    }

    $budgetCost = finance_to_float($totalRow['Budget_Cost'] ?? 0.0);
    $eac = finance_to_float($totalRow['EAC'] ?? 0.0);

    return [
        'Budget_Revenue' => finance_to_float($totalRow['Budget_Revenue'] ?? 0.0),
        'Budget_Cost' => $budgetCost,
        'EAC' => $eac,
        'Booked_Cost' => finance_to_float($totalRow['Booked_Cost'] ?? 0.0),
        'Entered_Obligations' => finance_to_float($totalRow['Entered_Obligations'] ?? 0.0),
        'Variance_Budget_EAC' => finance_to_float(
            $totalRow['Variance_Budget_EAC'] ?? finance_calculate_result($budgetCost, $eac)
        ),
    ];
}

function finrap_sum_project_task_amount_for_task_keys(
    array $projectTaskRows,
    string $projectNo,
    string $fieldName,
    array $allowedTaskKeys,
    bool $skipZeroContractForContractField = false
): float {
    $fieldName = trim($fieldName);
    if ($fieldName === '' || $allowedTaskKeys === []) {
        return 0.0;
    }

    $allowedLookup = [];
    foreach ($allowedTaskKeys as $allowedTaskKey) {
        if (!is_string($allowedTaskKey) || trim($allowedTaskKey) === '') {
            continue;
        }
        $allowedLookup[strtolower(trim($allowedTaskKey))] = true;
    }

    if ($allowedLookup === []) {
        return 0.0;
    }

    $total = 0.0;
    foreach ($projectTaskRows as $projectTaskRow) {
        if (!is_array($projectTaskRow)) {
            continue;
        }

        if (!finrap_project_task_row_matches_project($projectTaskRow, $projectNo)) {
            continue;
        }

        $taskNo = trim((string) ($projectTaskRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if (!isset($allowedLookup[$taskKey])) {
            continue;
        }

        if (
            $skipZeroContractForContractField
            && strcasecmp($fieldName, FINRAP_PROJECT_TASK_CONTRACT_FIELD) === 0
            && abs(finance_to_float($projectTaskRow[FINRAP_PROJECT_TASK_CONTRACT_FIELD] ?? 0.0)) < 0.000001
        ) {
            continue;
        }

        $total = finance_add_amount($total, finance_to_float($projectTaskRow[$fieldName] ?? 0.0));
    }

    return $total;
}

function finrap_aggregate_detail_task_metrics_for_task_keys(array $detailTaskRows, array $allowedTaskKeys): array
{
    if ($allowedTaskKeys === []) {
        return [
            'Contract_Value' => 0.0,
            'Budget_Revenue' => 0.0,
            'Budget_Cost' => 0.0,
            'EAC' => 0.0,
            'Booked_Cost' => 0.0,
            'Entered_Obligations' => 0.0,
            'Variance_Budget_EAC' => 0.0,
        ];
    }

    $allowedLookup = [];
    foreach ($allowedTaskKeys as $allowedTaskKey) {
        if (!is_string($allowedTaskKey) || trim($allowedTaskKey) === '') {
            continue;
        }
        $allowedLookup[strtolower(trim($allowedTaskKey))] = true;
    }

    $contractValueTotal = 0.0;
    $budgetRevenueTotal = 0.0;
    $budgetTotal = 0.0;
    $eacTotal = 0.0;
    $bookedTotal = 0.0;
    $obligationTotal = 0.0;
    $varianceTotal = 0.0;

    foreach ($detailTaskRows as $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $taskKey = strtolower(trim((string) ($taskRow['Cost_Group_Code'] ?? '')));
        if ($taskKey === '' || !isset($allowedLookup[$taskKey])) {
            continue;
        }

        $contractValueTotal = finance_add_amount($contractValueTotal, finance_to_float($taskRow['Contract_Value'] ?? 0.0));
        $budgetRevenueTotal = finance_add_amount($budgetRevenueTotal, finance_to_float($taskRow['Budget_Revenue'] ?? 0.0));
        $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($taskRow['Budget_Cost'] ?? 0.0));
        $eacTotal = finance_add_amount($eacTotal, finance_to_float($taskRow['EAC'] ?? 0.0));
        $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($taskRow['Booked_Cost'] ?? 0.0));
        $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($taskRow['Entered_Obligations'] ?? 0.0));
        $varianceTotal = finance_add_amount($varianceTotal, finance_to_float($taskRow['Variance_Budget_EAC'] ?? 0.0));
    }

    return [
        'Contract_Value' => $contractValueTotal,
        'Budget_Revenue' => $budgetRevenueTotal,
        'Budget_Cost' => $budgetTotal,
        'EAC' => $eacTotal,
        'Booked_Cost' => $bookedTotal,
        'Entered_Obligations' => $obligationTotal,
        'Variance_Budget_EAC' => $varianceTotal,
    ];
}

function finrap_sum_project_task_amount_for_change_order_group(
    array $projectTaskRows,
    string $projectNo,
    string $fieldName,
    string $groupChangeOrder,
    bool $skipZeroContractForContractField = false
): float {
    $fieldName = trim($fieldName);
    if ($fieldName === '') {
        return 0.0;
    }

    $total = 0.0;
    foreach ($projectTaskRows as $projectTaskRow) {
        if (!is_array($projectTaskRow)) {
            continue;
        }

        if (!finrap_project_task_row_matches_project($projectTaskRow, $projectNo)) {
            continue;
        }

        $taskNo = trim((string) ($projectTaskRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskChangeOrder = trim((string) ($projectTaskRow[FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD] ?? ''));
        if (!finrap_task_row_matches_change_order_group($taskChangeOrder, $groupChangeOrder)) {
            continue;
        }

        if (
            $skipZeroContractForContractField
            && strcasecmp($fieldName, FINRAP_PROJECT_TASK_CONTRACT_FIELD) === 0
            && abs(finance_to_float($projectTaskRow[FINRAP_PROJECT_TASK_CONTRACT_FIELD] ?? 0.0)) < 0.000001
        ) {
            continue;
        }

        $total = finance_add_amount($total, finance_to_float($projectTaskRow[$fieldName] ?? 0.0));
    }

    return $total;
}

function finrap_aggregate_detail_task_metrics_for_change_order(
    array $detailTaskRows,
    array $changeOrderByTask,
    string $groupChangeOrder
): array {
    $budgetRevenueTotal = 0.0;
    $budgetTotal = 0.0;
    $eacTotal = 0.0;
    $bookedTotal = 0.0;
    $obligationTotal = 0.0;
    $varianceTotal = 0.0;

    foreach ($detailTaskRows as $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $taskKey = strtolower(trim((string) ($taskRow['Cost_Group_Code'] ?? '')));
        if ($taskKey === '') {
            continue;
        }

        $taskChangeOrder = trim((string) ($changeOrderByTask[$taskKey] ?? ''));
        if (!finrap_task_row_matches_change_order_group($taskChangeOrder, $groupChangeOrder)) {
            continue;
        }

        $budgetRevenueTotal = finance_add_amount($budgetRevenueTotal, finance_to_float($taskRow['Budget_Revenue'] ?? 0.0));
        $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($taskRow['Budget_Cost'] ?? 0.0));
        $eacTotal = finance_add_amount($eacTotal, finance_to_float($taskRow['EAC'] ?? 0.0));
        $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($taskRow['Booked_Cost'] ?? 0.0));
        $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($taskRow['Entered_Obligations'] ?? 0.0));
        $varianceTotal = finance_add_amount($varianceTotal, finance_to_float($taskRow['Variance_Budget_EAC'] ?? 0.0));
    }

    return [
        'Budget_Revenue' => $budgetRevenueTotal,
        'Budget_Cost' => $budgetTotal,
        'EAC' => $eacTotal,
        'Booked_Cost' => $bookedTotal,
        'Entered_Obligations' => $obligationTotal,
        'Variance_Budget_EAC' => $varianceTotal,
    ];
}

function finrap_build_single_header_metric_row(
    string $type,
    bool $isProjectRow,
    string $groupChangeOrder,
    array $projectTaskRows,
    array $allTaskRows,
    array $detailTaskRows,
    array $changeOrderByTask,
    string $projectNo,
    float $installmentsReceived
): array {
    if ($isProjectRow && $groupChangeOrder === '') {
        $prjTaskKeys = finrap_collect_prj_header_task_keys($allTaskRows, $changeOrderByTask);
        $contractValue = finrap_sum_project_task_amount_for_task_keys(
            $projectTaskRows,
            $projectNo,
            FINRAP_PROJECT_TASK_CONTRACT_FIELD,
            $prjTaskKeys,
            true
        );
        $installmentsInvoiced = finrap_sum_project_task_amount_for_task_keys(
            $projectTaskRows,
            $projectNo,
            FINRAP_PROJECT_TASK_INVOICED_PRICE_FIELD,
            $prjTaskKeys
        );
        $taskMetrics = finrap_task_metrics_from_total_row(finrap_find_project_root_total_row($allTaskRows));
        if ($taskMetrics === null) {
            $taskMetrics = finrap_aggregate_detail_task_metrics_for_change_order(
                $detailTaskRows,
                $changeOrderByTask,
                ''
            );
        }
    } else {
        $contractValue = finrap_sum_project_task_amount_for_change_order_group(
            $projectTaskRows,
            $projectNo,
            FINRAP_PROJECT_TASK_CONTRACT_FIELD,
            $groupChangeOrder,
            true
        );
        $installmentsInvoiced = finrap_sum_project_task_amount_for_change_order_group(
            $projectTaskRows,
            $projectNo,
            FINRAP_PROJECT_TASK_INVOICED_PRICE_FIELD,
            $groupChangeOrder
        );
        $taskMetrics = finrap_aggregate_detail_task_metrics_for_change_order(
            $detailTaskRows,
            $changeOrderByTask,
            $groupChangeOrder
        );
    }

    $totalDirectCost = finance_to_float($taskMetrics['Budget_Cost'] ?? 0.0);
    $grossProfit = $contractValue - $totalDirectCost;
    $variance = finance_to_float($taskMetrics['Variance_Budget_EAC'] ?? 0.0);

    $row = [
        'type' => $type,
        'is_project_row' => $isProjectRow,
        'contract_value' => $contractValue,
        'budget_revenue' => finance_to_float($taskMetrics['Budget_Revenue'] ?? 0.0),
        'total_direct_cost' => $totalDirectCost,
        'gross_profit' => $grossProfit,
        'booked_cost' => finance_to_float($taskMetrics['Booked_Cost'] ?? 0.0),
        'entered_obligations' => finance_to_float($taskMetrics['Entered_Obligations'] ?? 0.0),
        'order_result' => $grossProfit + $variance,
        'installments_invoiced' => $installmentsInvoiced,
    ];

    if ($isProjectRow) {
        $row['installments_received'] = $installmentsReceived;
    }

    return $row;
}

function finrap_fetch_project_task_rows(
    string $baseUrl,
    string $environment,
    string $company,
    array $auth,
    string $projectFilter,
    int $ttl
): array {
    foreach ([FINRAP_PROJECT_TASK_ENTITY_SET, FINRAP_PROJECT_TASK_ENTITY_SET_FALLBACK] as $entitySet) {
        $entitySet = trim($entitySet);
        if ($entitySet === '') {
            continue;
        }

        $selectFields = finrap_project_task_entity_select_fields($entitySet);
        if ($selectFields === []) {
            continue;
        }

        try {
            $taskUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, $entitySet, [
                '$select' => implode(',', $selectFields),
                '$filter' => $projectFilter,
            ]);

            return odata_get_all($taskUrl, $auth, $ttl);
        } catch (Throwable $ignoredProjectTaskLoadError) {
            continue;
        }
    }

    return [];
}

function finrap_parse_project_task_contract_groups(array $projectTaskRows, string $projectNo = ''): array
{
    $projectContract = 0.0;
    $changeOrders = [];
    $contractByTask = [];
    $changeOrderByTask = [];

    foreach ($projectTaskRows as $projectTaskRow) {
        if (!is_array($projectTaskRow)) {
            continue;
        }

        if (!finrap_project_task_row_matches_project($projectTaskRow, $projectNo)) {
            continue;
        }

        $taskNo = trim((string) ($projectTaskRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $contractPrice = finance_to_float($projectTaskRow[FINRAP_PROJECT_TASK_CONTRACT_FIELD] ?? 0.0);
        if (abs($contractPrice) < 0.000001) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        $contractByTask[$taskKey] = finance_add_amount(
            (float) ($contractByTask[$taskKey] ?? 0.0),
            $contractPrice
        );
        $changeOrderByTask[$taskKey] = trim((string) ($projectTaskRow[FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD] ?? ''));
    }

    foreach ($contractByTask as $taskKey => $contractPrice) {
        $changeOrderNo = trim((string) ($changeOrderByTask[$taskKey] ?? ''));
        if ($changeOrderNo === '') {
            $projectContract = finance_add_amount($projectContract, $contractPrice);
            continue;
        }

        $changeOrders[$changeOrderNo] = finance_add_amount(
            (float) ($changeOrders[$changeOrderNo] ?? 0.0),
            $contractPrice
        );
    }

    if ($changeOrders !== []) {
        uksort($changeOrders, static function (string $left, string $right): int {
            return strnatcasecmp($left, $right);
        });
    }

    return [
        'project_contract' => $projectContract,
        'change_orders' => $changeOrders,
    ];
}

function finrap_build_header_metric_rows(
    array $projectTaskRows,
    array $taskRows,
    string $projectNo,
    array $contractGroups,
    float $installmentsReceived
): array {
    $changeOrderByTask = finrap_parse_project_task_change_orders_by_task($projectTaskRows, $projectNo);
    $detailTaskRows = array_values(array_filter($taskRows, static function ($taskRow): bool {
        return is_array($taskRow) && finrap_is_detail_task_row($taskRow);
    }));

    $rows = [
        finrap_build_single_header_metric_row(
            'PRJ',
            true,
            '',
            $projectTaskRows,
            $taskRows,
            $detailTaskRows,
            $changeOrderByTask,
            $projectNo,
            $installmentsReceived
        ),
    ];

    $changeOrders = is_array($contractGroups['change_orders'] ?? null) ? $contractGroups['change_orders'] : [];
    foreach ($changeOrders as $changeOrderNo => $contractValue) {
        unset($contractValue);

        $typeLabel = trim((string) $changeOrderNo);
        if ($typeLabel === '') {
            continue;
        }

        $rows[] = finrap_build_single_header_metric_row(
            $typeLabel,
            false,
            $typeLabel,
            $projectTaskRows,
            $taskRows,
            $detailTaskRows,
            $changeOrderByTask,
            $projectNo,
            0.0
        );
    }

    return $rows;
}

function finrap_header_table_has_change_orders(array $headerMetricRows): bool
{
    foreach ($headerMetricRows as $row) {
        if (is_array($row) && !($row['is_project_row'] ?? true)) {
            return true;
        }
    }

    return false;
}

function finrap_fetch_baseline_amounts_by_task(
    string $baseUrl,
    string $environment,
    string $company,
    array $auth,
    string $projectFilter,
    array $allowedTaskKeys,
    int $ttl
): array {
    return finrap_parse_baseline_amounts_by_task(
        finrap_fetch_filtered_baseline_rows($baseUrl, $environment, $company, $auth, $projectFilter, $ttl),
        $allowedTaskKeys
    );
}

function finrap_fetch_project_task_contract_groups(
    string $baseUrl,
    string $environment,
    string $company,
    array $auth,
    string $projectFilter,
    int $ttl,
    ?array $projectTaskRows = null
): array {
    if ($projectTaskRows === null) {
        $projectTaskRows = finrap_fetch_project_task_rows($baseUrl, $environment, $company, $auth, $projectFilter, $ttl);
    }

    return finrap_parse_project_task_contract_groups($projectTaskRows, finrap_extract_project_no_from_filter($projectFilter));
}

function finrap_extract_project_no_from_filter(string $projectFilter): string
{
    if (preg_match("/Job_No\\s+eq\\s+'((?:''|[^'])*)'/i", trim($projectFilter), $matches) !== 1) {
        return '';
    }

    return str_replace("''", "'", (string) ($matches[1] ?? ''));
}

function finrap_fetch_baseline_costs_by_task(
    string $baseUrl,
    string $environment,
    string $company,
    array $auth,
    string $projectFilter,
    array $allowedTaskKeys,
    int $ttl
): array {
    return finrap_fetch_baseline_amounts_by_task(
        $baseUrl,
        $environment,
        $company,
        $auth,
        $projectFilter,
        $allowedTaskKeys,
        $ttl
    )['costs'];
}

function finrap_fetch_estimated_hours_total(
    string $baseUrl,
    string $environment,
    string $company,
    array $auth,
    string $projectFilter,
    int $ttl
): ?float {
    if (trim(FINRAP_ESTIMATED_HOURS_ENTITY_SET) === '' || trim(FINRAP_ESTIMATED_HOURS_FIELD) === '') {
        return null;
    }

    try {
        $hoursUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, FINRAP_ESTIMATED_HOURS_ENTITY_SET, [
            '$select' => 'Job_Task_No,' . FINRAP_ESTIMATED_HOURS_FIELD,
            '$filter' => $projectFilter . " and Job_Task_No eq '" . FINRAP_GLOBAL_TOTAL_TASK_NO . "'",
        ]);
        $hoursRows = odata_get_all($hoursUrl, $auth, $ttl);
    } catch (Throwable $ignoredEstimatedHoursLoadError) {
        return null;
    }

    foreach ($hoursRows as $hoursRow) {
        if (!is_array($hoursRow)) {
            continue;
        }

        if (strcasecmp(trim((string) ($hoursRow['Job_Task_No'] ?? '')), FINRAP_GLOBAL_TOTAL_TASK_NO) !== 0) {
            continue;
        }

        return finance_to_float($hoursRow[FINRAP_ESTIMATED_HOURS_FIELD] ?? 0.0);
    }

    return null;
}

function finrap_fetch_project(string $company, string $projectNo, int $ttl = 300): ?array
{
    global $baseUrl;

    $projectNoText = trim($projectNo);
    if ($projectNoText === '') {
        return null;
    }

    $environment = auth_get_environment_for_company($company, 300);
    $auth = auth_get_auth_for_environment($environment);

    $url = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'Projecten', [
        '$select' => 'No,Description,Bill_to_Customer_No,Bill_to_Name,Sell_to_Customer_No,Sell_to_Customer_Name,Project_Manager,Person_Responsible,KVT_Sales_Person_Code,Your_Reference,LVS_Your_reference,Creation_Date,Ending_Date,Percent_Completed,Recog_Profit_Amount',
        '$filter' => "No eq '" . str_replace("'", "''", $projectNoText) . "'",
    ]);

    $rows = odata_get_all($url, $auth, $ttl);
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        if (strcasecmp(trim((string) ($row['No'] ?? '')), $projectNoText) === 0) {
            return $row;
        }
    }

    return null;
}

function finrap_collect_modal_data(string $company, string $projectNo, int $ttl): array
{
    global $baseUrl;

    $environment = auth_get_environment_for_company($company, 300);
    $auth = auth_get_auth_for_environment($environment);
    $escapedProject = str_replace("'", "''", trim($projectNo));
    $projectFilter = "Job_No eq '" . $escapedProject . "'";

    $modal = [
        'project_no' => $projectNo,
        'contract_value' => 0.0,
        'header_metric_rows' => [],
        'installments_received' => 0.0,
        'budget_cost_total' => 0.0,
        'task_rows' => [],
        'task_rows_total' => [],
    ];

    $baselineRows = finrap_fetch_filtered_baseline_rows($baseUrl, $environment, $company, $auth, $projectFilter, $ttl);
    $projectTaskRows = finrap_fetch_project_task_rows($baseUrl, $environment, $company, $auth, $projectFilter, $ttl);
    $contractGroups = finrap_fetch_project_task_contract_groups(
        $baseUrl,
        $environment,
        $company,
        $auth,
        $projectFilter,
        $ttl,
        $projectTaskRows
    );

    try {
        $contractUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'FactureerbareProjectPlanningsRegels', [
            '$select' => 'Job_No,Line_No,Line_Type,Job_Task_No,Description,Document_No,Line_Amount_LCY,Qty_Invoiced,Planning_Date,Invoiced_Amount_LCY,LVS_Document_Status,' . FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD,
            '$filter' => $projectFilter,
        ]);
        $contractRows = odata_get_all($contractUrl, $auth, $ttl);
    } catch (Throwable $ignoredContractLoadError) {
        $contractRows = [];
    }

    $termijnLines = [];
    foreach ($contractRows as $contractRow) {
        if (!is_array($contractRow)) {
            continue;
        }

        $lineType = strtolower(trim((string) ($contractRow['Line_Type'] ?? '')));
        $isFactureerbaar = str_contains($lineType, 'factureer');
        $isForecast = str_contains($lineType, 'prognose') || str_contains($lineType, 'forecast');
        if (!$isFactureerbaar || $isForecast) {
            continue;
        }

        $termijnLines[] = [
            'line_no' => (int) ($contractRow['Line_No'] ?? 0),
            'document_no' => trim((string) ($contractRow['Document_No'] ?? '')),
            'description' => trim((string) ($contractRow['Description'] ?? '')),
            'change_order_no' => trim((string) ($contractRow[FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD] ?? '')),
            'amount' => finance_to_float($contractRow['Line_Amount_LCY'] ?? 0.0),
            'planning_date' => (string) ($contractRow['Planning_Date'] ?? ''),
            'invoiced_amount' => finance_to_float($contractRow['Invoiced_Amount_LCY'] ?? 0.0),
        ];
    }

    $termijnLines = finrap_sort_termijn_lines_by_change_order($termijnLines);

    try {
        $customerUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'Customer_Ledger_Entries', [
            '$select' => 'Entry_No,Amount_LCY,Remaining_Amt_LCY,Sales_LCY,Posting_Date,Due_Date,Closed_at_Date,Description,Global_Dimension_2_Code',
            '$filter' => "Global_Dimension_2_Code eq '" . $escapedProject . "'",
        ]);
        $customerRows = odata_get_all($customerUrl, $auth, $ttl);
    } catch (Throwable $ignoredCustomerLoadError) {
        $customerRows = [];
    }

    $termijnLines = finrap_enrich_termijn_lines_with_customer_ledger($termijnLines, $customerRows);
    $modal['termijn_lines'] = $termijnLines;

    foreach ($customerRows as $customerRow) {
        if (!is_array($customerRow)) {
            continue;
        }

        $receivedAmount = finance_to_float($customerRow['Sales_LCY'] ?? 0.0)
            - finance_to_float($customerRow['Remaining_Amt_LCY'] ?? 0.0);
        $modal['installments_received'] = finance_add_amount(
            (float) ($modal['installments_received'] ?? 0.0),
            $receivedAmount
        );
    }

    try {
        $taskUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'ProjectenJobTaskLines', [
            '$select' => 'Job_No,Job_Task_No,Description,Job_Task_Type,Totaling',
            '$filter' => $projectFilter,
        ]);
        $taskRows = odata_get_all($taskUrl, $auth, $ttl);
    } catch (Throwable $ignoredTaskLoadError) {
        $taskRows = [];
    }

    $taskRowsByKey = [];
    foreach ($taskRows as $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $taskNo = trim((string) ($taskRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        $taskKey = strtolower($taskNo);
        $isDisplayRow = !FINRAP_FORMATTED_TASK_NOS_ONLY || finrap_is_formatted_task_no($taskNo);
        $taskRowsByKey[$taskKey] = [
            'Cost_Group_Code' => $taskNo,
            'Cost_Group_Description' => (string) ($taskRow['Description'] ?? ''),
            'Job_Task_Type' => (string) ($taskRow['Job_Task_Type'] ?? ''),
            'Totaling' => (string) ($taskRow['Totaling'] ?? ''),
            'Contract_Value' => 0.0,
            'Budget_Revenue' => 0.0,
            'Budget_Cost' => 0.0,
            'EAC' => 0.0,
            'Booked_Cost' => 0.0,
            'Entered_Obligations' => 0.0,
            'Invoiced_Amount' => 0.0,
            'Variance_Budget_EAC' => 0.0,
            'Job_Change_Order_No' => '',
            'Is_Total_Row' => finrap_is_total_task_type((string) ($taskRow['Job_Task_Type'] ?? '')),
            'Is_Display_Row' => $isDisplayRow,
        ];
    }

    $changeOrderByTask = finrap_parse_project_task_change_orders_by_task($projectTaskRows, $projectNo);
    foreach ($taskRowsByKey as $taskKey => &$taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $taskRow['Job_Change_Order_No'] = trim((string) ($changeOrderByTask[$taskKey] ?? ''));
    }
    unset($taskRow);

    foreach ($contractRows as $contractRow) {
        if (!is_array($contractRow)) {
            continue;
        }

        $taskNo = trim((string) ($contractRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if (!isset($taskRowsByKey[$taskKey])) {
            continue;
        }

        $lineAmount = finance_to_float($contractRow['Line_Amount_LCY'] ?? 0.0);
        $qtyInvoiced = finance_to_float($contractRow['Qty_Invoiced'] ?? 0.0);
        $taskRowsByKey[$taskKey]['Invoiced_Amount'] = finance_add_amount(
            (float) ($taskRowsByKey[$taskKey]['Invoiced_Amount'] ?? 0.0),
            $lineAmount * $qtyInvoiced
        );
    }

    try {
        $ledgerUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'JobLedgerEntries', [
            '$select' => 'Job_No,Job_Task_No,Total_Cost_LCY',
            '$filter' => $projectFilter,
        ]);
        $ledgerRows = odata_get_all($ledgerUrl, $auth, $ttl);
    } catch (Throwable $ignoredLedgerLoadError) {
        $ledgerRows = [];
    }

    foreach ($ledgerRows as $ledgerRow) {
        if (!is_array($ledgerRow)) {
            continue;
        }

        $taskNo = trim((string) ($ledgerRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if (!isset($taskRowsByKey[$taskKey])) {
            continue;
        }

        $taskRowsByKey[$taskKey]['Booked_Cost'] = finance_add_amount(
            (float) ($taskRowsByKey[$taskKey]['Booked_Cost'] ?? 0.0),
            finance_to_float($ledgerRow['Total_Cost_LCY'] ?? 0.0)
        );
    }

    $baselineAmountsByTask = finrap_parse_baseline_amounts_by_task($baselineRows, array_keys($taskRowsByKey));
    $baselineCostsByTask = finrap_parse_project_task_baseline_costs_by_task(
        $projectTaskRows,
        array_keys($taskRowsByKey),
        $projectNo
    );
    $purchasesByTask = finrap_parse_project_task_amounts_by_task(
        $projectTaskRows,
        array_keys($taskRowsByKey),
        $projectNo,
        FINRAP_PROJECT_TASK_PURCHASES_FIELD
    );
    $contractByTask = finrap_parse_project_task_contract_by_task(
        $projectTaskRows,
        array_keys($taskRowsByKey),
        $projectNo
    );
    $baselineRevenueByTask = is_array($baselineAmountsByTask['revenue'] ?? null) ? $baselineAmountsByTask['revenue'] : [];
    foreach ($taskRowsByKey as $taskKey => &$taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $taskRow['Contract_Value'] = finance_to_float($contractByTask[$taskKey] ?? 0.0);

        if ((bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $taskRow['Budget_Cost'] = finance_to_float($baselineCostsByTask[$taskKey] ?? 0.0);
        $taskRow['Budget_Revenue'] = finance_to_float($baselineRevenueByTask[$taskKey] ?? 0.0);
        $taskRow['Entered_Obligations'] = finance_to_float($purchasesByTask[$taskKey] ?? 0.0);
    }
    unset($taskRow);

    $bookingRows = [];
    foreach ($taskRowsByKey as $taskKey => $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        if ((bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $bookingRows[$taskKey] = $taskRow;
    }

    try {
        $hoursUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'Job_Task_Lines', [
            '$select' => 'Job_No,Job_Task_No,Job_Task_Type,LVS_Budget_Hours_Quantity,LVS_Used_Hours_Quantity,LVS_Forecast_Hours_Quantity',
            '$filter' => $projectFilter,
        ]);
        $hoursRows = odata_get_all($hoursUrl, $auth, $ttl);
    } catch (Throwable $ignoredHoursLoadError) {
        $hoursRows = [];
    }

    $hoursBudgetFallback = 0.0;
    $hoursBooked = 0.0;
    $hoursForecastFallback = 0.0;
    $hoursGlobalTotalRow = null;
    $hasHourDetailRows = false;
    foreach ($hoursRows as $hoursRow) {
        if (!is_array($hoursRow)) {
            continue;
        }

        if (strcasecmp(trim((string) ($hoursRow['Job_Task_No'] ?? '')), FINRAP_GLOBAL_TOTAL_TASK_NO) === 0) {
            $hoursGlobalTotalRow = $hoursRow;
            continue;
        }

        if (finrap_is_total_task_type((string) ($hoursRow['Job_Task_Type'] ?? ''))) {
            continue;
        }

        $hasHourDetailRows = true;
        $hoursBudgetFallback = finance_add_amount($hoursBudgetFallback, finance_to_float($hoursRow['LVS_Budget_Hours_Quantity'] ?? 0.0));
        $hoursBooked = finance_add_amount($hoursBooked, finance_to_float($hoursRow['LVS_Used_Hours_Quantity'] ?? 0.0));
        $hoursForecastFallback = finance_add_amount($hoursForecastFallback, finance_to_float($hoursRow['LVS_Forecast_Hours_Quantity'] ?? 0.0));
    }

    if (!$hasHourDetailRows && $hoursGlobalTotalRow !== null) {
        $hoursBudgetFallback = finance_to_float($hoursGlobalTotalRow['LVS_Budget_Hours_Quantity'] ?? 0.0);
        $hoursBooked = finance_to_float($hoursGlobalTotalRow['LVS_Used_Hours_Quantity'] ?? 0.0);
        $hoursForecastFallback = finance_to_float($hoursGlobalTotalRow['LVS_Forecast_Hours_Quantity'] ?? 0.0);
    }

    $hoursBudget = finrap_fetch_budget_hours_total($baseUrl, $environment, $company, $auth, $projectFilter, array_keys($bookingRows), $ttl);
    if ($hoursBudget === null) {
        $hoursBudget = $hoursBudgetFallback;
    }

    $hoursEstimated = finrap_fetch_estimated_hours_total($baseUrl, $environment, $company, $auth, $projectFilter, $ttl);
    if ($hoursEstimated === null) {
        $hoursEstimated = $hoursBudget;
    }

    $modal['hours_budget'] = $hoursBudget;
    $modal['hours_booked'] = $hoursBooked;
    $modal['hours_estimated'] = $hoursEstimated;

    foreach ($taskRowsByKey as $taskKey => $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        if ((bool) ($taskRow['Is_Total_Row'] ?? false)) {
            $budgetTotal = 0.0;
            $budgetRevenueTotal = 0.0;
            $contractValueTotal = 0.0;
            $bookedTotal = 0.0;
            $obligationTotal = 0.0;
            $invoicedTotal = 0.0;
            $directContractValue = finance_to_float($taskRow['Contract_Value'] ?? 0.0);
            $range = finrap_parse_totaling_range((string) ($taskRow['Totaling'] ?? ''));
            if ($range !== null) {
                foreach ($bookingRows as $bookingRow) {
                    if (!is_array($bookingRow)) {
                        continue;
                    }

                    $bookingTaskNo = (string) ($bookingRow['Cost_Group_Code'] ?? '');
                    if (!finrap_task_no_in_range($bookingTaskNo, $range)) {
                        continue;
                    }

                    $budgetRevenueTotal = finance_add_amount($budgetRevenueTotal, finance_to_float($bookingRow['Budget_Revenue'] ?? 0.0));
                    $contractValueTotal = finance_add_amount($contractValueTotal, finance_to_float($bookingRow['Contract_Value'] ?? 0.0));
                    $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($bookingRow['Budget_Cost'] ?? 0.0));
                    $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($bookingRow['Booked_Cost'] ?? 0.0));
                    $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($bookingRow['Entered_Obligations'] ?? 0.0));
                    $invoicedTotal = finance_add_amount($invoicedTotal, finance_to_float($bookingRow['Invoiced_Amount'] ?? 0.0));
                }

                $taskRowsByKey[$taskKey]['Budget_Revenue'] = $budgetRevenueTotal;
                $taskRowsByKey[$taskKey]['Contract_Value'] = finrap_resolve_total_row_contract_value(
                    $contractValueTotal,
                    $directContractValue
                );
                $taskRowsByKey[$taskKey]['Budget_Cost'] = $budgetTotal;
                $taskRowsByKey[$taskKey]['Booked_Cost'] = $bookedTotal;
                $taskRowsByKey[$taskKey]['Entered_Obligations'] = $obligationTotal;
                $taskRowsByKey[$taskKey]['Invoiced_Amount'] = $invoicedTotal;
            }
        }

        $taskRowsByKey[$taskKey]['Variance_Budget_EAC'] = finance_calculate_result(
            finance_to_float($taskRowsByKey[$taskKey]['Budget_Cost'] ?? 0.0),
            finance_to_float($taskRowsByKey[$taskKey]['EAC'] ?? 0.0)
        );
    }

    uasort($taskRowsByKey, static function (array $left, array $right): int {
        return strnatcasecmp((string) ($left['Cost_Group_Code'] ?? ''), (string) ($right['Cost_Group_Code'] ?? ''));
    });

    $budgetTotal = 0.0;
    $eacTotal = 0.0;
    $bookedTotal = 0.0;
    $obligationTotal = 0.0;
    $invoicedTotal = 0.0;
    foreach ($taskRowsByKey as $taskRow) {
        if (!is_array($taskRow) || (bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($taskRow['Budget_Cost'] ?? 0.0));
        $eacTotal = finance_add_amount($eacTotal, finance_to_float($taskRow['EAC'] ?? 0.0));
        $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($taskRow['Booked_Cost'] ?? 0.0));
        $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($taskRow['Entered_Obligations'] ?? 0.0));
        $invoicedTotal = finance_add_amount($invoicedTotal, finance_to_float($taskRow['Invoiced_Amount'] ?? 0.0));
    }

    $displayTaskRows = [];
    foreach ($taskRowsByKey as $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $isTotalRow = (bool) ($taskRow['Is_Total_Row'] ?? false);
        $isDisplayRow = (bool) ($taskRow['Is_Display_Row'] ?? true);
        if ($isTotalRow || $isDisplayRow) {
            unset($taskRow['Is_Display_Row']);
            $displayTaskRows[] = $taskRow;
        }
    }

    $displayTaskRows = finrap_finalize_task_row_metrics($displayTaskRows);

    $aggregatedTotals = finrap_aggregate_detail_task_rows($displayTaskRows);

    $modal['budget_cost_total'] = $aggregatedTotals['Budget_Cost'];
    $modal['task_rows'] = $displayTaskRows;
    $modal['task_rows_total'] = [
        'Cost_Group_Code' => 'TOTAL',
        'Cost_Group_Description' => 'Totaal alle regels',
        'Contract_Value' => $aggregatedTotals['Contract_Value'],
        'Budget_Revenue' => $aggregatedTotals['Budget_Revenue'],
        'Budget_Cost' => $aggregatedTotals['Budget_Cost'],
        'EAC' => $aggregatedTotals['EAC'],
        'Booked_Cost' => $aggregatedTotals['Booked_Cost'],
        'Entered_Obligations' => $aggregatedTotals['Entered_Obligations'],
        'Invoiced_Amount' => $aggregatedTotals['Invoiced_Amount'],
        'Variance_Budget_EAC' => $aggregatedTotals['Variance_Budget_EAC'],
        'Is_Total_Row' => true,
    ];
    $modal['task_rows_global_total'] = $modal['task_rows_total'];
    $modal['header_metric_rows'] = finrap_build_header_metric_rows(
        $projectTaskRows,
        $displayTaskRows,
        $projectNo,
        $contractGroups,
        finance_to_float($modal['installments_received'] ?? 0.0)
    );
    $prjHeaderRow = is_array($modal['header_metric_rows'][0] ?? null) ? $modal['header_metric_rows'][0] : [];
    $modal['contract_value'] = finance_to_float($prjHeaderRow['contract_value'] ?? 0.0);
    $modal['contract_invoiced_total'] = finance_to_float($prjHeaderRow['installments_invoiced'] ?? 0.0);

    return $modal;
}

function finrap_generate_month_for_project(string $company, string $projectNo, string $yearMonth): array
{
    $projectNoText = trim($projectNo);
    if ($projectNoText === '') {
        throw new RuntimeException('Projectnummer ontbreekt.');
    }

    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
        throw new RuntimeException('Ongeldige maand.');
    }

    $ttl = finrap_odata_ttl_for_month($yearMonth);
    $project = finrap_fetch_project($company, $projectNoText, $ttl);
    if (!is_array($project)) {
        throw new RuntimeException('Project niet gevonden in BC.');
    }

    $environment = auth_get_environment_for_company($company, 300);
    $service = new ProjectFinanceService($company, $environment);

    $projectFinance = $service->collectProjectFinanceForProjects([$projectNoText], $ttl);
    $forecast = $service->collectProjectForecastForProjects([$projectNoText], $ttl);
    $modal = finrap_collect_modal_data($company, $projectNoText, $ttl);

    $normProject = finrap_normalize_project_no($projectNoText);
    $totals = is_array($projectFinance['project_totals_by_job'][$normProject] ?? null)
        ? $projectFinance['project_totals_by_job'][$normProject]
        : ['costs' => 0.0, 'revenue' => 0.0, 'resultaat' => 0.0];
    $invoiceTotals = is_array($projectFinance['project_invoiced_total_by_job'] ?? null)
        ? (float) ($projectFinance['project_invoiced_total_by_job'][$normProject] ?? 0.0)
        : 0.0;
    $forecastTotals = is_array($forecast['forecast_totals_by_job'][$normProject] ?? null)
        ? $forecast['forecast_totals_by_job'][$normProject]
        : ['expected_revenue' => 0.0, 'expected_costs' => 0.0, 'extra_work' => 0.0];

    return [
        'company' => $company,
        'project_no' => (string) ($project['No'] ?? $projectNoText),
        'year_month' => $yearMonth,
        'fetched_at' => gmdate('c'),
        'project' => $project,
        'summary' => [
            'total_costs' => (float) ($totals['costs'] ?? 0.0),
            'total_revenue' => finance_to_float($modal['contract_invoiced_total'] ?? 0.0),
            'result' => (float) ($totals['resultaat'] ?? finance_calculate_result((float) ($totals['revenue'] ?? 0.0), (float) ($totals['costs'] ?? 0.0))),
            'expected_revenue' => (float) ($forecastTotals['expected_revenue'] ?? 0.0),
            'expected_costs' => (float) ($forecastTotals['expected_costs'] ?? 0.0),
            'extra_work' => (float) ($forecastTotals['extra_work'] ?? 0.0),
            'invoiced_total' => $invoiceTotals,
        ],
        'project_modal' => $modal,
    ];
}

function finrap_userdata_path(string $userEmail): ?string
{
    $email = strtolower(trim($userEmail));
    if ($email === '') {
        return null;
    }

    $hash = sha1($email);
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'userdata';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    return $dir . DIRECTORY_SEPARATOR . $hash . '.json';
}

function finrap_load_user_settings(string $userEmail): array
{
    $path = finrap_userdata_path($userEmail);
    if (!is_string($path) || $path === '' || !is_file($path)) {
        return [];
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function finrap_save_user_settings(string $userEmail, array $settings): bool
{
    $path = finrap_userdata_path($userEmail);
    if (!is_string($path) || $path === '') {
        return false;
    }

    $settings['updated_at'] = gmdate('c');
    $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        return false;
    }

    return file_put_contents($path, $json, LOCK_EX) !== false;
}

function finrap_project_overrides_settings_key(string $company, string $projectNo): string
{
    return 'finrap_overrides_' . sha1(strtolower(trim($company)) . '|' . strtolower(trim($projectNo)));
}

function finrap_load_project_overrides(string $userEmail, string $company, string $projectNo): array
{
    $settings = finrap_load_user_settings($userEmail);
    $key = finrap_project_overrides_settings_key($company, $projectNo);
    $stored = $settings[$key] ?? null;

    return is_array($stored) ? $stored : [];
}

function finrap_save_project_overrides(string $userEmail, string $company, string $projectNo, array $overrides): bool
{
    $settings = finrap_load_user_settings($userEmail);
    $key = finrap_project_overrides_settings_key($company, $projectNo);
    $settings[$key] = $overrides;

    return finrap_save_user_settings($userEmail, $settings);
}

function finrap_is_detail_task_row(array $taskRow): bool
{
    return !(bool) ($taskRow['Is_Total_Row'] ?? false);
}

function finrap_aggregate_detail_task_rows(array $taskRows): array
{
    $contractValueTotal = 0.0;
    $budgetRevenueTotal = 0.0;
    $budgetTotal = 0.0;
    $eacTotal = 0.0;
    $bookedTotal = 0.0;
    $obligationTotal = 0.0;
    $invoicedTotal = 0.0;

    foreach ($taskRows as $taskRow) {
        if (!is_array($taskRow) || !finrap_is_detail_task_row($taskRow)) {
            continue;
        }

        $contractValueTotal = finance_add_amount($contractValueTotal, finance_to_float($taskRow['Contract_Value'] ?? 0.0));
        $budgetRevenueTotal = finance_add_amount($budgetRevenueTotal, finance_to_float($taskRow['Budget_Revenue'] ?? 0.0));
        $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($taskRow['Budget_Cost'] ?? 0.0));
        $eacTotal = finance_add_amount($eacTotal, finance_to_float($taskRow['EAC'] ?? 0.0));
        $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($taskRow['Booked_Cost'] ?? 0.0));
        $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($taskRow['Entered_Obligations'] ?? 0.0));
        $invoicedTotal = finance_add_amount($invoicedTotal, finance_to_float($taskRow['Invoiced_Amount'] ?? 0.0));
    }

    return [
        'Contract_Value' => $contractValueTotal,
        'Budget_Revenue' => $budgetRevenueTotal,
        'Budget_Cost' => $budgetTotal,
        'EAC' => $eacTotal,
        'Booked_Cost' => $bookedTotal,
        'Entered_Obligations' => $obligationTotal,
        'Invoiced_Amount' => $invoicedTotal,
        'Variance_Budget_EAC' => finance_calculate_result($budgetTotal, $eacTotal),
    ];
}

function finrap_recalculate_task_row_variances(array &$taskRows): void
{
    foreach ($taskRows as &$taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $taskRow['Variance_Budget_EAC'] = finance_calculate_result(
            finance_to_float($taskRow['Budget_Cost'] ?? 0.0),
            finance_to_float($taskRow['EAC'] ?? 0.0)
        );
    }
    unset($taskRow);
}

function finrap_rollup_total_row_metrics(array &$taskRows): void
{
    foreach ($taskRows as &$taskRow) {
        if (!is_array($taskRow) || !(bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $range = finrap_parse_totaling_range((string) ($taskRow['Totaling'] ?? ''));
        if ($range === null) {
            continue;
        }

        $contractValueTotal = 0.0;
        $budgetRevenueTotal = 0.0;
        $budgetTotal = 0.0;
        $eacTotal = 0.0;
        $bookedTotal = 0.0;
        $obligationTotal = 0.0;
        $invoicedTotal = 0.0;
        $directContractValue = finance_to_float($taskRow['Contract_Value'] ?? 0.0);

        foreach ($taskRows as $detailRow) {
            if (!is_array($detailRow) || (bool) ($detailRow['Is_Total_Row'] ?? false)) {
                continue;
            }

            $detailCode = (string) ($detailRow['Cost_Group_Code'] ?? '');
            if (!finrap_task_no_in_range($detailCode, $range)) {
                continue;
            }

            $contractValueTotal = finance_add_amount($contractValueTotal, finance_to_float($detailRow['Contract_Value'] ?? 0.0));
            $budgetRevenueTotal = finance_add_amount($budgetRevenueTotal, finance_to_float($detailRow['Budget_Revenue'] ?? 0.0));
            $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($detailRow['Budget_Cost'] ?? 0.0));
            $eacTotal = finance_add_amount($eacTotal, finance_to_float($detailRow['EAC'] ?? 0.0));
            $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($detailRow['Booked_Cost'] ?? 0.0));
            $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($detailRow['Entered_Obligations'] ?? 0.0));
            $invoicedTotal = finance_add_amount($invoicedTotal, finance_to_float($detailRow['Invoiced_Amount'] ?? 0.0));
        }

        $taskRow['Contract_Value'] = finrap_resolve_total_row_contract_value(
            $contractValueTotal,
            $directContractValue
        );
        $taskRow['Budget_Revenue'] = $budgetRevenueTotal;
        $taskRow['Budget_Cost'] = $budgetTotal;
        $taskRow['EAC'] = $eacTotal;
        $taskRow['Booked_Cost'] = $bookedTotal;
        $taskRow['Entered_Obligations'] = $obligationTotal;
        $taskRow['Invoiced_Amount'] = $invoicedTotal;
    }
    unset($taskRow);

    finrap_recalculate_task_row_variances($taskRows);
}

function finrap_finalize_task_row_metrics(array $taskRows): array
{
    finrap_rollup_total_row_metrics($taskRows);

    return $taskRows;
}

function finrap_get_report_summary_totals(array $taskRows): array
{
    return finrap_aggregate_detail_task_rows($taskRows);
}

function finrap_resolve_total_row_contract_value(float $rolledUpContractValue, float $directContractValue): float
{
    $epsilon = 0.000001;

    return abs($rolledUpContractValue) >= $epsilon ? $rolledUpContractValue : $directContractValue;
}

function finrap_normalize_loaded_task_row_fields(array $taskRows): array
{
    foreach ($taskRows as &$taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        if (!array_key_exists('Contract_Value', $taskRow)) {
            $taskRow['Contract_Value'] = 0.0;
        }

        if (!array_key_exists('Job_Change_Order_No', $taskRow)) {
            $taskRow['Job_Change_Order_No'] = '';
        }
    }
    unset($taskRow);

    return $taskRows;
}

function finrap_apply_eac_overrides_to_task_rows(array $taskRows, array $eacByTask): array
{
    $normalizedOverrides = [];
    foreach ($eacByTask as $taskCode => $amount) {
        $code = trim((string) $taskCode);
        if ($code === '') {
            continue;
        }

        $normalizedOverrides[strtolower($code)] = finance_to_float($amount);
    }

    foreach ($taskRows as &$taskRow) {
        if (!is_array($taskRow) || (bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $taskCode = trim((string) ($taskRow['Cost_Group_Code'] ?? ''));
        $overrideKey = strtolower($taskCode);
        if ($taskCode !== '' && array_key_exists($overrideKey, $normalizedOverrides)) {
            $taskRow['EAC'] = $normalizedOverrides[$overrideKey];
        } else {
            $taskRow['EAC'] = finance_to_float($taskRow['Budget_Cost'] ?? 0.0);
        }
    }
    unset($taskRow);

    return finrap_finalize_task_row_metrics($taskRows);
}

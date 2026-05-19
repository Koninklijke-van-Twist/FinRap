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

/**
 * Functies
 */
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
        '$select' => 'No,Description,Bill_to_Customer_No,Bill_to_Name,Sell_to_Customer_No,Sell_to_Customer_Name,Project_Manager,Person_Responsible,KVT_Sales_Person_Code,Your_Reference,LVS_Your_reference,Creation_Date,Ending_Date',
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
        'installments_received' => 0.0,
        'budget_cost_total' => 0.0,
        'task_rows' => [],
        'task_rows_total' => [],
    ];

    try {
        $contractUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'FactureerbareProjectPlanningsRegels', [
            '$select' => 'Job_No,Line_Type,Line_Amount_LCY',
            '$filter' => $projectFilter,
        ]);
        $contractRows = odata_get_all($contractUrl, $auth, $ttl);
    } catch (Throwable $ignoredContractLoadError) {
        $contractRows = [];
    }

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

        $modal['contract_value'] = finance_add_amount(
            (float) ($modal['contract_value'] ?? 0.0),
            finance_to_float($contractRow['Line_Amount_LCY'] ?? 0.0)
        );
    }

    try {
        $customerUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'Customer_Ledger_Entries', [
            '$select' => 'Amount_LCY,Open,Your_Reference,External_Document_No',
            '$filter' => "(Open eq true) and (Your_Reference eq '" . $escapedProject . "' or External_Document_No eq '" . $escapedProject . "')",
        ]);
        $customerRows = odata_get_all($customerUrl, $auth, $ttl);
    } catch (Throwable $ignoredCustomerLoadError) {
        $customerRows = [];
    }

    foreach ($customerRows as $customerRow) {
        if (!is_array($customerRow)) {
            continue;
        }

        $modal['installments_received'] = finance_add_amount(
            (float) ($modal['installments_received'] ?? 0.0),
            finance_to_float($customerRow['Amount_LCY'] ?? 0.0)
        );
    }

    try {
        $taskUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'ProjectenJobTaskLines', [
            '$select' => 'Job_No,Job_Task_No,Description,Job_Task_Type,Totaling,Schedule_Total_Cost',
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
        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        $taskRowsByKey[$taskKey] = [
            'Cost_Group_Code' => $taskNo,
            'Cost_Group_Description' => (string) ($taskRow['Description'] ?? ''),
            'Job_Task_Type' => (string) ($taskRow['Job_Task_Type'] ?? ''),
            'Totaling' => (string) ($taskRow['Totaling'] ?? ''),
            'Budget_Cost' => finance_to_float($taskRow['Schedule_Total_Cost'] ?? 0.0),
            'EAC' => 0.0,
            'Booked_Cost' => 0.0,
            'Entered_Obligations' => 0.0,
            'Variance_Budget_EAC' => 0.0,
            'Is_Total_Row' => finrap_is_total_task_type((string) ($taskRow['Job_Task_Type'] ?? '')),
        ];
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
        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
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

    try {
        $purchaseUrl = finrap_company_entity_url_with_query($baseUrl, $environment, $company, 'PurchaseLines', [
            '$select' => 'Job_No,Job_Task_No,Line_Amount',
            '$filter' => $projectFilter,
        ]);
        $purchaseRows = odata_get_all($purchaseUrl, $auth, $ttl);
    } catch (Throwable $ignoredPurchaseLoadError) {
        $purchaseRows = [];
    }

    foreach ($purchaseRows as $purchaseRow) {
        if (!is_array($purchaseRow)) {
            continue;
        }

        $taskNo = trim((string) ($purchaseRow['Job_Task_No'] ?? ''));
        if ($taskNo === '') {
            continue;
        }
        if (FINRAP_FORMATTED_TASK_NOS_ONLY && !finrap_is_formatted_task_no($taskNo)) {
            continue;
        }

        $taskKey = strtolower($taskNo);
        if (!isset($taskRowsByKey[$taskKey])) {
            continue;
        }

        $taskRowsByKey[$taskKey]['Entered_Obligations'] = finance_add_amount(
            (float) ($taskRowsByKey[$taskKey]['Entered_Obligations'] ?? 0.0),
            finance_to_float($purchaseRow['Line_Amount'] ?? 0.0)
        );
    }

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

    foreach ($taskRowsByKey as $taskKey => $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        if ((bool) ($taskRow['Is_Total_Row'] ?? false)) {
            $range = finrap_parse_totaling_range((string) ($taskRow['Totaling'] ?? ''));
            if ($range !== null) {
                $budgetTotal = 0.0;
                $bookedTotal = 0.0;
                $obligationTotal = 0.0;

                foreach ($bookingRows as $bookingRow) {
                    if (!is_array($bookingRow)) {
                        continue;
                    }

                    $bookingTaskNo = (string) ($bookingRow['Cost_Group_Code'] ?? '');
                    if (!finrap_task_no_in_range($bookingTaskNo, $range)) {
                        continue;
                    }

                    $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($bookingRow['Budget_Cost'] ?? 0.0));
                    $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($bookingRow['Booked_Cost'] ?? 0.0));
                    $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($bookingRow['Entered_Obligations'] ?? 0.0));
                }

                $taskRowsByKey[$taskKey]['Budget_Cost'] = $budgetTotal;
                $taskRowsByKey[$taskKey]['Booked_Cost'] = $bookedTotal;
                $taskRowsByKey[$taskKey]['Entered_Obligations'] = $obligationTotal;
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
    foreach ($taskRowsByKey as $taskRow) {
        if (!is_array($taskRow) || (bool) ($taskRow['Is_Total_Row'] ?? false)) {
            continue;
        }

        $budgetTotal = finance_add_amount($budgetTotal, finance_to_float($taskRow['Budget_Cost'] ?? 0.0));
        $eacTotal = finance_add_amount($eacTotal, finance_to_float($taskRow['EAC'] ?? 0.0));
        $bookedTotal = finance_add_amount($bookedTotal, finance_to_float($taskRow['Booked_Cost'] ?? 0.0));
        $obligationTotal = finance_add_amount($obligationTotal, finance_to_float($taskRow['Entered_Obligations'] ?? 0.0));
    }

    $modal['budget_cost_total'] = $budgetTotal;
    $modal['task_rows'] = array_values($taskRowsByKey);
    $modal['task_rows_total'] = [
        'Cost_Group_Code' => 'TOTAL',
        'Cost_Group_Description' => 'Totaal alle regels',
        'Budget_Cost' => $budgetTotal,
        'EAC' => $eacTotal,
        'Booked_Cost' => $bookedTotal,
        'Entered_Obligations' => $obligationTotal,
        'Variance_Budget_EAC' => finance_calculate_result($budgetTotal, $eacTotal),
        'Is_Total_Row' => true,
    ];

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
            'total_revenue' => (float) ($totals['revenue'] ?? 0.0),
            'result' => (float) ($totals['resultaat'] ?? finance_calculate_result((float) ($totals['revenue'] ?? 0.0), (float) ($totals['costs'] ?? 0.0))),
            'expected_revenue' => (float) ($forecastTotals['expected_revenue'] ?? 0.0),
            'expected_costs' => (float) ($forecastTotals['expected_costs'] ?? 0.0),
            'extra_work' => (float) ($forecastTotals['extra_work'] ?? 0.0),
            'invoiced_total' => $invoiceTotals,
        ],
        'project_modal' => $modal,
    ];
}

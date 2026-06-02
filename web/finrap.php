<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Includes/requires
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';
require_once __DIR__ . '/localization.php';
require_once __DIR__ . '/finrap_data.php';

/**
 * Functies
 */
function finrap_format_hours(float $value): string
{
    return LOC('format.hours', number_format($value, 1, ',', '.'));
}

function finrap_format_percent(float $value): string
{
    return LOC('format.percent', number_format($value, 1, ',', '.'));
}

function finrap_format_date_nl(string $isoDate): string
{
    $value = trim($isoDate);
    if ($value === '') {
        return '-';
    }

    try {
        $dt = new DateTimeImmutable($value);
    } catch (Throwable $ignoredDateError) {
        return $isoDate;
    }

    return $dt->format('d-m-Y');
}

function finrap_format_currency(float $value): string
{
    return '€ ' . number_format($value, 2, ',', '.');
}

function finrap_tooltip_reference_html(string $tableName, string $fieldName): string
{
    return '<span class="value-tooltip-table">[' . htmlspecialchars($tableName) . ']</span>'
        . '<span class="value-tooltip-op"> -> </span>'
        . '<span class="value-tooltip-field">' . htmlspecialchars($fieldName) . '</span>';
}

function finrap_highlight_tooltip_operators_html(string $text): string
{
    $escaped = htmlspecialchars($text);
    return preg_replace('/([+\-\/])/', '<span class="value-tooltip-operator">$1</span>', $escaped) ?? $escaped;
}

function finrap_tooltip_formula_html(array $parts): string
{
    $html = '';
    foreach ($parts as $part) {
        if (!is_array($part)) {
            continue;
        }

        $type = (string) ($part['type'] ?? '');
        if ($type === 'ref') {
            $html .= finrap_tooltip_reference_html((string) ($part['table'] ?? ''), (string) ($part['field'] ?? ''));
            continue;
        }

        if ($type === 'text') {
            $html .= '<span class="value-tooltip-op">' . finrap_highlight_tooltip_operators_html((string) ($part['text'] ?? '')) . '</span>';
        }
    }

    return $html;
}

function finrap_render_value_with_tooltip_html(string $displayHtml, string $tooltipHtml, string $extraClass = ''): string
{
    $className = trim('has-value-tooltip ' . $extraClass);
    return '<span class="' . htmlspecialchars($className) . '">'
        . $displayHtml
        . '<span class="value-tooltip-rich">' . $tooltipHtml . '</span>'
        . '</span>';
}

function finrap_cost_group_value_tooltip_html(string $columnKey): string
{
    if ($columnKey === 'Cost_Group_Code') {
        return finrap_tooltip_formula_html([
            ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Job_Task_No'],
        ]);
    }

    if ($columnKey === 'Cost_Group_Description') {
        return finrap_tooltip_formula_html([
            ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Description'],
        ]);
    }

    if ($columnKey === 'Budget_Cost') {
        return finrap_tooltip_formula_html([
            ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Schedule_Total_Cost'],
        ]);
    }

    if ($columnKey === 'EAC') {
        return finrap_tooltip_formula_html([
            ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'EAC_Total_Cost'],
            ['type' => 'text', 'text' => LOC('report.tooltip.fallback')],
            ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Schedule_Total_Cost'],
            ['type' => 'text', 'text' => LOC('report.tooltip.fallback_close')],
        ]);
    }

    if ($columnKey === 'Booked_Cost') {
        return finrap_tooltip_formula_html([
            ['type' => 'ref', 'table' => 'JobLedgerEntries', 'field' => 'Total_Cost_LCY'],
        ]);
    }

    if ($columnKey === 'Entered_Obligations') {
        return finrap_tooltip_formula_html([
            ['type' => 'ref', 'table' => 'PurchaseLines', 'field' => 'Line_Amount'],
        ]);
    }

    if ($columnKey === 'Variance_Budget_EAC') {
        return finrap_tooltip_formula_html([
            ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Schedule_Total_Cost'],
            ['type' => 'text', 'text' => ' - '],
            ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'EAC_Total_Cost'],
        ]);
    }

    return finrap_tooltip_formula_html([
        ['type' => 'text', 'text' => LOC('report.tooltip.no_source')],
    ]);
}

function finrap_currency_sign_class(float $value): string
{
    $epsilon = 0.000001;
    if (abs($value) < $epsilon) {
        return 'is-zero';
    }

    return $value > 0 ? 'is-positive' : 'is-negative';
}

function finrap_month_label(string $yearMonth): string
{
    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
        return $yearMonth;
    }

    [$year, $month] = explode('-', $yearMonth);
    $monthKey = 'month.' . $month;
    return LOC($monthKey) . ' ' . $year;
}

function finrap_format_report_datetime(string $rawDateTime): string
{
    $value = trim($rawDateTime);
    if ($value === '') {
        return '';
    }

    try {
        $dt = new DateTimeImmutable($value);
        $dt = $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
    } catch (Throwable $ignoredDateParseError) {
        return $value;
    }

    $monthNumber = $dt->format('m');
    $monthLabel = LOC('month_lc.' . $monthNumber);

    return $dt->format('j') . ' ' . $monthLabel . ' ' . $dt->format('Y, H:i');
}

function finrap_cost_group_columns(): array
{
    return [
        ['key' => 'Cost_Group_Code', 'label' => LOC('report.col.cost_group_code'), 'is_right' => false, 'tooltip' => ''],
        ['key' => 'Cost_Group_Description', 'label' => LOC('report.col.cost_group_description'), 'is_right' => false, 'tooltip' => ''],
        ['key' => 'Budget_Cost', 'label' => LOC('report.col.budget_cost'), 'is_right' => true, 'tooltip' => LOC('report.tooltip.col.budget_cost')],
        ['key' => 'EAC', 'label' => LOC('report.col.eac'), 'is_right' => true, 'tooltip' => LOC('report.tooltip.col.eac')],
        ['key' => 'Booked_Cost', 'label' => LOC('report.col.booked_cost'), 'is_right' => true, 'tooltip' => LOC('report.tooltip.col.booked_cost')],
        ['key' => 'Entered_Obligations', 'label' => LOC('report.col.entered_obligations'), 'is_right' => true, 'tooltip' => LOC('report.tooltip.col.entered_obligations')],
        ['key' => 'Variance_Budget_EAC', 'label' => LOC('report.col.variance_budget_eac'), 'is_right' => true, 'tooltip' => LOC('report.tooltip.col.variance_budget_eac')],
    ];
}

function finrap_is_all_zero_totals_row(array $row): bool
{
    $budget = finance_to_float($row['Budget_Cost'] ?? 0.0);
    $eac = finance_to_float($row['EAC'] ?? 0.0);
    $booked = finance_to_float($row['Booked_Cost'] ?? 0.0);
    $obligations = finance_to_float($row['Entered_Obligations'] ?? 0.0);
    $variance = finance_to_float($row['Variance_Budget_EAC'] ?? 0.0);

    $epsilon = 0.000001;
    return abs($budget) < $epsilon
        && abs($eac) < $epsilon
        && abs($booked) < $epsilon
        && abs($obligations) < $epsilon
        && abs($variance) < $epsilon;
}

function finrap_render_cost_group_table(array $taskRows, bool $totalsOnly = false, bool $hideAllZeroTotals = false, bool $editableEac = false, string $tableId = ''): void
{
    $columns = finrap_cost_group_columns();
    $tableIdAttr = $tableId !== '' ? ' id="' . htmlspecialchars($tableId) . '"' : '';

    echo '<table class="project-cost-group-table"' . $tableIdAttr . '>';
    echo '<thead><tr>';
    foreach ($columns as $column) {
        $thClass = (bool) ($column['is_right'] ?? false) ? ' class="is-right"' : '';
        $tooltip = (string) ($column['tooltip'] ?? '');
        $tooltipAttr = $tooltip !== '' ? ' data-tooltip="' . htmlspecialchars($tooltip) . '"' : '';
        echo '<th' . $thClass . $tooltipAttr . '>' . htmlspecialchars((string) ($column['label'] ?? '')) . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($taskRows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $isTotalRow = (bool) ($row['Is_Total_Row'] ?? false);
        if ($totalsOnly && !$isTotalRow) {
            continue;
        }
        if ($totalsOnly && $hideAllZeroTotals && finrap_is_all_zero_totals_row($row)) {
            continue;
        }

        $taskCode = (string) ($row['Cost_Group_Code'] ?? '');
        $descriptionValue = (string) ($row['Cost_Group_Description'] ?? '');
        if (preg_match('/^\d{3}-000-000$/', $taskCode) === 1 && $isTotalRow) {
            $descriptionValue = (string) ($row['Cost_Group_Description'] ?? '');
        } elseif (preg_match('/^\d{3}-\d{3}-000$/', $taskCode) === 1 && $isTotalRow) {
            $descriptionValue = "\u{00A0}\u{00A0}" . $descriptionValue;
        } elseif (preg_match('/^\d{3}-\d{3}-\d{3}$/', $taskCode) === 1 && !$isTotalRow) {
            $descriptionValue = "\u{00A0}\u{00A0}\u{00A0}\u{00A0}" . $descriptionValue;
        }

        $rowClass = '';
        if ($isTotalRow) {
            $rowClass = 'is-total-row';
            if (preg_match('/^\d{3}-000-000$/', $taskCode) === 1) {
                $rowClass .= ' is-major-total-row';
            } elseif (preg_match('/^\d{3}-\d{3}-000$/', $taskCode) === 1) {
                $rowClass .= ' is-minor-total-row';
            }
        }

        echo '<tr' . ($rowClass !== '' ? ' class="' . htmlspecialchars($rowClass) . '"' : '') . ' data-task-code="' . htmlspecialchars($taskCode) . '" data-is-total-row="' . ($isTotalRow ? '1' : '0') . '">';
        foreach ($columns as $column) {
            $columnKey = (string) ($column['key'] ?? '');
            $isRight = (bool) ($column['is_right'] ?? false);

            if ($columnKey === 'Cost_Group_Code') {
                $cellClass = $isRight ? ' class="is-right"' : '';
                $display = htmlspecialchars($taskCode);
                $tooltipHtml = finrap_cost_group_value_tooltip_html($columnKey);
                echo '<td' . $cellClass . '>' . finrap_render_value_with_tooltip_html($display, $tooltipHtml) . '</td>';
                continue;
            }

            if ($columnKey === 'Cost_Group_Description') {
                $display = htmlspecialchars($descriptionValue);
                $tooltipHtml = finrap_cost_group_value_tooltip_html($columnKey);
                echo '<td class="is-description">' . finrap_render_value_with_tooltip_html($display, $tooltipHtml) . '</td>';
                continue;
            }

            $value = finance_to_float($row[$columnKey] ?? 0.0);
            $tooltipHtml = finrap_cost_group_value_tooltip_html($columnKey);

            if ($editableEac && $columnKey === 'EAC') {
                $cellClass = trim('is-right finrap-eac-cell ' . finrap_currency_sign_class($value));
                if ($isTotalRow) {
                    $display = htmlspecialchars(finrap_format_currency($value));
                    echo '<td class="' . htmlspecialchars($cellClass) . '" data-eac-cell="1" data-eac-editable="0" data-metric-key="EAC">' . finrap_render_value_with_tooltip_html($display, $tooltipHtml) . '</td>';
                } else {
                    $display = htmlspecialchars(finrap_format_currency($value));
                    echo '<td class="' . htmlspecialchars($cellClass) . '" data-eac-cell="1" data-eac-editable="1" data-metric-key="EAC">'
                        . '<button type="button" class="finrap-eac-edit-btn" data-task-code="' . htmlspecialchars($taskCode) . '">'
                        . finrap_render_value_with_tooltip_html($display, $tooltipHtml)
                        . '</button></td>';
                }
                continue;
            }

            if ($columnKey === 'Variance_Budget_EAC') {
                $cellClass = 'is-right ' . finrap_currency_sign_class($value);
                $display = htmlspecialchars(finrap_format_currency($value));
                echo '<td class="' . htmlspecialchars($cellClass) . '" data-metric-key="Variance_Budget_EAC">' . finrap_render_value_with_tooltip_html($display, $tooltipHtml) . '</td>';
                continue;
            }

            $cellClass = trim('is-right ' . finrap_currency_sign_class($value));
            $display = htmlspecialchars(finrap_format_currency($value));
            $metricAttr = in_array($columnKey, ['Budget_Cost', 'EAC', 'Booked_Cost', 'Entered_Obligations', 'Variance_Budget_EAC'], true)
                ? ' data-metric-key="' . htmlspecialchars($columnKey) . '"'
                : '';
            echo '<td class="' . htmlspecialchars($cellClass) . '"' . $metricAttr . '>' . finrap_render_value_with_tooltip_html($display, $tooltipHtml) . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

function finrap_json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function finrap_task_rows_for_client(array $taskRows): array
{
    $payload = [];
    foreach ($taskRows as $taskRow) {
        if (!is_array($taskRow)) {
            continue;
        }

        $payload[] = [
            'code' => (string) ($taskRow['Cost_Group_Code'] ?? ''),
            'is_total_row' => (bool) ($taskRow['Is_Total_Row'] ?? false),
            'totaling' => (string) ($taskRow['Totaling'] ?? ''),
            'budget_cost' => finance_to_float($taskRow['Budget_Cost'] ?? 0.0),
            'eac' => finance_to_float($taskRow['EAC'] ?? 0.0),
            'booked_cost' => finance_to_float($taskRow['Booked_Cost'] ?? 0.0),
            'entered_obligations' => finance_to_float($taskRow['Entered_Obligations'] ?? 0.0),
            'variance_budget_eac' => finance_to_float($taskRow['Variance_Budget_EAC'] ?? 0.0),
        ];
    }

    return $payload;
}

/**
 * Page load
 */
$company = trim((string) ($_GET['company'] ?? ''));
$projectNo = trim((string) ($_GET['project_no'] ?? ''));
$reportId = trim((string) ($_GET['report_id'] ?? ''));
$yearMonth = trim((string) ($_GET['year_month'] ?? ''));
$autoPrint = (string) ($_GET['print'] ?? '') === '1';
$embedMode = (string) ($_GET['embed'] ?? '') === '1';

if (($_GET['action'] ?? '') === 'save_overrides') {
    $saveCompany = trim((string) ($_POST['company'] ?? ''));
    $saveProjectNo = trim((string) ($_POST['project_no'] ?? ''));
    $saveReportId = trim((string) ($_POST['report_id'] ?? ''));

    if ($saveCompany === '' || $saveProjectNo === '' || $saveReportId === '') {
        finrap_json_response(['ok' => false, 'error' => LOC('error.invalid_input')], 400);
    }

    $overridePayload = [
        'eac_by_task' => [],
        'updated_at' => gmdate('c'),
    ];

    $existingOverrides = finrap_load_report_overrides($saveCompany, $saveProjectNo, $saveReportId);

    if (array_key_exists('poc_pm', $_POST)) {
        $pocPmRaw = trim((string) ($_POST['poc_pm'] ?? ''));
        $overridePayload['poc_pm'] = $pocPmRaw === '' ? null : finance_to_float($pocPmRaw);
    } elseif (array_key_exists('poc_pm', $existingOverrides)) {
        $overridePayload['poc_pm'] = $existingOverrides['poc_pm'];
    }

    $eacByTaskRaw = $_POST['eac_by_task'] ?? '{}';
    if (is_string($eacByTaskRaw)) {
        $decodedEac = json_decode($eacByTaskRaw, true);
    } else {
        $decodedEac = is_array($eacByTaskRaw) ? $eacByTaskRaw : [];
    }

    if (is_array($decodedEac)) {
        foreach ($decodedEac as $taskCode => $amount) {
            $code = trim((string) $taskCode);
            if ($code === '') {
                continue;
            }

            $overridePayload['eac_by_task'][$code] = finance_to_float($amount);
        }
    }

    if ($overridePayload['eac_by_task'] === [] && is_array($existingOverrides['eac_by_task'] ?? null)) {
        foreach ($existingOverrides['eac_by_task'] as $taskCode => $amount) {
            $code = trim((string) $taskCode);
            if ($code === '') {
                continue;
            }

            $overridePayload['eac_by_task'][$code] = finance_to_float($amount);
        }
    }

    $saved = finrap_save_report_overrides($saveCompany, $saveProjectNo, $saveReportId, $overridePayload);

    if (!$saved) {
        finrap_json_response(['ok' => false, 'error' => LOC('error.save_report_failed')], 500);
    }

    finrap_json_response(['ok' => true]);
}

$report = null;
$error = null;
if ($company === '' || $projectNo === '') {
    $error = LOC('report.error.invalid_params');
} else {
    if ($reportId !== '') {
        $report = finrap_load_report_snapshot($company, $projectNo, $reportId);
    } elseif (preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
        // Legacy fallback for old month-based URLs.
        $report = finrap_load($company, $projectNo, $yearMonth);
    }

    if (!is_array($report)) {
        $error = LOC('report.error.not_found');
    }
}

$project = is_array($report['project'] ?? null) ? $report['project'] : [];
$summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
$modal = is_array($report['project_modal'] ?? null) ? $report['project_modal'] : [];
$taskRows = is_array($modal['task_rows'] ?? null) ? $modal['task_rows'] : [];

$reportOverrides = $reportId !== '' ? finrap_load_report_overrides($company, $projectNo, $reportId) : [];
$eacOverrides = is_array($reportOverrides['eac_by_task'] ?? null) ? $reportOverrides['eac_by_task'] : [];
$taskRows = finrap_apply_eac_overrides_to_task_rows($taskRows, $eacOverrides);

$reportProjectNo = (string) ($report['project_no'] ?? $projectNo);
$description = (string) ($project['Description'] ?? '');
$customerNo = trim((string) ($project['Bill_to_Customer_No'] ?? $project['Sell_to_Customer_No'] ?? ''));
$customerName = trim((string) ($project['Bill_to_Name'] ?? $project['Sell_to_Customer_Name'] ?? ''));
$customer = trim($customerNo . ($customerName !== '' ? ' - ' . $customerName : ''));
$projectManager = (string) ($project['Project_Manager'] ?? $project['Person_Responsible'] ?? '');
$orderReference = (string) ($project['Your_Reference'] ?? $project['LVS_Your_reference'] ?? '');
$createdAt = (string) ($report['fetched_at'] ?? '');
$createdAtFormatted = finrap_format_report_datetime($createdAt);
$contractValue = (float) ($modal['contract_value'] ?? 0.0);
$totalDirectCost = (float) ($summary['expected_costs'] ?? 0.0);
$grossProfit = $contractValue - $totalDirectCost;

$taskRowsTotal = is_array($modal['task_rows_total'] ?? null) ? $modal['task_rows_total'] : [];
$summaryTotals = finrap_get_report_summary_totals($taskRows);

$bookedCostTotal = (float) ($summaryTotals['Booked_Cost'] ?? 0.0);
$eacTotal = (float) ($summaryTotals['EAC'] ?? 0.0);
$budgetCostTotal = (float) ($summaryTotals['Budget_Cost'] ?? 0.0);
$obligationTotal = (float) ($summaryTotals['Entered_Obligations'] ?? 0.0);
$variance = finance_to_float($summaryTotals['Variance_Budget_EAC'] ?? finance_calculate_result($budgetCostTotal, $eacTotal));
$orderResult = $grossProfit - $variance;
$installmentsInvoiced = (float) ($summary['total_revenue'] ?? 0.0);
$installmentsReceived = (float) ($modal['installments_received'] ?? 0.0);

$hoursBudget = (float) ($modal['hours_budget'] ?? 0.0);
$hoursEstimated = (float) ($modal['hours_estimated'] ?? 0.0);
$hoursBooked = (float) ($modal['hours_booked'] ?? 0.0);
$hoursToGo = $hoursEstimated - $hoursBooked;

$finrapEpsilon = 0.000001;
$grossProfitPct = abs($contractValue) > $finrapEpsilon ? ($grossProfit / $contractValue * 100.0) : 0.0;
$orderResultPct = abs($contractValue) > $finrapEpsilon ? ($orderResult / $contractValue * 100.0) : 0.0;
$variancePct = abs($contractValue) > $finrapEpsilon ? ($variance / $contractValue * 100.0) : 0.0;

$expVariance = $variance - (float) ($summary['expected_costs'] ?? 0.0);
$expOrderResult = $contractValue - (float) ($summary['expected_costs'] ?? 0.0);
$iprResult = $installmentsReceived - $bookedCostTotal;
$pocCalc = finrap_calculate_poc_percent($bookedCostTotal, $eacTotal);
$pocPmHasOverride = array_key_exists('poc_pm', $reportOverrides) && $reportOverrides['poc_pm'] !== null;
$pocPm = $pocPmHasOverride ? finance_to_float($reportOverrides['poc_pm']) : $pocCalc;

$estimatedHoursTable = FINRAP_ESTIMATED_HOURS_ENTITY_SET !== '' ? FINRAP_ESTIMATED_HOURS_ENTITY_SET : FINRAP_BUDGET_HOURS_ENTITY_SET;
$estimatedHoursField = FINRAP_ESTIMATED_HOURS_FIELD !== '' ? FINRAP_ESTIMATED_HOURS_FIELD : FINRAP_BUDGET_HOURS_FIELD;

$tooltipBudgetHours = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => FINRAP_BUDGET_HOURS_ENTITY_SET, 'field' => FINRAP_BUDGET_HOURS_FIELD],
]);
$tooltipEstimatedHours = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => $estimatedHoursTable, 'field' => $estimatedHoursField],
]);
$tooltipBookedHours = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Job_Task_Lines', 'field' => 'LVS_Used_Hours_Quantity'],
]);
$tooltipHoursToGo = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => $estimatedHoursTable, 'field' => $estimatedHoursField],
    ['type' => 'text', 'text' => ' - '],
    ['type' => 'ref', 'table' => 'Job_Task_Lines', 'field' => 'LVS_Used_Hours_Quantity'],
]);
$tooltipGrossProfitPct = finrap_tooltip_formula_html([
    ['type' => 'text', 'text' => '('],
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
    ['type' => 'text', 'text' => ' - '],
    ['type' => 'ref', 'table' => 'ProjectFinanceForecast', 'field' => 'expected_costs'],
    ['type' => 'text', 'text' => ') / '],
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
]);
$tooltipOrderResultPct = finrap_tooltip_formula_html([
    ['type' => 'text', 'text' => '(GrossProfit - Variance) / '],
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
]);
$tooltipVariancePct = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Schedule_Total_Cost'],
    ['type' => 'text', 'text' => ' / '],
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
]);
$tooltipExpVariance = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Schedule_Total_Cost'],
    ['type' => 'text', 'text' => ' - '],
    ['type' => 'ref', 'table' => 'ProjectFinanceForecast', 'field' => 'expected_costs'],
]);
$tooltipExpOrderResult = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
    ['type' => 'text', 'text' => ' - '],
    ['type' => 'ref', 'table' => 'ProjectFinanceForecast', 'field' => 'expected_costs'],
]);
$tooltipIprResult = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Customer_Ledger_Entries', 'field' => 'Amount_LCY'],
    ['type' => 'text', 'text' => ' - '],
    ['type' => 'ref', 'table' => 'JobLedgerEntries', 'field' => 'Total_Cost_LCY'],
]);
$tooltipPocPm = finrap_tooltip_formula_html([
    ['type' => 'text', 'text' => LOC('report.tooltip.exp.poc_pm')],
]);
$tooltipPocCalc = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'JobLedgerEntries', 'field' => 'Total_Cost_LCY'],
    ['type' => 'text', 'text' => ' / '],
    ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'EAC_Total_Cost'],
]);
$tooltipProjectNo = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'No'],
]);
$tooltipOrderReference = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Your_Reference'],
    ['type' => 'text', 'text' => ' / '],
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'LVS_Your_reference'],
]);
$tooltipDescription = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Description'],
]);
$tooltipCreatedAt = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FinRap_Report', 'field' => 'fetched_at'],
]);
$tooltipCustomer = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Bill_to_Customer_No'],
    ['type' => 'text', 'text' => ' + '],
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Bill_to_Name'],
    ['type' => 'text', 'text' => LOC('report.tooltip.fallback')],
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Sell_to_Customer_No'],
    ['type' => 'text', 'text' => ' + '],
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Sell_to_Customer_Name'],
    ['type' => 'text', 'text' => LOC('report.tooltip.fallback_close')],
]);
$tooltipProjectManager = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Project_Manager'],
    ['type' => 'text', 'text' => LOC('report.tooltip.fallback')],
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Person_Responsible'],
    ['type' => 'text', 'text' => LOC('report.tooltip.fallback_close')],
]);
$tooltipOrderDate = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Creation_Date'],
]);
$tooltipCompletedDate = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'Ending_Date'],
]);
$tooltipSalesManager = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Projecten', 'field' => 'KVT_Sales_Person_Code'],
]);
$tooltipContractValue = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
]);
$tooltipTotalDirectCost = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'ProjectFinanceForecast', 'field' => 'expected_costs'],
]);
$tooltipGrossProfit = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
    ['type' => 'text', 'text' => ' - '],
    ['type' => 'ref', 'table' => 'ProjectFinanceForecast', 'field' => 'expected_costs'],
]);
$tooltipVarianceValue = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'Schedule_Total_Cost'],
    ['type' => 'text', 'text' => ' - '],
    ['type' => 'ref', 'table' => 'ProjectenJobTaskLines', 'field' => 'EAC_Total_Cost'],
]);
$tooltipOrderResult = finrap_tooltip_formula_html([
    ['type' => 'text', 'text' => '(Contract Value - Total Direct Cost) - Variance'],
]);
$tooltipInstallmentsInvoiced = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'ProjectFinanceTotals', 'field' => 'total_revenue'],
]);
$tooltipInstallmentsReceived = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'Customer_Ledger_Entries', 'field' => 'Amount_LCY'],
]);
$tooltipTermijnNo = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_No'],
]);
$tooltipTermijnDocumentNo = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Document_No'],
]);
$tooltipTermijnDescription = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Description'],
]);
$tooltipTermijnDate = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Planning_Date'],
]);
$tooltipTermijnStatus = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'LVS_Document_Status'],
]);
$tooltipTermijnAmount = finrap_tooltip_formula_html([
    ['type' => 'ref', 'table' => 'FactureerbareProjectPlanningsRegels', 'field' => 'Line_Amount_LCY'],
]);

$termijnLines = is_array($modal['termijn_lines'] ?? null) ? $modal['termijn_lines'] : [];
$finrapClientTaskRows = finrap_task_rows_for_client($taskRows);
$finrapClientEacOverrides = $eacOverrides;
$finrapReportId = $reportId;
$finrapPocPmHasOverride = $pocPmHasOverride;
?>
<!doctype html>
<html lang="<?= htmlspecialchars(getHtmlLang(), ENT_QUOTES) ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="stylesheet" href="brand.css">
    <title><?= htmlspecialchars(LOC('app.title'), ENT_QUOTES) ?> <?= htmlspecialchars($reportProjectNo) ?></title>
    <?php renderLanguageSwitcherStyles(); ?>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f7fb;
            color: var(--kvt-text);
        }

        body.embed-mode {
            background: #ffffff;
        }

        .btn {
            border: 1px solid #b7cbe4;
            border-radius: 8px;
            min-height: 36px;
            padding: 8px 12px;
            font-weight: 700;
            cursor: pointer;
            background: #ffffff;
            color: var(--kvt-perkins-blue);
        }

        .btn:hover {
            background: #f2f9ff;
        }

        .btn-print {
            background: var(--kvt-main-blue);
            border-color: var(--kvt-main-blue);
            color: #ffffff;
        }

        .btn-back {
            background: #edf7ff;
            border-color: #b7cbe4;
            color: var(--kvt-perkins-blue);
        }

        .project-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .52);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2001;
            padding: 18px;
        }

        body.embed-mode .project-overlay {
            position: static;
            inset: auto;
            padding: 0;
            background: transparent;
            display: block;
        }

        .project-modal {
            width: min(1400px, calc(100vw - 24px));
            height: min(92vh, 980px);
            background: #fff;
            border-radius: 16px;
            border: 1px solid #c9d7eb;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .28);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        body.embed-mode .project-modal {
            width: 100%;
            height: 100vh;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .project-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            background: linear-gradient(135deg, var(--kvt-perkins-blue) 0%, var(--kvt-main-blue) 100%);
            color: #fff;
        }

        .project-modal-title {
            font-size: 22px;
            font-weight: 700;
        }

        .project-modal-subtitle {
            margin: 4px 0 0;
            color: var(--kvt-muted);
            font-size: 13px;
            line-height: 1.4;
        }

        .project-modal-head-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .project-close {
            border: 1px solid rgba(255, 255, 255, .45);
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-radius: 8px;
            padding: 6px 12px;
            cursor: pointer;
            font-weight: 700;
        }

        .project-close:hover {
            background: rgba(255, 255, 255, .2);
        }

        .project-modal-body {
            flex: 1;
            overflow-y: auto;
            overflow-x: visible;
            background: #f8fbff;
            padding: 0;
        }

        body.embed-mode .project-modal-body {
            overflow-y: auto;
            overflow-x: visible;
        }

        body.embed-mode .project-close.project-close-back {
            display: none;
        }

        .project-modal-header-panel {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 36px;
            padding: 20px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--kvt-main-blue) 0%, var(--kvt-perkins-blue) 100%);
            color: #fff;
        }

        .project-modal-info-panel {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 36px;
            padding: 24px 20px 28px;
            margin-bottom: 10px;
            background: #fff;
        }

        .project-modal-column {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .project-field {
            display: grid;
            grid-template-columns: 160px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
        }

        .project-field-label {
            font-weight: 700;
            letter-spacing: .01em;
        }

        .project-field-value {
            min-width: 0;
            color: var(--kvt-text);
            word-break: break-word;
        }

        .project-modal-header-panel .project-field-value,
        .project-modal-header-panel .project-field-label {
            color: #fff;
        }

        .project-modal-info-panel .project-field-label {
            color: var(--kvt-perkins-blue);
        }

        .project-field-value.is-empty {
            color: #94a3b8;
            font-style: italic;
        }

        .project-modal-summary-section,
        .project-modal-cost-groups-section {
            padding: 0 20px 20px;
            background: #ffffff;
            overflow: visible;
        }

        .project-modal-cost-groups-section {
            padding: 0 20px 24px;
        }

        .project-metric-table,
        .project-cost-group-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            overflow: visible;
        }

        .project-metric-table thead th,
        .project-cost-group-table thead th {
            background: var(--kvt-perkins-blue);
            color: #fff;
            font-weight: 700;
            padding: 9px 10px;
            text-align: left;
            border-right: 1px solid rgba(255, 255, 255, .2);
            white-space: nowrap;
        }

        .project-metric-table thead th:last-child,
        .project-cost-group-table thead th:last-child {
            border-right: 0;
        }

        .project-metric-table tbody td,
        .project-cost-group-table tbody td {
            border-top: 1px solid #e7edf5;
            padding: 8px 10px;
            color: #1f2937;
            background: #ffffff;
        }

        .project-metric-table tbody td.is-right,
        .project-cost-group-table tbody td.is-right,
        .project-metric-table thead th.is-right,
        .project-cost-group-table thead th.is-right {
            text-align: right;
        }

        .project-cost-group-table .is-description {
            white-space: pre;
        }

        .project-cost-group-table tbody tr.is-total-row td {
            font-weight: 700;
            background: #f8fbff;
        }

        .project-cost-group-table tbody tr.is-major-total-row td {
            background: #cfe0fb;
        }

        .project-cost-group-table tbody tr.is-minor-total-row td {
            background: #eff6ff;
        }

        .project-metric-table tbody td.is-positive,
        .project-cost-group-table tbody td.is-positive {
            color: #0a4e22;
            font-weight: 700;
        }

        .project-metric-table tbody td.is-negative,
        .project-cost-group-table tbody td.is-negative {
            color: #661515;
            font-weight: 700;
        }

        .project-metric-table tbody td.is-zero,
        .project-cost-group-table tbody td.is-zero {
            color: #64748b;
            font-weight: 700;
        }

        [data-tooltip] {
            position: relative;
            cursor: help;
        }

        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            background: #1f2937;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 400;
            white-space: normal;
            max-width: 200px;
            letter-spacing: normal;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            margin-bottom: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        [data-tooltip]::before {
            content: '';
            position: absolute;
            bottom: calc(100% - 2px);
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 6px solid #1f2937;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        [data-tooltip]:hover::after,
        [data-tooltip]:hover::before {
            opacity: 1;
        }

        .has-value-tooltip {
            position: relative;
            cursor: help;
        }

        .value-tooltip-rich {
            position: absolute;
            bottom: calc(100% + 10px);
            right: 0;
            z-index: 10010;
            background: #1f2937;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            white-space: normal;
            min-width: 220px;
            max-width: 360px;
            text-align: left;
            letter-spacing: normal;
            line-height: 1.4;
            pointer-events: none;
            opacity: 0;
            transform: translateY(4px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
        }

        .value-tooltip-rich::before {
            content: '';
            position: absolute;
            top: 100%;
            right: 14px;
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 6px solid #1f2937;
        }

        .has-value-tooltip:hover .value-tooltip-rich {
            opacity: 1;
            transform: translateY(0);
        }

        .value-tooltip-table {
            color: #22c55e;
            font-weight: 700;
        }

        .value-tooltip-field {
            color: #f59e0b;
            font-weight: 700;
        }

        .value-tooltip-op {
            color: #d1d5db;
        }

        .value-tooltip-operator {
            color: #ff2b2b;
            font-weight: 800;
        }

        .error-box {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .project-modal {
                width: 100%;
                height: 100%;
                max-height: none;
                border-radius: 0;
            }

            .project-overlay {
                padding: 0;
            }

            .project-modal-header-panel,
            .project-modal-info-panel {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .project-modal-summary-section,
            .project-modal-cost-groups-section {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        @media (max-width: 600px) {
            .project-field {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .project-modal-head {
                padding: 14px 16px;
            }

            .project-modal-header-panel,
            .project-modal-info-panel {
                padding-left: 16px;
                padding-right: 16px;
            }

            .project-metric-table,
            .project-cost-group-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media print {

            html,
            body {
                background: #ffffff;
                width: 100%;
                height: auto;
                overflow: visible;
                margin: 0;
                padding: 0;
                color: #000;
            }

            .project-overlay {
                position: static;
                inset: auto;
                padding: 0 !important;
                background: transparent;
                display: block;
                width: 100%;
                height: auto;
            }

            .project-modal-head,
            .project-modal-head-actions {
                display: none !important;
            }

            .project-modal {
                border: none;
                box-shadow: none;
                border-radius: 0;
                width: 100%;
                height: auto !important;
                max-width: 100%;
                max-height: none !important;
                display: block !important;
                flex: none !important;
                overflow: visible !important;
                background: #fff !important;
            }

            .project-modal-body {
                overflow: visible !important;
                height: auto !important;
                flex: none !important;
                display: block !important;
                background: #fff !important;
            }

            /* Keep header and info panels in their grid layout */
            .project-modal-header-panel {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 18px 36px !important;
                padding: 20px !important;
                background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: #ffffff !important;
                width: 100% !important;
                height: auto !important;
                page-break-inside: avoid;
                margin: 0 0 10px 0 !important;
            }

            .project-modal-info-panel {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 18px 36px !important;
                padding: 24px 20px 28px !important;
                background: #ffffff !important;
                width: 100% !important;
                height: auto !important;
                page-break-inside: avoid;
                margin: 0 0 10px 0 !important;
            }

            .project-modal-column {
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
            }

            .project-field {
                display: grid !important;
                grid-template-columns: 160px minmax(0, 1fr) !important;
                gap: 10px !important;
                align-items: start !important;
            }

            .project-field-label {
                font-weight: 700;
                letter-spacing: 0.01em;
                color: inherit !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .project-modal-header-panel .project-field-label,
            .project-modal-header-panel .project-field-value {
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .project-modal-info-panel .project-field-label {
                color: var(--kvt-perkins-blue) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .project-field-value {
                word-break: break-word;
                color: inherit !important;
            }

            /* Sections */
            .project-modal-summary-section,
            .project-modal-cost-groups-section {
                display: block !important;
                width: 100% !important;
                height: auto !important;
                padding: 0 20px 20px !important;
                background: #ffffff !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }

            .analytics-blocks-section {
                display: grid !important;
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 12px !important;
                padding: 0 20px 20px !important;
                background: #ffffff !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }

            .analytics-block {
                break-inside: avoid;
                page-break-inside: avoid;
                background: #ffffff !important;
            }

            .analytics-block-header {
                background: var(--kvt-perkins-blue) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .analytics-block-body {
                display: block !important;
                background: #ffffff !important;
            }

            .analytics-row {
                display: flex !important;
            }

            /* Tables */
            .project-metric-table,
            .project-cost-group-table {
                width: 100% !important;
                display: table !important;
                overflow: visible !important;
                white-space: normal !important;
                border-collapse: collapse;
                margin-bottom: 12px;
                page-break-inside: avoid;
                background: #ffffff !important;
            }

            .project-metric-table thead,
            .project-cost-group-table thead {
                display: table-header-group;
                background: var(--kvt-perkins-blue) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .project-metric-table tbody,
            .project-cost-group-table tbody {
                display: table-row-group;
                background: #ffffff !important;
            }

            .project-metric-table tr,
            .project-cost-group-table tr {
                display: table-row;
                page-break-inside: avoid;
                background: #ffffff !important;
            }

            .project-metric-table th,
            .project-cost-group-table th {
                display: table-cell;
                padding: 8px 10px !important;
                background: var(--kvt-perkins-blue) !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .project-metric-table td,
            .project-cost-group-table td {
                display: table-cell;
                padding: 8px 10px !important;
                background: #ffffff !important;
                color: #000 !important;
            }

            /* Termijn list */
            .termijn-list {
                display: block !important;
            }

            .termijn-item {
                display: grid !important;
                page-break-inside: avoid;
            }
        }

        .analytics-blocks-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            padding: 0 20px 20px;
            background: #ffffff;
            overflow: visible;
        }

        .analytics-block {
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            overflow: visible;
        }

        .analytics-block-header {
            background: var(--kvt-perkins-blue);
            color: #ffffff;
            font-weight: 700;
            font-size: 12px;
            padding: 8px 10px;
        }

        .analytics-block-body {
            padding: 6px 0;
        }

        .analytics-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
            padding: 5px 10px;
            font-size: 12px;
            border-top: 1px solid #f0f4fa;
        }

        .analytics-row:first-child {
            border-top: 0;
        }

        .analytics-label {
            color: #475569;
            flex-shrink: 0;
        }

        .analytics-value {
            color: #1f2937;
            font-weight: 700;
            text-align: right;
            min-width: 0;
        }

        .analytics-value.is-positive {
            color: #0a4e22;
        }

        .analytics-value.is-negative {
            color: #661515;
        }

        .analytics-value.is-zero {
            color: #64748b;
        }

        .termijn-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .termijn-item {
            display: grid;
            grid-template-columns: 7.5rem minmax(0, 1fr) auto;
            grid-template-rows: auto auto;
            gap: 2px 10px;
            align-items: baseline;
            font-size: 11px;
            padding: 5px 10px;
            border-top: 1px solid #f0f4fa;
            line-height: 1.4;
        }

        .termijn-item:first-child {
            border-top: 0;
        }

        .termijn-document {
            grid-row: 1;
            grid-column: 1;
            font-weight: 700;
            color: var(--kvt-perkins-blue);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .termijn-description {
            grid-row: 1;
            grid-column: 2;
            color: #475569;
            min-width: 0;
            word-break: break-word;
        }

        .termijn-status {
            grid-row: 1;
            grid-column: 3;
            font-weight: 700;
            white-space: nowrap;
            color: #1f2937;
            text-align: right;
        }

        .termijn-amount {
            grid-row: 2;
            grid-column: 1;
            font-weight: 700;
            color: #1f2937;
            white-space: nowrap;
        }

        .termijn-date {
            grid-row: 2;
            grid-column: 2;
            color: #1f2937;
            white-space: nowrap;
        }

        .termijn-empty {
            padding: 10px;
            font-size: 12px;
            color: #94a3b8;
            font-style: italic;
        }

        .finrap-eac-edit-btn,
        .finrap-editable-value-btn {
            border: 0;
            background: transparent;
            padding: 0;
            margin: 0;
            font: inherit;
            color: inherit;
            cursor: pointer;
            text-align: inherit;
        }

        .finrap-eac-edit-btn:hover,
        .finrap-editable-value-btn:hover {
            text-decoration: underline;
        }

        .finrap-value-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .52);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            padding: 18px;
        }

        .finrap-value-modal-overlay.is-visible {
            display: flex;
        }

        .finrap-value-modal {
            width: min(420px, calc(100vw - 24px));
            background: #fff;
            border: 1px solid #c9d7eb;
            border-radius: 14px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .28);
            padding: 16px;
        }

        .finrap-value-modal-title {
            margin: 0 0 8px;
            font-size: 18px;
            color: var(--kvt-perkins-blue);
        }

        .finrap-value-modal-notice {
            margin: 0 0 12px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.45;
        }

        .finrap-value-modal-label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .finrap-value-modal-input {
            width: 100%;
            min-height: 38px;
            border: 1px solid #c9d7eb;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 14px;
        }

        .finrap-value-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 14px;
        }

        @media (max-width: 900px) {
            .analytics-blocks-section {
                grid-template-columns: 1fr;
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        @media (max-width: 600px) {
            .analytics-blocks-section {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
</head>

<body class="<?= $embedMode ? 'embed-mode' : '' ?>">
    <?php if (!$embedMode): ?>
        <?php renderLanguageSwitcher(); ?>
    <?php endif; ?>
    <div class="project-overlay">
        <div class="project-modal" role="dialog" aria-modal="true" aria-labelledby="projectModalTitle">

            <div class="project-modal-body">

                <?php if ($error !== null): ?>
                    <section class="project-modal-summary-section" style="padding-top:16px;">
                        <p class="error-box"><?= htmlspecialchars($error) ?></p>
                    </section>
                <?php else: ?>
                    <section class="project-modal-header-panel">
                        <div class="project-modal-column">
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.order_number'), ENT_QUOTES) ?></div>
                                <div class="project-field-value"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($reportProjectNo), $tooltipProjectNo) ?></div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.order_reference'), ENT_QUOTES) ?></div>
                                <div class="project-field-value"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($orderReference), $tooltipOrderReference) ?></div>
                            </div>
                        </div>
                        <div class="project-modal-column">
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.description'), ENT_QUOTES) ?></div>
                                <div class="project-field-value"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($description), $tooltipDescription) ?></div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.created_at'), ENT_QUOTES) ?></div>
                                <div class="project-field-value"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($createdAtFormatted), $tooltipCreatedAt) ?></div>
                            </div>
                        </div>
                    </section>

                    <section class="project-modal-info-panel">
                        <div class="project-modal-column">
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.customer'), ENT_QUOTES) ?></div>
                                <div class="project-field-value"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($customer), $tooltipCustomer) ?></div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.project_manager'), ENT_QUOTES) ?></div>
                                <div class="project-field-value">
                                    <?= finrap_render_value_with_tooltip_html(str_replace("KVT\\", "", htmlspecialchars($projectManager)), $tooltipProjectManager) ?>
                                </div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.order_type'), ENT_QUOTES) ?></div>
                                <div class="project-field-value">-</div>
                            </div>
                        </div>
                        <div class="project-modal-column">
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.order_date'), ENT_QUOTES) ?></div>
                                <div class="project-field-value">
                                    <?= finrap_render_value_with_tooltip_html(htmlspecialchars((string) ($project['Creation_Date'] ?? '')), $tooltipOrderDate) ?>
                                </div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.completed_date'), ENT_QUOTES) ?></div>
                                <div class="project-field-value">
                                    <?= finrap_render_value_with_tooltip_html(htmlspecialchars((string) ($project['Ending_Date'] ?? '')), $tooltipCompletedDate) ?>
                                </div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label"><?= htmlspecialchars(LOC('report.sales_manager'), ENT_QUOTES) ?></div>
                                <div class="project-field-value">
                                    <?= finrap_render_value_with_tooltip_html(htmlspecialchars((string) ($project['KVT_Sales_Person_Code'] ?? '')), $tooltipSalesManager) ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="project-modal-summary-section">
                        <table class="project-metric-table">
                            <thead>
                                <tr>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.contract_value'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.contract_value'), ENT_QUOTES) ?></th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.total_direct_cost'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.total_direct_cost'), ENT_QUOTES) ?>
                                    </th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.gross_profit'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.gross_profit'), ENT_QUOTES) ?>
                                    </th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.col.budget_cost'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.col.budget_cost'), ENT_QUOTES) ?></th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.col.eac'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.col.eac'), ENT_QUOTES) ?></th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.col.booked_cost'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.col.booked_cost'), ENT_QUOTES) ?></th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.col.entered_obligations'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.col.entered_obligations'), ENT_QUOTES) ?></th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.variance'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.variance'), ENT_QUOTES) ?></th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.order_result'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.order_result'), ENT_QUOTES) ?>
                                    </th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.installments_invoiced'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.installments_invoiced'), ENT_QUOTES) ?>
                                    </th>
                                    <th class="is-right" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.installments_received'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.installments_received'), ENT_QUOTES) ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="is-right <?= finrap_currency_sign_class($contractValue) ?>">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($contractValue)), $tooltipContractValue) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($totalDirectCost) ?>">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($totalDirectCost)), $tooltipTotalDirectCost) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($grossProfit) ?>" id="metricGrossProfit">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($grossProfit)), $tooltipGrossProfit) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($budgetCostTotal) ?>" id="metricBudgetCost">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($budgetCostTotal)), finrap_cost_group_value_tooltip_html('Budget_Cost')) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($eacTotal) ?>" id="metricEac">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($eacTotal)), finrap_cost_group_value_tooltip_html('EAC')) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($bookedCostTotal) ?>" id="metricBookedCost">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($bookedCostTotal)), finrap_cost_group_value_tooltip_html('Booked_Cost')) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($obligationTotal) ?>" id="metricObligations">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($obligationTotal)), finrap_cost_group_value_tooltip_html('Entered_Obligations')) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($variance) ?>" id="metricVariance">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($variance)), $tooltipVarianceValue) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($orderResult) ?>" id="metricOrderResult">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($orderResult)), $tooltipOrderResult) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($installmentsInvoiced) ?>">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($installmentsInvoiced)), $tooltipInstallmentsInvoiced) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($installmentsReceived) ?>">
                                        <?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($installmentsReceived)), $tooltipInstallmentsReceived) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="project-modal-cost-groups-section">
                        <?php finrap_render_cost_group_table($taskRows, true, true, false, 'finrapCostTotalsTable'); ?>
                    </section>

                    <section class="analytics-blocks-section">

                        <div class="analytics-block">
                            <div class="analytics-block-header"><?= htmlspecialchars(LOC('report.block.hours_margins'), ENT_QUOTES) ?></div>
                            <div class="analytics-block-body">
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.hours.budget'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.hours.budget'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value has-value-tooltip"><?= htmlspecialchars(finrap_format_hours($hoursBudget)) ?><span class="value-tooltip-rich"><?= $tooltipBudgetHours ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.hours.estimated'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.hours.estimated'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value has-value-tooltip"><?= htmlspecialchars(finrap_format_hours($hoursEstimated)) ?><span class="value-tooltip-rich"><?= $tooltipEstimatedHours ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.hours.booked'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.hours.booked'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value has-value-tooltip"><?= htmlspecialchars(finrap_format_hours($hoursBooked)) ?><span class="value-tooltip-rich"><?= $tooltipBookedHours ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.hours.to_go'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.hours.to_go'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value <?= finrap_currency_sign_class($hoursToGo) ?> has-value-tooltip"><?= htmlspecialchars(finrap_format_hours($hoursToGo)) ?><span class="value-tooltip-rich"><?= $tooltipHoursToGo ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.hours.gross_profit_pct'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.hours.gross_profit_pct'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value <?= finrap_currency_sign_class($grossProfitPct) ?> has-value-tooltip"><?= htmlspecialchars(finrap_format_percent($grossProfitPct)) ?><span class="value-tooltip-rich"><?= $tooltipGrossProfitPct ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.hours.order_result_pct'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.hours.order_result_pct'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value <?= finrap_currency_sign_class($orderResultPct) ?> has-value-tooltip" id="metricOrderResultPct"><?= htmlspecialchars(finrap_format_percent($orderResultPct)) ?><span class="value-tooltip-rich"><?= $tooltipOrderResultPct ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.hours.variance_pct'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.hours.variance_pct'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value <?= finrap_currency_sign_class($variancePct) ?> has-value-tooltip" id="metricVariancePct"><?= htmlspecialchars(finrap_format_percent($variancePct)) ?><span class="value-tooltip-rich"><?= $tooltipVariancePct ?></span></span>
                                </div>
                            </div>
                        </div>

                        <div class="analytics-block">
                            <div class="analytics-block-header"><?= htmlspecialchars(LOC('report.block.expected_outcomes'), ENT_QUOTES) ?></div>
                            <div class="analytics-block-body">
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.exp.variance'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.exp.variance'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value <?= finrap_currency_sign_class($expVariance) ?> has-value-tooltip"><?= htmlspecialchars(finrap_format_currency($expVariance)) ?><span class="value-tooltip-rich"><?= $tooltipExpVariance ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.exp.order_result'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.exp.order_result'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value <?= finrap_currency_sign_class($expOrderResult) ?> has-value-tooltip"><?= htmlspecialchars(finrap_format_currency($expOrderResult)) ?><span class="value-tooltip-rich"><?= $tooltipExpOrderResult ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.exp.ipr_result'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.exp.ipr_result'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value <?= finrap_currency_sign_class($iprResult) ?> has-value-tooltip"><?= htmlspecialchars(finrap_format_currency($iprResult)) ?><span class="value-tooltip-rich"><?= $tooltipIprResult ?></span></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.exp.poc_pm'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.exp.poc_pm'), ENT_QUOTES) ?></span>
                                    <span class="analytics-value has-value-tooltip">
                                        <button type="button" class="finrap-editable-value-btn" id="pocPmButton" data-edit-kind="poc_pm">
                                            <?= htmlspecialchars($pocPm === null ? '-' : finrap_format_percent($pocPm)) ?>
                                        </button>
                                        <span class="value-tooltip-rich"><?= $tooltipPocPm ?></span>
                                    </span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label"
                                        data-tooltip="<?= htmlspecialchars(LOC('report.tooltip.exp.poc_calc'), ENT_QUOTES) ?>"><?= htmlspecialchars(LOC('report.exp.poc_calc'), ENT_QUOTES) ?></span>
                                    <span
                                        class="analytics-value has-value-tooltip" id="pocCalcValue"><?= htmlspecialchars(finrap_format_percent($pocCalc)) ?><span class="value-tooltip-rich"><?= $tooltipPocCalc ?></span></span>
                                </div>
                            </div>
                        </div>

                        <div class="analytics-block">
                            <div class="analytics-block-header"><?= htmlspecialchars(LOC('report.block.installments'), ENT_QUOTES) ?></div>
                            <div class="analytics-block-body">
                                <?php if (count($termijnLines) === 0): ?>
                                    <div class="termijn-empty"><?= htmlspecialchars(LOC('report.termijn.empty'), ENT_QUOTES) ?></div>
                                <?php else: ?>
                                    <ul class="termijn-list">
                                        <?php foreach ($termijnLines as $termijnLine): ?>
                                            <?php
                                            $termijnNo = (int) ($termijnLine['termijn_no'] ?? 0);
                                            $termijnDocumentNo = trim((string) ($termijnLine['document_no'] ?? ''));
                                            $termijnDescription = trim((string) ($termijnLine['description'] ?? ''));
                                            $termijnDocumentLabel = $termijnDocumentNo !== ''
                                                ? $termijnDocumentNo
                                                : LOC('report.termijn.label', $termijnNo);
                                            $termijnDocumentTooltip = $termijnDocumentNo !== '' ? $tooltipTermijnDocumentNo : $tooltipTermijnNo;
                                            $termijnAmount = (float) ($termijnLine['amount'] ?? 0.0);
                                            $termijnDate = finrap_format_date_nl((string) ($termijnLine['planning_date'] ?? ''));
                                            $termijnStatus = trim((string) ($termijnLine['status'] ?? ''));
                                            $termijnStatus = $termijnStatus !== '' ? $termijnStatus : (
                                                (float) ($termijnLine['invoiced_amount'] ?? 0.0) > 0.000001 ? LOC('report.termijn.status.invoiced') : LOC('report.termijn.status.open')
                                            );
                                            ?>
                                            <li class="termijn-item">
                                                <span class="termijn-document"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($termijnDocumentLabel), $termijnDocumentTooltip) ?></span>
                                                <span class="termijn-description"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($termijnDescription !== '' ? $termijnDescription : '-'), $tooltipTermijnDescription) ?></span>
                                                <span class="termijn-status"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($termijnStatus), $tooltipTermijnStatus) ?></span>
                                                <span class="termijn-amount"><?= finrap_render_value_with_tooltip_html(htmlspecialchars(finrap_format_currency($termijnAmount)), $tooltipTermijnAmount) ?></span>
                                                <span class="termijn-date"><?= finrap_render_value_with_tooltip_html(htmlspecialchars($termijnDate), $tooltipTermijnDate) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                    </section>

                    <section class="project-modal-cost-groups-section">
                        <?php finrap_render_cost_group_table($taskRows, false, false, true, 'finrapCostDetailTable'); ?>
                    </section>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div id="finrapValueModal" class="finrap-value-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="finrapValueModalTitle">
        <div class="finrap-value-modal">
            <h2 id="finrapValueModalTitle" class="finrap-value-modal-title"></h2>
            <p class="finrap-value-modal-notice"><?= htmlspecialchars(LOC('report.modal.temp_notice'), ENT_QUOTES) ?></p>
            <label for="finrapValueModalInput" class="finrap-value-modal-label" id="finrapValueModalLabel"><?= htmlspecialchars(LOC('report.modal.value_label'), ENT_QUOTES) ?></label>
            <input id="finrapValueModalInput" class="finrap-value-modal-input" type="text" inputmode="decimal" autocomplete="off">
            <div class="finrap-value-modal-actions">
                <button id="finrapValueModalCancel" class="btn btn-back" type="button"><?= htmlspecialchars(LOC('report.btn.cancel'), ENT_QUOTES) ?></button>
                <button id="finrapValueModalSave" class="btn btn-print" type="button"><?= htmlspecialchars(LOC('report.btn.save'), ENT_QUOTES) ?></button>
            </div>
        </div>
    </div>

    <?php if ($error === null): ?>
        <script>
            (function ()
            {
                const finrapI18n = <?= localizationJsTranslations([
                    'report.modal.poc_pm_title',
                    'report.modal.eac_title',
                    'report.modal.value_label',
                ]) ?>;
                const finrapContext = {
                    company: <?= json_encode($company, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    projectNo: <?= json_encode($reportProjectNo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    reportId: <?= json_encode($finrapReportId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    contractValue: <?= json_encode($contractValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    totalDirectCost: <?= json_encode($totalDirectCost, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    expectedCosts: <?= json_encode((float) ($summary['expected_costs'] ?? 0.0), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    taskRows: <?= json_encode($finrapClientTaskRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                };

                let eacOverrides = Object.assign({}, <?= json_encode($finrapClientEacOverrides, JSON_FORCE_OBJECT) ?>);
                let pocPm = <?= json_encode($pocPm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                let pocPmHasOverride = <?= $finrapPocPmHasOverride ? 'true' : 'false' ?>;

                const valueModal = document.getElementById('finrapValueModal');
                const valueModalTitle = document.getElementById('finrapValueModalTitle');
                const valueModalInput = document.getElementById('finrapValueModalInput');
                const valueModalSave = document.getElementById('finrapValueModalSave');
                const valueModalCancel = document.getElementById('finrapValueModalCancel');
                const pocPmButton = document.getElementById('pocPmButton');
                const detailTable = document.getElementById('finrapCostDetailTable');
                const totalsTable = document.getElementById('finrapCostTotalsTable');
                const costGroupTables = [detailTable, totalsTable].filter(function (table) { return table !== null; });

                let modalKind = '';
                let modalTaskCode = '';

                function eacOverrideForTask (taskCode)
                {
                    if (Object.prototype.hasOwnProperty.call(eacOverrides, taskCode))
                    {
                        return eacOverrides[taskCode];
                    }

                    const lowerKey = String(taskCode || '').toLowerCase();
                    if (Object.prototype.hasOwnProperty.call(eacOverrides, lowerKey))
                    {
                        return eacOverrides[lowerKey];
                    }

                    return undefined;
                }

                function updateTooltipDisplay (container, displayText)
                {
                    if (!(container instanceof Element))
                    {
                        return;
                    }

                    const tooltipHolder = container.querySelector('.has-value-tooltip') || container;
                    const tooltipRich = tooltipHolder.querySelector('.value-tooltip-rich');
                    tooltipHolder.textContent = displayText;
                    if (tooltipRich)
                    {
                        tooltipHolder.appendChild(tooltipRich);
                    }
                }

                function parseNumberInput (value)
                {
                    const normalized = String(value || '').trim().replace(/\./g, '').replace(',', '.');
                    const parsed = Number(normalized);
                    return Number.isFinite(parsed) ? parsed : null;
                }

                function formatCurrency (value)
                {
                    const amount = Number(value || 0);
                    return '€ ' + amount.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                function formatPercent (value)
                {
                    const amount = Number(value || 0);
                    return amount.toLocaleString('nl-NL', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
                }

                function signClass (value)
                {
                    const epsilon = 0.000001;
                    if (Math.abs(value) < epsilon)
                    {
                        return 'is-zero';
                    }

                    return value > 0 ? 'is-positive' : 'is-negative';
                }

                function taskNoToNumeric (value)
                {
                    const parts = String(value || '').trim().split('-');
                    if (parts.length !== 3)
                    {
                        return null;
                    }

                    return (Number(parts[0]) * 1000000) + (Number(parts[1]) * 1000) + Number(parts[2]);
                }

                function parseTotalingRange (totaling)
                {
                    const match = String(totaling || '').trim().match(/^(\d{3}-\d{3}-\d{3})\.\.(\d{3}-\d{3}-\d{3})$/);
                    if (!match)
                    {
                        return null;
                    }

                    const fromNumeric = taskNoToNumeric(match[1]);
                    const toNumeric = taskNoToNumeric(match[2]);
                    if (fromNumeric === null || toNumeric === null || fromNumeric > toNumeric)
                    {
                        return null;
                    }

                    return { from: fromNumeric, to: toNumeric };
                }

                function taskInRange (taskNo, range)
                {
                    const taskNumeric = taskNoToNumeric(taskNo);
                    if (taskNumeric === null)
                    {
                        return false;
                    }

                    return taskNumeric >= range.from && taskNumeric <= range.to;
                }

                function aggregateDetailRows (rows)
                {
                    let budgetTotal = 0;
                    let eacTotal = 0;
                    let bookedTotal = 0;
                    let obligationsTotal = 0;

                    rows.forEach(function (row)
                    {
                        if (row.is_total_row)
                        {
                            return;
                        }

                        budgetTotal += Number(row.budget_cost || 0);
                        eacTotal += Number(row.eac || 0);
                        bookedTotal += Number(row.booked_cost || 0);
                        obligationsTotal += Number(row.entered_obligations || 0);
                    });

                    return {
                        budget_cost: budgetTotal,
                        eac: eacTotal,
                        booked_cost: bookedTotal,
                        entered_obligations: obligationsTotal,
                        variance_budget_eac: budgetTotal - eacTotal
                    };
                }

                function recalculateTaskRows ()
                {
                    const rows = finrapContext.taskRows.map(function (row)
                    {
                        return Object.assign({}, row);
                    });

                    rows.forEach(function (row)
                    {
                        if (row.is_total_row)
                        {
                            return;
                        }

                        const override = eacOverrideForTask(row.code);
                        if (override !== undefined && override !== null)
                        {
                            row.eac = Number(override);
                        }
                    });

                    rows.forEach(function (row)
                    {
                        if (!row.is_total_row)
                        {
                            return;
                        }

                        const range = parseTotalingRange(row.totaling);
                        if (!range)
                        {
                            return;
                        }

                        let budgetTotal = 0;
                        let eacTotal = 0;
                        let bookedTotal = 0;
                        let obligationsTotal = 0;

                        rows.forEach(function (detailRow)
                        {
                            if (detailRow.is_total_row || !taskInRange(detailRow.code, range))
                            {
                                return;
                            }

                            budgetTotal += Number(detailRow.budget_cost || 0);
                            eacTotal += Number(detailRow.eac || 0);
                            bookedTotal += Number(detailRow.booked_cost || 0);
                            obligationsTotal += Number(detailRow.entered_obligations || 0);
                        });

                        row.budget_cost = budgetTotal;
                        row.eac = eacTotal;
                        row.booked_cost = bookedTotal;
                        row.entered_obligations = obligationsTotal;
                    });

                    rows.forEach(function (row)
                    {
                        row.variance_budget_eac = Number(row.budget_cost || 0) - Number(row.eac || 0);
                    });

                    return rows;
                }

                function getReportSummaryTotals (rows)
                {
                    return aggregateDetailRows(rows);
                }

                function updateMetricCell (id, value)
                {
                    const cell = document.getElementById(id);
                    if (!cell)
                    {
                        return;
                    }

                    updateTooltipDisplay(cell, formatCurrency(value));
                    cell.classList.remove('is-positive', 'is-negative', 'is-zero');
                    cell.classList.add(signClass(value));
                }

                function updateAnalyticsValue (elementId, value, formatter)
                {
                    const element = document.getElementById(elementId);
                    if (!element)
                    {
                        return;
                    }

                    updateTooltipDisplay(element, formatter(value));
                    element.classList.remove('is-positive', 'is-negative', 'is-zero');
                    element.classList.add(signClass(value));
                }

                function updateTableCellInTable (table, taskCode, metricKey, value)
                {
                    if (!table)
                    {
                        return;
                    }

                    const row = table.querySelector('tr[data-task-code="' + CSS.escape(taskCode) + '"]');
                    if (!row)
                    {
                        return;
                    }

                    const cell = row.querySelector('[data-metric-key="' + metricKey + '"]');
                    if (!cell)
                    {
                        return;
                    }

                    const displayTarget = cell.querySelector('.finrap-eac-edit-btn') || cell;
                    updateTooltipDisplay(displayTarget, formatCurrency(value));
                    cell.classList.remove('is-positive', 'is-negative', 'is-zero', 'finrap-eac-cell');
                    cell.classList.add(signClass(value));
                    if (metricKey === 'EAC')
                    {
                        cell.classList.add('finrap-eac-cell');
                    }
                }

                function updateTableCell (taskCode, metricKey, value)
                {
                    costGroupTables.forEach(function (table)
                    {
                        updateTableCellInTable(table, taskCode, metricKey, value);
                    });
                }

                function calculatePocPercent (bookedCost, eac)
                {
                    const booked = Number(bookedCost || 0);
                    const eacAmount = Number(eac || 0);
                    if (Math.abs(eacAmount) < 0.000001)
                    {
                        return 0;
                    }

                    return (booked / eacAmount) * 100;
                }

                function renderCalculatedState ()
                {
                    const rows = recalculateTaskRows();
                    const summaryTotals = getReportSummaryTotals(rows);
                    const budgetCost = Number(summaryTotals.budget_cost || 0);
                    const eac = Number(summaryTotals.eac || 0);
                    const bookedCost = Number(summaryTotals.booked_cost || 0);
                    const obligations = Number(summaryTotals.entered_obligations || 0);
                    const variance = Number(summaryTotals.variance_budget_eac || (budgetCost - eac));
                    const grossProfit = Number(finrapContext.contractValue || 0) - Number(finrapContext.totalDirectCost || 0);
                    const orderResult = grossProfit - variance;
                    const pocCalc = calculatePocPercent(bookedCost, eac);
                    const contractValue = Number(finrapContext.contractValue || 0);
                    const variancePct = Math.abs(contractValue) > 0.000001 ? (variance / contractValue * 100) : 0;
                    const orderResultPct = Math.abs(contractValue) > 0.000001 ? (orderResult / contractValue * 100) : 0;

                    if (!pocPmHasOverride)
                    {
                        pocPm = pocCalc;
                        if (pocPmButton)
                        {
                            pocPmButton.textContent = formatPercent(pocPm);
                        }
                    }

                    updateMetricCell('metricBudgetCost', budgetCost);
                    updateMetricCell('metricEac', eac);
                    updateMetricCell('metricBookedCost', bookedCost);
                    updateMetricCell('metricObligations', obligations);
                    updateMetricCell('metricVariance', variance);
                    updateMetricCell('metricOrderResult', orderResult);
                    updateAnalyticsValue('pocCalcValue', pocCalc, formatPercent);

                    const variancePctEl = document.getElementById('metricVariancePct');
                    if (variancePctEl)
                    {
                        updateTooltipDisplay(variancePctEl, formatPercent(variancePct));
                        variancePctEl.classList.remove('is-positive', 'is-negative', 'is-zero');
                        variancePctEl.classList.add(signClass(variancePct));
                    }

                    const orderResultPctEl = document.getElementById('metricOrderResultPct');
                    if (orderResultPctEl)
                    {
                        updateTooltipDisplay(orderResultPctEl, formatPercent(orderResultPct));
                        orderResultPctEl.classList.remove('is-positive', 'is-negative', 'is-zero');
                        orderResultPctEl.classList.add(signClass(orderResultPct));
                    }

                    rows.forEach(function (row)
                    {
                        updateTableCell(row.code, 'Budget_Cost', row.budget_cost);
                        updateTableCell(row.code, 'EAC', row.eac);
                        updateTableCell(row.code, 'Booked_Cost', row.booked_cost);
                        updateTableCell(row.code, 'Entered_Obligations', row.entered_obligations);
                        updateTableCell(row.code, 'Variance_Budget_EAC', row.variance_budget_eac);
                    });
                }

                function openValueModal (kind, taskCode, currentValue)
                {
                    modalKind = kind;
                    modalTaskCode = taskCode || '';
                    if (valueModalTitle)
                    {
                        valueModalTitle.textContent = kind === 'poc_pm'
                            ? finrapI18n['report.modal.poc_pm_title']
                            : finrapI18n['report.modal.eac_title'] + (taskCode ? ' (' + taskCode + ')' : '');
                    }
                    if (valueModalInput)
                    {
                        valueModalInput.value = currentValue === null || currentValue === undefined ? '' : String(currentValue).replace('.', ',');
                    }
                    if (valueModal)
                    {
                        valueModal.classList.add('is-visible');
                    }
                    if (valueModalInput)
                    {
                        valueModalInput.focus();
                        valueModalInput.select();
                    }
                }

                function closeValueModal ()
                {
                    modalKind = '';
                    modalTaskCode = '';
                    if (valueModal)
                    {
                        valueModal.classList.remove('is-visible');
                    }
                }

                function saveOverrides ()
                {
                    if (!finrapContext.reportId)
                    {
                        return Promise.resolve(null);
                    }

                    const body = new URLSearchParams({
                        company: finrapContext.company,
                        project_no: finrapContext.projectNo,
                        report_id: finrapContext.reportId,
                        eac_by_task: JSON.stringify(eacOverrides)
                    });

                    if (pocPmHasOverride)
                    {
                        body.set('poc_pm', pocPm === null ? '' : String(pocPm));
                    }

                    return fetch('finrap.php?action=save_overrides', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: body
                    }).then(function (res)
                    {
                        return res.json();
                    });
                }

                if (pocPmButton)
                {
                    pocPmButton.addEventListener('click', function ()
                    {
                        openValueModal('poc_pm', '', pocPm);
                    });
                }

                if (detailTable)
                {
                    detailTable.addEventListener('click', function (event)
                    {
                        const target = event.target;
                        if (!(target instanceof Element))
                        {
                            return;
                        }

                        const button = target.closest('.finrap-eac-edit-btn');
                        if (!(button instanceof HTMLButtonElement))
                        {
                            return;
                        }

                        const taskCode = String(button.dataset.taskCode || '').trim();
                        if (taskCode === '')
                        {
                            return;
                        }

                        const rows = recalculateTaskRows();
                        const taskRow = rows.find(function (row) { return row.code === taskCode; });
                        const current = eacOverrideForTask(taskCode);
                        const displayValue = current !== undefined ? current : (taskRow ? Number(taskRow.eac || 0) : 0);
                        openValueModal('eac', taskCode, displayValue);
                    });
                }

                if (valueModalCancel)
                {
                    valueModalCancel.addEventListener('click', closeValueModal);
                }

                if (valueModal)
                {
                    valueModal.addEventListener('click', function (event)
                    {
                        if (event.target === valueModal)
                        {
                            closeValueModal();
                        }
                    });
                }

                if (valueModalSave)
                {
                    valueModalSave.addEventListener('click', function ()
                    {
                        const parsed = parseNumberInput(valueModalInput ? valueModalInput.value : '');
                        if (parsed === null)
                        {
                            return;
                        }

                        const saveKind = modalKind;
                        const saveTaskCode = modalTaskCode;

                        if (saveKind === 'poc_pm')
                        {
                            pocPm = parsed;
                            pocPmHasOverride = true;
                            if (pocPmButton)
                            {
                                pocPmButton.textContent = formatPercent(pocPm);
                            }
                        }
                        else if (saveKind === 'eac' && saveTaskCode !== '')
                        {
                            eacOverrides[saveTaskCode] = parsed;
                        }

                        closeValueModal();
                        renderCalculatedState();
                        saveOverrides().catch(function () { return null; });
                    });
                }

                renderCalculatedState();
            })();
        </script>
    <?php endif; ?>

    <?php if ($autoPrint && $error === null): ?>
        <script>
            window.addEventListener('load', function ()
            {
                window.print();
            });
        </script>
    <?php endif; ?>
    <?php if (!$embedMode): ?>
        <?php renderLanguageSwitcherScript(); ?>
    <?php endif; ?>
</body>

</html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Includes/requires
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';
require_once __DIR__ . '/finrap_data.php';

/**
 * Functies
 */
function finrap_format_hours(float $value): string
{
    return number_format($value, 1, ',', '.') . ' u';
}

function finrap_format_percent(float $value): string
{
    return number_format($value, 1, ',', '.') . '%';
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
    static $months = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maart',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Augustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'December',
    ];

    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
        return $yearMonth;
    }

    [$year, $month] = explode('-', $yearMonth);
    return ($months[$month] ?? $month) . ' ' . $year;
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

    $months = [
        '01' => 'januari',
        '02' => 'februari',
        '03' => 'maart',
        '04' => 'april',
        '05' => 'mei',
        '06' => 'juni',
        '07' => 'juli',
        '08' => 'augustus',
        '09' => 'september',
        '10' => 'oktober',
        '11' => 'november',
        '12' => 'december',
    ];

    $monthNumber = $dt->format('m');
    $monthLabel = $months[$monthNumber] ?? $monthNumber;

    return $dt->format('j') . ' ' . $monthLabel . ' ' . $dt->format('Y, H:i');
}

function finrap_cost_group_columns(): array
{
    return [
        ['key' => 'Cost_Group_Code', 'label' => 'Cost group code', 'is_right' => false, 'tooltip' => ''],
        ['key' => 'Cost_Group_Description', 'label' => 'Cost Group Description', 'is_right' => false, 'tooltip' => ''],
        ['key' => 'Budget_Cost', 'label' => 'Budget Cost', 'is_right' => true, 'tooltip' => 'Begrote kosten voor deze kostengroep'],
        ['key' => 'EAC', 'label' => 'EAC', 'is_right' => true, 'tooltip' => 'Estimate At Completion: totale verwachte kosten aan einde project'],
        ['key' => 'Booked_Cost', 'label' => 'Booked Cost', 'is_right' => true, 'tooltip' => 'Werkelijk geboekte kosten tot nu toe'],
        ['key' => 'Entered_Obligations', 'label' => 'Entered Obligations', 'is_right' => true, 'tooltip' => 'Gereserveerde bedragen voor bestellingen/verplichtingen'],
        ['key' => 'Variance_Budget_EAC', 'label' => 'Variance Budget - EAC', 'is_right' => true, 'tooltip' => 'Verschil tussen begroting en verwachte kosten'],
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

function finrap_render_cost_group_table(array $taskRows, bool $totalsOnly = false, bool $hideAllZeroTotals = false): void
{
    $columns = finrap_cost_group_columns();

    echo '<table class="project-cost-group-table">';
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

        echo '<tr' . ($rowClass !== '' ? ' class="' . htmlspecialchars($rowClass) . '"' : '') . '>';
        foreach ($columns as $column) {
            $columnKey = (string) ($column['key'] ?? '');
            $isRight = (bool) ($column['is_right'] ?? false);

            if ($columnKey === 'Cost_Group_Code') {
                $cellClass = $isRight ? ' class="is-right"' : '';
                echo '<td' . $cellClass . '>' . htmlspecialchars($taskCode) . '</td>';
                continue;
            }

            if ($columnKey === 'Cost_Group_Description') {
                echo '<td class="is-description">' . htmlspecialchars($descriptionValue) . '</td>';
                continue;
            }

            $value = finance_to_float($row[$columnKey] ?? 0.0);
            if ($columnKey === 'Variance_Budget_EAC') {
                $cellClass = 'is-right ' . finrap_currency_sign_class($value);
                echo '<td class="' . htmlspecialchars($cellClass) . '">' . htmlspecialchars(finrap_format_currency($value)) . '</td>';
                continue;
            }

            $cellClass = trim('is-right ' . finrap_currency_sign_class($value));
            echo '<td class="' . htmlspecialchars($cellClass) . '">' . htmlspecialchars(finrap_format_currency($value)) . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
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

$report = null;
$error = null;
if ($company === '' || $projectNo === '') {
    $error = 'Ongeldige parameters. Open dit rapport via de startpagina.';
} else {
    if ($reportId !== '') {
        $report = finrap_load_report_snapshot($company, $projectNo, $reportId);
    } elseif (preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
        // Legacy fallback for old month-based URLs.
        $report = finrap_load($company, $projectNo, $yearMonth);
    }

    if (!is_array($report)) {
        $error = 'Geen opgeslagen rapport gevonden voor dit project. Genereer eerst een rapport op de startpagina.';
    }
}

$project = is_array($report['project'] ?? null) ? $report['project'] : [];
$summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
$modal = is_array($report['project_modal'] ?? null) ? $report['project_modal'] : [];
$taskRows = is_array($modal['task_rows'] ?? null) ? $modal['task_rows'] : [];

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
$variance = (float) ($modal['budget_cost_total'] ?? 0.0);
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
$pocPercent = (float) ($project['Percent_Completed'] ?? 0.0);
$iprResult = (float) ($project['Recog_Profit_Amount'] ?? 0.0);

$termijnLines = is_array($modal['termijn_lines'] ?? null) ? $modal['termijn_lines'] : [];
?>
<!doctype html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="stylesheet" href="brand.css">
    <title>FinRap <?= htmlspecialchars($reportProjectNo) ?></title>
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
            border-bottom: 1px dotted currentColor;
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
            grid-template-columns: auto 1fr auto;
            gap: 4px 8px;
            align-items: start;
            font-size: 11px;
            padding: 5px 10px;
            border-top: 1px solid #f0f4fa;
            line-height: 1.4;
        }

        .termijn-item:first-child {
            border-top: 0;
        }

        .termijn-no {
            font-weight: 700;
            color: var(--kvt-perkins-blue);
            white-space: nowrap;
        }

        .termijn-meta {
            color: #475569;
        }

        .termijn-status {
            font-weight: 700;
            white-space: nowrap;
            color: #1f2937;
        }

        .termijn-amount {
            grid-column: 2 / -1;
            font-weight: 700;
            color: #1f2937;
        }

        .termijn-empty {
            padding: 10px;
            font-size: 12px;
            color: #94a3b8;
            font-style: italic;
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
                                <div class="project-field-label">Ordernumber</div>
                                <div class="project-field-value"><?= htmlspecialchars($reportProjectNo) ?></div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label">OrderReference</div>
                                <div class="project-field-value"><?= htmlspecialchars($orderReference) ?></div>
                            </div>
                        </div>
                        <div class="project-modal-column">
                            <div class="project-field">
                                <div class="project-field-label">Description</div>
                                <div class="project-field-value"><?= htmlspecialchars($description) ?></div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label">Rapport gemaakt op</div>
                                <div class="project-field-value"><?= htmlspecialchars($createdAtFormatted) ?></div>
                            </div>
                        </div>
                    </section>

                    <section class="project-modal-info-panel">
                        <div class="project-modal-column">
                            <div class="project-field">
                                <div class="project-field-label">Customer</div>
                                <div class="project-field-value"><?= htmlspecialchars($customer) ?></div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label">Project manager</div>
                                <div class="project-field-value">
                                    <?= str_replace("KVT\\", "", htmlspecialchars($projectManager)) ?>
                                </div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label">Order type</div>
                                <div class="project-field-value">-</div>
                            </div>
                        </div>
                        <div class="project-modal-column">
                            <div class="project-field">
                                <div class="project-field-label">Order Date</div>
                                <div class="project-field-value">
                                    <?= htmlspecialchars((string) ($project['Creation_Date'] ?? '')) ?>
                                </div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label">Completed date</div>
                                <div class="project-field-value">
                                    <?= htmlspecialchars((string) ($project['Ending_Date'] ?? '')) ?>
                                </div>
                            </div>
                            <div class="project-field">
                                <div class="project-field-label">Sales Manager</div>
                                <div class="project-field-value">
                                    <?= htmlspecialchars((string) ($project['KVT_Sales_Person_Code'] ?? '')) ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="project-modal-summary-section">
                        <table class="project-metric-table">
                            <thead>
                                <tr>
                                    <th class="is-right" data-tooltip="Totale contractwaarde met klant">Contract Value</th>
                                    <th class="is-right" data-tooltip="Totale verwachte directe kosten">Total Direct Cost</th>
                                    <th class="is-right" data-tooltip="Contractwaarde minus directe kosten">Gross Profit</th>
                                    <th class="is-right" data-tooltip="Verschil tussen begrote en verwachte kosten">Variance Budget - EAC</th>
                                    <th class="is-right" data-tooltip="Netto winst na alle kosten en verwachtingen">Order Result</th>
                                    <th class="is-right" data-tooltip="Totaal gefactureerde bedrag tot nu toe">Installments Invoiced</th>
                                    <th class="is-right" data-tooltip="Totaal ontvangen betalingen tot nu toe">Installments Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="is-right <?= finrap_currency_sign_class($contractValue) ?>">
                                        <?= htmlspecialchars(finrap_format_currency($contractValue)) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($totalDirectCost) ?>">
                                        <?= htmlspecialchars(finrap_format_currency($totalDirectCost)) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($grossProfit) ?>">
                                        <?= htmlspecialchars(finrap_format_currency($grossProfit)) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($variance) ?>">
                                        <?= htmlspecialchars(finrap_format_currency($variance)) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($orderResult) ?>">
                                        <?= htmlspecialchars(finrap_format_currency($orderResult)) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($installmentsInvoiced) ?>">
                                        <?= htmlspecialchars(finrap_format_currency($installmentsInvoiced)) ?>
                                    </td>
                                    <td class="is-right <?= finrap_currency_sign_class($installmentsReceived) ?>">
                                        <?= htmlspecialchars(finrap_format_currency($installmentsReceived)) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="project-modal-cost-groups-section">
                        <?php finrap_render_cost_group_table($taskRows, true, true); ?>
                    </section>

                    <section class="analytics-blocks-section">

                        <div class="analytics-block">
                            <div class="analytics-block-header">Uren &amp; marges</div>
                            <div class="analytics-block-body">
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Gepland aantal uren voor het project">Budget Hours</span>
                                    <span class="analytics-value"><?= htmlspecialchars(finrap_format_hours($hoursBudget)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Verwacht aantal uren op basis van huidige prognose">Estimated Hours</span>
                                    <span class="analytics-value"><?= htmlspecialchars(finrap_format_hours($hoursEstimated)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Werkelijk geboekt aantal uren tot nu toe">Booked Hours</span>
                                    <span class="analytics-value"><?= htmlspecialchars(finrap_format_hours($hoursBooked)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Resterende uren: Geschat - Geboekt">Hours to go</span>
                                    <span class="analytics-value <?= finrap_currency_sign_class($hoursToGo) ?>"><?= htmlspecialchars(finrap_format_hours($hoursToGo)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Bruto winst in procenten van contractwaarde">Gross profit %</span>
                                    <span class="analytics-value <?= finrap_currency_sign_class($grossProfitPct) ?>"><?= htmlspecialchars(finrap_format_percent($grossProfitPct)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Netto winst in procenten van contractwaarde">Order result %</span>
                                    <span class="analytics-value <?= finrap_currency_sign_class($orderResultPct) ?>"><?= htmlspecialchars(finrap_format_percent($orderResultPct)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Verschil tussen budget en EAC in procenten van contractwaarde">Variance budget - EAC %</span>
                                    <span class="analytics-value <?= finrap_currency_sign_class($variancePct) ?>"><?= htmlspecialchars(finrap_format_percent($variancePct)) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="analytics-block">
                            <div class="analytics-block-header">Verwachte uitkomsten</div>
                            <div class="analytics-block-body">
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Verwachte kosten minus begrote kosten">Exp. Variance Budget - EAC</span>
                                    <span class="analytics-value <?= finrap_currency_sign_class($expVariance) ?>"><?= htmlspecialchars(finrap_format_currency($expVariance)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Contractwaarde minus verwachte kosten">Expected Order Result</span>
                                    <span class="analytics-value <?= finrap_currency_sign_class($expOrderResult) ?>"><?= htmlspecialchars(finrap_format_currency($expOrderResult)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Gerealiseerde winst uit WIP-berekening, oftewel wat er tot nu toe overblijft">IPR result</span>
                                    <span class="analytics-value <?= finrap_currency_sign_class($iprResult) ?>"><?= htmlspecialchars(finrap_format_currency($iprResult)) ?></span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-label" data-tooltip="Voortgang in % van het project">POC</span>
                                    <span class="analytics-value"><?= htmlspecialchars(finrap_format_percent($pocPercent)) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="analytics-block">
                            <div class="analytics-block-header">Termijn facturen</div>
                            <div class="analytics-block-body">
                                <?php if (count($termijnLines) === 0): ?>
                                    <div class="termijn-empty">Geen termijn facturen gevonden.</div>
                                <?php else: ?>
                                    <ul class="termijn-list">
                                        <?php foreach ($termijnLines as $termijnLine): ?>
                                            <?php
                                            $termijnNo = (int) ($termijnLine['termijn_no'] ?? 0);
                                            $termijnAmount = (float) ($termijnLine['amount'] ?? 0.0);
                                            $termijnDate = finrap_format_date_nl((string) ($termijnLine['planning_date'] ?? ''));
                                            $termijnStatus = trim((string) ($termijnLine['status'] ?? ''));
                                            $termijnStatus = $termijnStatus !== '' ? $termijnStatus : (
                                                (float) ($termijnLine['invoiced_amount'] ?? 0.0) > 0.000001 ? 'Gefactureerd' : 'Openstaand'
                                            );
                                            ?>
                                            <li class="termijn-item">
                                                <span class="termijn-no">Termijn <?= $termijnNo ?></span>
                                                <span class="termijn-meta"><?= htmlspecialchars($termijnDate) ?></span>
                                                <span class="termijn-status"><?= htmlspecialchars($termijnStatus) ?></span>
                                                <span class="termijn-amount"><?= htmlspecialchars(finrap_format_currency($termijnAmount)) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                    </section>

                    <section class="project-modal-cost-groups-section">
                        <?php finrap_render_cost_group_table($taskRows, false, false); ?>
                    </section>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php if ($autoPrint && $error === null): ?>
        <script>
            window.addEventListener('load', function ()
            {
                window.print();
            });
        </script>
    <?php endif; ?>
</body>

</html>
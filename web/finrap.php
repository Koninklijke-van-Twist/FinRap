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
function finrap_format_currency(float $value): string
{
    return 'EUR ' . number_format($value, 2, ',', '.');
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

/**
 * Page load
 */
$company = trim((string) ($_GET['company'] ?? ''));
$projectNo = trim((string) ($_GET['project_no'] ?? ''));
$yearMonth = trim((string) ($_GET['year_month'] ?? ''));
$autoPrint = (string) ($_GET['print'] ?? '') === '1';
$embedMode = (string) ($_GET['embed'] ?? '') === '1';

$report = null;
$error = null;
if ($company === '' || $projectNo === '' || !preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
    $error = 'Ongeldige parameters. Open dit rapport via de startpagina.';
} else {
    $report = finrap_load($company, $projectNo, $yearMonth);
    if (!is_array($report)) {
        $error = 'Geen cache gevonden voor dit project en deze maand. Genereer de maand eerst op de startpagina.';
    }
}

$project = is_array($report['project'] ?? null) ? $report['project'] : [];
$summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
$modal = is_array($report['project_modal'] ?? null) ? $report['project_modal'] : [];
$taskRows = is_array($modal['task_rows'] ?? null) ? $modal['task_rows'] : [];
$taskTotal = is_array($modal['task_rows_total'] ?? null) ? $modal['task_rows_total'] : [];

$reportProjectNo = (string) ($report['project_no'] ?? $projectNo);
$description = (string) ($project['Description'] ?? '');
$customerNo = trim((string) ($project['Bill_to_Customer_No'] ?? $project['Sell_to_Customer_No'] ?? ''));
$customerName = trim((string) ($project['Bill_to_Name'] ?? $project['Sell_to_Customer_Name'] ?? ''));
$customer = trim($customerNo . ($customerName !== '' ? ' - ' . $customerName : ''));
$projectManager = (string) ($project['Project_Manager'] ?? $project['Person_Responsible'] ?? '');
$orderReference = (string) ($project['Your_Reference'] ?? $project['LVS_Your_reference'] ?? '');
$createdAt = (string) ($report['fetched_at'] ?? '');
$contractValue = (float) ($modal['contract_value'] ?? 0.0);
$totalDirectCost = (float) ($summary['expected_costs'] ?? 0.0);
$grossProfit = $contractValue - $totalDirectCost;
$variance = (float) ($modal['budget_cost_total'] ?? 0.0);
$orderResult = $grossProfit - $variance;
$installmentsInvoiced = (float) ($summary['total_revenue'] ?? 0.0);
$installmentsReceived = (float) ($modal['installments_received'] ?? 0.0);
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
            overflow: auto;
            background: #f8fbff;
            padding: 0;
        }

        body.embed-mode .project-modal-body {
            overflow: auto;
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
            overflow: hidden;
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

        .project-cost-group-table tbody tr.is-grand-total-row td {
            font-weight: 700;
            background: #dbeafe;
            border-top: 2px solid var(--kvt-perkins-blue);
        }

        .is-positive {
            color: #0f7a34;
            font-weight: 700;
        }

        .is-negative {
            color: #c62828;
            font-weight: 700;
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
            body {
                background: #ffffff;
            }

            .project-overlay {
                position: static;
                inset: auto;
                padding: 0;
                background: transparent;
                display: block;
            }

            .project-modal-head-actions {
                display: none !important;
            }

            .project-modal {
                border: 0;
                box-shadow: none;
                border-radius: 0;
                width: 100%;
                height: auto;
            }

            .project-modal-body {
                overflow: visible;
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
                                <div class="project-field-value"><?= htmlspecialchars($projectManager) ?></div>
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
                                    <th class="is-right">Contract Value</th>
                                    <th class="is-right">Total Direct Cost</th>
                                    <th class="is-right">Gross Profit</th>
                                    <th class="is-right">Variance Budget - EAC</th>
                                    <th class="is-right">Order Result</th>
                                    <th class="is-right">Installments Invoiced</th>
                                    <th class="is-right">Installments Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="is-right"><?= htmlspecialchars(finrap_format_currency($contractValue)) ?>
                                    </td>
                                    <td class="is-right"><?= htmlspecialchars(finrap_format_currency($totalDirectCost)) ?>
                                    </td>
                                    <td class="is-right <?= $grossProfit >= 0 ? 'is-positive' : 'is-negative' ?>">
                                        <?= htmlspecialchars(finrap_format_currency($grossProfit)) ?>
                                    </td>
                                    <td class="is-right <?= $variance >= 0 ? 'is-positive' : 'is-negative' ?>">
                                        <?= htmlspecialchars(finrap_format_currency($variance)) ?>
                                    </td>
                                    <td class="is-right <?= $orderResult >= 0 ? 'is-positive' : 'is-negative' ?>">
                                        <?= htmlspecialchars(finrap_format_currency($orderResult)) ?>
                                    </td>
                                    <td class="is-right">
                                        <?= htmlspecialchars(finrap_format_currency($installmentsInvoiced)) ?>
                                    </td>
                                    <td class="is-right">
                                        <?= htmlspecialchars(finrap_format_currency($installmentsReceived)) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="project-modal-cost-groups-section">
                        <table class="project-cost-group-table">
                            <thead>
                                <tr>
                                    <th>Cost group code</th>
                                    <th>Cost Group Description</th>
                                    <th class="is-right">Budget Cost</th>
                                    <th class="is-right">EAC</th>
                                    <th class="is-right">Booked Cost</th>
                                    <th class="is-right">Entered Obligations</th>
                                    <th class="is-right">Variance Budget - EAC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taskRows as $row): ?>
                                    <?php
                                    $isTotalRow = (bool) ($row['Is_Total_Row'] ?? false);
                                    $taskCode = (string) ($row['Cost_Group_Code'] ?? '');
                                    $descriptionValue = (string) ($row['Cost_Group_Description'] ?? '');
                                    if (preg_match('/^\d{3}-000-000$/', $taskCode) === 1 && $isTotalRow) {
                                        $descriptionValue = (string) ($row['Cost_Group_Description'] ?? '');
                                    } elseif (preg_match('/^\d{3}-\d{3}-000$/', $taskCode) === 1 && $isTotalRow) {
                                        $descriptionValue = "\u{00A0}\u{00A0}" . $descriptionValue;
                                    } elseif (preg_match('/^\d{3}-\d{3}-\d{3}$/', $taskCode) === 1 && !$isTotalRow) {
                                        $descriptionValue = "\u{00A0}\u{00A0}\u{00A0}\u{00A0}" . $descriptionValue;
                                    }
                                    $varianceBudget = (float) ($row['Variance_Budget_EAC'] ?? 0.0);
                                    $rowClass = '';
                                    if ($isTotalRow) {
                                        $rowClass = 'is-total-row';
                                        if (preg_match('/^\d{3}-000-000$/', $taskCode) === 1) {
                                            $rowClass .= ' is-major-total-row';
                                        } elseif (preg_match('/^\d{3}-\d{3}-000$/', $taskCode) === 1) {
                                            $rowClass .= ' is-minor-total-row';
                                        }
                                    }
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td><?= htmlspecialchars($taskCode) ?></td>
                                        <td class="is-description"><?= htmlspecialchars($descriptionValue) ?></td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($row['Budget_Cost'] ?? 0.0))) ?>
                                        </td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($row['EAC'] ?? 0.0))) ?>
                                        </td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($row['Booked_Cost'] ?? 0.0))) ?>
                                        </td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($row['Entered_Obligations'] ?? 0.0))) ?>
                                        </td>
                                        <td class="is-right <?= $varianceBudget >= 0 ? 'is-positive' : 'is-negative' ?>">
                                            <?= htmlspecialchars(finrap_format_currency($varianceBudget)) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($taskTotal !== []): ?>
                                    <tr class="is-grand-total-row">
                                        <td><?= htmlspecialchars((string) ($taskTotal['Cost_Group_Code'] ?? 'TOTAL')) ?></td>
                                        <td class="is-description">
                                            <?= htmlspecialchars((string) ($taskTotal['Cost_Group_Description'] ?? 'Totaal alle regels')) ?>
                                        </td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($taskTotal['Budget_Cost'] ?? 0.0))) ?>
                                        </td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($taskTotal['EAC'] ?? 0.0))) ?>
                                        </td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($taskTotal['Booked_Cost'] ?? 0.0))) ?>
                                        </td>
                                        <td class="is-right">
                                            <?= htmlspecialchars(finrap_format_currency((float) ($taskTotal['Entered_Obligations'] ?? 0.0))) ?>
                                        </td>
                                        <?php $totalVariance = (float) ($taskTotal['Variance_Budget_EAC'] ?? 0.0); ?>
                                        <td class="is-right <?= $totalVariance >= 0 ? 'is-positive' : 'is-negative' ?>">
                                            <?= htmlspecialchars(finrap_format_currency($totalVariance)) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
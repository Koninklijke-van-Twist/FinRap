<?php
/**
 * TEMPORARY #787 verification — do not keep on master.
 *
 * Synthetic PRJ2602236 fixture: aanneemsom/omzet must be €280.000 when only
 * Job Planning Lines with Type=GB-rekening and No=800000 are included.
 * Resource/item/other G/L/forecast distractors must be excluded.
 * No live Business Central calls.
 */

declare(strict_types=1);

require_once __DIR__ . '/../web/finrap_data.php';

const TMP_787_EXPECTED_AANNEEMSOM = 280000.0;

/**
 * Synthetic FactureerbareProjectPlanningsRegels rows modeled on PRJ2602236.
 * Unfiltered Line_Amount_LCY is inflated; 800000 G/L billable lines sum to 280.000.
 */
function tmp_787_prj2602236_planning_fixture(): array
{
    return [
        [
            'Job_No' => 'PRJ2602236',
            'Job_Task_No' => '100-000-000',
            'Line_No' => 10000,
            'Line_Type' => 'Factureerbaar',
            'Type' => 'GB-rekening',
            'No' => '800000',
            'Description' => 'Aanneemsom',
            'Line_Amount_LCY' => 200000.0,
            'Invoiced_Amount_LCY' => 0.0,
            'LVS_Job_Change_Order_No' => '',
        ],
        [
            'Job_No' => 'PRJ2602236',
            'Job_Task_No' => '100-000-000',
            'Line_No' => 20000,
            'Line_Type' => 'Factureerbaar',
            'Type' => 'GB-rekening',
            'No' => '800000',
            'Description' => 'Aanneemsom restant',
            'Line_Amount_LCY' => 80000.0,
            'Invoiced_Amount_LCY' => 0.0,
            'LVS_Job_Change_Order_No' => '',
        ],
        [
            'Job_No' => 'PRJ2602236',
            'Job_Task_No' => '200-000-000',
            'Line_No' => 30000,
            'Line_Type' => 'Factureerbaar',
            'Type' => 'Resource',
            'No' => 'MONTEUR',
            'Description' => 'Urenboeking (distractor)',
            'Line_Amount_LCY' => 45000.0,
            'Invoiced_Amount_LCY' => 0.0,
            'LVS_Job_Change_Order_No' => '',
        ],
        [
            'Job_No' => 'PRJ2602236',
            'Job_Task_No' => '200-000-000',
            'Line_No' => 40000,
            'Line_Type' => 'Factureerbaar',
            'Type' => 'Artikel',
            'No' => 'ART-100',
            'Description' => 'Materiaalboeking (distractor)',
            'Line_Amount_LCY' => 12500.0,
            'Invoiced_Amount_LCY' => 0.0,
            'LVS_Job_Change_Order_No' => '',
        ],
        [
            'Job_No' => 'PRJ2602236',
            'Job_Task_No' => '300-000-000',
            'Line_No' => 50000,
            'Line_Type' => 'Factureerbaar',
            'Type' => 'GB-rekening',
            'No' => '400000',
            'Description' => 'Kostenrekening (distractor)',
            'Line_Amount_LCY' => 9900.0,
            'Invoiced_Amount_LCY' => 0.0,
            'LVS_Job_Change_Order_No' => '',
        ],
        [
            'Job_No' => 'PRJ2602236',
            'Job_Task_No' => '100-000-000',
            'Line_No' => 60000,
            'Line_Type' => 'Prognose',
            'Type' => 'GB-rekening',
            'No' => '800000',
            'Description' => 'Forecast 800000 (distractor)',
            'Line_Amount_LCY' => 99999.0,
            'Invoiced_Amount_LCY' => 0.0,
            'LVS_Job_Change_Order_No' => '',
        ],
    ];
}

function tmp_787_unfiltered_sum(array $rows): float
{
    $total = 0.0;
    foreach ($rows as $row) {
        $total = finance_add_amount($total, $row['Line_Amount_LCY'] ?? 0.0);
    }

    return $total;
}

function tmp_787_assert(bool $ok, string $message): void
{
    if ($ok) {
        echo "PASS  {$message}\n";
        return;
    }

    fwrite(STDERR, "FAIL  {$message}\n");
    exit(1);
}

$fixture = tmp_787_prj2602236_planning_fixture();
$unfiltered = tmp_787_unfiltered_sum($fixture);
tmp_787_assert(
    $unfiltered > TMP_787_EXPECTED_AANNEEMSOM + 1,
    'Unfiltered PRJ2602236 fixture is inflated above €280.000 (got ' . $unfiltered . ')'
);

foreach ($fixture as $row) {
    $isRevenueGl = finance_is_revenue_gl_account_line($row);
    $expectRevenueGl = (($row['Type'] ?? '') === 'GB-rekening' && ($row['No'] ?? '') === '800000');
    tmp_787_assert(
        $isRevenueGl === $expectRevenueGl,
        'finance_is_revenue_gl_account_line for No=' . ($row['No'] ?? '') . ' Type=' . ($row['Type'] ?? '')
    );
}

$filtered = finrap_filter_gl_revenue_planning_rows($fixture);
tmp_787_assert(count($filtered) === 2, 'Filter keeps two billable 800000 G/L lines (got ' . count($filtered) . ')');

$totals = finrap_parse_gl_revenue_planning_totals($fixture, []);
$aanneemsom = (float) ($totals['project_contract'] ?? 0.0);
tmp_787_assert(
    abs($aanneemsom - TMP_787_EXPECTED_AANNEEMSOM) < 0.000001,
    'PRJ2602236 aanneemsom/omzet is €280.000 after 800000 filter (got ' . $aanneemsom . ')'
);

echo "OK  temporary #787 PRJ2602236 omzet filter verification\n";
exit(0);

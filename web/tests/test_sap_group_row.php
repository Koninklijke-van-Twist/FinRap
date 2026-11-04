<?php

/**
 * Oude SAP-taken (niet NNN-NNN-NNN) worden gebundeld in optelgroep SAP en opgeteld bij 000.
 * Run: php web/tests/test_sap_group_row.php
 */

require_once dirname(__DIR__) . '/localization.php';
require_once dirname(__DIR__) . '/finrap_data.php';

$failures = 0;

function test_assert(string $name, bool $condition, string $detail = ''): void
{
    global $failures;
    if ($condition) {
        echo "OK  {$name}\n";
        return;
    }

    $failures++;
    echo "FAIL {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

$root = ['Cost_Group_Code' => '000', 'Is_Total_Row' => true, 'Totaling' => '000-000-000..999-999-999', 'Booked_Hours' => 10.0, 'Budget_Cost' => 100.0, 'EAC' => 80.0];
$detail = ['Cost_Group_Code' => '100-100-100', 'Is_Total_Row' => false, 'Booked_Hours' => 10.0, 'Budget_Cost' => 100.0, 'EAC' => 80.0];
$sapA = ['Cost_Group_Code' => '4052840', 'Is_Total_Row' => false, 'Booked_Hours' => 2.5, 'Budget_Cost' => 0.0, 'EAC' => 20.0];
$sapB = ['Cost_Group_Code' => '4052841', 'Is_Total_Row' => false, 'Booked_Hours' => 1.5, 'Budget_Cost' => 0.0, 'EAC' => 0.0];
$ass = ['Cost_Group_Code' => '150000', 'Is_Total_Row' => false, 'Booked_Hours' => 7.0, 'Budget_Cost' => 0.0, 'EAC' => 0.0];
$wo = ['Cost_Group_Code' => 'WO2607807', 'Is_Total_Row' => false, 'Booked_Hours' => 9.0, 'Budget_Cost' => 0.0, 'EAC' => 0.0];

$rows = finrap_append_sap_group_row([$root, $detail], [$root, $detail, $sapA, $ass, $sapB, $wo]);
$sap = end($rows);
test_assert('SAP-rij toegevoegd als laatste', ($sap['Cost_Group_Code'] ?? '') === 'SAP');
test_assert('SAP omschrijving', ($sap['Cost_Group_Description'] ?? '') === 'Import vanuit SAP');
test_assert('SAP uren opgeteld', abs($sap['Booked_Hours'] - 4.0) < 0.000001);
test_assert('000 bevat SAP uren', abs($rows[0]['Booked_Hours'] - 14.0) < 0.000001);
test_assert('000 variantie herberekend', abs($rows[0]['Variance_Budget_EAC'] - finance_calculate_result(100.0, 100.0)) < 0.000001);
$summary = finrap_get_report_summary_totals($rows);
test_assert('Samenvatting = 000 (geen dubbeltelling)', abs($summary['Booked_Hours'] - $rows[0]['Booked_Hours']) < 0.000001);
$detailCodes = array_map(static fn(array $r): string => $r['Cost_Group_Code'], $sap['Sap_Detail_Rows']);
test_assert('SAP detailregels aanwezig', $detailCodes === ['4052840', '4052841'], implode(',', $detailCodes));
test_assert('SAP detailregel behoudt waarden', abs($sap['Sap_Detail_Rows'][0]['Booked_Hours'] - 2.5) < 0.000001);
test_assert('SAP detailregels niet los in task_rows', count($rows) === 3);
test_assert('ASS 150000 en WO niet in SAP', count($sap['Sap_Task_Codes']) === 2);
test_assert('Geen SAP-rij zonder SAP-taken', count(finrap_append_sap_group_row([$root, $detail], [$root, $detail, $ass, $wo])) === 2);
test_assert('SAP-herkenning', finrap_is_sap_task_no('4046560') && !finrap_is_sap_task_no('150000') && !finrap_is_sap_task_no('40465601') && !finrap_is_sap_task_no('WO2607807'));
// SAP-project zonder 000-regel (bv. 144382): SAP-rij toch zichtbaar
$sapOnly = finrap_append_sap_group_row([['Cost_Group_Code' => '000-000-010', 'Is_Total_Row' => false]], [$sapA, $sapB]);
test_assert('SAP-project toont SAP-rij', (end($sapOnly)['Cost_Group_Code'] ?? '') === 'SAP' && abs(end($sapOnly)['Booked_Hours'] - 4.0) < 0.000001);

// 153586: PHP maakt van array-key '4067021' een int; VC Kosten mag daardoor niet wegvallen.
$taskRowsByKey = ['000-000-010' => [], '4067021' => [], '4067022' => [], '153586-m020-v06' => []];
$projectTaskRows = [
    ['Job_No' => '153586', 'Job_Task_No' => '4067021', 'LVS_Baseline_Total_Cost' => 4789, 'KVT_EAC_Total_Unit_Cost' => 10, 'LVS_Registered_Purchases_Amt' => 5],
    ['Job_No' => '153586', 'Job_Task_No' => '4067022', 'LVS_Baseline_Total_Cost' => 801],
    ['Job_No' => '153586', 'Job_Task_No' => '153586-M020-V06', 'LVS_Baseline_Total_Cost' => 999],
];
$vc = finrap_parse_project_task_baseline_costs_by_task($projectTaskRows, array_keys($taskRowsByKey), '153586');
test_assert('SAP VC Kosten 4067021', abs(($vc['4067021'] ?? 0) - 4789) < 0.000001, json_encode($vc));
test_assert('SAP VC Kosten 4067022', abs(($vc['4067022'] ?? 0) - 801) < 0.000001);
test_assert('Niet-SAP taak blijft buiten', !isset($vc['153586-m020-v06']));
$eac = finrap_parse_project_task_eac_costs_by_task($projectTaskRows, array_keys($taskRowsByKey), '153586');
test_assert('SAP EAC Kosten', abs(($eac['4067021'] ?? 0) - 10) < 0.000001);
$obl = finrap_parse_project_task_amounts_by_task($projectTaskRows, array_keys($taskRowsByKey), '153586', 'LVS_Registered_Purchases_Amt');
test_assert('SAP verplichtingen', abs(($obl['4067021'] ?? 0) - 5) < 0.000001);

if ($failures > 0) {
    echo "\n{$failures} failure(s)\n";
    exit(1);
}

echo "\nAll tests passed.\n";

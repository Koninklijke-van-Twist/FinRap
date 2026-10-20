<?php

/**
 * Termijnstatus via Qty_Invoiced en factuurkoppeling via SalesInvoiceLines.
 * Project 164397: gelijke termijnbedragen mogen niet de klantpost van een andere termijn pakken.
 * Run: php web/tests/test_termijn_invoice_match.php
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

function test_assert_float(string $name, float $expected, float $actual): void
{
    test_assert($name, abs($expected - $actual) < 0.001, "expected {$expected}, got {$actual}");
}

function test_planning_row(
    int $lineNo,
    string $description,
    float $amount,
    float $quantity,
    float $qtyInvoiced,
    float $invoicedAmountLcy = 0.0,
    string $changeOrder = ''
): array {
    return [
        'Line_No' => $lineNo,
        'Line_Type' => 'Billable',
        'Job_Task_No' => '100',
        'Type' => 'G/L Account',
        'No' => '800000',
        'Description' => $description,
        'Description_2' => '',
        'Document_No' => '',
        FINRAP_BILLABLE_PLANNING_AMOUNT_FIELD => $amount,
        'Quantity' => $quantity,
        'Qty_Invoiced' => $qtyInvoiced,
        'Planning_Date' => '2026-01-15',
        FINRAP_BILLABLE_PLANNING_INVOICED_FIELD => $invoicedAmountLcy,
        FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD => $changeOrder,
    ];
}

function test_termijn_from_planning(array $planningRow): array
{
    return finrap_map_termijn_line_from_planning_row($planningRow);
}

function test_index_termijnen_by_line_no(array $termijnLines): array
{
    $indexed = [];
    foreach ($termijnLines as $termijnLine) {
        if (!is_array($termijnLine)) {
            continue;
        }

        $indexed[(int) ($termijnLine['line_no'] ?? 0)] = $termijnLine;
    }

    return $indexed;
}

$projectNo = '164397';
$amountTermijn4And7 = 188655.05;
$amountTermijn5And8 = 75462.02;
$amountTermijn6 = 120000.00;
$amountTermijn9 = 50000.00;

$planningRows = [
    test_planning_row(400, 'Termijn 4 - 25%', $amountTermijn4And7, 1, 1, 0.0),
    test_planning_row(500, 'Termijn 5 - 10%', $amountTermijn5And8, 1, 1, 0.0),
    test_planning_row(600, 'Termijn 6 - 15%', $amountTermijn6, 1, 1, 0.0),
    test_planning_row(700, 'Termijn 7 - 25%', $amountTermijn4And7, 1, 1, 0.0),
    test_planning_row(800, 'Termijn 8 - 10%', $amountTermijn5And8, 1, 1, 0.0),
    test_planning_row(900, 'Termijn 9 - 5%', $amountTermijn9, 1, 0, 999999.99),
];

$termijnLines = [];
foreach ($planningRows as $planningRow) {
    $termijnLines[] = test_termijn_from_planning($planningRow);
}

$salesInvoiceLines = [
    [
        'Document_No' => 'S126005114',
        'Job_No' => $projectNo,
        'Description' => 'Termijn 7 - 25%',
        'Amount' => $amountTermijn4And7,
    ],
    [
        'Document_No' => 'S126005115',
        'Job_No' => $projectNo,
        'Description' => 'Termijn 8 - 10%',
        'Amount' => $amountTermijn5And8,
    ],
    [
        'Document_No' => 'S126002549',
        'Job_No' => $projectNo,
        'Description' => 'Termijn 4 - 25%',
        'Amount' => $amountTermijn4And7,
    ],
    [
        'Document_No' => 'S126002549',
        'Job_No' => $projectNo,
        'Description' => 'Termijn 5 - 10%',
        'Amount' => $amountTermijn5And8,
    ],
    [
        'Document_No' => 'S126004279',
        'Job_No' => $projectNo,
        'Description' => 'Termijn 6 - 15%',
        'Amount' => $amountTermijn6,
    ],
    [
        'Document_No' => 'S126009001',
        'Job_No' => $projectNo,
        'Description' => 'Termijn 9 - 5%',
        'Amount' => $amountTermijn9,
    ],
    [
        'Document_No' => 'S-ANDER-PROJECT',
        'Job_No' => '999999',
        'Description' => 'Termijn 4 - 25%',
        'Amount' => $amountTermijn4And7,
    ],
];

$customerRows = [
    [
        'Entry_No' => 70,
        'Document_No' => 'S126005114',
        'Description' => 'PF12602530',
        'Sales_LCY' => $amountTermijn4And7,
        'Amount_LCY' => $amountTermijn4And7,
        'Remaining_Amt_LCY' => $amountTermijn4And7,
        'Posting_Date' => '2026-09-29',
        'Due_Date' => '2026-10-29',
        'Closed_at_Date' => '0001-01-01',
    ],
    [
        'Entry_No' => 80,
        'Document_No' => 'S126005115',
        'Description' => 'PF12602531',
        'Sales_LCY' => $amountTermijn5And8,
        'Amount_LCY' => $amountTermijn5And8,
        'Remaining_Amt_LCY' => $amountTermijn5And8,
        'Posting_Date' => '2026-09-29',
        'Due_Date' => '2026-10-29',
        'Closed_at_Date' => '',
    ],
    [
        'Entry_No' => 61,
        'Document_No' => 'S126009999',
        'Description' => 'PF-FOUT',
        'Sales_LCY' => $amountTermijn6,
        'Amount_LCY' => $amountTermijn6,
        'Remaining_Amt_LCY' => $amountTermijn6,
        'Posting_Date' => '2026-08-01',
        'Due_Date' => '2026-08-31',
        'Closed_at_Date' => '',
    ],
    [
        'Entry_No' => 45,
        'Document_No' => 'S126002549',
        'Description' => 'PF12601174',
        'Sales_LCY' => 264117.07,
        'Amount_LCY' => 319581.65,
        'Remaining_Amt_LCY' => 0.0,
        'Posting_Date' => '2026-06-12',
        'Due_Date' => '2026-07-12',
        'Closed_at_Date' => '2026-07-02',
    ],
    [
        'Entry_No' => 60,
        'Document_No' => 'S126004279',
        'Description' => '',
        'Sales_LCY' => $amountTermijn6,
        'Amount_LCY' => $amountTermijn6,
        'Remaining_Amt_LCY' => 0.0,
        'Posting_Date' => '2026-08-01',
        'Due_Date' => '2026-08-31',
        'Closed_at_Date' => '2026-08-15',
    ],
    [
        'Entry_No' => 90,
        'Document_No' => 'S126009001',
        'Description' => 'PF-NIET-GEBRUIKEN',
        'Sales_LCY' => $amountTermijn9,
        'Amount_LCY' => $amountTermijn9,
        'Remaining_Amt_LCY' => 0.0,
        'Posting_Date' => '2026-10-01',
        'Due_Date' => '2026-10-31',
        'Closed_at_Date' => '2026-10-05',
    ],
];

$enriched = finrap_enrich_termijn_lines_with_invoices($termijnLines, $salesInvoiceLines, $customerRows, $projectNo);
$byLine = test_index_termijnen_by_line_no($enriched);

$expected = [
    400 => [
        'status' => 'paid',
        'invoice_document_no' => 'S126002549',
        'ledger_description' => 'PF12601174',
        'closed_at_date' => '2026-07-02',
        'posting_date' => '2026-06-12',
    ],
    500 => [
        'status' => 'paid',
        'invoice_document_no' => 'S126002549',
        'ledger_description' => 'PF12601174',
        'closed_at_date' => '2026-07-02',
        'posting_date' => '2026-06-12',
    ],
    600 => [
        'status' => 'paid',
        'invoice_document_no' => 'S126004279',
        'ledger_description' => 'S126004279',
        'closed_at_date' => '2026-08-15',
        'posting_date' => '2026-08-01',
    ],
    700 => [
        'status' => 'invoiced',
        'invoice_document_no' => 'S126005114',
        'ledger_description' => 'PF12602530',
        'closed_at_date' => '',
        'posting_date' => '2026-09-29',
    ],
    800 => [
        'status' => 'invoiced',
        'invoice_document_no' => 'S126005115',
        'ledger_description' => 'PF12602531',
        'closed_at_date' => '',
        'posting_date' => '2026-09-29',
    ],
    900 => [
        'status' => 'not_invoiced',
        'invoice_document_no' => '',
        'ledger_description' => '',
        'closed_at_date' => '',
        'posting_date' => '',
    ],
];

foreach ($expected as $lineNo => $expectedLine) {
    $actual = $byLine[$lineNo] ?? null;
    test_assert("164397 lijn {$lineNo} bestaat", is_array($actual));
    if (!is_array($actual)) {
        continue;
    }

    $label = $expectedLine['invoice_document_no'] !== ''
        ? $expectedLine['invoice_document_no'] . '/' . $expectedLine['ledger_description']
        : 'niet gefactureerd';
    test_assert(
        "164397 lijn {$lineNo} {$label} status {$expectedLine['status']}",
        ($actual['status'] ?? '') === $expectedLine['status']
            && ($actual['invoice_document_no'] ?? '') === $expectedLine['invoice_document_no']
            && ($actual['ledger_description'] ?? '') === $expectedLine['ledger_description']
            && ($actual['closed_at_date'] ?? '') === $expectedLine['closed_at_date']
            && ($actual['posting_date'] ?? '') === $expectedLine['posting_date'],
        json_encode([
            'status' => $actual['status'] ?? null,
            'invoice_document_no' => $actual['invoice_document_no'] ?? null,
            'ledger_description' => $actual['ledger_description'] ?? null,
            'closed_at_date' => $actual['closed_at_date'] ?? null,
            'posting_date' => $actual['posting_date'] ?? null,
        ], JSON_UNESCAPED_UNICODE)
    );
}

test_assert(
    '164397 termijn 4 en 5 delen de verzamelfactuur',
    ($byLine[400]['invoice_document_no'] ?? '') === 'S126002549'
        && ($byLine[500]['invoice_document_no'] ?? '') === 'S126002549'
        && ($byLine[400]['ledger_description'] ?? '') === 'PF12601174'
        && ($byLine[500]['ledger_description'] ?? '') === 'PF12601174'
);
test_assert(
    '164397 termijn 4 pakt niet de open klantpost van termijn 7',
    ($byLine[400]['ledger_description'] ?? '') !== 'PF12602530'
        && ($byLine[400]['invoice_document_no'] ?? '') !== 'S126005114'
);
test_assert(
    '164397 termijn 7 en 8 blijven open gefactureerd',
    ($byLine[700]['status'] ?? '') === 'invoiced'
        && ($byLine[800]['status'] ?? '') === 'invoiced'
        && ($byLine[700]['closed_at_date'] ?? '') === ''
        && ($byLine[800]['closed_at_date'] ?? '') === ''
);

$expectedInvoicedTotal = $amountTermijn4And7 + $amountTermijn5And8 + $amountTermijn6 + $amountTermijn4And7 + $amountTermijn5And8;
$totalsRows = $planningRows;
$totalsRows[] = test_planning_row(1000, 'Deels gefactureerd', 1000.0, 2, 1, 1000.0);
$totalsRows[] = test_planning_row(1100, 'Meerwerk termijn', 10.0, 1, 1, 0.0, 'MW1');
$budgetRow = test_planning_row(1200, 'Budgetregel', 77777.0, 1, 1, 0.0);
$budgetRow['Line_Type'] = 'Budget';
$totalsRows[] = $budgetRow;
$planningTotals = finrap_parse_gl_revenue_planning_totals($totalsRows, []);
test_assert_float(
    'gefactureerde termijnen negeert Invoiced_Amount_LCY en telt Line_Amount_LCY bij Qty_Invoiced = Quantity',
    $expectedInvoicedTotal,
    (float) ($planningTotals['invoiced_by_group'][''] ?? 0.0)
);
test_assert_float(
    'meerwerktermijn telt in de eigen groep',
    10.0,
    (float) ($planningTotals['invoiced_by_group']['MW1'] ?? 0.0)
);
$headerRow = finrap_build_single_header_metric_row(
    'PRJ',
    true,
    '',
    [],
    [],
    [],
    [],
    $projectNo,
    0.0,
    $planningTotals
);
test_assert_float(
    'header Gefactureerde termijnen is niet €0,00',
    $expectedInvoicedTotal,
    (float) ($headerRow['installments_invoiced'] ?? 0.0)
);

$equalAmount = 188655.05;
$equalLines = [
    test_termijn_from_planning(test_planning_row(10, 'Termijn 4 - 25%', $equalAmount, 1, 1)),
    test_termijn_from_planning(test_planning_row(20, 'Termijn 7 - 25%', $equalAmount, 1, 1)),
];
$equalInvoices = [
    [
        'Document_No' => 'S-LATER',
        'Job_No' => 'P1',
        'Description' => 'Termijn 7 - 25%',
        'Amount' => $equalAmount,
    ],
    [
        'Document_No' => 'S-EERDER',
        'Job_No' => 'P1',
        'Description' => 'Termijn 4 - 25%',
        'Amount' => $equalAmount,
    ],
];
$equalLedger = [
    [
        'Entry_No' => 1,
        'Document_No' => 'S-LATER',
        'Description' => 'PF-LATER',
        'Sales_LCY' => $equalAmount,
        'Posting_Date' => '2026-09-29',
        'Due_Date' => '2026-10-29',
        'Closed_at_Date' => '',
    ],
    [
        'Entry_No' => 2,
        'Document_No' => 'S-EERDER',
        'Description' => 'PF-EERDER',
        'Sales_LCY' => $equalAmount,
        'Posting_Date' => '2026-05-01',
        'Due_Date' => '2026-06-01',
        'Closed_at_Date' => '2026-05-20',
    ],
];
$equalEnriched = test_index_termijnen_by_line_no(
    finrap_enrich_termijn_lines_with_invoices($equalLines, $equalInvoices, $equalLedger, 'P1')
);
test_assert(
    'gelijke bedragen blijven op de eigen factuurregel',
    ($equalEnriched[10]['status'] ?? '') === 'paid'
        && ($equalEnriched[10]['invoice_document_no'] ?? '') === 'S-EERDER'
        && ($equalEnriched[10]['ledger_description'] ?? '') === 'PF-EERDER'
        && ($equalEnriched[20]['status'] ?? '') === 'invoiced'
        && ($equalEnriched[20]['invoice_document_no'] ?? '') === 'S-LATER'
        && ($equalEnriched[20]['ledger_description'] ?? '') === 'PF-LATER'
);

$ambiguousLines = [
    test_termijn_from_planning(test_planning_row(30, 'Omschrijving wijkt af A', 75.0, 1, 1)),
    test_termijn_from_planning(test_planning_row(40, 'Omschrijving wijkt af B', 75.0, 1, 1)),
];
$ambiguousInvoices = [
    ['Document_No' => 'S-AMB-1', 'Job_No' => 'P1', 'Description' => 'Factuurregel A', 'Amount' => 75.0],
    ['Document_No' => 'S-AMB-2', 'Job_No' => 'P1', 'Description' => 'Factuurregel B', 'Amount' => 75.0],
];
$ambiguousEnriched = test_index_termijnen_by_line_no(
    finrap_enrich_termijn_lines_with_invoices($ambiguousLines, $ambiguousInvoices, [], 'P1')
);
test_assert(
    'gelijke bedragen zonder unieke factuurregel blijven zonder factuurnummer',
    ($ambiguousEnriched[30]['status'] ?? '') === 'invoiced'
        && ($ambiguousEnriched[30]['invoice_document_no'] ?? '') === ''
        && ($ambiguousEnriched[40]['status'] ?? '') === 'invoiced'
        && ($ambiguousEnriched[40]['invoice_document_no'] ?? '') === ''
);

$uniqueFallbackLines = [
    test_termijn_from_planning(test_planning_row(50, 'Termijn zonder gelijke omschrijving', 80.0, 1, 1)),
];
$uniqueFallbackInvoices = [
    ['Document_No' => 'S-UNIEK', 'Job_No' => 'P1', 'Description' => 'Voorschot', 'Amount' => 80.0],
    ['Document_No' => 'S-ANDERS', 'Job_No' => 'P1', 'Description' => 'Iets anders', 'Amount' => 15.0],
];
$uniqueFallbackLedger = [
    [
        'Entry_No' => 3,
        'Document_No' => 'S-UNIEK',
        'Description' => 'PF-UNIEK',
        'Sales_LCY' => 80.0,
        'Posting_Date' => '2026-04-01',
        'Due_Date' => '2026-05-01',
        'Closed_at_Date' => '2026-04-18',
    ],
];
$uniqueFallbackEnriched = test_index_termijnen_by_line_no(
    finrap_enrich_termijn_lines_with_invoices($uniqueFallbackLines, $uniqueFallbackInvoices, $uniqueFallbackLedger, 'P1')
);
test_assert(
    'uniek bedrag koppelt als de omschrijving niet matcht',
    ($uniqueFallbackEnriched[50]['status'] ?? '') === 'paid'
        && ($uniqueFallbackEnriched[50]['invoice_document_no'] ?? '') === 'S-UNIEK'
        && ($uniqueFallbackEnriched[50]['ledger_description'] ?? '') === 'PF-UNIEK'
);

$sharedLineLines = [
    test_termijn_from_planning(test_planning_row(60, 'Termijn dubbel', 40.0, 1, 1)),
    test_termijn_from_planning(test_planning_row(70, 'Termijn dubbel', 40.0, 1, 1)),
];
$sharedLineInvoices = [
    ['Document_No' => 'S-EEN', 'Job_No' => 'P1', 'Description' => 'Termijn dubbel', 'Amount' => 40.0],
];
$sharedLineEnriched = test_index_termijnen_by_line_no(
    finrap_enrich_termijn_lines_with_invoices($sharedLineLines, $sharedLineInvoices, [], 'P1')
);
test_assert(
    'één factuurregel bedient niet twee termijnen',
    ($sharedLineEnriched[60]['invoice_document_no'] ?? '') === 'S-EEN'
        && ($sharedLineEnriched[70]['invoice_document_no'] ?? '') === ''
        && ($sharedLineEnriched[70]['status'] ?? '') === 'invoiced'
);

$openSiblingLines = [
    test_termijn_from_planning(test_planning_row(80, 'Termijn deels gesloten', 33.0, 1, 1)),
];
$openSiblingInvoices = [
    ['Document_No' => 'S-DEELS', 'Job_No' => 'P1', 'Description' => 'Termijn deels gesloten', 'Amount' => 33.0],
];
$openSiblingLedger = [
    [
        'Entry_No' => 8,
        'Document_No' => 'S-DEELS',
        'Description' => 'PF-DEELS',
        'Sales_LCY' => 33.0,
        'Posting_Date' => '2026-03-01',
        'Due_Date' => '2026-04-01',
        'Closed_at_Date' => '2026-03-20',
    ],
    [
        'Entry_No' => 9,
        'Document_No' => 'S-DEELS',
        'Description' => '',
        'Sales_LCY' => 0.0,
        'Posting_Date' => '2026-03-21',
        'Due_Date' => '',
        'Closed_at_Date' => '',
    ],
];
$openSiblingEnriched = test_index_termijnen_by_line_no(
    finrap_enrich_termijn_lines_with_invoices($openSiblingLines, $openSiblingInvoices, $openSiblingLedger, 'P1')
);
test_assert(
    'betaald pas als elke klantpost van het document gesloten is',
    ($openSiblingEnriched[80]['status'] ?? '') === 'invoiced'
        && ($openSiblingEnriched[80]['ledger_description'] ?? '') === 'PF-DEELS'
        && ($openSiblingEnriched[80]['closed_at_date'] ?? '') === ''
);

test_assert(
    'planningselect haalt Quantity en Qty_Invoiced op en niet Invoiced_Amount_LCY',
    str_contains(finrap_billable_planning_select(), 'Quantity,Qty_Invoiced')
        && !str_contains(finrap_billable_planning_select(), 'Invoiced_Amount_LCY')
);
test_assert(
    'verkoopfactuurselect bevat Job_No, Description, Amount en Document_No',
    finrap_sales_invoice_line_select() === 'Document_No,Job_No,Description,Amount'
);
test_assert(
    'klantpostselect bevat Document_No',
    str_contains(finrap_customer_ledger_termijn_select(), 'Document_No')
        && str_contains(finrap_customer_ledger_termijn_select(), 'Closed_at_Date')
        && str_contains(finrap_customer_ledger_termijn_select(), 'Description')
);

if ($failures > 0) {
    echo "\n{$failures} failure(s)\n";
    exit(1);
}

echo "\nAll tests passed.\n";

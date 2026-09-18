<?php

/**
 * Small helper tests for booked vs to-book cost semantics (Asclepius #815).
 * Run: php web/tests/test_to_book_cost.php
 */

require_once dirname(__DIR__) . '/finance_calculations.php';

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
    test_assert($name, abs($expected - $actual) < 0.000001, "expected {$expected}, got {$actual}");
}

// WO2609822 / PRJ2607877: Job Ledger purchase → Geboekt, not also Te boeken.
$received = finance_column_received_not_posted_line(true, 0.0, 1500.0);
$capped = finance_column_received_not_posted_capped($received, 1500.0);
test_assert_float('WO2609822 received capped after ledger purchase', 0.0, $capped);
test_assert_float(
    'WO2609822 booked unchanged',
    1500.0,
    1500.0
);
test_assert_float(
    'WO2609822 totale = geboekt',
    1500.0,
    finance_column_costs_total(1500.0, finance_column_to_book_cost(0.0, $capped))
);

// WO2610072 / PRJ2608013: Completely_Received, Qty_Posted=0, empty Job Ledger → Te boeken.
$toBookReceived = finance_column_received_not_posted_line(true, 0.0, 420.50);
test_assert_float('WO2610072 te boeken from received', 420.50, $toBookReceived);
test_assert_float(
    'WO2610072 totale = te boeken',
    420.50,
    finance_column_costs_total(0.0, finance_column_to_book_cost(0.0, $toBookReceived))
);

// Outstanding / not received PO must stay out.
test_assert_float(
    'outstanding PO not completely received is 0',
    0.0,
    finance_column_received_not_posted_line(false, 0.0, 999.0)
);

// WO2609377 / PRJ2607554: budget only (Qty_Posted=0 but not received) → nowhere in trio.
test_assert_float(
    'WO2609377 budget Total_Cost is not used as received amount',
    0.0,
    finance_column_purchase_received_amount(0.0, 0.0, 0.0, 0.0)
);
test_assert_float(
    'WO2609377 not Completely_Received stays out of te boeken',
    0.0,
    finance_column_received_not_posted_line(false, 0.0, 328000.0)
);

// Already posted received line (Qty_Posted > 0) is geboekt, not te boeken.
test_assert_float(
    'posted received line excluded from te boeken',
    0.0,
    finance_column_received_not_posted_line(true, 2.0, 800.0)
);

// WO2610284 stock pick: Unposted Standard journal belongs in Te boeken, not budget.
$unposted = finance_column_unposted_cost_line(3.0, 12.5);
test_assert_float('WO2610284 unposted stock pick', 37.5, $unposted);
test_assert_float(
    'WO2610284 te boeken merges unposted, not budget',
    37.5,
    finance_column_to_book_cost($unposted, 0.0)
);

// Received amount prefers qty received × unit cost (goods-in), then Amt_Rcd, never Outstanding.
test_assert_float(
    'received amount prefers qty × unit cost over Amt_Rcd',
    100.0,
    finance_column_purchase_received_amount(110.0, 2.0, 50.0, 999.0)
);
test_assert_float(
    'received amount falls back to Amt_Rcd_Not_Invoiced when qty is empty',
    110.0,
    finance_column_purchase_received_amount(110.0, 0.0, 50.0, 999.0)
);
test_assert_float(
    'received amount never uses outstanding as qty/amt fallback',
    100.0,
    finance_column_purchase_received_amount(0.0, 2.0, 50.0, 999.0)
);

// Completely received line amount is only used when qty/amt received are empty.
test_assert_float(
    'completely received line amount fallback',
    75.0,
    finance_column_purchase_received_amount(0.0, 0.0, 0.0, 75.0)
);

test_assert('odata flag true', finance_odata_flag_is_true(true));
test_assert('odata flag ja', finance_odata_flag_is_true('Ja'));
test_assert('odata flag false empty', !finance_odata_flag_is_true(''));
test_assert('ledger type item is purchase', finance_ledger_type_is_purchase('Item'));
test_assert('ledger type artikel is purchase', finance_ledger_type_is_purchase('Artikel'));
test_assert('ledger type resource is not purchase', !finance_ledger_type_is_purchase('Resource'));

test_assert_float(
    'POC uses booked + te boeken',
    190.0,
    finance_column_poc_cost_progress(100.0, 90.0)
);

if ($failures > 0) {
    echo "\n{$failures} test(s) failed.\n";
    exit(1);
}

echo "\nAll to-book cost helper tests passed.\n";
exit(0);

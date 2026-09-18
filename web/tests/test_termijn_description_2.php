<?php

/**
 * Mapping and termijn card rendering for Description_2 (Omschrijving 2).
 * Run: php web/tests/test_termijn_description_2.php
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

$mapped = finrap_map_termijn_line_from_planning_row([
    'Line_No' => 20,
    'Document_No' => 'VF2601234',
    'Description' => 'Termijn 1',
    'Description_2' => '  Voorschot engineering  ',
    FINRAP_PROJECT_TASK_CHANGE_ORDER_FIELD => '',
    FINRAP_BILLABLE_PLANNING_AMOUNT_FIELD => 1500.5,
    'Planning_Date' => '2026-03-01',
    FINRAP_BILLABLE_PLANNING_INVOICED_FIELD => 0,
]);

test_assert('maps description_2 from Description_2', ($mapped['description_2'] ?? null) === 'Voorschot engineering');
test_assert('keeps Description as description', ($mapped['description'] ?? null) === 'Termijn 1');
test_assert('maps amount unchanged', abs((float) ($mapped['amount'] ?? 0.0) - 1500.5) < 0.000001);

$emptyMapped = finrap_map_termijn_line_from_planning_row([
    'Line_No' => 30,
    'Description' => 'Termijn 2',
    FINRAP_BILLABLE_PLANNING_AMOUNT_FIELD => 10,
]);
test_assert('missing Description_2 becomes empty string', ($emptyMapped['description_2'] ?? 'missing') === '');
test_assert('whitespace-only Description_2 is empty', finrap_map_termijn_line_from_planning_row([
    'Description_2' => "  \t",
    FINRAP_BILLABLE_PLANNING_AMOUNT_FIELD => 1,
])['description_2'] === '');

test_assert(
    'billable planning select includes Description_2',
    str_contains(finrap_billable_planning_select(), 'Description,Description_2,Document_No')
);

$ariaLabel = LOC('report.termijn.description_2');
$rendered = finrap_render_termijn_description_2_html($mapped['description_2'], $ariaLabel);
test_assert('non-empty render includes termijn-description-2', str_contains($rendered, 'class="termijn-description-2"'));
test_assert(
    'non-empty render includes aria-label',
    str_contains($rendered, 'aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES) . '"')
);
test_assert('non-empty render includes escaped value', str_contains($rendered, 'Voorschot engineering'));

$unsafeMapped = finrap_map_termijn_line_from_planning_row([
    'Description_2' => 'Foo <script>alert(1)</script>',
    FINRAP_BILLABLE_PLANNING_AMOUNT_FIELD => 1,
]);
$escapedRendered = finrap_render_termijn_description_2_html($unsafeMapped['description_2'], $ariaLabel);
test_assert('rendered value is escaped', str_contains($escapedRendered, 'Foo &lt;script&gt;alert(1)&lt;/script&gt;'));
test_assert('rendered value has no raw script tag', !str_contains($escapedRendered, '<script>'));

$emptyRendered = finrap_render_termijn_description_2_html($emptyMapped['description_2'], $ariaLabel);
test_assert('empty Description_2 omits the element', $emptyRendered === '');
test_assert(
    'whitespace Description_2 omits the element',
    finrap_render_termijn_description_2_html("  \t", $ariaLabel) === ''
);

if ($failures > 0) {
    echo "\n{$failures} failure(s)\n";
    exit(1);
}

echo "\nAll tests passed.\n";

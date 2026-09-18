<?php

/**
 * Constants
 */
const FINANCE_REVENUE_GL_ACCOUNT_TYPE = 'GB-rekening';
const FINANCE_REVENUE_GL_ACCOUNT_TYPE_ODATA = 'G/L Account';
const FINANCE_REVENUE_GL_ACCOUNT_NO = '800000';

/**
 * Functies
 */

/**
 * Berekent kolomwaarde Totale Kosten door alle werkorderkosten op te tellen.
 */
function finance_column_total_costs(array $workorders): float
{
    $total = 0.0;

    foreach ($workorders as $workorder) {
        if (!is_array($workorder)) {
            continue;
        }

        $total = finance_add_amount($total, $workorder['Actual_Costs'] ?? 0.0);
    }

    return $total;
}

/**
 * Berekent kolomwaarde Totale Opbrengst door alle werkorderopbrengsten op te tellen.
 */
function finance_column_total_revenue(array $workorders): float
{
    $total = 0.0;

    foreach ($workorders as $workorder) {
        if (!is_array($workorder)) {
            continue;
        }

        $total = finance_add_amount($total, $workorder['Total_Revenue'] ?? 0.0);
    }

    return $total;
}

/**
 * Berekent kolomwaarde Winst OHW met de formule: Marge Ttl x % gereed.
 */
function finance_column_winst_ohw(float $marginTotal, float $percentCompleted): float
{
    return $marginTotal * ($percentCompleted / 100.0);
}

/**
 * Berekent kolomwaarde Winst Vorige Periode als opbrengst minus kosten uit de vorige periode.
 */
function finance_column_prev_profit(?array $prevProfitData): ?float
{
    if (!is_array($prevProfitData)) {
        return null;
    }

    $revenue = finance_to_float($prevProfitData['revenue'] ?? 0.0);
    $costs = finance_to_float($prevProfitData['costs'] ?? 0.0);

    return finance_calculate_result($revenue, $costs);
}

/**
 * Berekent kolomwaarde Verschil als (huidige winst minus winst vorige periode).
 */
function finance_column_difference(float $currentRevenue, float $currentCosts, ?float $prevProfit): ?float
{
    if ($prevProfit === null) {
        return null;
    }

    $currentProfit = finance_calculate_result($currentRevenue, $currentCosts);

    return $currentProfit - $prevProfit;
}

/**
 * Berekent kolomwaarde Marge Totaal als verwachte opbrengst minus verwachte kosten VC.
 */
function finance_column_margin_total(float $expectedRevenue, float $expectedCostsVc): float
{
    return finance_calculate_result($expectedRevenue, $expectedCostsVc);
}

/**
 * Zet een inkomende waarde veilig om naar een numeriek bedrag.
 */
function finance_to_float(mixed $value): float
{
    return is_numeric($value) ? (float) $value : 0.0;
}

/**
 * Zet een bedrag om naar de absolute waarde voor consistente kosten/opbrengstvergelijking.
 */
function finance_abs_amount(mixed $value): float
{
    return abs(finance_to_float($value));
}

/**
 * Telt een nieuw bedrag op bij een bestaand financieel totaal.
 */
function finance_add_amount(float $current, mixed $value): float
{
    return $current + finance_to_float($value);
}

/**
 * Berekent financieel resultaat als opbrengst minus kosten.
 */
function finance_calculate_result(float $revenue, float $costs): float
{
    return $revenue - $costs;
}

/**
 * Bepaalt of een projectstatus financieel als afgesloten moet worden behandeld.
 */
function finance_is_closed_project_status(string $status): bool
{
    $normalized = strtolower(trim($status));

    return in_array($normalized, ['completed', 'closed', 'afgesloten', 'gereed'], true);
}

/**
 * Normaliseert row mode naar ondersteunde modi voor bedragberekening.
 */
function finance_normalize_row_mode(string $mode): string
{
    $normalized = strtolower(trim($mode));
    if ($normalized === 'sum_raw') {
        return 'sum_raw';
    }
    if ($normalized === 'sum') {
        return 'sum';
    }
    if ($normalized === 'sum_invert') {
        return 'sum_invert';
    }

    return 'first_numeric';
}

/**
 * Leest het eerste numerieke bedrag uit een prioriteitenlijst van bronkolommen.
 */
function finance_first_numeric_value(array $details, array $fields): float
{
    foreach ($fields as $field) {
        if (!is_string($field) || $field === '' || !array_key_exists($field, $details)) {
            continue;
        }

        $raw = $details[$field];
        if (!is_numeric($raw)) {
            continue;
        }

        return finance_abs_amount($raw);
    }

    return 0.0;
}

/**
 * Berekent een regelbedrag op basis van row mode.
 */
function finance_extract_row_amount(array $row, array $fields, string $mode): float
{
    $normalizedMode = finance_normalize_row_mode($mode);

    if ($normalizedMode === 'sum_raw') {
        $sum = 0.0;

        foreach ($fields as $field) {
            if (!is_string($field) || $field === '' || !array_key_exists($field, $row)) {
                continue;
            }

            $raw = $row[$field];
            if (!is_numeric($raw)) {
                continue;
            }

            $sum += (float) $raw;
        }

        return $sum;
    }

    if ($normalizedMode === 'sum_invert') {
        $sum = 0.0;
        $hasNegativeValue = false;

        foreach ($fields as $field) {
            if (!is_string($field) || $field === '' || !array_key_exists($field, $row)) {
                continue;
            }

            $raw = $row[$field];
            if (!is_numeric($raw)) {
                continue;
            }

            $numeric = (float) $raw;
            if ($numeric < 0.0) {
                $hasNegativeValue = true;
            }

            $sum += $numeric;
        }

        return $hasNegativeValue ? -$sum : $sum;
    }

    if ($normalizedMode === 'sum') {
        $sum = 0.0;
        foreach ($fields as $field) {
            if (!is_string($field) || $field === '' || !array_key_exists($field, $row)) {
                continue;
            }

            $raw = $row[$field];
            if (!is_numeric($raw)) {
                continue;
            }

            $sum += finance_abs_amount($raw);
        }

        return $sum;
    }

    return finance_first_numeric_value($row, $fields);
}

/**
 * Berekent kolomwaarde Actual_Costs voor één werkorderregel uit de twee BC kostenvelden.
 */
function finance_workorder_actual_costs(array $workorder): float
{
    $costItems = finance_to_float($workorder['KVT_Sum_Work_Order_Cost_Items'] ?? 0);
    $costOther = finance_to_float($workorder['KVT_Sum_Work_Order_Cost_Other'] ?? 0);

    return $costItems + $costOther;
}

/**
 * Berekent kolomwaarde Total_Revenue voor één werkorderregel uit het BC opbrengstveld.
 */
function finance_workorder_total_revenue(array $workorder): float
{
    return finance_abs_amount($workorder['KVT_Sum_Work_Order_Revenue'] ?? 0);
}

/**
 * Berekent kolomwaarde Ontvangen termijnen voor één klantpost, excl. BTW.
 *
 * Sales_LCY is excl. BTW; Amount_LCY en Remaining_Amt_LCY zijn incl. BTW.
 * Open facturen (Remaining = Amount) gaven met Sales − Remaining de BTW
 * als negatief “ontvangen” (PRJ2607934: 21150 − 25591,50 = −4441,50).
 * Formule: Sales_LCY × (Amount_LCY − Remaining_Amt_LCY) / Amount_LCY.
 * Betalingen hebben Sales_LCY = 0 en tellen niet dubbel mee.
 */
function finance_column_installments_received_ledger_line(array $ledgerRow): float
{
    $salesLcy = finance_to_float($ledgerRow['Sales_LCY'] ?? 0.0);
    $amountLcy = finance_to_float($ledgerRow['Amount_LCY'] ?? 0.0);
    $remainingLcy = finance_to_float($ledgerRow['Remaining_Amt_LCY'] ?? 0.0);

    if (abs($amountLcy) < 0.000001) {
        return 0.0;
    }

    return $salesLcy * (($amountLcy - $remainingLcy) / $amountLcy);
}

/**
 * Berekent kolomwaarde Ontvangen termijnen als som van ontvangen excl. BTW
 * over alle Customer_Ledger_Entries van het project.
 */
function finance_column_installments_received(array $customerLedgerRows): float
{
    $total = 0.0;

    foreach ($customerLedgerRows as $ledgerRow) {
        if (!is_array($ledgerRow)) {
            continue;
        }

        $total = finance_add_amount($total, finance_column_installments_received_ledger_line($ledgerRow));
    }

    return $total;
}

/**
 * Geeft de Type-waarde(n) die BC OData accepteert voor de G/L-omzetrekening.
 * Job Planning Line Type-enum: Resource, Item, G/L Account, Text.
 * NL-caption `GB-rekening` en enum-naam `GLAccount` zijn ongeldig in $filter (HTTP 400).
 */
function finance_revenue_gl_account_type_odata_values(): array
{
    return [
        FINANCE_REVENUE_GL_ACCOUNT_TYPE_ODATA,
    ];
}

/**
 * Bouwt een OData Type-filter met alleen de BC-enumwaarde `G/L Account`.
 * Geen OR met GB-rekening of GLAccount: die waarden laten de hele query 400'en.
 */
function finance_revenue_gl_account_type_odata_filter(string $fieldName = 'Type'): string
{
    $field = trim($fieldName);
    if ($field === '') {
        $field = 'Type';
    }

    $escaped = str_replace("'", "''", FINANCE_REVENUE_GL_ACCOUNT_TYPE_ODATA);

    return $field . " eq '" . $escaped . "'";
}

/**
 * Geeft het Type-label voor UI-formules: de OData-enumwaarde `G/L Account`.
 */
function finance_revenue_gl_account_type_label(): string
{
    return FINANCE_REVENUE_GL_ACCOUNT_TYPE_ODATA;
}

/**
 * Bepaalt of Type een G/L-/grootboekrekening aanduidt.
 * Accepteert NL `GB-rekening` en EN `G/L Account` / `GLAccount` (case-insensitive,
 * spaties/streepjes/slashes genegeerd). Lege waarden tellen niet mee.
 */
function finance_is_revenue_gl_account_type(string $type): bool
{
    $token = strtolower(trim($type));
    if ($token === '') {
        return false;
    }

    $token = str_replace([' ', '_', '-', '/', '\\'], '', $token);

    return in_array($token, ['gbrekening', 'glaccount', 'glrekening', 'grootboekrekening'], true);
}

/**
 * Bepaalt of een BC-projectplanningsregel (Job Planning Line / JobBaselineLines /
 * FactureerbareProjectPlanningsRegels) meetelt voor aanneemsom/omzet.
 * Alleen G/L-omzetrekening Type = G/L Account (OData) of PHP-alias GB-rekening/GLAccount
 * en No = 800000 telt mee;
 * resource-/artikelboekingen op dezelfde planning blijven buiten deze som.
 */
function finance_is_revenue_gl_account_line(array $row): bool
{
    $type = trim((string) ($row['Type'] ?? ''));
    $no = trim((string) ($row['No'] ?? ''));

    return finance_is_revenue_gl_account_type($type)
        && $no === FINANCE_REVENUE_GL_ACCOUNT_NO;
}

/**
 * Bepaalt of Line_Type een factureerbare/billable planningregel aanduidt.
 * Accepteert Engelse `billable` en Nederlandse `factureerbaar` / `factureer*`
 * (case-insensitive). Lege waarden tellen niet mee.
 */
function finance_line_type_is_billable(string $lineType): bool
{
    $normalized = strtolower(trim($lineType));
    if ($normalized === '') {
        return false;
    }

    return str_contains($normalized, 'billable')
        || str_contains($normalized, 'factureer');
}

/**
 * Bepaalt of Line_Type een prognose/forecast-regel aanduidt.
 * Accepteert Engelse `forecast` en Nederlandse `prognose` (case-insensitive).
 */
function finance_line_type_is_forecast(string $lineType): bool
{
    $normalized = strtolower(trim($lineType));
    if ($normalized === '') {
        return false;
    }

    return str_contains($normalized, 'forecast')
        || str_contains($normalized, 'prognose');
}

/**
 * Berekent ongeboekte kosten voor één projectplanningsregel:
 * Qty_to_Transfer_to_Journal × Unit_Cost_LCY.
 */
function finance_column_unposted_cost_line(float $qtyToTransferToJournal, float $unitCostLcy): float
{
    return finance_to_float($qtyToTransferToJournal) * finance_to_float($unitCostLcy);
}

/**
 * Interpreteert een OData boolean/flag (true, 1, yes/ja) zonder enum-filters naar BC te sturen.
 */
function finance_odata_flag_is_true(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return abs((float) $value) >= 0.000001;
    }

    $normalized = strtolower(trim((string) $value));
    if ($normalized === '') {
        return false;
    }

    return in_array($normalized, ['true', '1', 'yes', 'ja', 'waar'], true);
}

/**
 * Bepaalt of een Job Ledger Type een inkoop-/artikelboeking is (geen uren/resource).
 * Gebruikt alleen PHP-matching; deze waarden gaan niet als OData-enumfilter naar BC.
 */
function finance_ledger_type_is_purchase(string $type): bool
{
    $normalized = strtolower(trim($type));
    if ($normalized === '') {
        return false;
    }

    return $normalized === 'item'
        || $normalized === 'artikel'
        || $normalized === 'g/l account'
        || $normalized === 'gb-rekening'
        || $normalized === 'glaccount'
        || $normalized === 'gl account';
}

/**
 * Berekent het ontvangen (goods-in) bedrag van een inkoopregel.
 * Volgorde: Quantity_Received × unit cost, daarna Amt_Rcd_Not_Invoiced, daarna Line_Amount
 * alleen bij Completely_Received. Nooit budget-Total_Cost en nooit Outstanding_Amount.
 */
function finance_column_purchase_received_amount(
    float $amtReceivedNotInvoiced,
    float $quantityReceived,
    float $unitCostLcy,
    float $completelyReceivedLineAmount
): float
{
    $qtyTimesCost = finance_to_float($quantityReceived) * finance_to_float($unitCostLcy);
    if (abs($qtyTimesCost) >= 0.000001) {
        return $qtyTimesCost;
    }

    if (abs($amtReceivedNotInvoiced) >= 0.000001) {
        return finance_to_float($amtReceivedNotInvoiced);
    }

    return finance_to_float($completelyReceivedLineAmount);
}

/**
 * Berekent te-boeken ontvangen kosten voor één inkoop-/ontvangstregel:
 * alleen Completely_Received (of LVS-equivalent) én Qty_Posted = 0.
 * Openstaande PO en budgetregels vallen hier buiten.
 */
function finance_column_received_not_posted_line(
    mixed $completelyReceivedFlag,
    float $qtyPosted,
    float $receivedAmount
): float
{
    if (!finance_odata_flag_is_true($completelyReceivedFlag)) {
        return 0.0;
    }

    if (abs(finance_to_float($qtyPosted)) >= 0.000001) {
        return 0.0;
    }

    return finance_to_float($receivedAmount);
}

/**
 * Voorkomt dubbeltelling: ontvangen-niet-geboekt minus Job Ledger inkoop op dezelfde taak,
 * nooit onder nul. Resource/uren in Geboekt worden hier niet afgetrokken.
 */
function finance_column_received_not_posted_capped(float $receivedNotPosted, float $bookedPurchaseCost): float
{
    $remaining = finance_to_float($receivedNotPosted) - finance_to_float($bookedPurchaseCost);

    return $remaining > 0.0 ? $remaining : 0.0;
}

/**
 * Berekent kolomwaarde Te boeken kosten: ongeboekt (Standard journal) plus ontvangen-niet-geboekt.
 */
function finance_column_to_book_cost(float $unpostedCost, float $receivedNotPostedCost): float
{
    return finance_add_amount($unpostedCost, $receivedNotPostedCost);
}

/**
 * Berekent kolomwaarde Totale kosten (FinRap-sleutel Costs_Total): Geboekt + Te boeken.
 */
function finance_column_costs_total(float $bookedCost, float $toBookCost): float
{
    return finance_add_amount($bookedCost, $toBookCost);
}

/**
 * Berekent de POC-kostenteller als geboekte kosten plus te-boeken kosten.
 * Tweede argument is To_Book_Cost (historisch Unposted_Cost, nu daarin opgenomen).
 */
function finance_column_poc_cost_progress(float $bookedCost, float $toBookCost): float
{
    return finance_column_costs_total($bookedCost, $toBookCost);
}

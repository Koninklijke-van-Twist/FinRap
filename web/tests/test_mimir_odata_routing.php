<?php

/**
 * Routing-tests: Mímir-modus zonder BC-credentials, en BC-modus zonder $mimirApi.
 * Raakt het netwerk niet. Run: php web/tests/test_mimir_odata_routing.php
 */

$mode = getenv('FINRAP_MIMIR_MODE');
if ($mode !== 'on' && $mode !== 'off') {
    $bin = escapeshellarg(PHP_BINARY);
    $script = escapeshellarg(__FILE__);
    passthru('FINRAP_MIMIR_MODE=on ' . $bin . ' ' . $script, $onCode);
    passthru('FINRAP_MIMIR_MODE=off ' . $bin . ' ' . $script, $offCode);
    exit(($onCode === 0 && $offCode === 0) ? 0 : 1);
}

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

if ($mode === 'on') {
    $mimirApi = 'test-key';
    require_once dirname(__DIR__) . '/finrap_data.php';

    $included = array_map('strval', get_included_files());
    $loadedAuth = false;
    foreach ($included as $includedPath) {
        if (basename($includedPath) === 'auth.php') {
            $loadedAuth = true;
        }
    }
    test_assert('mimir test laadt auth.php niet', !$loadedAuth);

    test_assert('mimir staat aan', odata_mimir_enabled());
    test_assert(
        'lege baseUrl wordt placeholder',
        ($baseUrl ?? '') === odata_mimir_placeholder_base_url(),
        (string) ($baseUrl ?? '')
    );

    $auth = auth_get_auth_for_environment('Production');
    test_assert('geen auth_list geeft lege auth', $auth === []);

    $GLOBALS['demeter_active_environments'] = ['Production'];
    $context = auth_set_current_company_context(null);
    test_assert('context zonder company faalt niet', is_array($context) && array_key_exists('auth', $context));

    $service = new ProjectFinanceService('Koninklijke van Twist', 'Production');
    $method = new ReflectionMethod(ProjectFinanceService::class, 'companyEntityUrlWithQuery');
    $method->setAccessible(true);
    $url = $method->invoke($service, 'Projecten', [
        '$select' => 'No,Description',
        '$filter' => "No eq 'P-1'",
    ]);
    $parsed = odata_mimir_parse_entity_url($url);
    test_assert('finance-URL is een entity-URL', is_array($parsed), (string) $url);
    test_assert(
        'company uit finance-URL',
        is_array($parsed) && ($parsed['company'] ?? '') === 'Koninklijke van Twist',
        (string) json_encode($parsed)
    );
    test_assert(
        'entity uit finance-URL',
        is_array($parsed) && ($parsed['entity'] ?? '') === 'Projecten'
    );
    test_assert(
        'filter uit finance-URL',
        is_array($parsed) && ($parsed['query']['$filter'] ?? '') === "No eq 'P-1'",
        (string) ($parsed['query']['$filter'] ?? '')
    );
    test_assert(
        'companies-parser slaat entity-URL over',
        odata_mimir_parse_companies_url($url) === null
    );

    $companiesUrl = 'https://bc.example/Production/ODataV4/Companies?$select=Name';
    $companies = odata_mimir_parse_companies_url($companiesUrl);
    test_assert(
        'companies-URL levert environment',
        is_array($companies) && ($companies['environment'] ?? '') === 'Production'
    );

    $dataUrl = finrap_company_entity_url_with_query('', 'Production', "Van Twist", 'JobLedgerEntries', [
        '$select' => 'Job_No,Total_Cost_LCY',
        '$filter' => "Job_No eq 'PRJ1'",
    ]);
    $dataParsed = odata_mimir_parse_entity_url($dataUrl);
    test_assert(
        'finrap_data-URL zonder baseUrl blijft parseerbaar',
        is_array($dataParsed)
            && ($dataParsed['company'] ?? '') === 'Van Twist'
            && ($dataParsed['entity'] ?? '') === 'JobLedgerEntries'
            && ($dataParsed['query']['$filter'] ?? '') === "Job_No eq 'PRJ1'",
        (string) $dataUrl . ' => ' . json_encode($dataParsed)
    );

    $threw = false;
    $message = '';
    try {
        odata_get_all('https://example.test/not-odata', [], 30);
    } catch (Throwable $error) {
        $threw = true;
        $message = $error->getMessage();
    }
    test_assert(
        'odata_get_all gaat naar Mímir en niet naar BC',
        $threw && str_contains($message, 'Mímir: OData-URL'),
        $message
    );
}

if ($mode === 'off') {
    $baseUrl = 'https://bc.example';
    $environment = 'Production';
    $auth_list = [
        'Production' => ['mode' => 'basic', 'user' => 'bc-user', 'pass' => 'bc-pass'],
    ];
    require_once dirname(__DIR__) . '/auth_helper.php';

    test_assert('zonder mimirApi staat Mímir uit', !auth_mimir_enabled());
    test_assert('baseUrl blijft de BC-host', $baseUrl === 'https://bc.example');

    $active = auth_get_active_environments();
    test_assert(
        'BC-environments blijven uit auth_list',
        $active === ['Production'],
        json_encode($active)
    );

    $auth = auth_get_auth_for_environment('Production');
    test_assert('BC-auth blijft beschikbaar', ($auth['user'] ?? '') === 'bc-user');

    $missingThrew = false;
    try {
        auth_get_auth_for_environment('Onbekend');
    } catch (RuntimeException $error) {
        $missingThrew = str_contains($error->getMessage(), 'Geen auth-configuratie');
    }
    test_assert('ontbrekende BC-auth gooit nog', $missingThrew);

    require_once dirname(__DIR__) . '/project_finance.php';
    $baseMissing = false;
    $savedBase = $baseUrl;
    $baseUrl = '';
    try {
        new ProjectFinanceService('Koninklijke van Twist', 'Production');
    } catch (RuntimeException $error) {
        $baseMissing = str_contains($error->getMessage(), 'baseUrl ontbreekt');
    }
    $baseUrl = $savedBase;
    test_assert('zonder Mímir blijft lege baseUrl een fout', $baseMissing);
}

if ($failures > 0) {
    echo "{$failures} failed\n";
    exit(1);
}

echo "all passed ({$mode})\n";
exit(0);

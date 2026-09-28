<?php

/**
 * Functies
 */

/**
 * Mímir-proxy: als $mimirApi in auth.php staat, gaan OData-fetches eerst naar Mímir.
 * Faalt die aanroep (cURL/timeout, non-2xx, ongeldige JSON of een Mímir-foutpayload),
 * dan valt FinRap terug op de directe BC-route van vóór de Mímir-migratie: $baseUrl +
 * $auth / $auth_list / $environment en de lokale odata-filecache.
 * Na de eerste fout in dit PHP-proces wordt Mímir overgeslagen.
 * Zonder $mimirApi blijft alleen die directe route actief.
 * Zonder BC-credentials wordt de oorspronkelijke Mímir-fout opnieuw gegooid.
 *
 * Tim moet in web/auth.php zetten (niet in git), naast elkaar:
 *   $mimirApi  = 'mimir_…';              // verplicht om Mímir te activeren
 *   $mimirBase = 'https://sleutels.kvt.nl/mimir/api'; // optioneel
 *   én $auth_list / $environment / $auth / $baseUrl voor de BC-fallback.
 */

function odata_mimir_api_key(): string
{
    global $mimirApi;
    if (!isset($mimirApi) || !is_string($mimirApi)) {
        return '';
    }
    return trim($mimirApi);
}

function odata_mimir_enabled(): bool
{
    return odata_mimir_api_key() !== '';
}

function odata_mimir_base_url(): string
{
    global $mimirBase;
    if (isset($mimirBase) && is_string($mimirBase) && trim($mimirBase) !== '') {
        return rtrim(trim($mimirBase), '/');
    }
    return 'https://sleutels.kvt.nl/mimir/api';
}

/**
 * @return array{open: bool, error: ?Throwable}
 */
function &odata_mimir_circuit_state(): array
{
    static $state = [
        'open' => false,
        'error' => null,
    ];
    return $state;
}

function odata_mimir_circuit_open(): bool
{
    $state = &odata_mimir_circuit_state();
    return $state['open'] === true;
}

function odata_mimir_last_error(): ?Throwable
{
    $state = &odata_mimir_circuit_state();
    return $state['error'] instanceof Throwable ? $state['error'] : null;
}

function odata_mimir_trip(Throwable $exception): void
{
    $state = &odata_mimir_circuit_state();
    if ($state['open'] === true) {
        return;
    }
    $state['open'] = true;
    $state['error'] = $exception;
}

function odata_mimir_circuit_reset(): void
{
    $state = &odata_mimir_circuit_state();
    $state['open'] = false;
    $state['error'] = null;
}

function odata_mimir_connect_timeout_seconds(): int
{
    return 10;
}

function odata_mimir_timeout_seconds_for_sapi(string $sapi): int
{
    return strtolower($sapi) === 'cli' ? 600 : 90;
}

function odata_mimir_timeout_seconds(): int
{
    return odata_mimir_timeout_seconds_for_sapi(PHP_SAPI);
}

function odata_mimir_fail(Exception $exception): void
{
    odata_mimir_trip($exception);
    throw $exception;
}

function odata_auth_is_usable($auth): bool
{
    if (!is_array($auth)) {
        return false;
    }
    $user = trim((string) ($auth['user'] ?? ''));
    if ($user === '') {
        return false;
    }
    $mode = (string) ($auth['mode'] ?? '');
    if ($mode !== 'basic' && $mode !== 'ntlm') {
        return false;
    }
    return array_key_exists('pass', $auth);
}

/**
 * Laadt auth.php in globale scope als de BC-variabelen nog ontbreken.
 * Entry points includen auth.php al; dit vangt CLI-paden die Mímir eerst probeerden.
 */
function odata_bc_ensure_config_loaded(): void
{
    global $baseUrl, $environment, $auth, $auth_list, $mimirApi, $mimirBase;
    if (odata_bc_base_url() !== null && odata_bc_environment() !== null && odata_bc_auth_for_fallback([]) !== null) {
        return;
    }
    $path = __DIR__ . '/auth.php';
    if (!is_file($path)) {
        return;
    }
    require_once $path;
}

function odata_bc_base_url(): ?string
{
    global $baseUrl;
    if (!isset($baseUrl) || !is_string($baseUrl)) {
        return null;
    }
    $base = trim($baseUrl);
    if ($base === '' || stripos($base, 'mimir.invalid') !== false) {
        return null;
    }
    return $base;
}

function odata_bc_environment(): ?string
{
    global $environment, $auth_list;
    if (isset($environment) && is_string($environment)) {
        $env = trim($environment);
        if ($env !== '' && strcasecmp($env, 'mimir') !== 0) {
            return $env;
        }
    }
    if (isset($auth_list) && is_array($auth_list)) {
        foreach ($auth_list as $key => $entry) {
            if (!is_string($key)) {
                continue;
            }
            $candidate = trim($key);
            if ($candidate === '' || strcasecmp($candidate, 'mimir') === 0) {
                continue;
            }
            if (odata_auth_is_usable($entry)) {
                return $candidate;
            }
        }
    }
    return null;
}

function odata_bc_auth_for_fallback(array $passed): ?array
{
    if (odata_auth_is_usable($passed)) {
        return $passed;
    }
    global $auth, $auth_list;
    if (isset($auth) && odata_auth_is_usable($auth)) {
        return $auth;
    }
    if (!isset($auth_list) || !is_array($auth_list)) {
        return null;
    }
    $env = odata_bc_environment();
    if ($env !== null && isset($auth_list[$env]) && odata_auth_is_usable($auth_list[$env])) {
        return $auth_list[$env];
    }
    foreach ($auth_list as $entry) {
        if (odata_auth_is_usable($entry)) {
            return $entry;
        }
    }
    return null;
}

function odata_bc_credentials_configured(): bool
{
    odata_bc_ensure_config_loaded();
    if (odata_bc_base_url() === null || odata_bc_environment() === null) {
        return false;
    }
    return odata_bc_auth_for_fallback([]) !== null;
}

function odata_mimir_log_fallback(Throwable $exception): void
{
    $message = $exception->getMessage();
    $redactions = [];
    $apiKey = odata_mimir_api_key();
    if ($apiKey !== '') {
        $redactions[] = $apiKey;
    }
    global $auth, $auth_list;
    if (isset($auth) && is_array($auth) && isset($auth['pass']) && is_string($auth['pass']) && $auth['pass'] !== '') {
        $redactions[] = $auth['pass'];
    }
    if (isset($auth_list) && is_array($auth_list)) {
        foreach ($auth_list as $entry) {
            if (is_array($entry) && isset($entry['pass']) && is_string($entry['pass']) && $entry['pass'] !== '') {
                $redactions[] = $entry['pass'];
            }
        }
    }
    foreach ($redactions as $secret) {
        $message = str_replace($secret, '[redacted]', $message);
    }
    $sanitized = preg_replace('/(Bearer\s+)\S+/i', '$1[redacted]', $message);
    if (is_string($sanitized)) {
        $message = $sanitized;
    }
    error_log('[FinRap] Mímir failed, falling back to direct OData: ' . $message);
}

/**
 * @template T
 * @param callable(): T $viaMimir
 * @param callable(): T $viaDirect
 * @return T
 */
function odata_mimir_or_direct(callable $viaMimir, callable $viaDirect)
{
    if (odata_mimir_circuit_open()) {
        $original = odata_mimir_last_error();
        if (!odata_bc_credentials_configured()) {
            if ($original instanceof Throwable) {
                throw $original;
            }
            throw new Exception('Mímir eerder mislukt.');
        }
        odata_mimir_log_fallback($original instanceof Throwable ? $original : new Exception('Mímir overgeslagen na eerdere fout.'));
        return $viaDirect();
    }

    try {
        return $viaMimir();
    } catch (Throwable $exception) {
        odata_mimir_trip($exception);
        if (!odata_bc_credentials_configured()) {
            throw $exception;
        }
        odata_mimir_log_fallback($exception);
        return $viaDirect();
    }
}

function odata_bc_url_from_odata_url(string $url): string
{
    $parts = parse_url($url);
    if (!is_array($parts)) {
        return $url;
    }
    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($host !== '' && $host !== 'mimir.invalid') {
        return $url;
    }
    $base = odata_bc_base_url();
    if ($base === null) {
        return $url;
    }
    $path = (string) ($parts['path'] ?? '');
    $prefix = rtrim($base, '/');
    if ($host === 'mimir.invalid') {
        $env = odata_bc_environment();
        if ($env === null) {
            return $url;
        }
        if (preg_match('#^/[^/]+(/.+)$#', $path, $match) !== 1) {
            return $url;
        }
        $rebuilt = $prefix . '/' . $env . $match[1];
    } else {
        if ($path === '' || $path[0] !== '/') {
            return $url;
        }
        $rebuilt = $prefix . $path;
    }
    if (isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== '') {
        $rebuilt .= '?' . $parts['query'];
    }
    return $rebuilt;
}

function odata_mimir_request(string $method, string $path, ?array $jsonBody = null): array
{
    $apiKey = odata_mimir_api_key();
    if ($apiKey === '') {
        throw new Exception('Mímir API-sleutel ontbreekt ($mimirApi).');
    }

    if (odata_mimir_circuit_open()) {
        $previous = odata_mimir_last_error();
        if ($previous instanceof Throwable) {
            throw $previous;
        }
        throw new Exception('Mímir overgeslagen na eerdere fout in dit verzoek.');
    }

    $url = odata_mimir_base_url() . '/' . ltrim($path, '/');
    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $apiKey,
        'X-API-Key: ' . $apiKey,
    ];
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        // Geen redirects: Authorization en X-API-Key mogen niet naar een andere host.
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => odata_mimir_connect_timeout_seconds(),
        CURLOPT_TIMEOUT => odata_mimir_timeout_seconds(),
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'FinRap-MimirClient/1.0',
    ];
    if ($jsonBody !== null) {
        $payload = json_encode($jsonBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            curl_close($ch);
            throw new Exception('Mímir request JSON encode mislukt.');
        }
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_HTTPHEADER] = $headers;
        $opts[CURLOPT_POSTFIELDS] = $payload;
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        odata_mimir_fail(new Exception('Mímir cURL error: ' . $err));
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($raw, true);
    if ($code < 200 || $code >= 300) {
        $message = is_array($decoded) ? (string) ($decoded['error'] ?? $raw) : $raw;
        odata_mimir_fail(new Exception('Mímir HTTP ' . $code . ': ' . $message));
    }
    if (!is_array($decoded)) {
        odata_mimir_fail(new Exception('Mímir gaf ongeldige JSON terug.'));
    }
    $errorField = $decoded['error'] ?? null;
    if ($errorField !== null && $errorField !== '' && $errorField !== false) {
        $message = is_string($errorField) ? $errorField : (string) json_encode($errorField, JSON_UNESCAPED_UNICODE);
        odata_mimir_fail(new Exception('Mímir error: ' . $message));
    }
    return $decoded;
}

/**
 * @return array{company: string, entity: string, query: array<string, string>}|null
 */
function odata_mimir_parse_entity_url(string $url): ?array
{
    $parts = parse_url($url);
    if (!is_array($parts) || !isset($parts['path'])) {
        return null;
    }
    $path = (string) $parts['path'];
    // .../ODataV4/Company('Name')/EntitySet  or urlencoded company
    if (preg_match("#/ODataV4/Company\\((?:'([^']*)'|%27([^%]+)%27)\\)/([^/?]+)#i", $path, $match) !== 1) {
        return null;
    }
    $company = rawurldecode($match[1] !== '' ? $match[1] : $match[2]);
    $company = str_replace("''", "'", $company);
    $entity = rawurldecode($match[3]);
    $query = [];
    if (isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== '') {
        parse_str($parts['query'], $parsed);
        foreach ($parsed as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $query[$key] = (string) $value;
            }
        }
    }
    return [
        'company' => $company,
        'entity' => $entity,
        'query' => $query,
    ];
}

/**
 * @return array{environment: string}|null
 */
function odata_mimir_parse_companies_url(string $url): ?array
{
    $parts = parse_url($url);
    if (!is_array($parts) || !isset($parts['path'])) {
        return null;
    }
    $path = (string) $parts['path'];
    // .../{environment}/ODataV4/Company or Companies
    if (preg_match('#/([^/]+)/ODataV4/(?:Companies|Company)(?:/|\\?|$)#i', $path . (isset($parts['query']) ? '?' : ''), $match) !== 1
        && preg_match('#/([^/]+)/ODataV4/(?:Companies|Company)$#i', $path, $match) !== 1) {
        return null;
    }
    return ['environment' => rawurldecode($match[1])];
}

/**
 * @return list<array<string, mixed>>
 */
function odata_mimir_companies_as_rows_impl(?string $environment = null): array
{
    $response = odata_mimir_request('GET', 'companies.php');
    $items = $response['value'] ?? null;
    if (!is_array($items)) {
        throw new Exception("Mímir companies-antwoord mist 'value'.");
    }
    $rows = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string) ($item['name'] ?? $item['Name'] ?? ''));
        $env = trim((string) ($item['environment'] ?? ''));
        if ($name === '') {
            continue;
        }
        if ($environment !== null && $environment !== '' && $env !== '' && strcasecmp($env, $environment) !== 0) {
            continue;
        }
        $rows[] = ['Name' => $name, 'environment' => $env];
    }
    return $rows;
}

/**
 * Directe BC-companylijst via de pre-Mímir OData-route ({base}/{env}/ODataV4/Company).
 * Lege antwoorden vallen terug op dezelfde URL-varianten als auth_build_companies_urls().
 *
 * @return list<array<string, mixed>>
 */
function odata_direct_companies_as_rows(?string $environmentFilter = null): array
{
    $envs = [];
    if ($environmentFilter !== null && trim($environmentFilter) !== '' && strcasecmp(trim($environmentFilter), 'mimir') !== 0) {
        $envs[] = trim($environmentFilter);
    } else {
        $primary = odata_bc_environment();
        if ($primary !== null) {
            $envs[] = $primary;
        }
    }

    $base = odata_bc_base_url();
    if ($base === null || $envs === []) {
        $previous = odata_mimir_last_error();
        if ($previous instanceof Throwable) {
            throw $previous;
        }
        throw new Exception('Mímir mislukt.');
    }

    $prefix = rtrim($base, '/');
    $out = [];
    foreach ($envs as $env) {
        $auth = null;
        global $auth_list;
        if (isset($auth_list) && is_array($auth_list) && isset($auth_list[$env]) && odata_auth_is_usable($auth_list[$env])) {
            $auth = $auth_list[$env];
        }
        if ($auth === null) {
            $auth = odata_bc_auth_for_fallback([]);
        }
        if ($auth === null) {
            continue;
        }

        $urls = [
            $prefix . '/' . $env . '/ODataV4/Company',
            $prefix . '/' . $env . '/ODataV4/Companies?$select=Name',
            $prefix . '/' . $env . '/ODataV4/Company?$select=Name',
            $prefix . '/' . $env . '/ODataV4/Companies',
        ];
        foreach ($urls as $url) {
            try {
                $rows = odata_get_all_direct($url, $auth, 300);
            } catch (Throwable $ignored) {
                continue;
            }
            $named = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['Name'] ?? $row['name'] ?? $row['Display_Name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $named[] = ['Name' => $name, 'environment' => $env];
            }
            if ($named !== []) {
                foreach ($named as $namedRow) {
                    $out[] = $namedRow;
                }
                break;
            }
        }
    }

    if ($out === []) {
        $previous = odata_mimir_last_error();
        if ($previous instanceof Throwable) {
            throw $previous;
        }
        throw new Exception('Mímir mislukt.');
    }
    return $out;
}

/**
 * @return list<array<string, mixed>>
 */
function odata_mimir_companies_as_rows(?string $environment = null): array
{
    $fromMimir = static function () use ($environment): array {
        return odata_mimir_companies_as_rows_impl($environment);
    };
    if (!odata_mimir_enabled()) {
        return $fromMimir();
    }
    return odata_mimir_or_direct(
        $fromMimir,
        static function () use ($environment): array {
            return odata_direct_companies_as_rows($environment);
        }
    );
}

/**
 * Bedrijfsnamen via Mímir companies.php (gesorteerd).
 *
 * @return list<string>
 */
function odata_mimir_list_companies(?string $environment = null): array
{
    $rows = odata_mimir_companies_as_rows($environment);
    $names = [];
    $seen = [];
    foreach ($rows as $row) {
        $name = trim((string) ($row['Name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $key = strtolower($name);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $names[] = $name;
    }
    natcasesort($names);
    return array_values($names);
}

/**
 * name => environment map uit Mímir companies.php.
 *
 * @return array<string, string>
 */
function odata_mimir_company_environment_map(?string $environment = null): array
{
    $rows = odata_mimir_companies_as_rows($environment);
    $map = [];
    foreach ($rows as $row) {
        $name = trim((string) ($row['Name'] ?? ''));
        $env = trim((string) ($row['environment'] ?? ''));
        if ($name === '' || $env === '') {
            continue;
        }
        $map[$name] = $env;
    }
    ksort($map, SORT_NATURAL | SORT_FLAG_CASE);
    return $map;
}

/**
 * Directe company/table-query via Mímir — geen BC-URL nodig.
 * $odataQuery gebruikt FinRap-keys zoals $select / $filter.
 *
 * @param array<string, mixed> $odataQuery
 * @return list<array<string, mixed>>
 */
function odata_mimir_query_impl(string $company, string $table, array $odataQuery, int $ttlSeconds): array
{
    consolelog("Mímir query company=$company table=$table\n");

    $body = [
        'company' => $company,
        'table' => $table,
        'max_age' => max(0, $ttlSeconds),
        'top' => 0,
    ];

    $select = trim((string) ($odataQuery['$select'] ?? $odataQuery['select'] ?? ''));
    if ($select !== '') {
        $cols = [];
        foreach (explode(',', $select) as $col) {
            $col = trim($col);
            if ($col !== '') {
                $cols[] = $col;
            }
        }
        if ($cols !== []) {
            $body['select'] = $cols;
        }
    }

    $filter = trim((string) ($odataQuery['$filter'] ?? $odataQuery['filter'] ?? ''));
    if ($filter !== '') {
        $body['filter'] = $filter;
    }

    $response = odata_mimir_request('POST', 'query.php', $body);
    if (!isset($response['value']) || !is_array($response['value'])) {
        throw new Exception("Mímir query-antwoord mist 'value'.");
    }
    /** @var list<array<string, mixed>> $value */
    $value = $response['value'];
    return $value;
}

/**
 * Zelfde company/table-query, maar via de pre-Mímir BC-URL en filecache.
 *
 * @param array<string, mixed> $odataQuery
 * @return list<array<string, mixed>>
 */
function odata_direct_query(string $company, string $table, array $odataQuery, int $ttlSeconds): array
{
    $env = odata_bc_environment();
    $base = odata_bc_base_url();
    $auth = odata_bc_auth_for_fallback([]);
    if ($env === null || $base === null || $auth === null) {
        $previous = odata_mimir_last_error();
        if ($previous instanceof Throwable) {
            throw $previous;
        }
        throw new Exception('Mímir mislukt.');
    }

    $params = [];
    foreach (['$select', '$filter', '$orderby', '$expand', '$top', '$skip', 'select', 'filter'] as $key) {
        if (!array_key_exists($key, $odataQuery)) {
            continue;
        }
        $value = trim((string) $odataQuery[$key]);
        if ($value === '') {
            continue;
        }
        $odataKey = ($key === 'select' || $key === 'filter') ? ('$' . $key) : $key;
        $params[$odataKey] = $value;
    }

    $url = rtrim($base, '/') . '/' . $env . "/ODataV4/Company('" . rawurlencode($company) . "')/" . $table;
    if ($params !== []) {
        $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    return odata_get_all_direct($url, $auth, $ttlSeconds);
}

/**
 * @param array<string, mixed> $odataQuery
 * @return list<array<string, mixed>>
 */
function odata_mimir_query(string $company, string $table, array $odataQuery, int $ttlSeconds): array
{
    $fromMimir = static function () use ($company, $table, $odataQuery, $ttlSeconds): array {
        return odata_mimir_query_impl($company, $table, $odataQuery, $ttlSeconds);
    };
    if (!odata_mimir_enabled()) {
        return $fromMimir();
    }
    return odata_mimir_or_direct(
        $fromMimir,
        static function () use ($company, $table, $odataQuery, $ttlSeconds): array {
            return odata_direct_query($company, $table, $odataQuery, $ttlSeconds);
        }
    );
}

/**
 * @return list<array<string, mixed>>
 */
function odata_mimir_fetch_all_impl(string $url, int $ttlSeconds): array
{
    consolelog("Mímir fetch $url\n");

    $companies = odata_mimir_parse_companies_url($url);
    if ($companies !== null) {
        return odata_mimir_companies_as_rows_impl($companies['environment']);
    }

    $parsed = odata_mimir_parse_entity_url($url);
    if ($parsed === null) {
        throw new Exception('Mímir: OData-URL kon niet worden vertaald naar company/table: ' . $url);
    }

    return odata_mimir_query_impl($parsed['company'], $parsed['entity'], $parsed['query'], $ttlSeconds);
}

/**
 * @return list<array<string, mixed>>
 */
function odata_mimir_fetch_all(string $url, int $ttlSeconds): array
{
    $fromMimir = static function () use ($url, $ttlSeconds): array {
        return odata_mimir_fetch_all_impl($url, $ttlSeconds);
    };
    if (!odata_mimir_enabled()) {
        return $fromMimir();
    }
    return odata_mimir_or_direct(
        $fromMimir,
        static function () use ($url, $ttlSeconds): array {
            $auth = odata_bc_auth_for_fallback([]);
            if ($auth === null) {
                $previous = odata_mimir_last_error();
                if ($previous instanceof Throwable) {
                    throw $previous;
                }
                throw new Exception('Mímir mislukt.');
            }
            return odata_get_all_direct(odata_bc_url_from_odata_url($url), $auth, $ttlSeconds);
        }
    );
}

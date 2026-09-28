# FinRap

## Mímir (optioneel)

Zet in `web/auth.php` (niet in git), naast de Business Central-credentials:

```php
$mimirApi  = 'mimir_…';
// optioneel:
$mimirBase = 'https://sleutels.kvt.nl/mimir/api';
```

Met `$mimirApi` gezet gaan OData-fetches (live pagina's én `nightly.php` / CLI) eerst naar Mímir. Faalt die aanroep (verbinding/timeout, non-2xx, ongeldige JSON of een Mímir-foutpayload), dan haalt FinRap dezelfde data op via de oude Business Central-route (`$baseUrl`, `$auth` / `$auth_list`, `$environment`, lokale odata-filecache) en slaat Mímir voor de rest van dat PHP-proces over. Laat die BC-credentials in `auth.php` naast `$mimirApi` staan; ontbreken ze, dan wordt de oorspronkelijke Mímir-fout opnieuw gegooid. Zonder `$mimirApi` blijft alleen de bestaande BC-route actief.

`web/nightly.php` laadt `auth.php` vóór de OData-calls, zodat de fallback ook in cron/CLI de BC-credentials heeft. Webrequests gebruiken een Mímir-timeout van ongeveer 90 seconden; CLI houdt de lange timeout (600 seconden). De directe fallback gebruikt het environment van het gevraagde bedrijf en de bijbehorende `$auth_list`-credentials.

`web/odata.php` blijft verder onaangeroerd. Goedgekeurde uitzondering voor deze fallback: `odata_get_all` heeft een korte afslag; de fallbacklogica staat in `web/mimir_odata.php`.

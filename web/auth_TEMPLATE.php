<?php
/**
 * Auth-template voor FinRap. Kopieer naar web/auth.php (niet in git).
 *
 * Mímir heeft voorrang. De BC-blok hieronder blijft de automatische fallback
 * wanneer Mímir uitvalt — laat die credentials naast $mimirApi staan.
 *   $mimirApi  = 'mimir_…';  // verplicht om Mímir te activeren
 *   $mimirBase = 'https://sleutels.kvt.nl/mimir/api'; // optioneel
 *
 * Met $mimirApi gezet proberen fetches eerst Mímir en vallen terug op de BC-variabelen.
 * Zonder $mimirApi wordt alleen het BC-blok gebruikt.
 */

// --- Mímir (aanbevolen) ---
// $mimirApi  = 'mimir_…';
// $mimirBase = 'https://sleutels.kvt.nl/mimir/api';

// --- Business Central (directe route, én fallback als Mímir faalt) ---
$auth_list =
    [
        "env1" => ['mode' => 'basic', 'user' => 'USERNAME', 'pass' => 'PASSWORD'],
        "env2" => ['mode' => 'basic', 'user' => 'USERNAME', 'pass' => 'PASSWORD'],
    ];
$environment = "env1";
$auth = $auth_list[$environment];
$baseUrl = "https://my-bc-domain.com:7148/";

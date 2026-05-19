<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Includes/requires
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';
require_once __DIR__ . '/odata.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/finrap_data.php';

/**
 * Functies
 */
function index_json_response(array $payload, int $statusCode = 200): void
{
	http_response_code($statusCode);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

function index_discover_companies(): array
{
	try {
		$result = auth_discover_companies_across_active_environments(300);
		$companies = is_array($result['companies'] ?? null) ? $result['companies'] : [];
	} catch (Throwable $ignoredDiscoveryError) {
		$companies = [];
	}

	if ($companies === []) {
		$companies = [
			'Koninklijke van Twist',
			'Hunter van Twist',
			'KVT Gas',
		];
	}

	return $companies;
}

function index_month_label(string $yearMonth): string
{
	static $months = [
		'01' => 'Januari',
		'02' => 'Februari',
		'03' => 'Maart',
		'04' => 'April',
		'05' => 'Mei',
		'06' => 'Juni',
		'07' => 'Juli',
		'08' => 'Augustus',
		'09' => 'September',
		'10' => 'Oktober',
		'11' => 'November',
		'12' => 'December',
	];

	[$year, $month] = explode('-', $yearMonth);
	return ($months[$month] ?? $month) . ' ' . $year;
}

function index_get_user_email(): string
{
	return strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
}

function index_userdata_dir(): string
{
	$dir = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'userdata';
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}

	return $dir;
}

function index_legacy_usersettings_path(string $userEmail): ?string
{
	$email = strtolower(trim($userEmail));
	if ($email === '') {
		return null;
	}

	$safeEmail = preg_replace('/[^a-z0-9@._-]/i', '_', $email);
	$dir = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'usersettings';
	return $dir . DIRECTORY_SEPARATOR . $safeEmail . '.txt';
}

function index_userdata_path(string $userEmail): ?string
{
	$email = strtolower(trim($userEmail));
	if ($email === '') {
		return null;
	}

	$hash = sha1($email);
	return index_userdata_dir() . DIRECTORY_SEPARATOR . $hash . '.json';
}

function index_load_user_settings(string $userEmail): array
{
	$path = index_userdata_path($userEmail);
	if (!is_string($path) || $path === '' || !is_file($path)) {
		$legacyPath = index_legacy_usersettings_path($userEmail);
		if (!is_string($legacyPath) || $legacyPath === '' || !is_file($legacyPath)) {
			return [];
		}
		$path = $legacyPath;
	}

	$raw = @file_get_contents($path);
	if (!is_string($raw) || trim($raw) === '') {
		return [];
	}

	$decoded = json_decode($raw, true);
	return is_array($decoded) ? $decoded : [];
}

function index_save_user_settings(string $userEmail, array $settings): bool
{
	$path = index_userdata_path($userEmail);
	if (!is_string($path) || $path === '') {
		return false;
	}

	$settings['updated_at'] = gmdate('c');
	$json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	if (!is_string($json)) {
		return false;
	}

	return file_put_contents($path, $json, LOCK_EX) !== false;
}

function index_normalize_recent_projects(array $projects): array
{
	$normalized = [];
	foreach ($projects as $entry) {
		if (!is_array($entry)) {
			continue;
		}

		$projectNo = trim((string) ($entry['project_no'] ?? ''));
		$company = trim((string) ($entry['company'] ?? ''));
		$lastSearchedAt = trim((string) ($entry['last_searched_at'] ?? ''));
		if ($projectNo === '') {
			continue;
		}

		$key = strtolower($projectNo) . '|' . strtolower($company);
		$existingDate = (string) ($normalized[$key]['last_searched_at'] ?? '');
		if (!isset($normalized[$key]) || strcmp($lastSearchedAt, $existingDate) > 0) {
			$normalized[$key] = [
				'project_no' => $projectNo,
				'company' => $company,
				'last_searched_at' => $lastSearchedAt,
			];
		}
	}

	$rows = array_values($normalized);
	usort($rows, static function (array $left, array $right): int {
		return strcmp((string) ($right['last_searched_at'] ?? ''), (string) ($left['last_searched_at'] ?? ''));
	});

	return array_slice($rows, 0, 25);
}

function index_add_recent_project(array $settings, string $company, string $projectNo): array
{
	$recent = is_array($settings['finrap_recent_projects'] ?? null) ? $settings['finrap_recent_projects'] : [];
	$recent = index_normalize_recent_projects($recent);
	$needleCompany = strtolower(trim($company));
	$needleProjectNo = strtolower(trim($projectNo));
	$filtered = [];

	foreach ($recent as $entry) {
		$entryProjectNo = strtolower(trim((string) ($entry['project_no'] ?? '')));
		$entryCompany = strtolower(trim((string) ($entry['company'] ?? '')));
		if ($entryProjectNo === $needleProjectNo && $entryCompany === $needleCompany) {
			continue;
		}
		$filtered[] = $entry;
	}

	array_unshift($filtered, [
		'project_no' => trim($projectNo),
		'company' => trim($company),
		'last_searched_at' => gmdate('c'),
	]);

	$settings['finrap_recent_projects'] = index_normalize_recent_projects($filtered);
	$settings['finrap_selected_company'] = trim($company);
	return $settings;
}

/**
 * Page load
 */
$companies = index_discover_companies();
$userEmail = index_get_user_email();
$userSettings = index_load_user_settings($userEmail);
$defaultCompany = is_array($userSettings) ? (string) ($userSettings['finrap_selected_company'] ?? '') : '';
$selectedCompany = (string) ($_GET['company'] ?? ($defaultCompany !== '' ? $defaultCompany : $companies[0]));
if (!in_array($selectedCompany, $companies, true)) {
	$selectedCompany = $companies[0];
}

$recentProjects = index_normalize_recent_projects(
	is_array($userSettings['finrap_recent_projects'] ?? null) ? $userSettings['finrap_recent_projects'] : []
);

if (($_GET['action'] ?? '') === 'save_company_preference') {
	$company = trim((string) ($_POST['company'] ?? ''));
	if ($company === '' || !in_array($company, $companies, true)) {
		index_json_response(['ok' => false, 'error' => 'Kies een geldig bedrijf.'], 400);
	}

	$settings = index_load_user_settings($userEmail);
	$settings['finrap_selected_company'] = $company;
	$saveOk = index_save_user_settings($userEmail, $settings);
	if (!$saveOk) {
		index_json_response(['ok' => false, 'error' => 'Opslaan van gebruikersvoorkeur is mislukt.'], 500);
	}

	index_json_response([
		'ok' => true,
		'company' => $company,
	]);
}

if (($_GET['action'] ?? '') === 'find_project') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true)) {
		index_json_response(['ok' => false, 'error' => 'Kies een geldig bedrijf.'], 400);
	}
	if ($projectNo === '') {
		index_json_response(['ok' => false, 'error' => 'Voer een projectnummer in.'], 400);
	}

	try {
		$project = finrap_fetch_project($company, $projectNo, 300);
		if (!is_array($project)) {
			index_json_response(['ok' => false, 'error' => 'Project niet gevonden in BC.'], 404);
		}

		$resolvedProjectNo = (string) ($project['No'] ?? $projectNo);
		$settings = index_load_user_settings($userEmail);
		$settings = index_add_recent_project($settings, $company, $resolvedProjectNo);
		index_save_user_settings($userEmail, $settings);
		$recentProjectsPayload = index_normalize_recent_projects(
			is_array($settings['finrap_recent_projects'] ?? null) ? $settings['finrap_recent_projects'] : []
		);

		index_json_response([
			'ok' => true,
			'project' => $project,
			'project_no' => $resolvedProjectNo,
			'cached_months' => finrap_list_cached_months($company, $resolvedProjectNo),
			'recent_projects' => $recentProjectsPayload,
		]);
	} catch (Throwable $error) {
		index_json_response(['ok' => false, 'error' => 'Project zoeken mislukt: ' . $error->getMessage()], 500);
	}
}

if (($_GET['action'] ?? '') === 'list_cached_months') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '') {
		index_json_response(['ok' => false, 'error' => 'Ongeldige invoer.'], 400);
	}

	index_json_response([
		'ok' => true,
		'cached_months' => finrap_list_cached_months($company, $projectNo),
	]);
}

if (($_GET['action'] ?? '') === 'generate_month') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	$yearMonth = trim((string) ($_POST['year_month'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true)) {
		index_json_response(['ok' => false, 'error' => 'Kies een geldig bedrijf.'], 400);
	}
	if ($projectNo === '') {
		index_json_response(['ok' => false, 'error' => 'Projectnummer ontbreekt.'], 400);
	}
	if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
		index_json_response(['ok' => false, 'error' => 'Kies een geldige maand.'], 400);
	}

	try {
		$report = finrap_generate_month_for_project($company, $projectNo, $yearMonth);
		$saveOk = finrap_save($company, $projectNo, $yearMonth, $report);
		if (!$saveOk) {
			index_json_response(['ok' => false, 'error' => 'Opslaan van de maandcache is mislukt.'], 500);
		}

		index_json_response([
			'ok' => true,
			'project_no' => (string) ($report['project_no'] ?? $projectNo),
			'year_month' => $yearMonth,
			'cached_months' => finrap_list_cached_months($company, (string) ($report['project_no'] ?? $projectNo)),
			'report_url' => 'finrap.php?company=' . rawurlencode($company) . '&project_no=' . rawurlencode((string) ($report['project_no'] ?? $projectNo)) . '&year_month=' . rawurlencode($yearMonth),
		]);
	} catch (Throwable $error) {
		index_json_response(['ok' => false, 'error' => 'Genereren mislukt: ' . $error->getMessage()], 500);
	}
}
?>
<!doctype html>
<html lang="nl">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="/favicon.ico">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="site.webmanifest">
	<link rel="stylesheet" href="brand.css">
	<title>Daedalus FinRap</title>
	<style>
		:root {
			--bg: var(--kvt-page-bg);
			--panel: var(--kvt-panel-bg);
			--text: var(--kvt-text);
			--muted: var(--kvt-muted);
			--line: var(--kvt-line);
			--brand: var(--kvt-main-blue);
			--brand-2: var(--kvt-light-blue);
			--brand-dark: var(--kvt-perkins-blue);
			--danger: var(--kvt-danger);
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			color: var(--text);
			background:
				radial-gradient(1200px 500px at -10% -20%, #f0f8ff 0%, transparent 70%),
				radial-gradient(1200px 500px at 120% 120%, #e6f7ff 0%, transparent 70%),
				var(--bg);
		}

		.wrap {
			max-width: 980px;
			margin: 0 auto;
			padding: 18px;
		}

		.workspace-grid {
			display: grid;
			gap: 14px;
			grid-template-columns: 1fr;
		}

		.hero {
			background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand) 100%);
			color: #ffffff;
			border-radius: 16px;
			padding: 18px;
			box-shadow: 0 14px 28px rgba(0, 82, 155, 0.25);
			margin-bottom: 16px;
		}

		.hero img {
			width: 190px;
			max-width: 100%;
			display: block;
			margin-bottom: 10px;
		}

		.hero h1 {
			margin: 0;
			font-size: 26px;
			letter-spacing: 0.3px;
		}

		.hero p {
			margin: 8px 0 0;
			color: #dff4ff;
		}

		.panel {
			background: var(--panel);
			border: 1px solid var(--line);
			border-radius: 14px;
			padding: 14px;
			box-shadow: 0 8px 20px rgba(39, 39, 29, 0.08);
			margin-bottom: 14px;
		}

		.sidebar-panel {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}

		.panel-title {
			margin: 0;
			font-size: 17px;
			font-weight: 700;
			color: var(--brand-dark);
		}

		.panel-subtitle {
			margin: 0;
			font-size: 13px;
			color: var(--muted);
		}

		.recent-project-list {
			margin: 0;
			padding: 0;
			list-style: none;
			display: grid;
			gap: 8px;
		}

		.recent-project-button {
			width: 100%;
			text-align: left;
			background: #f8fbff;
			border: 1px solid #d7e4f2;
			border-radius: 10px;
			padding: 8px 10px;
			cursor: pointer;
			display: grid;
			gap: 3px;
		}

		.recent-project-button:hover {
			background: #edf7ff;
			border-color: #b7cbe4;
		}

		.recent-project-main {
			font-size: 14px;
			font-weight: 700;
			color: var(--brand-dark);
		}

		.recent-project-meta {
			font-size: 12px;
			color: var(--muted);
		}

		.grid {
			display: grid;
			gap: 12px;
			grid-template-columns: 1fr;
		}

		label {
			display: block;
			font-size: 13px;
			margin-bottom: 6px;
			color: var(--muted);
			font-weight: 700;
		}

		input,
		select,
		button {
			width: 100%;
			min-height: 42px;
			border-radius: 10px;
			border: 1px solid #b7cbe4;
			padding: 10px 12px;
			font-size: 15px;
		}

		.btn {
			cursor: pointer;
			border: 0;
			font-weight: 700;
			transition: transform .12s ease, opacity .12s ease;
		}

		.btn:active {
			transform: translateY(1px);
		}

		.btn-main {
			background: var(--brand);
			color: #fff;
		}

		.btn-alt {
			background: var(--brand-2);
			color: #00384d;
		}

		.btn-open {
			background: var(--brand-dark);
			color: #fff;
		}

		.btn-print {
			background: #edf7ff;
			color: var(--brand-dark);
			border: 1px solid #b7cbe4;
		}

		.btn[disabled] {
			opacity: 0.5;
			cursor: default;
		}

		.status {
			margin-top: 10px;
			font-size: 14px;
			color: var(--muted);
		}

		.status.error {
			color: var(--danger);
			font-weight: 700;
		}

		.project-card {
			border: 1px dashed #b9c5b1;
			border-radius: 12px;
			padding: 10px;
			margin-top: 12px;
			background: #fafcf5;
		}

		.project-title {
			font-size: 18px;
			font-weight: 700;
			margin: 0 0 4px;
		}

		.meta {
			font-size: 14px;
			color: var(--muted);
			margin: 0;
		}

		.row-actions {
			display: grid;
			gap: 10px;
			grid-template-columns: 1fr;
			margin-top: 12px;
		}

		.month-list {
			margin: 10px 0 0;
			padding: 0;
			list-style: none;
		}

		.month-list li {
			display: flex;
			justify-content: space-between;
			gap: 8px;
			padding: 7px 0;
			border-bottom: 1px solid #ece6d8;
			font-size: 14px;
		}

		.loader-overlay {
			position: fixed;
			inset: 0;
			background: rgba(22, 32, 22, 0.48);
			backdrop-filter: blur(2px);
			display: none;
			align-items: center;
			justify-content: center;
			z-index: 9999;
		}

		.loader-overlay.is-visible {
			display: flex;
		}

		.loader-card {
			width: min(94vw, 520px);
			background: #fdfbf4;
			border: 1px solid #cfd8c8;
			border-radius: 16px;
			padding: 16px;
			box-shadow: 0 18px 42px rgba(20, 30, 22, 0.35);
		}

		.loader-title {
			margin: 0;
			font-size: 20px;
		}

		.loader-subtitle {
			margin: 6px 0 12px;
			font-size: 14px;
			color: var(--muted);
		}

		.loader-steps {
			margin: 0;
			padding: 0;
			list-style: none;
			display: grid;
			gap: 8px;
		}

		.loader-step {
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 6px 8px;
			border-radius: 8px;
			background: #f5f7ef;
			border: 1px solid #e4e9de;
			font-size: 14px;
		}

		.loader-step-dot {
			width: 16px;
			height: 16px;
			border-radius: 50%;
			border: 2px solid #95a692;
			position: relative;
			flex-shrink: 0;
		}

		.loader-step.is-loading .loader-step-dot {
			border-color: var(--brand);
			animation: loaderPulse 1s infinite ease-in-out;
		}

		.loader-step.is-done .loader-step-dot {
			background: var(--brand);
			border-color: var(--brand);
		}

		.loader-step.is-done .loader-step-dot::after {
			content: '';
			position: absolute;
			left: 4px;
			top: 1px;
			width: 4px;
			height: 8px;
			border: solid #fff;
			border-width: 0 2px 2px 0;
			transform: rotate(45deg);
		}

		.loader-live {
			margin: 12px 0 0;
			font-size: 13px;
			color: var(--muted);
		}

		.finrap-modal-overlay {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.55);
			display: none;
			align-items: stretch;
			justify-content: center;
			z-index: 11000;
			padding: 14px;
		}

		.finrap-modal-overlay.is-visible {
			display: flex;
		}

		.finrap-modal-dialog {
			width: min(1500px, 100%);
			height: min(96vh, 1080px);
			background: #ffffff;
			border: 1px solid #c9d7eb;
			border-radius: 14px;
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}

		.finrap-modal-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 10px;
			padding: 10px 12px;
			background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand) 100%);
			color: #ffffff;
		}

		.finrap-modal-title {
			margin: 0;
			font-size: 16px;
			font-weight: 700;
		}

		.finrap-modal-actions {
			display: flex;
			gap: 8px;
		}

		.finrap-modal-actions .btn {
			min-height: 34px;
			padding: 6px 10px;
			font-size: 13px;
		}

		.finrap-modal-frame {
			flex: 1;
			width: 100%;
			border: 0;
			background: #ffffff;
		}

		@keyframes loaderPulse {

			0%,
			100% {
				transform: scale(1);
			}

			50% {
				transform: scale(1.15);
			}
		}

		.muted {
			color: var(--muted);
		}

		@media (min-width: 820px) {
			.workspace-grid {
				grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
				align-items: start;
			}

			.grid {
				grid-template-columns: 1fr 1fr;
			}

			.row-actions {
				grid-template-columns: 1fr 1fr;
			}
		}

		@media (max-width: 820px) {
			.finrap-modal-overlay {
				padding: 0;
			}

			.finrap-modal-dialog {
				width: 100%;
				height: 100vh;
				border-radius: 0;
				border: 0;
			}

			.finrap-modal-head {
				padding: 10px;
			}

			.finrap-modal-title {
				font-size: 14px;
			}
		}
	</style>
</head>

<body>
	<div class="wrap">
		<section class="hero">
			<img src="logo-website.png" alt="KVT logo">
			<h1>Financieel Rapport</h1>
			<p>Kies bedrijf, zoek project, genereer maand en open of print het rapport.</p>
		</section>

		<div class="workspace-grid">
			<section class="panel">
				<div class="grid">
					<div>
						<label for="companySelect">Bedrijf</label>
						<select id="companySelect">
							<?php foreach ($companies as $company): ?>
								<option value="<?= htmlspecialchars($company, ENT_QUOTES) ?>" <?= $company === $selectedCompany ? 'selected' : '' ?>>
									<?= htmlspecialchars($company) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="projectInput">Projectnummer</label>
						<input id="projectInput" type="text" autocomplete="off" placeholder="Bijv. P12345">
					</div>
				</div>
				<div class="row-actions">
					<button id="findBtn" class="btn btn-main" type="button">Zoek project in BC</button>
				</div>
				<p id="statusLine" class="status">Nog geen project gezocht.</p>
				<div id="projectArea"></div>
			</section>

			<aside class="panel sidebar-panel">
				<h2 class="panel-title">Recent gevonden projecten</h2>
				<p class="panel-subtitle">Gesorteerd op laatst opgezocht voor gebruiker: <?= htmlspecialchars($userEmail !== '' ? $userEmail : 'onbekend') ?></p>
				<ul id="recentProjectsList" class="recent-project-list"></ul>
			</aside>
		</div>
	</div>

	<div id="finrapLoader" class="loader-overlay" aria-live="polite" aria-busy="true">
		<div class="loader-card">
			<h2 id="loaderTitle" class="loader-title">FinRap laden</h2>
			<p id="loaderSubtitle" class="loader-subtitle">Even geduld...</p>
			<ul id="loaderSteps" class="loader-steps"></ul>
			<p id="loaderLive" class="loader-live">Voorbereiden...</p>
		</div>
	</div>

	<div id="finrapModalOverlay" class="finrap-modal-overlay" role="dialog" aria-modal="true"
		aria-labelledby="finrapModalTitle">
		<div class="finrap-modal-dialog">
			<div class="finrap-modal-head">
				<h2 id="finrapModalTitle" class="finrap-modal-title">Financieel Rapport</h2>
				<div class="finrap-modal-actions">
					<button id="finrapModalPrint" class="btn btn-print" type="button">Print</button>
					<button id="finrapModalClose" class="btn btn-main" type="button">Sluiten</button>
				</div>
			</div>
			<iframe id="finrapModalFrame" class="finrap-modal-frame" title="Financieel rapport"></iframe>
		</div>
	</div>

	<script>
		(function ()
		{
			const initialRecentProjects = <?= json_encode($recentProjects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
			const companySelect = document.getElementById('companySelect');
			const projectInput = document.getElementById('projectInput');
			const findBtn = document.getElementById('findBtn');
			const statusLine = document.getElementById('statusLine');
			const projectArea = document.getElementById('projectArea');
			const recentProjectsList = document.getElementById('recentProjectsList');
			const finrapLoader = document.getElementById('finrapLoader');
			const loaderTitle = document.getElementById('loaderTitle');
			const loaderSubtitle = document.getElementById('loaderSubtitle');
			const loaderSteps = document.getElementById('loaderSteps');
			const loaderLive = document.getElementById('loaderLive');
			const finrapModalOverlay = document.getElementById('finrapModalOverlay');
			const finrapModalFrame = document.getElementById('finrapModalFrame');
			const finrapModalClose = document.getElementById('finrapModalClose');
			const finrapModalPrint = document.getElementById('finrapModalPrint');
			const finrapModalTitle = document.getElementById('finrapModalTitle');

			let activeProjectNo = '';
			let loaderTick = null;
			let loaderStepsState = [];
			let loaderCurrentStep = -1;
			let recentProjects = Array.isArray(initialRecentProjects) ? initialRecentProjects : [];

			function monthLabel (ym)
			{
				const m = {
					'01': 'Januari', '02': 'Februari', '03': 'Maart', '04': 'April',
					'05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Augustus',
					'09': 'September', '10': 'Oktober', '11': 'November', '12': 'December'
				};
				const parts = String(ym || '').split('-');
				if (parts.length !== 2) { return ym; }
				return (m[parts[1]] || parts[1]) + ' ' + parts[0];
			}

			function postForm (url, body)
			{
				return fetch(url, { method: 'POST', body: new URLSearchParams(body) })
					.then(function (res) { return res.json(); });
			}

			function loaderSetMode (mode, subtitle)
			{
				if (!loaderSteps || !loaderTitle || !loaderSubtitle)
				{
					return;
				}

				if (mode === 'search')
				{
					loaderTitle.textContent = 'Project zoeken in BC';
					loaderStepsState = [
						'Verbinding met omgeving maken',
						'Projectgegevens ophalen',
						'Gecachte maanden laden'
					];
				} else
				{
					loaderTitle.textContent = 'FinRap maand laden';
					loaderStepsState = [
						'Project verifiëren',
						'Financiële data ophalen',
						'Kostenregels opbouwen',
						'Maandcache opslaan',
						'Rapport in modal openen'
					];
				}

				loaderSubtitle.textContent = subtitle || 'Even geduld...';
				loaderSteps.innerHTML = '';
				loaderCurrentStep = -1;

				loaderStepsState.forEach(function (label, index)
				{
					const li = document.createElement('li');
					li.className = 'loader-step';
					li.dataset.index = String(index);

					const dot = document.createElement('span');
					dot.className = 'loader-step-dot';
					const text = document.createElement('span');
					text.textContent = label;

					li.appendChild(dot);
					li.appendChild(text);
					loaderSteps.appendChild(li);
				});
			}

			function loaderMarkStep (stepIndex, state)
			{
				if (!loaderSteps)
				{
					return;
				}
				const step = loaderSteps.querySelector('.loader-step[data-index="' + String(stepIndex) + '"]');
				if (!step)
				{
					return;
				}
				step.classList.remove('is-loading');
				step.classList.remove('is-done');
				if (state === 'loading')
				{
					step.classList.add('is-loading');
				}
				if (state === 'done')
				{
					step.classList.add('is-done');
				}
			}

			function loaderAdvance ()
			{
				const nextStep = loaderCurrentStep + 1;
				if (nextStep >= loaderStepsState.length)
				{
					return;
				}

				if (loaderCurrentStep >= 0)
				{
					loaderMarkStep(loaderCurrentStep, 'done');
				}
				loaderCurrentStep = nextStep;
				loaderMarkStep(loaderCurrentStep, 'loading');
				if (loaderLive)
				{
					loaderLive.textContent = loaderStepsState[loaderCurrentStep] + '...';
				}
			}

			function showLoader (mode, subtitle)
			{
				if (!finrapLoader)
				{
					return;
				}
				loaderSetMode(mode, subtitle);
				finrapLoader.classList.add('is-visible');
				loaderAdvance();
				if (loaderTick !== null)
				{
					window.clearInterval(loaderTick);
				}
				loaderTick = window.setInterval(function ()
				{
					if (loaderCurrentStep < loaderStepsState.length - 2)
					{
						loaderAdvance();
					}
				}, mode === 'search' ? 500 : 900);
			}

			function finalizeLoader (message)
			{
				if (!finrapLoader)
				{
					return;
				}
				if (loaderTick !== null)
				{
					window.clearInterval(loaderTick);
					loaderTick = null;
				}
				for (let i = 0; i < loaderStepsState.length; i++)
				{
					loaderMarkStep(i, 'done');
				}
				if (loaderLive)
				{
					loaderLive.textContent = message || 'Klaar.';
				}
			}

			function hideLoader ()
			{
				if (!finrapLoader)
				{
					return;
				}
				if (loaderTick !== null)
				{
					window.clearInterval(loaderTick);
					loaderTick = null;
				}
				finrapLoader.classList.remove('is-visible');
			}

			function setStatus (text, isError)
			{
				statusLine.textContent = text;
				statusLine.className = isError ? 'status error' : 'status';
			}

			function saveSelectedCompanyPreference ()
			{
				if (!companySelect)
				{
					return;
				}

				postForm('index.php?action=save_company_preference', {
					company: companySelect.value
				}).catch(function ()
				{
					return null;
				});
			}

			function formatRecentDate (value)
			{
				const dt = new Date(String(value || ''));
				if (Number.isNaN(dt.getTime()))
				{
					return '';
				}

				return dt.toLocaleString('nl-NL', {
					year: 'numeric',
					month: '2-digit',
					day: '2-digit',
					hour: '2-digit',
					minute: '2-digit'
				});
			}

			function normalizeRecentProjects (items)
			{
				if (!Array.isArray(items))
				{
					return [];
				}

				return items
					.filter(function (entry)
					{
						return entry && String(entry.project_no || '').trim() !== '';
					})
					.sort(function (a, b)
					{
						return String(b.last_searched_at || '').localeCompare(String(a.last_searched_at || ''));
					})
					.slice(0, 25);
			}

			function renderRecentProjects ()
			{
				if (!recentProjectsList)
				{
					return;
				}

				recentProjectsList.innerHTML = '';
				if (!Array.isArray(recentProjects) || recentProjects.length === 0)
				{
					const empty = document.createElement('li');
					empty.className = 'muted';
					empty.textContent = 'Nog geen projecten gevonden in BC.';
					recentProjectsList.appendChild(empty);
					return;
				}

				recentProjects.forEach(function (entry)
				{
					const projectNo = String(entry.project_no || '').trim();
					const company = String(entry.company || '').trim();
					if (projectNo === '')
					{
						return;
					}

					const item = document.createElement('li');
					const button = document.createElement('button');
					button.type = 'button';
					button.className = 'recent-project-button';

					const main = document.createElement('span');
					main.className = 'recent-project-main';
					main.textContent = projectNo;

					const meta = document.createElement('span');
					meta.className = 'recent-project-meta';
					const dtLabel = formatRecentDate(entry.last_searched_at);
					meta.textContent = (company !== '' ? company : 'Onbekend bedrijf') + (dtLabel !== '' ? ' | ' + dtLabel : '');

					button.appendChild(main);
					button.appendChild(meta);
					button.addEventListener('click', function ()
					{
						if (company !== '' && companySelect)
						{
							const hasOption = Array.prototype.some.call(companySelect.options, function (opt)
							{
								return opt.value === company;
							});
							if (hasOption)
							{
								companySelect.value = company;
								saveSelectedCompanyPreference();
							}
						}

						projectInput.value = projectNo;
						findProject(projectNo);
					});

					item.appendChild(button);
					recentProjectsList.appendChild(item);
				});
			}

			function closeFinrapModal ()
			{
				if (!finrapModalOverlay || !finrapModalFrame)
				{
					return;
				}
				finrapModalOverlay.classList.remove('is-visible');
				finrapModalFrame.src = 'about:blank';
				document.body.style.overflow = '';
			}

			function openFinrapModal (url, title, autoPrint)
			{
				if (!finrapModalOverlay || !finrapModalFrame)
				{
					window.location.href = url;
					return;
				}

				if (finrapModalTitle)
				{
					finrapModalTitle.textContent = title || 'Financieel Rapport';
				}

				const finalUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'embed=1';
				finrapModalFrame.src = finalUrl;
				finrapModalOverlay.classList.add('is-visible');
				document.body.style.overflow = 'hidden';

				if (autoPrint)
				{
					const handleLoad = function ()
					{
						finrapModalFrame.removeEventListener('load', handleLoad);
						if (finrapModalFrame.contentWindow)
						{
							finrapModalFrame.contentWindow.focus();
							finrapModalFrame.contentWindow.print();
						}
					};
					finrapModalFrame.addEventListener('load', handleLoad);
				}
			}

			function buildMonthSelect (months)
			{
				const select = document.createElement('select');
				select.id = 'cachedMonthSelect';

				const list = Array.isArray(months) ? months : [];
				if (list.length === 0)
				{
					const opt = document.createElement('option');
					opt.value = '';
					opt.textContent = 'Nog geen gecachte maanden';
					select.appendChild(opt);
					return select;
				}

				list.forEach(function (entry)
				{
					const ym = String(entry.year_month || '');
					const opt = document.createElement('option');
					opt.value = ym;
					opt.textContent = monthLabel(ym);
					select.appendChild(opt);
				});

				return select;
			}

			function renderProject (project, cachedMonths)
			{
				const no = String((project && project.No) || activeProjectNo || '').trim();
				activeProjectNo = no;
				projectArea.innerHTML = '';

				const card = document.createElement('div');
				card.className = 'project-card';

				const title = document.createElement('h2');
				title.className = 'project-title';
				title.textContent = no + ' - ' + String((project && project.Description) || '');
				card.appendChild(title);

				const meta = document.createElement('p');
				meta.className = 'meta';
				const customerNo = String((project && project.Bill_to_Customer_No) || (project && project.Sell_to_Customer_No) || '').trim();
				const customerName = String((project && project.Bill_to_Name) || (project && project.Sell_to_Customer_Name) || '').trim();
				meta.textContent = customerNo !== '' ? ('Debiteur: ' + customerNo + (customerName ? ' - ' + customerName : '')) : 'Debiteur: niet beschikbaar';
				card.appendChild(meta);

				const monthInputLabel = document.createElement('label');
				monthInputLabel.textContent = 'Genereer specifieke maand';
				monthInputLabel.setAttribute('for', 'generateMonthInput');
				monthInputLabel.style.marginTop = '12px';
				card.appendChild(monthInputLabel);

				const monthInput = document.createElement('input');
				monthInput.id = 'generateMonthInput';
				monthInput.type = 'month';
				monthInput.value = new Date().toISOString().slice(0, 7);
				card.appendChild(monthInput);

				const generateBtn = document.createElement('button');
				generateBtn.className = 'btn btn-alt';
				generateBtn.type = 'button';
				generateBtn.textContent = 'Genereer maand en open rapport';
				generateBtn.style.marginTop = '10px';
				card.appendChild(generateBtn);

				const cachedLabel = document.createElement('label');
				cachedLabel.textContent = 'Gecachte maanden';
				cachedLabel.setAttribute('for', 'cachedMonthSelect');
				cachedLabel.style.marginTop = '14px';
				card.appendChild(cachedLabel);

				const cachedSelect = buildMonthSelect(cachedMonths);
				card.appendChild(cachedSelect);

				const actionWrap = document.createElement('div');
				actionWrap.className = 'row-actions';
				actionWrap.style.marginTop = '10px';

				const openBtn = document.createElement('button');
				openBtn.className = 'btn btn-open';
				openBtn.type = 'button';
				openBtn.textContent = 'Open bestaand rapport';

				const printBtn = document.createElement('button');
				printBtn.className = 'btn btn-print';
				printBtn.type = 'button';
				printBtn.textContent = 'Open + print';

				actionWrap.appendChild(openBtn);
				actionWrap.appendChild(printBtn);
				card.appendChild(actionWrap);

				const monthList = document.createElement('ul');
				monthList.className = 'month-list';
				if (!Array.isArray(cachedMonths) || cachedMonths.length === 0)
				{
					const li = document.createElement('li');
					li.innerHTML = '<span class="muted">Nog geen maandcache voor dit project.</span><span></span>';
					monthList.appendChild(li);
				} else
				{
					cachedMonths.forEach(function (entry)
					{
						const li = document.createElement('li');
						li.innerHTML = '<span>' + monthLabel(String(entry.year_month || '')) + '</span><span class="muted">' + String(entry.fetched_at || '') + '</span>';
						monthList.appendChild(li);
					});
				}
				card.appendChild(monthList);

				generateBtn.addEventListener('click', function ()
				{
					const ym = String(monthInput.value || '').trim();
					if (!/^\d{4}-\d{2}$/.test(ym))
					{
						setStatus('Kies eerst een geldige maand.', true);
						return;
					}

					setStatus('Genereren van ' + monthLabel(ym) + '...', false);
					generateBtn.disabled = true;
					showLoader('generate', 'FinRap voor ' + no + ' - ' + monthLabel(ym));
					postForm('index.php?action=generate_month', {
						company: companySelect.value,
						project_no: activeProjectNo,
						year_month: ym
					}).then(function (json)
					{
						generateBtn.disabled = false;
						if (!json.ok)
						{
							hideLoader();
							setStatus(json.error || 'Genereren mislukt.', true);
							return;
						}

						finalizeLoader('Maand gereed, rapport wordt geopend...');
						setStatus('Maand gegenereerd: ' + monthLabel(ym), false);
						renderProject(project, json.cached_months || []);
						if (json.report_url)
						{
							openFinrapModal(json.report_url, 'Financieel Rapport ' + activeProjectNo + ' - ' + monthLabel(ym), false);
						}
						window.setTimeout(hideLoader, 320);
					}).catch(function (error)
					{
						generateBtn.disabled = false;
						hideLoader();
						setStatus('Netwerkfout: ' + error.message, true);
					});
				});

				function openReport (withPrint)
				{
					const ym = String(cachedSelect.value || '').trim();
					if (!/^\d{4}-\d{2}$/.test(ym))
					{
						setStatus('Selecteer eerst een gecachte maand.', true);
						return;
					}
					let url = 'finrap.php?company=' + encodeURIComponent(companySelect.value)
						+ '&project_no=' + encodeURIComponent(activeProjectNo)
						+ '&year_month=' + encodeURIComponent(ym);
					openFinrapModal(url, 'Financieel Rapport ' + activeProjectNo + ' - ' + monthLabel(ym), withPrint);
				}

				openBtn.addEventListener('click', function () { openReport(false); });
				printBtn.addEventListener('click', function () { openReport(true); });

				projectArea.appendChild(card);
			}

			function findProject (projectNoInput)
			{
				const projectNo = String(projectNoInput || '').trim();
				if (projectNo === '')
				{
					setStatus('Voer een projectnummer in.', true);
					return;
				}

				setStatus('Project zoeken in BC...', false);
				findBtn.disabled = true;
				showLoader('search', 'Project ' + projectNo + ' opzoeken');
				postForm('index.php?action=find_project', {
					company: companySelect.value,
					project_no: projectNo
				}).then(function (json)
				{
					findBtn.disabled = false;
					if (!json.ok)
					{
						hideLoader();
						projectArea.innerHTML = '';
						setStatus(json.error || 'Project niet gevonden.', true);
						return;
					}

					if (Array.isArray(json.recent_projects))
					{
						recentProjects = normalizeRecentProjects(json.recent_projects);
						renderRecentProjects();
					}

					finalizeLoader('Project gevonden.');
					setStatus('Project gevonden: ' + String(json.project_no || projectNo), false);
					renderProject(json.project || {}, json.cached_months || []);
					window.setTimeout(hideLoader, 240);
				}).catch(function (error)
				{
					findBtn.disabled = false;
					hideLoader();
					setStatus('Netwerkfout: ' + error.message, true);
				});
			}

			findBtn.addEventListener('click', function ()
			{
				findProject(projectInput.value);
			});

			if (companySelect)
			{
				companySelect.addEventListener('change', saveSelectedCompanyPreference);
			}

			recentProjects = normalizeRecentProjects(recentProjects);
			renderRecentProjects();

			if (finrapModalClose)
			{
				finrapModalClose.addEventListener('click', closeFinrapModal);
			}

			if (finrapModalPrint)
			{
				finrapModalPrint.addEventListener('click', function ()
				{
					if (finrapModalFrame && finrapModalFrame.contentWindow)
					{
						finrapModalFrame.contentWindow.focus();
						finrapModalFrame.contentWindow.print();
					}
				});
			}

			if (finrapModalOverlay)
			{
				finrapModalOverlay.addEventListener('click', function (event)
				{
					if (event.target === finrapModalOverlay)
					{
						closeFinrapModal();
					}
				});
			}

			window.addEventListener('keydown', function (event)
			{
				if (event.key === 'Escape' && finrapModalOverlay && finrapModalOverlay.classList.contains('is-visible'))
				{
					closeFinrapModal();
				}
			});
		})();
	</script>
</body>

</html>
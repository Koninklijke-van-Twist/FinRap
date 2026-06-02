<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Includes/requires
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';
require_once __DIR__ . '/localization.php';
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
	return __($monthKey = 'month.' . $month) . ' ' . $year;
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
		index_json_response(['ok' => false, 'error' => __('error.company_invalid')], 400);
	}

	$settings = index_load_user_settings($userEmail);
	$settings['finrap_selected_company'] = $company;
	$saveOk = index_save_user_settings($userEmail, $settings);
	if (!$saveOk) {
		index_json_response(['ok' => false, 'error' => __('error.save_preference_failed')], 500);
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
		index_json_response(['ok' => false, 'error' => __('error.company_invalid')], 400);
	}
	if ($projectNo === '') {
		index_json_response(['ok' => false, 'error' => __('error.project_no_required')], 400);
	}

	try {
		$project = finrap_fetch_project($company, $projectNo, 300);
		if (!is_array($project)) {
			index_json_response(['ok' => false, 'error' => __('error.project_not_found')], 404);
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
			'reports' => finrap_list_report_snapshots($company, $resolvedProjectNo),
			'recent_projects' => $recentProjectsPayload,
		]);
	} catch (Throwable $error) {
		index_json_response(['ok' => false, 'error' => __('error.find_project_failed', $error->getMessage())], 500);
	}
}

if (($_GET['action'] ?? '') === 'list_reports') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '') {
		index_json_response(['ok' => false, 'error' => __('error.invalid_input')], 400);
	}

	index_json_response([
		'ok' => true,
		'reports' => finrap_list_report_snapshots($company, $projectNo),
	]);
}

if (($_GET['action'] ?? '') === 'generate_report') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true)) {
		index_json_response(['ok' => false, 'error' => __('error.company_invalid')], 400);
	}
	if ($projectNo === '') {
		index_json_response(['ok' => false, 'error' => __('error.project_no_missing')], 400);
	}

	try {
		$yearMonth = gmdate('Y-m');
		$report = finrap_generate_month_for_project($company, $projectNo, $yearMonth);
		$resolvedProjectNo = (string) ($report['project_no'] ?? $projectNo);
		$reportId = finrap_save_report_snapshot($company, $resolvedProjectNo, $report);
		if (!is_string($reportId) || $reportId === '') {
			index_json_response(['ok' => false, 'error' => __('error.save_report_failed')], 500);
		}

		$reportLang = rawurlencode(getCurrentLanguage());
		index_json_response([
			'ok' => true,
			'project_no' => $resolvedProjectNo,
			'report_id' => $reportId,
			'reports' => finrap_list_report_snapshots($company, $resolvedProjectNo),
			'report_url' => 'finrap.php?company=' . rawurlencode($company) . '&project_no=' . rawurlencode($resolvedProjectNo) . '&report_id=' . rawurlencode($reportId) . '&lang=' . $reportLang,
		]);
	} catch (Throwable $error) {
		index_json_response(['ok' => false, 'error' => __('error.generate_failed', $error->getMessage())], 500);
	}
}

if (($_GET['action'] ?? '') === 'delete_report') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	$reportId = trim((string) ($_POST['report_id'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '' || $reportId === '') {
		index_json_response(['ok' => false, 'error' => __('error.invalid_input')], 400);
	}

	$deleted = finrap_delete_report_snapshot($company, $projectNo, $reportId);
	if (!$deleted) {
		index_json_response(['ok' => false, 'error' => __('error.delete_report_failed')], 404);
	}

	index_json_response([
		'ok' => true,
		'reports' => finrap_list_report_snapshots($company, $projectNo),
	]);
}
$indexI18nKeys = [
	'index.loader.finrap',
	'index.loader.search',
	'index.loader.wait',
	'index.loader.prepare',
	'index.loader.done',
	'index.loader.step.search_connect',
	'index.loader.step.search_fetch',
	'index.loader.step.search_cache',
	'index.loader.step.gen_verify',
	'index.loader.step.gen_finance',
	'index.loader.step.gen_costs',
	'index.loader.step.gen_save',
	'index.loader.step.gen_open',
	'index.js.customer',
	'index.js.customer_unavailable',
	'index.js.generate_label',
	'index.js.generate_btn',
	'index.js.reports_label',
	'index.js.reports_empty',
	'index.js.btn.open',
	'index.js.report_modal_title',
	'index.js.recent_empty',
	'index.js.recent_unknown_company',
	'index.js.unknown_moment',
	'index.js.status.enter_project',
	'index.js.status.searching',
	'index.js.status.search_subtitle',
	'index.js.status.not_found',
	'index.js.status.found',
	'index.js.status.generating',
	'index.js.status.generate_failed',
	'index.js.status.generated',
	'index.js.status.generate_subtitle',
	'index.js.status.report_ready',
	'index.js.status.delete_failed',
	'index.js.status.deleted',
	'index.js.network_error',
	'index.js.loader_ellipsis',
	'index.modal.title',
];
?>
<!doctype html>
<html lang="<?= htmlspecialchars(getHtmlLang(), ENT_QUOTES) ?>">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="/favicon.ico">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="site.webmanifest">
	<link rel="stylesheet" href="brand.css">
	<title><?= htmlspecialchars(__('app.title'), ENT_QUOTES) ?></title>
	<?php renderLanguageSwitcherStyles(); ?>
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

		.report-list {
			margin: 12px 0 0;
			padding: 0;
			list-style: none;
			display: grid;
			gap: 8px;
		}

		.report-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			padding: 8px 10px;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			background: #fff;
		}

		.report-item-meta {
			font-size: 13px;
			color: var(--muted);
		}

		.report-item-actions {
			display: flex;
			gap: 6px;
			flex-shrink: 0;
		}

		.btn-danger-icon {
			background: #fff5f5;
			border: 1px solid #fecaca;
			color: #b42318;
			min-height: 34px;
			padding: 4px 10px;
			font-size: 16px;
		}

		.confirm-overlay {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.58);
			display: none;
			align-items: center;
			justify-content: center;
			z-index: 12000;
			padding: 16px;
		}

		.confirm-overlay.is-visible {
			display: flex;
		}

		.confirm-dialog {
			width: min(520px, 96vw);
			background: #ffffff;
			border-radius: 12px;
			overflow: hidden;
			border: 1px solid #cbd5e1;
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
		}

		.confirm-topbar {
			padding: 10px 12px;
			font-weight: 700;
			color: #ffffff;
			min-height: 50px;
		}

		.confirm-topbar.is-red {
			background: #b42318;
		}

		.confirm-topbar.is-hazard {
			background: repeating-linear-gradient(45deg,
					#111111 0,
					#111111 14px,
					#facc15 14px,
					#facc15 28px);
			color: #111111;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.3);
		}

		.confirm-body {
			padding: 14px 12px;
			font-size: 14px;
			color: #1f2937;
		}

		.confirm-actions {
			display: flex;
			justify-content: flex-end;
			gap: 8px;
			padding: 0 12px 12px;
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
	<?php renderLanguageSwitcher(); ?>
	<div class="wrap">
		<section class="hero">
			<img src="logo-website.png" alt="KVT logo">
			<h1><?= htmlspecialchars(__('index.hero.title'), ENT_QUOTES) ?></h1>
			<p><?= htmlspecialchars(__('index.hero.subtitle'), ENT_QUOTES) ?></p>
		</section>

		<div class="workspace-grid">
			<section class="panel">
				<div class="grid">
					<div>
						<label for="companySelect"><?= htmlspecialchars(__('index.label.company'), ENT_QUOTES) ?></label>
						<select id="companySelect">
							<?php foreach ($companies as $company): ?>
								<option value="<?= htmlspecialchars($company, ENT_QUOTES) ?>" <?= $company === $selectedCompany ? 'selected' : '' ?>>
									<?= htmlspecialchars($company) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="projectInput"><?= htmlspecialchars(__('index.label.project_no'), ENT_QUOTES) ?></label>
						<input id="projectInput" type="text" autocomplete="off" placeholder="<?= htmlspecialchars(__('index.placeholder.project_no'), ENT_QUOTES) ?>">
					</div>
				</div>
				<div class="row-actions">
					<button id="findBtn" class="btn btn-main" type="button"><?= htmlspecialchars(__('index.btn.find'), ENT_QUOTES) ?></button>
				</div>
				<p id="statusLine" class="status"><?= htmlspecialchars(__('index.status.none'), ENT_QUOTES) ?></p>
				<div id="projectArea"></div>
			</section>

			<aside class="panel sidebar-panel">
				<h2 class="panel-title"><?= htmlspecialchars(__('index.recent.title'), ENT_QUOTES) ?></h2>
				<ul id="recentProjectsList" class="recent-project-list"></ul>
			</aside>
		</div>
	</div>

	<div id="finrapLoader" class="loader-overlay" aria-live="polite" aria-busy="true">
		<div class="loader-card">
			<h2 id="loaderTitle" class="loader-title"><?= htmlspecialchars(__('index.loader.finrap'), ENT_QUOTES) ?></h2>
			<p id="loaderSubtitle" class="loader-subtitle"><?= htmlspecialchars(__('index.loader.wait'), ENT_QUOTES) ?></p>
			<ul id="loaderSteps" class="loader-steps"></ul>
			<p id="loaderLive" class="loader-live"><?= htmlspecialchars(__('index.loader.prepare'), ENT_QUOTES) ?></p>
		</div>
	</div>

	<div id="finrapModalOverlay" class="finrap-modal-overlay" role="dialog" aria-modal="true"
		aria-labelledby="finrapModalTitle">
		<div class="finrap-modal-dialog">
			<div class="finrap-modal-head">
				<h2 id="finrapModalTitle" class="finrap-modal-title"><?= htmlspecialchars(__('index.modal.title'), ENT_QUOTES) ?></h2>
				<div class="finrap-modal-actions">
					<button id="finrapModalPrint" class="btn btn-print" type="button"><?= htmlspecialchars(__('index.modal.print'), ENT_QUOTES) ?></button>
					<button id="finrapModalClose" class="btn btn-main" type="button"><?= htmlspecialchars(__('index.modal.close'), ENT_QUOTES) ?></button>
				</div>
			</div>
			<iframe id="finrapModalFrame" class="finrap-modal-frame" title="<?= htmlspecialchars(__('index.modal.report_iframe'), ENT_QUOTES) ?>"></iframe>
		</div>
	</div>

	<div id="confirmDeleteStep1" class="confirm-overlay" role="dialog" aria-modal="true"
		aria-labelledby="confirmDeleteStep1Title">
		<div class="confirm-dialog">
			<div id="confirmDeleteStep1Title" class="confirm-topbar is-red"></div>
			<div class="confirm-body"><?= htmlspecialchars(__('index.delete.step1.body'), ENT_QUOTES) ?></div>
			<div class="confirm-actions">
				<button id="confirmDeleteStep1No" class="btn btn-print" type="button"><?= htmlspecialchars(__('index.btn.no'), ENT_QUOTES) ?></button>
				<button id="confirmDeleteStep1Yes" class="btn btn-main" type="button"><?= htmlspecialchars(__('index.btn.yes'), ENT_QUOTES) ?></button>
			</div>
		</div>
	</div>

	<div id="confirmDeleteStep2" class="confirm-overlay" role="dialog" aria-modal="true"
		aria-labelledby="confirmDeleteStep2Title">
		<div class="confirm-dialog">
			<div id="confirmDeleteStep2Title" class="confirm-topbar is-hazard"></div>
			<div class="confirm-body"><?= htmlspecialchars(__('index.delete.step2.body'), ENT_QUOTES) ?></div>
			<div class="confirm-actions">
				<button id="confirmDeleteStep2No" class="btn btn-print" type="button"><?= htmlspecialchars(__('index.btn.no'), ENT_QUOTES) ?></button>
				<button id="confirmDeleteStep2Yes" class="btn btn-main" type="button"><?= htmlspecialchars(__('index.btn.yes'), ENT_QUOTES) ?></button>
			</div>
		</div>
	</div>

	<script>
		(function ()
		{
			const i18n = <?= localizationJsTranslations($indexI18nKeys) ?>;
			const dateLocale = <?= json_encode(getDateLocale(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
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
			const confirmDeleteStep1 = document.getElementById('confirmDeleteStep1');
			const confirmDeleteStep2 = document.getElementById('confirmDeleteStep2');
			const confirmDeleteStep1No = document.getElementById('confirmDeleteStep1No');
			const confirmDeleteStep1Yes = document.getElementById('confirmDeleteStep1Yes');
			const confirmDeleteStep2No = document.getElementById('confirmDeleteStep2No');
			const confirmDeleteStep2Yes = document.getElementById('confirmDeleteStep2Yes');

			let activeProjectNo = '';
			let loaderTick = null;
			let loaderStepsState = [];
			let loaderCurrentStep = -1;
			let recentProjects = Array.isArray(initialRecentProjects) ? initialRecentProjects : [];
			let pendingDeleteReportId = '';
			let currentProjectData = null;

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
					loaderTitle.textContent = i18n['index.loader.search'];
					loaderStepsState = [
						i18n['index.loader.step.search_connect'],
						i18n['index.loader.step.search_fetch'],
						i18n['index.loader.step.search_cache']
					];
				} else
				{
					loaderTitle.textContent = i18n['index.loader.finrap'];
					loaderStepsState = [
						i18n['index.loader.step.gen_verify'],
						i18n['index.loader.step.gen_finance'],
						i18n['index.loader.step.gen_costs'],
						i18n['index.loader.step.gen_save'],
						i18n['index.loader.step.gen_open']
					];
				}

				loaderSubtitle.textContent = subtitle || i18n['index.loader.wait'];
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
					loaderLive.textContent = i18n['index.js.loader_ellipsis'].replace('%s', loaderStepsState[loaderCurrentStep]);
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
					loaderLive.textContent = message || i18n['index.loader.done'];
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

				return dt.toLocaleString(dateLocale, {
					year: 'numeric',
					month: '2-digit',
					day: '2-digit',
					hour: '2-digit',
					minute: '2-digit'
				});
			}

			function formatReportTimestamp (value)
			{
				const dt = new Date(String(value || ''));
				if (Number.isNaN(dt.getTime()))
				{
					return String(value || i18n['index.js.unknown_moment']);
				}

				return dt.toLocaleString(dateLocale, {
					year: 'numeric',
					month: '2-digit',
					day: '2-digit',
					hour: '2-digit',
					minute: '2-digit',
					second: '2-digit'
				});
			}

			function closeDeleteModals ()
			{
				pendingDeleteReportId = '';
				if (confirmDeleteStep1)
				{
					confirmDeleteStep1.classList.remove('is-visible');
				}
				if (confirmDeleteStep2)
				{
					confirmDeleteStep2.classList.remove('is-visible');
				}
			}

			function askDeleteReport (reportId)
			{
				pendingDeleteReportId = String(reportId || '').trim();
				if (pendingDeleteReportId === '' || !confirmDeleteStep1)
				{
					return;
				}

				confirmDeleteStep1.classList.add('is-visible');
			}

			function renderReportList (container, reports)
			{
				container.innerHTML = '';
				const list = Array.isArray(reports) ? reports : [];

				if (list.length === 0)
				{
					const empty = document.createElement('li');
					empty.className = 'muted';
					empty.textContent = i18n['index.js.reports_empty'];
					container.appendChild(empty);
					return;
				}

				list.forEach(function (entry)
				{
					const reportId = String((entry && entry.report_id) || '').trim();
					if (reportId === '')
					{
						return;
					}

					const item = document.createElement('li');
					item.className = 'report-item';

					const meta = document.createElement('div');
					meta.className = 'report-item-meta';
					meta.textContent = formatReportTimestamp(entry.fetched_at || '') + ' (' + reportId + ')';

					const actions = document.createElement('div');
					actions.className = 'report-item-actions';

					const openBtn = document.createElement('button');
					openBtn.className = 'btn btn-open';
					openBtn.type = 'button';
					openBtn.textContent = i18n['index.js.btn.open'];
					openBtn.addEventListener('click', function ()
					{
						const url = 'finrap.php?company=' + encodeURIComponent(companySelect.value)
							+ '&project_no=' + encodeURIComponent(activeProjectNo)
							+ '&report_id=' + encodeURIComponent(reportId)
							+ '&lang=' + encodeURIComponent(<?= json_encode(getCurrentLanguage(), JSON_UNESCAPED_UNICODE) ?>);
						openFinrapModal(url, i18n['index.js.report_modal_title'].replace('%s', activeProjectNo), false);
					});

					const deleteBtn = document.createElement('button');
					deleteBtn.className = 'btn btn-danger-icon';
					deleteBtn.type = 'button';
					deleteBtn.textContent = '🗑️';
					deleteBtn.addEventListener('click', function ()
					{
						askDeleteReport(reportId);
					});

					actions.appendChild(openBtn);
					actions.appendChild(deleteBtn);
					item.appendChild(meta);
					item.appendChild(actions);
					container.appendChild(item);
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
					empty.textContent = i18n['index.js.recent_empty'];
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
					meta.textContent = (company !== '' ? company : i18n['index.js.recent_unknown_company']) + (dtLabel !== '' ? ' | ' + dtLabel : '');

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
					finrapModalTitle.textContent = title || i18n['index.modal.title'];
				}

				const finalUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'embed=1&lang=' + encodeURIComponent(<?= json_encode(getCurrentLanguage(), JSON_UNESCAPED_UNICODE) ?>);
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

			function renderProject (project, reports)
			{
				const no = String((project && project.No) || activeProjectNo || '').trim();
				currentProjectData = project || {};
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
				meta.textContent = customerNo !== ''
					? i18n['index.js.customer'].replace('%s', customerNo).replace('%s', customerName ? ' - ' + customerName : '')
					: i18n['index.js.customer_unavailable'];
				card.appendChild(meta);

				const generateLabel = document.createElement('label');
				generateLabel.textContent = i18n['index.js.generate_label'];
				generateLabel.style.marginTop = '12px';
				card.appendChild(generateLabel);

				const generateBtn = document.createElement('button');
				generateBtn.className = 'btn btn-alt';
				generateBtn.type = 'button';
				generateBtn.style.marginTop = '10px';
				generateBtn.textContent = i18n['index.js.generate_btn'];
				card.appendChild(generateBtn);

				const reportsLabel = document.createElement('label');
				reportsLabel.textContent = i18n['index.js.reports_label'];
				reportsLabel.style.marginTop = '14px';
				card.appendChild(reportsLabel);

				const reportList = document.createElement('ul');
				reportList.className = 'report-list';
				card.appendChild(reportList);
				renderReportList(reportList, reports);

				generateBtn.addEventListener('click', function ()
				{
					setStatus(i18n['index.js.status.generating'], false);
					generateBtn.disabled = true;
					showLoader('generate', i18n['index.js.status.generate_subtitle'].replace('%s', no));
					postForm('index.php?action=generate_report', {
						company: companySelect.value,
						project_no: activeProjectNo
					}).then(function (json)
					{
						generateBtn.disabled = false;
						if (!json.ok)
						{
							hideLoader();
							setStatus(json.error || i18n['index.js.status.generate_failed'], true);
							return;
						}

						finalizeLoader(i18n['index.js.status.report_ready']);
						setStatus(i18n['index.js.status.generated'], false);
						renderProject(project, json.reports || []);
						if (json.report_url)
						{
							openFinrapModal(json.report_url, i18n['index.js.report_modal_title'].replace('%s', activeProjectNo), false);
						}
						window.setTimeout(hideLoader, 320);
					}).catch(function (error)
					{
						generateBtn.disabled = false;
						hideLoader();
						setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
					});
				});

				projectArea.appendChild(card);
			}

			function findProject (projectNoInput)
			{
				const projectNo = String(projectNoInput || '').trim();
				if (projectNo === '')
				{
					setStatus(i18n['index.js.status.enter_project'], true);
					return;
				}

				setStatus(i18n['index.js.status.searching'], false);
				findBtn.disabled = true;
				showLoader('search', i18n['index.js.status.search_subtitle'].replace('%s', projectNo));
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
						setStatus(json.error || i18n['index.js.status.not_found'], true);
						return;
					}

					if (Array.isArray(json.recent_projects))
					{
						recentProjects = normalizeRecentProjects(json.recent_projects);
						renderRecentProjects();
					}

					finalizeLoader(i18n['index.loader.done']);
					setStatus(i18n['index.js.status.found'].replace('%s', String(json.project_no || projectNo)), false);
					renderProject(json.project || {}, json.reports || []);
					window.setTimeout(hideLoader, 240);
				}).catch(function (error)
				{
					findBtn.disabled = false;
					hideLoader();
					setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
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

			if (confirmDeleteStep1No)
			{
				confirmDeleteStep1No.addEventListener('click', closeDeleteModals);
			}

			if (confirmDeleteStep2No)
			{
				confirmDeleteStep2No.addEventListener('click', closeDeleteModals);
			}

			if (confirmDeleteStep1Yes)
			{
				confirmDeleteStep1Yes.addEventListener('click', function ()
				{
					if (confirmDeleteStep1)
					{
						confirmDeleteStep1.classList.remove('is-visible');
					}
					if (confirmDeleteStep2)
					{
						confirmDeleteStep2.classList.add('is-visible');
					}
				});
			}

			if (confirmDeleteStep2Yes)
			{
				confirmDeleteStep2Yes.addEventListener('click', function ()
				{
					const reportId = String(pendingDeleteReportId || '').trim();
					if (reportId === '')
					{
						closeDeleteModals();
						return;
					}

					postForm('index.php?action=delete_report', {
						company: companySelect.value,
						project_no: activeProjectNo,
						report_id: reportId
					}).then(function (json)
					{
						closeDeleteModals();
						if (!json.ok)
						{
							setStatus(json.error || i18n['index.js.status.delete_failed'], true);
							return;
						}

						setStatus(i18n['index.js.status.deleted'], false);
						renderProject(currentProjectData || {}, json.reports || []);
					}).catch(function (error)
					{
						closeDeleteModals();
						setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
					});
				});
			}

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
				if (event.key === 'Escape' && ((confirmDeleteStep1 && confirmDeleteStep1.classList.contains('is-visible')) || (confirmDeleteStep2 && confirmDeleteStep2.classList.contains('is-visible'))))
				{
					closeDeleteModals();
					return;
				}

				if (event.key === 'Escape' && finrapModalOverlay && finrapModalOverlay.classList.contains('is-visible'))
				{
					closeFinrapModal();
				}
			});
		})();
	</script>
	<?php renderLanguageSwitcherScript(); ?>
</body>

</html>
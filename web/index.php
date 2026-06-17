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

function index_default_companies(): array
{
	return [
		'Koninklijke van Twist',
		'Hunter van Twist',
		'KVT Gas',
	];
}

function index_companies_cache_path(): string
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'index_companies.json';
}

function index_read_companies_cache(): ?array
{
	$path = index_companies_cache_path();
	if (!is_file($path)) {
		return null;
	}

	$raw = @file_get_contents($path);
	if (!is_string($raw) || trim($raw) === '') {
		return null;
	}

	$decoded = json_decode($raw, true);
	if (!is_array($decoded)) {
		return null;
	}

	$companies = is_array($decoded['companies'] ?? null) ? $decoded['companies'] : [];
	$normalized = [];
	foreach ($companies as $company) {
		$name = trim((string) $company);
		if ($name !== '') {
			$normalized[] = $name;
		}
	}

	if ($normalized === []) {
		return null;
	}

	return [
		'companies' => array_values(array_unique($normalized)),
		'cached_at' => (int) ($decoded['cached_at'] ?? 0),
	];
}

function index_write_companies_cache(array $companies): array
{
	$normalized = [];
	foreach ($companies as $company) {
		$name = trim((string) $company);
		if ($name !== '') {
			$normalized[] = $name;
		}
	}

	$normalized = array_values(array_unique($normalized));
	if ($normalized === []) {
		return index_default_companies();
	}

	$dir = dirname(index_companies_cache_path());
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}

	@file_put_contents(index_companies_cache_path(), json_encode([
		'companies' => $normalized,
		'cached_at' => time(),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);

	return $normalized;
}

function index_discover_companies_live(): array
{
	try {
		$result = auth_discover_companies_across_active_environments(300);
		$companies = is_array($result['companies'] ?? null) ? $result['companies'] : [];
	} catch (Throwable $ignoredDiscoveryError) {
		$companies = [];
	}

	if ($companies === []) {
		return index_companies_for_page();
	}

	return index_write_companies_cache($companies);
}

function index_companies_for_page(): array
{
	$cached = index_read_companies_cache();
	if (is_array($cached['companies'] ?? null) && $cached['companies'] !== []) {
		return $cached['companies'];
	}

	return index_default_companies();
}

/** @deprecated Use index_companies_for_page() or index_discover_companies_live(). */
function index_discover_companies(): array
{
	return index_companies_for_page();
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
	return LOC($monthKey = 'month.' . $month) . ' ' . $year;
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
$requestAction = trim((string) ($_GET['action'] ?? ''));

if ($requestAction === 'discover_companies') {
	$companies = index_discover_companies_live();
	index_json_response([
		'ok' => true,
		'companies' => $companies,
	]);
}

$companies = index_companies_for_page();
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
$showAutoReports = (bool) ($userSettings['finrap_show_auto_reports'] ?? false);

if (($_GET['action'] ?? '') === 'save_company_preference') {
	$company = trim((string) ($_POST['company'] ?? ''));
	if ($company === '' || !in_array($company, $companies, true)) {
		index_json_response(['ok' => false, 'error' => LOC('error.company_invalid')], 400);
	}

	$settings = index_load_user_settings($userEmail);
	$settings['finrap_selected_company'] = $company;
	$saveOk = index_save_user_settings($userEmail, $settings);
	if (!$saveOk) {
		index_json_response(['ok' => false, 'error' => LOC('error.save_preference_failed')], 500);
	}

	index_json_response([
		'ok' => true,
		'company' => $company,
	]);
}

if (($_GET['action'] ?? '') === 'save_auto_reports_preference') {
	$showAutoReportsInput = trim((string) ($_POST['show_auto_reports'] ?? ''));
	$showAutoReportsValue = $showAutoReportsInput === '1';

	$settings = index_load_user_settings($userEmail);
	$settings['finrap_show_auto_reports'] = $showAutoReportsValue;
	$saveOk = index_save_user_settings($userEmail, $settings);
	if (!$saveOk) {
		index_json_response(['ok' => false, 'error' => LOC('error.save_preference_failed')], 500);
	}

	index_json_response([
		'ok' => true,
		'show_auto_reports' => $showAutoReportsValue,
	]);
}

if (($_GET['action'] ?? '') === 'find_project') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true)) {
		index_json_response(['ok' => false, 'error' => LOC('error.company_invalid')], 400);
	}
	if ($projectNo === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.project_no_required')], 400);
	}

	try {
		$project = finrap_fetch_project($company, $projectNo, 300);
		if (!is_array($project)) {
			index_json_response(['ok' => false, 'error' => LOC('error.project_not_found')], 404);
		}

		$resolvedProjectNo = (string) ($project['No'] ?? $projectNo);
		$settings = index_load_user_settings($userEmail);
		$settings = index_add_recent_project($settings, $company, $resolvedProjectNo);
		index_save_user_settings($userEmail, $settings);
		$recentProjectsPayload = index_normalize_recent_projects(
			is_array($settings['finrap_recent_projects'] ?? null) ? $settings['finrap_recent_projects'] : []
		);
		$includeAutoReports = array_key_exists('include_auto_reports', $_POST)
			? trim((string) $_POST['include_auto_reports']) === '1'
			: $showAutoReports;
		$reportsPage = finrap_report_list_page(
			$company,
			$resolvedProjectNo,
			FINRAP_REPORT_LIST_PAGE_SIZE,
			0,
			$includeAutoReports,
			true
		);

		index_json_response([
			'ok' => true,
			'project' => $project,
			'project_no' => $resolvedProjectNo,
			'reports' => $reportsPage['reports'],
			'reports_total_count' => $reportsPage['total_count'],
			'reports_has_more' => $reportsPage['has_more'],
			'recent_projects' => $recentProjectsPayload,
		]);
	} catch (Throwable $error) {
		index_json_response(['ok' => false, 'error' => LOC('error.find_project_failed', $error->getMessage())], 500);
	}
}

if (($_GET['action'] ?? '') === 'list_reports') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.invalid_input')], 400);
	}

	$limit = max(1, min(100, (int) ($_POST['limit'] ?? FINRAP_REPORT_LIST_PAGE_SIZE)));
	$offset = max(0, (int) ($_POST['offset'] ?? 0));
	$includeAutoReports = trim((string) ($_POST['include_auto_reports'] ?? '1')) === '1';
	$reportsPage = finrap_report_list_page($company, $projectNo, $limit, $offset, $includeAutoReports, false);

	index_json_response([
		'ok' => true,
		'reports' => $reportsPage['reports'],
		'total_count' => $reportsPage['total_count'],
		'limit' => $reportsPage['limit'],
		'offset' => $reportsPage['offset'],
		'has_more' => $reportsPage['has_more'],
	]);
}

if (($_GET['action'] ?? '') === 'list_report_comments') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	$reportId = trim((string) ($_POST['report_id'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '' || $reportId === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.invalid_input')], 400);
	}

	$reportPath = finrap_report_cache_path($company, $projectNo, $reportId);
	if (!is_file($reportPath)) {
		index_json_response(['ok' => false, 'error' => LOC('error.report_not_found')], 404);
	}

	$messages = finrap_load_report_comments($company, $projectNo, $reportId);
	index_json_response([
		'ok' => true,
		'messages' => $messages,
		'comment_count' => count($messages),
	]);
}

if (($_GET['action'] ?? '') === 'add_report_comment') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	$reportId = trim((string) ($_POST['report_id'] ?? ''));
	$text = trim((string) ($_POST['text'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '' || $reportId === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.invalid_input')], 400);
	}

	$userEmail = index_get_user_email();
	if ($userEmail === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.comment_auth_required')], 403);
	}

	if ($text === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.comment_empty')], 400);
	}

	$message = finrap_add_report_comment($company, $projectNo, $reportId, $userEmail, $text);
	if ($message === null) {
		index_json_response(['ok' => false, 'error' => LOC('error.comment_save_failed')], 500);
	}

	index_json_response([
		'ok' => true,
		'message' => $message,
		'comment_count' => finrap_count_report_comments($company, $projectNo, $reportId),
	]);
}

if (($_GET['action'] ?? '') === 'update_report_comment') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	$reportId = trim((string) ($_POST['report_id'] ?? ''));
	$commentId = (int) ($_POST['comment_id'] ?? 0);
	$text = trim((string) ($_POST['text'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '' || $reportId === '' || $commentId <= 0) {
		index_json_response(['ok' => false, 'error' => LOC('error.invalid_input')], 400);
	}

	$userEmail = index_get_user_email();
	if ($userEmail === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.comment_auth_required')], 403);
	}

	if ($text === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.comment_empty')], 400);
	}

	$message = finrap_update_report_comment($company, $projectNo, $reportId, $commentId, $userEmail, $text);
	if ($message === null) {
		index_json_response(['ok' => false, 'error' => LOC('error.comment_update_failed')], 500);
	}

	index_json_response([
		'ok' => true,
		'message' => $message,
	]);
}

if (($_GET['action'] ?? '') === 'generate_report') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true)) {
		index_json_response(['ok' => false, 'error' => LOC('error.company_invalid')], 400);
	}
	if ($projectNo === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.project_no_missing')], 400);
	}

	try {
		$yearMonth = gmdate('Y-m');
		$report = finrap_generate_month_for_project($company, $projectNo, $yearMonth);
		$resolvedProjectNo = (string) ($report['project_no'] ?? $projectNo);
		$userEmail = index_get_user_email();
		if ($userEmail !== '') {
			$report['created_by_email'] = $userEmail;
		}
		$reportId = finrap_save_report_snapshot($company, $resolvedProjectNo, $report);
		if (!is_string($reportId) || $reportId === '') {
			index_json_response(['ok' => false, 'error' => LOC('error.save_report_failed')], 500);
		}

		finrap_inherit_overrides_from_previous_report($company, $resolvedProjectNo, $reportId);

		$includeAutoReports = array_key_exists('include_auto_reports', $_POST)
			? trim((string) $_POST['include_auto_reports']) === '1'
			: $showAutoReports;
		$reportsPage = finrap_report_list_page(
			$company,
			$resolvedProjectNo,
			FINRAP_REPORT_LIST_PAGE_SIZE,
			0,
			$includeAutoReports,
			false
		);
		$reportLang = rawurlencode(getCurrentLanguage());
		index_json_response([
			'ok' => true,
			'project_no' => $resolvedProjectNo,
			'report_id' => $reportId,
			'reports' => $reportsPage['reports'],
			'reports_total_count' => $reportsPage['total_count'],
			'reports_has_more' => $reportsPage['has_more'],
			'report_url' => 'finrap.php?company=' . rawurlencode($company) . '&project_no=' . rawurlencode($resolvedProjectNo) . '&report_id=' . rawurlencode($reportId) . '&lang=' . $reportLang,
		]);
	} catch (Throwable $error) {
		index_json_response(['ok' => false, 'error' => LOC('error.generate_failed', $error->getMessage())], 500);
	}
}

if (($_GET['action'] ?? '') === 'delete_report') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	$reportId = trim((string) ($_POST['report_id'] ?? ''));

	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '' || $reportId === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.invalid_input')], 400);
	}

	$deleted = finrap_delete_report_snapshot($company, $projectNo, $reportId);
	if (!$deleted) {
		index_json_response(['ok' => false, 'error' => LOC('error.delete_report_failed')], 404);
	}

	$includeAutoReports = array_key_exists('include_auto_reports', $_POST)
		? trim((string) $_POST['include_auto_reports']) === '1'
		: $showAutoReports;
	$reportsPage = finrap_report_list_page(
		$company,
		$projectNo,
		FINRAP_REPORT_LIST_PAGE_SIZE,
		0,
		$includeAutoReports,
		false
	);

	index_json_response([
		'ok' => true,
		'reports' => $reportsPage['reports'],
		'reports_total_count' => $reportsPage['total_count'],
		'reports_has_more' => $reportsPage['has_more'],
	]);
}

if (($_GET['action'] ?? '') === 'project_dashboard') {
	$company = trim((string) ($_POST['company'] ?? ''));
	$projectNo = trim((string) ($_POST['project_no'] ?? ''));
	$debugAllReports = (string) ($_GET['debug_allreports'] ?? '') === '1'
		|| trim((string) ($_POST['debug_all_reports'] ?? '')) === '1';

	if ($company === '' || !in_array($company, $companies, true) || $projectNo === '') {
		index_json_response(['ok' => false, 'error' => LOC('error.invalid_input')], 400);
	}

	try {
		$dashboard = finrap_build_project_dashboard($company, $projectNo, $debugAllReports);
		index_json_response([
			'ok' => true,
			'dashboard' => $dashboard,
		]);
	} catch (Throwable $error) {
		index_json_response(['ok' => false, 'error' => LOC('error.dashboard_failed', $error->getMessage())], 500);
	}
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
	'index.js.show_auto_reports',
	'index.js.reports_empty',
	'index.js.reports_empty_filtered',
	'index.js.reports_load_more',
	'index.js.reports_loading_more',
	'index.js.btn.open',
	'index.js.comments_btn',
	'index.js.comments_modal_title',
	'index.js.comments_loading',
	'index.js.comments_empty',
	'index.js.comments_send',
	'index.js.comments_save',
	'index.js.comments_cancel',
	'index.js.comments_edit',
	'index.js.comments_edited',
	'index.js.comments_placeholder',
	'index.js.comments_load_failed',
	'index.js.comments_send_failed',
	'index.js.comments_update_failed',
	'index.js.dashboard_btn',
	'index.js.dashboard_modal_title',
	'index.js.dashboard_loading',
	'index.js.dashboard_empty',
	'index.js.dashboard_load_failed',
	'index.js.dashboard_chart_poc_title',
	'index.js.dashboard_chart_poc_baseline',
	'index.js.dashboard_chart_poc_eac',
	'index.js.dashboard_chart_y_axis',
	'index.js.dashboard_chart_cost_title',
	'index.js.dashboard_chart_cost_subtitle',
	'index.js.dashboard_chart_eac_title',
	'index.js.dashboard_chart_invoiced_title',
	'index.js.dashboard_chart_installments_title',
	'index.js.dashboard_chart_cost_major',
	'index.js.dashboard_chart_cost_subtotal',
	'index.js.dashboard_latest_report_note',
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
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
	<title><?= htmlspecialchars(LOC('app.title'), ENT_QUOTES) ?></title>
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
		}

		.report-item.is-manual-report {
			background: #f8fbff;
			border-color: #d7e4f2;
		}

		.report-item.is-auto-report {
			background: #e8e8e8;
			border-color: #cccccc;
		}

		.reports-toolbar {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			justify-content: space-between;
			gap: 10px 16px;
			margin-top: 14px;
		}

		.report-list-load-more {
			margin-top: 8px;
			width: 100%;
		}

		.auto-reports-toggle {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-size: 13px;
			color: var(--muted);
			cursor: pointer;
			user-select: none;
		}

		.auto-reports-toggle input[type="checkbox"] {
			width: 16px;
			height: 16px;
			min-height: 0;
			margin: 0;
			padding: 0;
			flex: 0 0 auto;
			accent-color: var(--brand);
		}

		.report-item-meta {
			flex: 1;
			min-width: 0;
			font-size: 13px;
			color: var(--muted);
		}

		.report-comment-btn {
			flex: 0 0 auto;
			width: fit-content;
			min-width: 0;
			max-width: none;
			min-height: 0;
			height: auto;
			border: 1px solid #d7e4f2;
			background: #fff;
			color: #334155;
			border-radius: 5px;
			padding: 1px 5px;
			font-size: 11px;
			font-weight: 600;
			line-height: 1.15;
			cursor: pointer;
			white-space: nowrap;
			font-variant-numeric: tabular-nums;
		}

		.report-comment-btn:hover {
			background: #f8fbff;
			border-color: #94a3b8;
		}

		.report-comments-overlay {
			position: fixed;
			inset: 0;
			z-index: 1200;
			display: none;
			align-items: center;
			justify-content: center;
			padding: 16px;
			background: rgba(15, 23, 42, 0.45);
		}

		.report-comments-overlay.is-visible {
			display: flex;
		}

		.report-comments-dialog {
			width: min(560px, 100%);
			max-height: min(80vh, 720px);
			display: flex;
			flex-direction: column;
			background: #fff;
			border-radius: 14px;
			box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
			overflow: hidden;
		}

		.report-comments-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 14px 16px;
			border-bottom: 1px solid #e2e8f0;
		}

		.report-comments-title {
			margin: 0;
			font-size: 16px;
			font-weight: 600;
		}

		.report-comments-log {
			flex: 1;
			min-height: 220px;
			max-height: 52vh;
			overflow-y: auto;
			padding: 14px 16px;
			display: flex;
			flex-direction: column;
			gap: 6px;
			background: #f8fafc;
		}

		.report-comments-empty {
			margin: auto 0;
			text-align: center;
			color: var(--muted);
			font-size: 14px;
		}

		.report-comment-message {
			position: relative;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 6px 8px;
			background: #fff;
		}

		.report-comment-message.is-own {
			padding-right: 28px;
		}

		.report-comment-message-meta {
			display: flex;
			flex-wrap: wrap;
			align-items: baseline;
			gap: 4px 8px;
			margin-bottom: 3px;
			font-size: 11px;
			line-height: 1.2;
			color: var(--muted);
		}

		.report-comment-message-edited {
			font-style: italic;
		}

		.report-comment-message-edit-btn {
			position: absolute;
			top: 4px;
			right: 4px;
			width: 20px;
			height: 20px;
			min-height: 0;
			padding: 0;
			border: none;
			border-radius: 4px;
			background: transparent;
			color: #64748b;
			font-size: 12px;
			line-height: 1;
			cursor: pointer;
			opacity: 0;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			transition: opacity .12s ease, background .12s ease;
		}

		.report-comment-message:hover .report-comment-message-edit-btn,
		.report-comment-message:focus-within .report-comment-message-edit-btn {
			opacity: 1;
		}

		.report-comment-message-edit-btn:hover {
			background: rgba(148, 163, 184, 0.18);
		}

		.report-comment-message-edit textarea {
			width: 100%;
			min-height: 56px;
			resize: vertical;
			margin-bottom: 6px;
			padding: 6px 8px;
			font-size: 13px;
			line-height: 1.35;
		}

		.report-comment-message-edit-actions {
			display: flex;
			gap: 6px;
			justify-content: flex-end;
		}

		.report-comment-message-edit-actions .btn {
			min-height: 0;
			width: auto;
			padding: 4px 10px;
			font-size: 12px;
		}

		.report-comment-message-email {
			display: inline-block;
			padding: 1px 7px;
			border-radius: 999px;
			font-weight: 600;
			font-size: 11px;
			line-height: 1.35;
		}

		.report-comment-message-text {
			font-size: 13px;
			line-height: 1.35;
			white-space: pre-wrap;
			word-break: break-word;
		}

		.report-comments-compose {
			display: flex;
			gap: 8px;
			align-items: flex-end;
			padding: 12px 16px 16px;
			border-top: 1px solid #e2e8f0;
			background: #fff;
		}

		.report-comments-input {
			flex: 1 1 auto;
			min-width: 0;
			width: auto;
			min-height: 42px;
			max-height: 120px;
			resize: vertical;
			margin: 0;
		}

		.report-comments-send {
			flex: 0 0 auto;
			min-width: 0;
			min-height: 0;
			height: 34px;
			width: 34px;
			padding: 0;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 16px;
			line-height: 1;
			border-radius: 8px;
		}

		.report-item-actions {
			display: flex;
			align-items: center;
			gap: 6px;
			flex-shrink: 0;
		}

		.report-auto-badge {
			font-size: 16px;
			line-height: 1;
		}

		.report-manual-badge {
			font-size: 12px;
			font-weight: 600;
			line-height: 1;
			color: var(--muted);
			white-space: nowrap;
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

		.confirm-topbar.is-hazard-scroll {
			background: repeating-linear-gradient(45deg,
					#111111 0,
					#111111 14px,
					#facc15 14px,
					#facc15 28px);
			background-size: 40px 40px;
			color: #111111;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.3);
			animation: confirmHazardScroll 1s linear infinite;
		}

		@keyframes confirmHazardScroll {
			from {
				background-position: 0 0;
			}

			to {
				background-position: 40px 0;
			}
		}

		.confirm-dialog.is-auto-delete-pulse {
			animation: confirmAutoDeletePulse 1s ease-in-out infinite;
		}

		@keyframes confirmAutoDeletePulse {
			0%,
			100% {
				background: #ffffff;
				border-color: #cbd5e1;
				box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
			}

			50% {
				background: #fee2e2;
				border-color: #ef4444;
				box-shadow: 0 24px 60px rgba(239, 68, 68, 0.45);
			}
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

		.project-title-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 4px;
		}

		.project-title {
			font-size: 18px;
			font-weight: 700;
			margin: 0;
			min-width: 0;
		}

		.btn-dashboard {
			background: #ffffff;
			color: var(--brand-dark);
			border: 1px solid #b7cbe4;
			min-height: 34px;
			padding: 6px 12px;
			font-size: 13px;
			width: auto;
			flex-shrink: 0;
			white-space: nowrap;
		}

		.btn-dashboard:hover {
			background: #edf7ff;
		}

		.dashboard-modal-overlay {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.55);
			display: none;
			align-items: stretch;
			justify-content: center;
			z-index: 11500;
			padding: 10px;
		}

		.dashboard-modal-overlay.is-visible {
			display: flex;
		}

		.dashboard-modal-dialog {
			width: min(1200px, 100%);
			height: calc(100vh - 20px);
			max-height: none;
			background: #ffffff;
			border: 1px solid #c9d7eb;
			border-radius: 14px;
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}

		.dashboard-modal-body {
			flex: 1;
			overflow: auto;
			padding: 18px;
			background: #f8fbff;
		}

		.dashboard-chart-card {
			background: #ffffff;
			border: 1px solid #dbe3ee;
			border-radius: 12px;
			padding: 16px;
		}

		.dashboard-chart-title {
			margin: 0 0 14px;
			font-size: 16px;
			font-weight: 700;
			color: var(--brand-dark);
		}

		.dashboard-chart-wrap {
			position: relative;
			height: min(520px, 58vh);
			overflow: visible;
		}

		.dashboard-charts-grid {
			display: grid;
			gap: 18px;
		}

		.dashboard-charts-row-cols {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 18px;
		}

		.dashboard-breakdown-block {
			display: grid;
			gap: 8px;
		}

		.dashboard-chart-wrap--compact {
			height: min(400px, 46vh);
		}

		.dashboard-chart-subtitle--row {
			margin: 0 0 4px;
		}

		.dashboard-chart-subtitle {
			margin: -8px 0 14px;
			font-size: 12px;
			color: var(--muted);
		}

		.dashboard-cost-chart-stack {
			position: relative;
			width: 100%;
			height: 100%;
			overflow: visible;
		}

		.dashboard-cost-chart-stack canvas {
			width: 100% !important;
			height: 100% !important;
		}

		.dashboard-modal-body .chartjs-tooltip {
			z-index: 30;
		}

		.dashboard-status {
			font-size: 14px;
			color: var(--muted);
			margin: 0;
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
			<h1><?= htmlspecialchars(LOC('index.hero.title'), ENT_QUOTES) ?></h1>
			<p><?= htmlspecialchars(LOC('index.hero.subtitle'), ENT_QUOTES) ?></p>
		</section>

		<div class="workspace-grid">
			<section class="panel">
				<div class="grid">
					<div>
						<label for="companySelect"><?= htmlspecialchars(LOC('index.label.company'), ENT_QUOTES) ?></label>
						<select id="companySelect">
							<?php foreach ($companies as $company): ?>
								<option value="<?= htmlspecialchars($company, ENT_QUOTES) ?>" <?= $company === $selectedCompany ? 'selected' : '' ?>>
									<?= htmlspecialchars($company) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="projectInput"><?= htmlspecialchars(LOC('index.label.project_no'), ENT_QUOTES) ?></label>
						<input id="projectInput" type="text" autocomplete="off" placeholder="<?= htmlspecialchars(LOC('index.placeholder.project_no'), ENT_QUOTES) ?>">
					</div>
				</div>
				<div class="row-actions">
					<button id="findBtn" class="btn btn-main" type="button"><?= htmlspecialchars(LOC('index.btn.find'), ENT_QUOTES) ?></button>
				</div>
				<p id="statusLine" class="status"><?= htmlspecialchars(LOC('index.status.none'), ENT_QUOTES) ?></p>
				<div id="projectArea"></div>
			</section>

			<aside class="panel sidebar-panel">
				<h2 class="panel-title"><?= htmlspecialchars(LOC('index.recent.title'), ENT_QUOTES) ?></h2>
				<ul id="recentProjectsList" class="recent-project-list"></ul>
			</aside>
		</div>
	</div>

	<div id="finrapLoader" class="loader-overlay" aria-live="polite" aria-busy="true">
		<div class="loader-card">
			<h2 id="loaderTitle" class="loader-title"><?= htmlspecialchars(LOC('index.loader.finrap'), ENT_QUOTES) ?></h2>
			<p id="loaderSubtitle" class="loader-subtitle"><?= htmlspecialchars(LOC('index.loader.wait'), ENT_QUOTES) ?></p>
			<ul id="loaderSteps" class="loader-steps"></ul>
			<p id="loaderLive" class="loader-live"><?= htmlspecialchars(LOC('index.loader.prepare'), ENT_QUOTES) ?></p>
		</div>
	</div>

	<div id="finrapModalOverlay" class="finrap-modal-overlay" role="dialog" aria-modal="true"
		aria-labelledby="finrapModalTitle">
		<div class="finrap-modal-dialog">
			<div class="finrap-modal-head">
				<h2 id="finrapModalTitle" class="finrap-modal-title"><?= htmlspecialchars(LOC('index.modal.title'), ENT_QUOTES) ?></h2>
				<div class="finrap-modal-actions">
					<button id="finrapModalPrint" class="btn btn-print" type="button"><?= htmlspecialchars(LOC('index.modal.print'), ENT_QUOTES) ?></button>
					<button id="finrapModalClose" class="btn btn-main" type="button"><?= htmlspecialchars(LOC('index.modal.close'), ENT_QUOTES) ?></button>
				</div>
			</div>
			<iframe id="finrapModalFrame" class="finrap-modal-frame" title="<?= htmlspecialchars(LOC('index.modal.report_iframe'), ENT_QUOTES) ?>"></iframe>
		</div>
	</div>

	<div id="dashboardModalOverlay" class="dashboard-modal-overlay" role="dialog" aria-modal="true"
		aria-labelledby="dashboardModalTitle">
		<div class="dashboard-modal-dialog">
			<div class="finrap-modal-head">
				<h2 id="dashboardModalTitle" class="finrap-modal-title"><?= htmlspecialchars(LOC('index.js.dashboard_modal_title'), ENT_QUOTES) ?></h2>
				<div class="finrap-modal-actions">
					<button id="dashboardModalClose" class="btn btn-main" type="button"><?= htmlspecialchars(LOC('index.modal.close'), ENT_QUOTES) ?></button>
				</div>
			</div>
			<div class="dashboard-modal-body">
				<p id="dashboardStatus" class="dashboard-status"><?= htmlspecialchars(LOC('index.js.dashboard_loading'), ENT_QUOTES) ?></p>
				<div id="dashboardContent" hidden>
					<div class="dashboard-charts-grid">
						<div id="dashboardPocSection" class="dashboard-chart-card" hidden>
							<h3 class="dashboard-chart-title"><?= htmlspecialchars(LOC('index.js.dashboard_chart_poc_title'), ENT_QUOTES) ?></h3>
							<div class="dashboard-chart-wrap">
								<canvas id="dashboardPocChart" aria-label="<?= htmlspecialchars(LOC('index.js.dashboard_chart_poc_title'), ENT_QUOTES) ?>"></canvas>
							</div>
						</div>
						<div id="dashboardBreakdownBlock" class="dashboard-breakdown-block" hidden>
							<p id="dashboardLatestReportSubtitle" class="dashboard-chart-subtitle dashboard-chart-subtitle--row"></p>
							<div class="dashboard-charts-row-cols">
								<div id="dashboardBookedSection" class="dashboard-chart-card" hidden>
									<h3 class="dashboard-chart-title"><?= htmlspecialchars(LOC('index.js.dashboard_chart_cost_title'), ENT_QUOTES) ?></h3>
									<div class="dashboard-chart-wrap dashboard-chart-wrap--compact">
										<div class="dashboard-cost-chart-stack">
											<canvas id="dashboardBookedChart" aria-label="<?= htmlspecialchars(LOC('index.js.dashboard_chart_cost_title'), ENT_QUOTES) ?>"></canvas>
										</div>
									</div>
								</div>
								<div id="dashboardEacSection" class="dashboard-chart-card" hidden>
									<h3 class="dashboard-chart-title"><?= htmlspecialchars(LOC('index.js.dashboard_chart_eac_title'), ENT_QUOTES) ?></h3>
									<div class="dashboard-chart-wrap dashboard-chart-wrap--compact">
										<div class="dashboard-cost-chart-stack">
											<canvas id="dashboardEacChart" aria-label="<?= htmlspecialchars(LOC('index.js.dashboard_chart_eac_title'), ENT_QUOTES) ?>"></canvas>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div id="dashboardFinanceBlock" class="dashboard-charts-row-cols" hidden>
							<div id="dashboardInvoicedSection" class="dashboard-chart-card" hidden>
								<h3 class="dashboard-chart-title"><?= htmlspecialchars(LOC('index.js.dashboard_chart_invoiced_title'), ENT_QUOTES) ?></h3>
								<div class="dashboard-chart-wrap dashboard-chart-wrap--compact">
									<div class="dashboard-cost-chart-stack">
										<canvas id="dashboardInvoicedChart" aria-label="<?= htmlspecialchars(LOC('index.js.dashboard_chart_invoiced_title'), ENT_QUOTES) ?>"></canvas>
									</div>
								</div>
							</div>
							<div id="dashboardInstallmentsSection" class="dashboard-chart-card" hidden>
								<h3 class="dashboard-chart-title"><?= htmlspecialchars(LOC('index.js.dashboard_chart_installments_title'), ENT_QUOTES) ?></h3>
								<div id="dashboardInstallmentsWrap" class="dashboard-chart-wrap dashboard-chart-wrap--compact">
									<canvas id="dashboardInstallmentsChart" aria-label="<?= htmlspecialchars(LOC('index.js.dashboard_chart_installments_title'), ENT_QUOTES) ?>"></canvas>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="confirmDeleteStep1" class="confirm-overlay" role="dialog" aria-modal="true"
		aria-labelledby="confirmDeleteStep1Title">
		<div class="confirm-dialog">
			<div id="confirmDeleteStep1Title" class="confirm-topbar is-red"></div>
			<div class="confirm-body"><?= htmlspecialchars(LOC('index.delete.step1.body'), ENT_QUOTES) ?></div>
			<div class="confirm-actions">
				<button id="confirmDeleteStep1No" class="btn btn-print" type="button"><?= htmlspecialchars(LOC('index.btn.no'), ENT_QUOTES) ?></button>
				<button id="confirmDeleteStep1Yes" class="btn btn-main" type="button"><?= htmlspecialchars(LOC('index.btn.yes'), ENT_QUOTES) ?></button>
			</div>
		</div>
	</div>

	<div id="confirmDeleteStep2" class="confirm-overlay" role="dialog" aria-modal="true"
		aria-labelledby="confirmDeleteStep2Title">
		<div class="confirm-dialog">
			<div id="confirmDeleteStep2Title" class="confirm-topbar is-hazard"></div>
			<div class="confirm-body"><?= htmlspecialchars(LOC('index.delete.step2.body'), ENT_QUOTES) ?></div>
			<div class="confirm-actions">
				<button id="confirmDeleteStep2No" class="btn btn-print" type="button"><?= htmlspecialchars(LOC('index.btn.no'), ENT_QUOTES) ?></button>
				<button id="confirmDeleteStep2Yes" class="btn btn-main" type="button"><?= htmlspecialchars(LOC('index.btn.yes'), ENT_QUOTES) ?></button>
			</div>
		</div>
	</div>

	<div id="confirmDeleteStep3" class="confirm-overlay" role="dialog" aria-modal="true"
		aria-labelledby="confirmDeleteStep3Title">
		<div id="confirmDeleteStep3Dialog" class="confirm-dialog is-auto-delete-pulse">
			<div id="confirmDeleteStep3Title" class="confirm-topbar is-hazard-scroll"></div>
			<div class="confirm-body"><?= htmlspecialchars(LOC('index.delete.step3.body'), ENT_QUOTES) ?></div>
			<div class="confirm-actions">
				<button id="confirmDeleteStep3No" class="btn btn-print" type="button"><?= htmlspecialchars(LOC('index.btn.no'), ENT_QUOTES) ?></button>
				<button id="confirmDeleteStep3Yes" class="btn btn-main" type="button"><?= htmlspecialchars(LOC('index.btn.yes'), ENT_QUOTES) ?></button>
			</div>
		</div>
	</div>

	<div id="reportCommentsOverlay" class="report-comments-overlay" role="dialog" aria-modal="true"
		aria-labelledby="reportCommentsTitle">
		<div class="report-comments-dialog">
			<div class="report-comments-head">
				<h2 id="reportCommentsTitle" class="report-comments-title"><?= htmlspecialchars(LOC('index.js.comments_modal_title'), ENT_QUOTES) ?></h2>
				<button id="reportCommentsClose" class="btn btn-print" type="button"><?= htmlspecialchars(LOC('index.modal.close'), ENT_QUOTES) ?></button>
			</div>
			<div id="reportCommentsLog" class="report-comments-log">
				<div id="reportCommentsEmpty" class="report-comments-empty"><?= htmlspecialchars(LOC('index.js.comments_empty'), ENT_QUOTES) ?></div>
			</div>
			<div class="report-comments-compose">
				<textarea id="reportCommentsInput" class="report-comments-input" rows="2"
					placeholder="<?= htmlspecialchars(LOC('index.js.comments_placeholder'), ENT_QUOTES) ?>"></textarea>
				<button id="reportCommentsSend" class="btn btn-main report-comments-send" type="button"
					aria-label="<?= htmlspecialchars(LOC('index.js.comments_send'), ENT_QUOTES) ?>"
					title="<?= htmlspecialchars(LOC('index.js.comments_send'), ENT_QUOTES) ?>">⤴️</button>
			</div>
		</div>
	</div>

	<script>
		(function ()
		{
			const i18n = <?= localizationJsTranslations($indexI18nKeys) ?>;
			const dateLocale = <?= json_encode(getDateLocale(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
			const initialRecentProjects = <?= json_encode($recentProjects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
			const initialShowAutoReports = <?= $showAutoReports ? 'true' : 'false' ?>;
			const REPORT_LIST_PAGE_SIZE = <?= (int) FINRAP_REPORT_LIST_PAGE_SIZE ?>;
			const currentUserHandle = <?= json_encode(finrap_email_local_part(index_get_user_email()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
			const currentUserEmail = <?= json_encode(strtolower(trim(index_get_user_email())), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
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
			const confirmDeleteStep3 = document.getElementById('confirmDeleteStep3');
			const confirmDeleteStep3No = document.getElementById('confirmDeleteStep3No');
			const confirmDeleteStep3Yes = document.getElementById('confirmDeleteStep3Yes');
			const reportCommentsOverlay = document.getElementById('reportCommentsOverlay');
			const reportCommentsTitle = document.getElementById('reportCommentsTitle');
			const reportCommentsLog = document.getElementById('reportCommentsLog');
			const reportCommentsEmpty = document.getElementById('reportCommentsEmpty');
			const reportCommentsInput = document.getElementById('reportCommentsInput');
			const reportCommentsSend = document.getElementById('reportCommentsSend');
			const reportCommentsClose = document.getElementById('reportCommentsClose');
			const dashboardModalOverlay = document.getElementById('dashboardModalOverlay');
			const dashboardModalClose = document.getElementById('dashboardModalClose');
			const dashboardModalTitle = document.getElementById('dashboardModalTitle');
			const dashboardStatus = document.getElementById('dashboardStatus');
			const dashboardContent = document.getElementById('dashboardContent');
			const dashboardPocChartCanvas = document.getElementById('dashboardPocChart');
			const dashboardPocSection = document.getElementById('dashboardPocSection');
			const dashboardBreakdownBlock = document.getElementById('dashboardBreakdownBlock');
			const dashboardBookedSection = document.getElementById('dashboardBookedSection');
			const dashboardEacSection = document.getElementById('dashboardEacSection');
			const dashboardFinanceBlock = document.getElementById('dashboardFinanceBlock');
			const dashboardInvoicedSection = document.getElementById('dashboardInvoicedSection');
			const dashboardInstallmentsSection = document.getElementById('dashboardInstallmentsSection');
			const dashboardLatestReportSubtitle = document.getElementById('dashboardLatestReportSubtitle');
			const dashboardBookedChartCanvas = document.getElementById('dashboardBookedChart');
			const dashboardEacChartCanvas = document.getElementById('dashboardEacChart');
			const dashboardInvoicedChartCanvas = document.getElementById('dashboardInvoicedChart');
			const dashboardInstallmentsChartCanvas = document.getElementById('dashboardInstallmentsChart');
			const dashboardInstallmentsWrap = document.getElementById('dashboardInstallmentsWrap');
			const debugAllReports = new URLSearchParams(window.location.search).has('debug_allreports');

			let activeProjectNo = '';
			let loaderTick = null;
			let loaderStepsState = [];
			let loaderCurrentStep = -1;
			let recentProjects = Array.isArray(initialRecentProjects) ? initialRecentProjects : [];
			let pendingDeleteReportId = '';
			let pendingDeleteIsAutoReport = false;
			let currentProjectData = null;
			let currentReports = [];
			let currentReportsTotal = 0;
			let currentReportsHasMore = false;
			let activeReportListEl = null;
			let reportListLoadMoreBtn = null;
			let reportListLoadingMore = false;
			let showAutoReports = initialShowAutoReports === true;
			let activeCommentsReportId = '';
			let activeCommentsMessages = [];
			let commentsLoading = false;
			let commentsSending = false;
			let editingCommentId = '';
			let dashboardPocChartInstance = null;
			let dashboardBookedChartInstance = null;
			let dashboardEacChartInstance = null;
			let dashboardInvoicedChartInstance = null;
			let dashboardInstallmentsChartInstance = null;
			let dashboardInstallmentsSourceHistory = [];
			let dashboardInstallmentsResizeObserver = null;
			let dashboardInstallmentsLastBarCount = 0;
			const DASHBOARD_INSTALLMENTS_MIN_BAR_WIDTH = 22;

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

			function renderCompanyOptions (companies, preferredCompany)
			{
				if (!companySelect || !Array.isArray(companies) || companies.length === 0)
				{
					return;
				}

				const currentValue = preferredCompany || companySelect.value;
				companySelect.innerHTML = '';
				companies.forEach(function (company)
				{
					const option = document.createElement('option');
					option.value = company;
					option.textContent = company;
					if (company === currentValue)
					{
						option.selected = true;
					}
					companySelect.appendChild(option);
				});

				if (!Array.prototype.some.call(companySelect.options, function (opt) { return opt.selected; }))
				{
					companySelect.selectedIndex = 0;
				}
			}

			function refreshCompaniesInBackground ()
			{
				postForm('index.php?action=discover_companies', {})
					.then(function (json)
					{
						if (!json || !json.ok || !Array.isArray(json.companies) || json.companies.length === 0)
						{
							return;
						}

						renderCompanyOptions(json.companies, companySelect ? companySelect.value : '');
					})
					.catch(function ()
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
				pendingDeleteIsAutoReport = false;
				if (confirmDeleteStep1)
				{
					confirmDeleteStep1.classList.remove('is-visible');
				}
				if (confirmDeleteStep2)
				{
					confirmDeleteStep2.classList.remove('is-visible');
				}
				if (confirmDeleteStep3)
				{
					confirmDeleteStep3.classList.remove('is-visible');
				}
			}

			function executeDeleteReport ()
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
					report_id: reportId,
					include_auto_reports: showAutoReports ? '1' : '0'
				}).then(function (json)
				{
					closeDeleteModals();
					if (!json.ok)
					{
						setStatus(json.error || i18n['index.js.status.delete_failed'], true);
						return;
					}

					setStatus(i18n['index.js.status.deleted'], false);
					applyReportsPayload(json);
					if (activeReportListEl)
					{
						renderReportList(activeReportListEl, currentReports);
					}
				}).catch(function (error)
				{
					closeDeleteModals();
					setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
				});
			}

			function askDeleteReport (reportId, isAutoReport)
			{
				pendingDeleteReportId = String(reportId || '').trim();
				pendingDeleteIsAutoReport = isAutoReport === true;
				if (pendingDeleteReportId === '' || !confirmDeleteStep1)
				{
					return;
				}

				confirmDeleteStep1.classList.add('is-visible');
			}

			function saveAutoReportsPreference (enabled)
			{
				postForm('index.php?action=save_auto_reports_preference', {
					show_auto_reports: enabled ? '1' : '0'
				}).catch(function ()
				{
					// Preference save failure should not block UI filtering.
				});
			}

			function applyReportsPayload (payload, append)
			{
				const nextReports = Array.isArray(payload && payload.reports) ? payload.reports : [];
				if (append === true)
				{
					currentReports = currentReports.concat(nextReports);
				}
				else
				{
					currentReports = nextReports;
				}

				if (payload && typeof payload.reports_total_count === 'number')
				{
					currentReportsTotal = payload.reports_total_count;
				}
				else if (payload && typeof payload.total_count === 'number')
				{
					currentReportsTotal = payload.total_count;
				}
				else if (append !== true)
				{
					currentReportsTotal = currentReports.length;
				}

				if (payload && typeof payload.reports_has_more === 'boolean')
				{
					currentReportsHasMore = payload.reports_has_more;
				}
				else if (payload && typeof payload.has_more === 'boolean')
				{
					currentReportsHasMore = payload.has_more;
				}
				else
				{
					currentReportsHasMore = currentReports.length < currentReportsTotal;
				}
			}

			function getVisibleReports (reports)
			{
				const list = Array.isArray(reports) ? reports : [];
				if (showAutoReports)
				{
					return list;
				}

				return list.filter(function (entry)
				{
					return !(entry && entry.auto_report === true);
				});
			}

			function renderReportListLoadMore (container)
			{
				if (reportListLoadMoreBtn && reportListLoadMoreBtn.parentElement)
				{
					reportListLoadMoreBtn.parentElement.removeChild(reportListLoadMoreBtn);
				}
				reportListLoadMoreBtn = null;

				if (!currentReportsHasMore || !container)
				{
					return;
				}

				const shownCount = currentReports.length;
				const totalCount = Math.max(currentReportsTotal, shownCount);
				const loadMoreBtn = document.createElement('button');
				loadMoreBtn.className = 'btn btn-alt report-list-load-more';
				loadMoreBtn.type = 'button';
				loadMoreBtn.textContent = i18n['index.js.reports_load_more']
					.replace('%s', String(shownCount))
					.replace('%s', String(totalCount));
				loadMoreBtn.disabled = reportListLoadingMore;
				loadMoreBtn.addEventListener('click', loadMoreReports);
				container.insertAdjacentElement('afterend', loadMoreBtn);
				reportListLoadMoreBtn = loadMoreBtn;
			}

			function loadMoreReports ()
			{
				if (reportListLoadingMore || !currentReportsHasMore || activeProjectNo === '')
				{
					return;
				}

				reportListLoadingMore = true;
				if (reportListLoadMoreBtn)
				{
					reportListLoadMoreBtn.disabled = true;
					reportListLoadMoreBtn.textContent = i18n['index.js.reports_loading_more'];
				}

				postForm('index.php?action=list_reports', {
					company: companySelect.value,
					project_no: activeProjectNo,
					limit: String(REPORT_LIST_PAGE_SIZE),
					offset: String(currentReports.length),
					include_auto_reports: showAutoReports ? '1' : '0'
				}).then(function (json)
				{
					reportListLoadingMore = false;
					if (!json.ok)
					{
						setStatus(json.error || i18n['index.js.network_error'].replace('%s', ''), true);
						if (reportListLoadMoreBtn)
						{
							reportListLoadMoreBtn.disabled = false;
							renderReportListLoadMore(activeReportListEl);
						}
						return;
					}

					applyReportsPayload(json, true);
					if (activeReportListEl)
					{
						renderReportList(activeReportListEl, currentReports);
					}
				}).catch(function (error)
				{
					reportListLoadingMore = false;
					setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
					if (reportListLoadMoreBtn)
					{
						reportListLoadMoreBtn.disabled = false;
						renderReportListLoadMore(activeReportListEl);
					}
				});
			}

			function reloadReportListPage ()
			{
				if (activeProjectNo === '')
				{
					return;
				}

				postForm('index.php?action=list_reports', {
					company: companySelect.value,
					project_no: activeProjectNo,
					limit: String(REPORT_LIST_PAGE_SIZE),
					offset: '0',
					include_auto_reports: showAutoReports ? '1' : '0'
				}).then(function (json)
				{
					if (!json.ok)
					{
						return;
					}

					applyReportsPayload(json, false);
					if (activeReportListEl)
					{
						renderReportList(activeReportListEl, currentReports);
					}
				}).catch(function ()
				{
					// List refresh failure should not block UI filtering.
				});
			}

			function formatCommentTimestamp (value)
			{
				return formatReportTimestamp(value);
			}

			function reportCommentButtonLabel (count)
			{
				return i18n['index.js.comments_btn'].replace('%s', String(Number(count || 0)));
			}

			function updateReportCommentBadge (reportId, count)
			{
				if (!activeReportListEl || reportId === '')
				{
					return;
				}

				const button = activeReportListEl.querySelector('.report-comment-btn[data-report-id="' + reportId + '"]');
				if (button)
				{
					button.textContent = reportCommentButtonLabel(count);
				}

				currentReports = currentReports.map(function (entry)
				{
					if (String(entry.report_id || '') === reportId)
					{
						return Object.assign({}, entry, { comment_count: Number(count || 0) });
					}

					return entry;
				});
			}

			function scrollCommentsLogToBottom ()
			{
				if (!reportCommentsLog)
				{
					return;
				}

				reportCommentsLog.scrollTop = reportCommentsLog.scrollHeight;
			}

			function isOwnComment (message)
			{
				return currentUserEmail !== ''
					&& String((message && message.email) || '').toLowerCase() === currentUserEmail;
			}

			function hashEmailForColor (value)
			{
				let hash = 0;
				const text = String(value || '');
				for (let i = 0; i < text.length; i++)
				{
					hash = text.charCodeAt(i) + ((hash << 5) - hash);
				}

				return hash;
			}

			function commentColorFromEmail (email)
			{
				const normalized = String(email || '').toLowerCase().trim();
				if (normalized === '')
				{
					return {
						border: '#cbd5e1',
						chipBackground: '#e2e8f0',
						cardBackground: '#ffffff',
						chipTextColor: '#334155'
					};
				}

				const hash = hashEmailForColor(normalized);
				const hue = Math.abs(hash) % 360;
				const saturation = 72 + (Math.abs(hash >> 8) % 14);
				const lightness = 56 + (Math.abs(hash >> 16) % 10);
				const borderLightness = Math.max(lightness - 6, 48);
				const chipTextColor = lightness >= 58 ? '#1e293b' : '#ffffff';

				return {
					border: 'hsl(' + hue + ', ' + saturation + '%, ' + borderLightness + '%)',
					chipBackground: 'hsl(' + hue + ', ' + saturation + '%, ' + lightness + '%)',
					cardBackground: 'hsl(' + hue + ', ' + Math.min(saturation, 48) + '%, 96%)',
					chipTextColor: chipTextColor
				};
			}

			function renderCommentMessageElement (message)
			{
				const wrapper = document.createElement('div');
				wrapper.className = 'report-comment-message' + (isOwnComment(message) ? ' is-own' : '');
				wrapper.dataset.commentId = String(message.id || '');

				const emailColors = commentColorFromEmail(message.email || '');
				wrapper.style.borderColor = emailColors.border;
				wrapper.style.backgroundColor = emailColors.cardBackground;

				const meta = document.createElement('div');
				meta.className = 'report-comment-message-meta';

				const emailEl = document.createElement('span');
				emailEl.className = 'report-comment-message-email';
				emailEl.textContent = String(message.email || '');
				emailEl.style.backgroundColor = emailColors.chipBackground;
				emailEl.style.color = emailColors.chipTextColor;

				const timeEl = document.createElement('span');
				const timestamp = message.is_edited ? (message.updated_at || message.created_at) : (message.created_at || '');
				timeEl.textContent = formatCommentTimestamp(timestamp);
				if (message.is_edited)
				{
					const editedEl = document.createElement('span');
					editedEl.className = 'report-comment-message-edited';
					editedEl.textContent = '(' + i18n['index.js.comments_edited'] + ')';
					meta.appendChild(emailEl);
					meta.appendChild(timeEl);
					meta.appendChild(editedEl);
				}
				else
				{
					meta.appendChild(emailEl);
					meta.appendChild(timeEl);
				}

				if (isOwnComment(message))
				{
					const editBtn = document.createElement('button');
					editBtn.type = 'button';
					editBtn.className = 'report-comment-message-edit-btn';
					editBtn.textContent = '✏️';
					editBtn.setAttribute('aria-label', i18n['index.js.comments_edit']);
					editBtn.title = i18n['index.js.comments_edit'];
					editBtn.addEventListener('click', function ()
					{
						startEditComment(message);
					});
					wrapper.appendChild(editBtn);
				}

				const textEl = document.createElement('div');
				textEl.className = 'report-comment-message-text';
				textEl.textContent = String(message.text || '');

				wrapper.appendChild(meta);
				wrapper.appendChild(textEl);

				return wrapper;
			}

			function renderCommentsLog ()
			{
				if (!reportCommentsLog)
				{
					return;
				}

				reportCommentsLog.innerHTML = '';
				if (!Array.isArray(activeCommentsMessages) || activeCommentsMessages.length === 0)
				{
					if (reportCommentsEmpty)
					{
						const empty = document.createElement('div');
						empty.className = 'report-comments-empty';
						empty.textContent = commentsLoading
							? i18n['index.js.comments_loading']
							: i18n['index.js.comments_empty'];
						reportCommentsLog.appendChild(empty);
					}
					return;
				}

				activeCommentsMessages.forEach(function (message)
				{
					reportCommentsLog.appendChild(renderCommentMessageElement(message));
				});
				scrollCommentsLogToBottom();
			}

			function closeReportCommentsModal ()
			{
				activeCommentsReportId = '';
				activeCommentsMessages = [];
				editingCommentId = '';
				commentsLoading = false;
				commentsSending = false;
				if (reportCommentsOverlay)
				{
					reportCommentsOverlay.classList.remove('is-visible');
				}
				if (reportCommentsInput)
				{
					reportCommentsInput.value = '';
				}
				document.body.style.overflow = '';
			}

			function openReportCommentsModal (reportId, fetchedAt)
			{
				if (reportId === '' || activeProjectNo === '')
				{
					return;
				}

				activeCommentsReportId = reportId;
				activeCommentsMessages = [];
				editingCommentId = '';
				commentsLoading = true;
				if (reportCommentsTitle)
				{
					const title = i18n['index.js.comments_modal_title'];
					const when = formatReportTimestamp(fetchedAt || '');
					reportCommentsTitle.textContent = when !== '' ? title + ' — ' + when : title;
				}
				if (reportCommentsOverlay)
				{
					reportCommentsOverlay.classList.add('is-visible');
				}
				document.body.style.overflow = 'hidden';
				renderCommentsLog();

				postForm('index.php?action=list_report_comments', {
					company: companySelect.value,
					project_no: activeProjectNo,
					report_id: reportId
				}).then(function (json)
				{
					commentsLoading = false;
					if (!json.ok || activeCommentsReportId !== reportId)
					{
						if (activeCommentsReportId === reportId)
						{
							setStatus(json.error || i18n['index.js.comments_load_failed'], true);
							closeReportCommentsModal();
						}
						return;
					}

					activeCommentsMessages = Array.isArray(json.messages) ? json.messages : [];
					updateReportCommentBadge(reportId, json.comment_count);
					renderCommentsLog();
					if (reportCommentsInput)
					{
						reportCommentsInput.focus();
					}
				}).catch(function (error)
				{
					commentsLoading = false;
					if (activeCommentsReportId === reportId)
					{
						setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
						closeReportCommentsModal();
					}
				});
			}

			function startEditComment (message)
			{
				if (!reportCommentsLog || !message)
				{
					return;
				}

				editingCommentId = String(message.id || '');
				const existing = reportCommentsLog.querySelector('[data-comment-id="' + editingCommentId + '"]');
				if (!existing)
				{
					return;
				}

				existing.innerHTML = '';
				existing.classList.add('is-own');

				const editWrap = document.createElement('div');
				editWrap.className = 'report-comment-message-edit';

				const textarea = document.createElement('textarea');
				textarea.value = String(message.text || '');

				const actions = document.createElement('div');
				actions.className = 'report-comment-message-edit-actions';

				const cancelBtn = document.createElement('button');
				cancelBtn.type = 'button';
				cancelBtn.className = 'btn btn-print';
				cancelBtn.textContent = i18n['index.js.comments_cancel'];
				cancelBtn.addEventListener('click', function ()
				{
					editingCommentId = '';
					renderCommentsLog();
				});

				const saveBtn = document.createElement('button');
				saveBtn.type = 'button';
				saveBtn.className = 'btn btn-main';
				saveBtn.textContent = i18n['index.js.comments_save'];
				saveBtn.addEventListener('click', function ()
				{
					saveEditedComment(message, textarea.value);
				});

				textarea.addEventListener('keydown', function (event)
				{
					if (event.key === 'Enter' && !event.shiftKey)
					{
						event.preventDefault();
						saveEditedComment(message, textarea.value);
					}
					if (event.key === 'Escape')
					{
						event.preventDefault();
						editingCommentId = '';
						renderCommentsLog();
					}
				});

				actions.appendChild(cancelBtn);
				actions.appendChild(saveBtn);
				editWrap.appendChild(textarea);
				editWrap.appendChild(actions);
				existing.appendChild(editWrap);
				textarea.focus();
				textarea.select();
			}

			function saveEditedComment (message, rawText)
			{
				if (commentsSending || !message || activeCommentsReportId === '')
				{
					return;
				}

				const text = String(rawText || '').trim();
				if (text === '')
				{
					setStatus(i18n['index.js.comments_send_failed'], true);
					return;
				}

				commentsSending = true;
				postForm('index.php?action=update_report_comment', {
					company: companySelect.value,
					project_no: activeProjectNo,
					report_id: activeCommentsReportId,
					comment_id: String(message.id || ''),
					text: text
				}).then(function (json)
				{
					commentsSending = false;
					if (!json.ok)
					{
						setStatus(json.error || i18n['index.js.comments_update_failed'], true);
						return;
					}

					editingCommentId = '';
					const updated = json.message;
					activeCommentsMessages = activeCommentsMessages.map(function (entry)
					{
						return String(entry.id || '') === String(updated.id || '') ? updated : entry;
					});
					renderCommentsLog();
				}).catch(function (error)
				{
					commentsSending = false;
					setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
				});
			}

			function sendReportComment ()
			{
				if (commentsSending || commentsLoading || activeCommentsReportId === '' || !reportCommentsInput)
				{
					return;
				}

				const text = reportCommentsInput.value.trim();
				if (text === '')
				{
					return;
				}

				commentsSending = true;
				postForm('index.php?action=add_report_comment', {
					company: companySelect.value,
					project_no: activeProjectNo,
					report_id: activeCommentsReportId,
					text: text
				}).then(function (json)
				{
					commentsSending = false;
					if (!json.ok)
					{
						setStatus(json.error || i18n['index.js.comments_send_failed'], true);
						return;
					}

					reportCommentsInput.value = '';
					if (json.message)
					{
						activeCommentsMessages = activeCommentsMessages.concat([json.message]);
					}
					updateReportCommentBadge(activeCommentsReportId, json.comment_count);
					renderCommentsLog();
					reportCommentsInput.focus();
				}).catch(function (error)
				{
					commentsSending = false;
					setStatus(i18n['index.js.network_error'].replace('%s', error.message), true);
				});
			}

			function renderReportList (container, reports)
			{
				activeReportListEl = container;
				container.innerHTML = '';
				const list = Array.isArray(reports) ? reports : [];
				const visibleReports = getVisibleReports(list);

				if (visibleReports.length === 0)
				{
					const empty = document.createElement('li');
					empty.className = 'muted';
					const hasHiddenAutoReports = !showAutoReports && list.some(function (entry)
					{
						return entry && entry.auto_report === true;
					});
					const hasMoreReports = currentReportsHasMore || currentReportsTotal > list.length;
					empty.textContent = hasHiddenAutoReports && !hasMoreReports
						? i18n['index.js.reports_empty_filtered']
						: i18n['index.js.reports_empty'];
					container.appendChild(empty);
					renderReportListLoadMore(container);
					return;
				}

				visibleReports.forEach(function (entry)
				{
					const reportId = String((entry && entry.report_id) || '').trim();
					if (reportId === '')
					{
						return;
					}

					const item = document.createElement('li');
					item.className = 'report-item ' + (entry.auto_report === true ? 'is-auto-report' : 'is-manual-report');

					const commentBtn = document.createElement('button');
					commentBtn.type = 'button';
					commentBtn.className = 'report-comment-btn';
					commentBtn.dataset.reportId = reportId;
					commentBtn.textContent = reportCommentButtonLabel(entry.comment_count || 0);
					commentBtn.title = i18n['index.js.comments_modal_title'];
					commentBtn.addEventListener('click', function ()
					{
						openReportCommentsModal(reportId, entry.fetched_at || '');
					});

					const meta = document.createElement('div');
					meta.className = 'report-item-meta';
					meta.textContent = formatReportTimestamp(entry.fetched_at || '');

					const actions = document.createElement('div');
					actions.className = 'report-item-actions';

					if (entry.auto_report === true)
					{
						const autoBadge = document.createElement('span');
						autoBadge.className = 'report-auto-badge';
						autoBadge.textContent = '🤖';
						autoBadge.setAttribute('aria-hidden', 'true');
						actions.appendChild(autoBadge);
					}
					else
					{
						const manualBadge = document.createElement('span');
						manualBadge.className = 'report-manual-badge';
						const handle = String(entry.created_by || '').trim() || currentUserHandle;
						if (handle !== '')
						{
							manualBadge.textContent = handle;
							manualBadge.title = handle;
							actions.appendChild(manualBadge);
						}
					}

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
						askDeleteReport(reportId, entry.auto_report === true);
					});

					actions.appendChild(openBtn);
					actions.appendChild(deleteBtn);
					item.appendChild(commentBtn);
					item.appendChild(meta);
					item.appendChild(actions);
					container.appendChild(item);
				});

				renderReportListLoadMore(container);
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

			function dashboardApiUrl ()
			{
				let url = 'index.php?action=project_dashboard';
				if (debugAllReports)
				{
					url += '&debug_allreports=1';
				}

				return url;
			}

			function destroyDashboardCharts ()
			{
				if (dashboardPocChartInstance)
				{
					dashboardPocChartInstance.destroy();
					dashboardPocChartInstance = null;
				}
				if (dashboardBookedChartInstance)
				{
					dashboardBookedChartInstance.destroy();
					dashboardBookedChartInstance = null;
				}
				if (dashboardEacChartInstance)
				{
					dashboardEacChartInstance.destroy();
					dashboardEacChartInstance = null;
				}
				if (dashboardInvoicedChartInstance)
				{
					dashboardInvoicedChartInstance.destroy();
					dashboardInvoicedChartInstance = null;
				}
				if (dashboardInstallmentsChartInstance)
				{
					dashboardInstallmentsChartInstance.destroy();
					dashboardInstallmentsChartInstance = null;
				}
				if (dashboardInstallmentsResizeObserver)
				{
					dashboardInstallmentsResizeObserver.disconnect();
					dashboardInstallmentsResizeObserver = null;
				}
				dashboardInstallmentsSourceHistory = [];
				dashboardInstallmentsLastBarCount = 0;
			}

			function formatDashboardCurrency (value)
			{
				const amount = Number(value || 0);
				return '€ ' + amount.toLocaleString('nl-NL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
			}

			function dashboardHasBreakdown (breakdown)
			{
				const majors = breakdown && Array.isArray(breakdown.major_totals) ? breakdown.major_totals : [];
				return majors.some(function (major)
				{
					return Number(major && major.amount || 0) > 0
						|| (Array.isArray(major && major.subtotals) && major.subtotals.some(function (sub)
						{
							return Number(sub && sub.amount || 0) > 0;
						}));
				});
			}

			function dashboardHasInstallmentsHistory (history)
			{
				return Array.isArray(history) && history.some(function (point)
				{
					return Math.abs(Number(point && point.amount || 0)) > 0.000001;
				});
			}

			function setDashboardLatestReportSubtitle (breakdown)
			{
				if (!dashboardLatestReportSubtitle || !breakdown)
				{
					return;
				}

				const reportId = String(breakdown.report_id || '').trim();
				const fetchedAt = formatReportTimestamp(String(breakdown.fetched_at || ''));
				dashboardLatestReportSubtitle.textContent = i18n['index.js.dashboard_latest_report_note']
					.replace('%s', fetchedAt || i18n['index.js.unknown_moment'])
					.replace('%s', reportId || '-');
			}

			function dashboardHasAnyContent (dashboard)
			{
				const points = Array.isArray(dashboard && dashboard.points) ? dashboard.points : [];
				return points.length > 0
					|| dashboardHasBreakdown(dashboard && dashboard.cost_breakdown)
					|| dashboardHasBreakdown(dashboard && dashboard.eac_breakdown)
					|| dashboardHasBreakdown(dashboard && dashboard.invoiced_breakdown)
					|| dashboardHasInstallmentsHistory(dashboard && dashboard.installments_history);
			}

			function renderDashboardCharts (dashboard)
			{
				const points = Array.isArray(dashboard && dashboard.points) ? dashboard.points : [];
				const bookedBreakdown = dashboard && dashboard.cost_breakdown ? dashboard.cost_breakdown : {};
				const eacBreakdown = dashboard && dashboard.eac_breakdown ? dashboard.eac_breakdown : {};
				const invoicedBreakdown = dashboard && dashboard.invoiced_breakdown ? dashboard.invoiced_breakdown : {};
				const installmentsHistory = Array.isArray(dashboard && dashboard.installments_history) ? dashboard.installments_history : [];
				const hasPoc = points.length > 0;
				const hasBooked = dashboardHasBreakdown(bookedBreakdown);
				const hasEac = dashboardHasBreakdown(eacBreakdown);
				const hasInvoiced = dashboardHasBreakdown(invoicedBreakdown);
				const hasInstallments = dashboardHasInstallmentsHistory(installmentsHistory);

				if (dashboardPocSection)
				{
					dashboardPocSection.hidden = !hasPoc;
				}
				if (dashboardBreakdownBlock)
				{
					dashboardBreakdownBlock.hidden = !(hasBooked || hasEac);
				}
				if (dashboardBookedSection)
				{
					dashboardBookedSection.hidden = !hasBooked;
				}
				if (dashboardEacSection)
				{
					dashboardEacSection.hidden = !hasEac;
				}
				if (dashboardFinanceBlock)
				{
					dashboardFinanceBlock.hidden = !(hasInvoiced || hasInstallments);
				}
				if (dashboardInvoicedSection)
				{
					dashboardInvoicedSection.hidden = !hasInvoiced;
				}
				if (dashboardInstallmentsSection)
				{
					dashboardInstallmentsSection.hidden = !hasInstallments;
				}

				if (dashboardLatestReportSubtitle)
				{
					const subtitleSource = bookedBreakdown.report_id ? bookedBreakdown
						: (eacBreakdown.report_id ? eacBreakdown : invoicedBreakdown);
					if (subtitleSource && subtitleSource.report_id)
					{
						setDashboardLatestReportSubtitle(subtitleSource);
						dashboardLatestReportSubtitle.hidden = false;
					}
					else
					{
						dashboardLatestReportSubtitle.hidden = true;
					}
				}

				destroyDashboardCharts();

				if (hasPoc)
				{
					renderDashboardPocChart(dashboard);
				}

				if (hasBooked)
				{
					dashboardBookedChartInstance = renderDashboardBreakdownChart(
						dashboardBookedChartCanvas,
						bookedBreakdown,
						i18n['index.js.dashboard_chart_cost_subtotal']
					);
				}

				if (hasEac)
				{
					dashboardEacChartInstance = renderDashboardBreakdownChart(
						dashboardEacChartCanvas,
						eacBreakdown,
						i18n['index.js.dashboard_chart_cost_subtotal']
					);
				}

				if (hasInvoiced)
				{
					dashboardInvoicedChartInstance = renderDashboardBreakdownChart(
						dashboardInvoicedChartCanvas,
						invoicedBreakdown,
						i18n['index.js.dashboard_chart_cost_subtotal']
					);
				}

				if (hasInstallments)
				{
					const installmentsData = installmentsHistory.slice();
					window.requestAnimationFrame(function ()
					{
						renderDashboardInstallmentsChart(installmentsData);
					});
				}
			}

			function formatDashboardDate (isoDate)
			{
				const parts = String(isoDate || '').split('-');
				if (parts.length !== 3)
				{
					return isoDate;
				}

				const dt = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
				return dt.toLocaleDateString(dateLocale, { day: 'numeric', month: 'short', year: 'numeric' });
			}

			function renderDashboardPocChart (dashboard)
			{
				if (!dashboardPocChartCanvas || typeof Chart === 'undefined')
				{
					return;
				}

				const points = Array.isArray(dashboard && dashboard.points) ? dashboard.points : [];
				const yMax = Math.max(100, Number((dashboard && dashboard.y_max_percent) || 100));

				dashboardPocChartInstance = new Chart(dashboardPocChartCanvas, {
					type: 'line',
					data: {
						labels: points.map(function (point) { return formatDashboardDate(point.date); }),
						datasets: [
							{
								label: i18n['index.js.dashboard_chart_poc_baseline'],
								data: points.map(function (point) { return Number(point.poc_baseline || 0); }),
								borderColor: '#f97316',
								backgroundColor: 'rgba(249, 115, 22, 0.08)',
								tension: 0.2,
								pointRadius: 3
							},
							{
								label: i18n['index.js.dashboard_chart_poc_eac'],
								data: points.map(function (point) { return Number(point.poc_eac || 0); }),
								borderColor: '#00529b',
								backgroundColor: 'rgba(0, 82, 155, 0.08)',
								tension: 0.2,
								pointRadius: 3
							}
						]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								position: 'top'
							}
						},
						scales: {
							y: {
								min: 0,
								max: yMax,
								title: {
									display: true,
									text: i18n['index.js.dashboard_chart_y_axis']
								},
								ticks: {
									callback: function (value)
									{
										return value + '%';
									}
								}
							}
						}
					}
				});
			}

			const majorTotalOuterRingPlugin = {
				id: 'majorTotalOuterRing',
				afterDatasetsDraw: function (chart)
				{
					const groups = chart.options.plugins && chart.options.plugins.majorTotalOuterRing
						? chart.options.plugins.majorTotalOuterRing.groups
						: [];
					if (!Array.isArray(groups) || groups.length === 0)
					{
						return;
					}

					const meta = chart.getDatasetMeta(0);
					if (!meta || !Array.isArray(meta.data) || meta.data.length === 0)
					{
						return;
					}

					function getVisibleArcRange (group)
					{
						let firstVisibleArc = null;
						let lastVisibleArc = null;

						for (let index = group.startIndex; index < group.startIndex + group.count; index += 1)
						{
							if (!chart.getDataVisibility(index))
							{
								continue;
							}

							const arc = meta.data[index];
							if (!arc)
							{
								continue;
							}

							if (!firstVisibleArc)
							{
								firstVisibleArc = arc;
							}
							lastVisibleArc = arc;
						}

						if (!firstVisibleArc || !lastVisibleArc)
						{
							return null;
						}

						return {
							first: firstVisibleArc,
							last: lastVisibleArc
						};
					}

					const ctx = chart.ctx;
					const firstArc = meta.data.find(function (arc, index)
					{
						return chart.getDataVisibility(index) && arc;
					});
					if (!firstArc)
					{
						return;
					}

					const ringRadius = (firstArc.outerRadius || 0) + 10;
					const labelRadius = ringRadius + 24;

					ctx.save();

					groups.forEach(function (group)
					{
						const visibleRange = getVisibleArcRange(group);
						if (!visibleRange)
						{
							return;
						}

						const startArc = visibleRange.first;
						const endArc = visibleRange.last;
						const startAngle = startArc.startAngle;
						const endAngle = endArc.endAngle;
						const centerX = startArc.x;
						const centerY = startArc.y;

						ctx.strokeStyle = group.color || '#1f2937';
						ctx.lineWidth = 2.5;
						ctx.lineCap = 'butt';
						ctx.beginPath();
						ctx.arc(centerX, centerY, ringRadius, startAngle, endAngle);
						ctx.stroke();

						const amount = Number(group.amount || 0);
						if (amount <= 0)
						{
							return;
						}

						const midAngle = (startAngle + endAngle) / 2;
						const labelX = centerX + Math.cos(midAngle) * labelRadius;
						const labelY = centerY + Math.sin(midAngle) * labelRadius;
						const label = formatDashboardCurrency(amount);

						ctx.font = '600 11px Segoe UI, sans-serif';
						ctx.fillStyle = '#1f2937';
						ctx.textBaseline = 'middle';

						if (Math.cos(midAngle) >= 0)
						{
							ctx.textAlign = 'left';
							ctx.fillText(label, labelX + 4, labelY);
						}
						else
						{
							ctx.textAlign = 'right';
							ctx.fillText(label, labelX - 4, labelY);
						}
					});

					ctx.restore();
				}
			};

			function dashboardSubtotalSliceColor (hue, subIndex, subtotalCount)
			{
				const count = Math.max(subtotalCount, 1);
				if (count === 1)
				{
					return 'hsl(' + hue + ' 54% 56%)';
				}

				const minLight = 44;
				const maxLight = 68;
				const lightness = minLight + (subIndex / (count - 1)) * (maxLight - minLight);
				const saturation = 50 + (subIndex / (count - 1)) * 8;

				return 'hsl(' + hue + ' ' + saturation + '% ' + lightness + '%)';
			}

			function renderDashboardBreakdownChart (canvas, costBreakdown, datasetLabel)
			{
				if (!canvas || typeof Chart === 'undefined')
				{
					return null;
				}

				const majors = (Array.isArray(costBreakdown.major_totals) ? costBreakdown.major_totals : []).filter(function (major)
				{
					return Number(major && major.amount || 0) > 0;
				});
				const subtotalLabels = [];
				const subtotalData = [];
				const subtotalColors = [];
				const majorGroups = [];
				const majorHues = majors.map(function (_, index)
				{
					return (index * 47) % 360;
				});

				let subtotalIndex = 0;

				majors.forEach(function (major, majorIndex)
				{
					const hue = majorHues[majorIndex];
					const majorColor = 'hsl(' + hue + ' 62% 42%)';
					const subtotals = Array.isArray(major.subtotals) ? major.subtotals : [];
					const visibleSubtotals = subtotals.filter(function (sub)
					{
						return Number(sub && sub.amount || 0) > 0;
					});
					const groupStart = subtotalIndex;

					if (visibleSubtotals.length > 0)
					{
						visibleSubtotals.forEach(function (sub, subIndex)
						{
							subtotalLabels.push(sub.description || sub.code || i18n['index.js.dashboard_chart_cost_subtotal']);
							subtotalData.push(Number(sub.amount || 0));
							subtotalColors.push(dashboardSubtotalSliceColor(hue, subIndex, visibleSubtotals.length));
							subtotalIndex += 1;
						});
					}
					else
					{
						subtotalLabels.push(major.description || major.code || i18n['index.js.dashboard_chart_cost_major']);
						subtotalData.push(Number(major.amount || 0));
						subtotalColors.push(dashboardSubtotalSliceColor(hue, 0, 1));
						subtotalIndex += 1;
					}

					majorGroups.push({
						amount: Number(major.amount || 0),
						color: majorColor,
						startIndex: groupStart,
						count: subtotalIndex - groupStart,
						label: (major.description || major.code || i18n['index.js.dashboard_chart_cost_major']) + ' (' + major.code + ')'
					});
				});

				return new Chart(canvas, {
					type: 'doughnut',
					data: {
						labels: subtotalLabels,
						datasets: [{
							label: datasetLabel,
							data: subtotalData,
							backgroundColor: subtotalColors,
							borderWidth: 0,
							hoverBorderWidth: 0,
							spacing: 0,
							hoverOffset: 6
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						rotation: -Math.PI / 2,
						layout: {
							padding: {
								top: 20,
								right: 56,
								bottom: 20,
								left: 56
							}
						},
						plugins: {
							legend: {
								position: 'bottom',
								labels: {
									boxWidth: 12,
									font: { size: 10 }
								}
							},
							tooltip: {
								z: 20,
								callbacks: {
									label: function (context)
									{
										const value = Number((context.raw ?? context.parsed) || 0);
										return (context.label || '') + ': ' + formatDashboardCurrency(value);
									}
								}
							},
							majorTotalOuterRing: {
								groups: majorGroups
							}
						},
						cutout: '28%',
						radius: '72%'
					},
					plugins: [majorTotalOuterRingPlugin]
				});
			}

			const installmentsBarAmountLabelsPlugin = {
				id: 'installmentsBarAmountLabels',
				afterDatasetsDraw: function (chart)
				{
					if (chart.config.type !== 'bar')
					{
						return;
					}

					const dataset = chart.data.datasets[0];
					const meta = chart.getDatasetMeta(0);
					if (!dataset || !meta || !Array.isArray(meta.data))
					{
						return;
					}

					const ctx = chart.ctx;
					ctx.save();
					ctx.fillStyle = '#ffffff';
					ctx.font = '600 9px Segoe UI, sans-serif';

					meta.data.forEach(function (bar, index)
					{
						if (!bar || bar.hidden)
						{
							return;
						}

						const amount = Number((dataset.data || [])[index] || 0);
						if (amount <= 0)
						{
							return;
						}

						const barTop = Math.min(Number(bar.y || 0), Number(bar.base || 0));
						const barBottom = Math.max(Number(bar.y || 0), Number(bar.base || 0));
						const barHeight = barBottom - barTop;
						const barWidth = Number(bar.width || 0);
						if (barHeight < 24 || barWidth < 8)
						{
							return;
						}

						const label = formatDashboardCurrency(amount);
						const inset = 5;
						const innerHeight = barHeight - (inset * 2);
						const innerWidth = barWidth - 4;
						let fontSize = Math.min(10, Math.max(7, Math.floor(innerWidth * 0.85)));

						ctx.save();
						ctx.beginPath();
						ctx.rect(bar.x - (barWidth / 2), barTop, barWidth, barHeight);
						ctx.clip();

						ctx.font = '600 ' + fontSize + 'px Segoe UI, sans-serif';
						while (fontSize > 6 && ctx.measureText(label).width > innerHeight)
						{
							fontSize -= 1;
							ctx.font = '600 ' + fontSize + 'px Segoe UI, sans-serif';
						}

						if (ctx.measureText(label).width > innerHeight)
						{
							ctx.restore();
							return;
						}

						ctx.translate(bar.x, barTop + inset);
						ctx.rotate(-Math.PI / 2);
						ctx.textAlign = 'right';
						ctx.textBaseline = 'top';
						ctx.fillText(label, 0, 0);
						ctx.restore();
					});

					ctx.restore();
				}
			};

			function filterDashboardInstallmentsHistory (history)
			{
				const list = Array.isArray(history) ? history : [];
				if (list.length <= 1)
				{
					return list.slice();
				}

				const filtered = list.filter(function (point, index)
				{
					if (index >= list.length - 1)
					{
						return true;
					}

					const currentAmount = Number(point && point.amount || 0);
					const newerAmount = Number(list[index + 1] && list[index + 1].amount || 0);

					return Math.abs(currentAmount - newerAmount) > 0.000001;
				});

				if (filtered.length === 0 && list.length > 0)
				{
					return [list[list.length - 1]];
				}

				return filtered;
			}

			function getDashboardInstallmentsVisiblePoints (filteredHistory)
			{
				const history = Array.isArray(filteredHistory) ? filteredHistory : [];
				if (history.length === 0)
				{
					return [];
				}

				const wrap = dashboardInstallmentsWrap;
				const availableWidth = wrap && wrap.clientWidth > 0 ? wrap.clientWidth : 320;
				const maxBars = Math.max(1, Math.floor(availableWidth / DASHBOARD_INSTALLMENTS_MIN_BAR_WIDTH));

				return history.slice(-Math.min(maxBars, history.length));
			}

			function buildDashboardInstallmentsChartData (points)
			{
				return {
					labels: points.map(function (point)
					{
						return formatDashboardDate(point.date || '');
					}),
					values: points.map(function (point)
					{
						return Number(point.amount || 0);
					})
				};
			}

			function updateDashboardInstallmentsChart (points)
			{
				if (!dashboardInstallmentsChartCanvas || typeof Chart === 'undefined' || points.length === 0)
				{
					return;
				}

				const chartData = buildDashboardInstallmentsChartData(points);
				dashboardInstallmentsLastBarCount = points.length;

				if (dashboardInstallmentsChartInstance)
				{
					dashboardInstallmentsChartInstance.data.labels = chartData.labels;
					dashboardInstallmentsChartInstance.data.datasets[0].data = chartData.values;
					dashboardInstallmentsChartInstance.update();
					return;
				}

				dashboardInstallmentsChartInstance = new Chart(dashboardInstallmentsChartCanvas, {
					type: 'bar',
					data: {
						labels: chartData.labels,
						datasets: [{
							label: i18n['index.js.dashboard_chart_installments_title'],
							data: chartData.values,
							backgroundColor: 'rgba(0, 82, 155, 0.82)',
							borderRadius: 3,
							maxBarThickness: 28
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: { display: false },
							tooltip: {
								z: 20,
								callbacks: {
									label: function (context)
									{
										const value = Number((context.raw ?? context.parsed) || 0);
										return formatDashboardCurrency(value);
									}
								}
							}
						},
						scales: {
							x: {
								grid: { display: false },
								ticks: {
									maxRotation: 0,
									autoSkip: false,
									font: { size: 10 }
								}
							},
							y: {
								beginAtZero: true,
								ticks: {
									font: { size: 10 },
									callback: function (value)
									{
										return formatDashboardCurrency(value);
									}
								}
							}
						}
					},
					plugins: [installmentsBarAmountLabelsPlugin]
				});
			}

			function refreshDashboardInstallmentsChart ()
			{
				const filtered = filterDashboardInstallmentsHistory(dashboardInstallmentsSourceHistory);
				const points = getDashboardInstallmentsVisiblePoints(filtered);
				if (points.length === 0)
				{
					return;
				}

				if (dashboardInstallmentsWrap && dashboardInstallmentsWrap.clientWidth <= 0)
				{
					window.requestAnimationFrame(refreshDashboardInstallmentsChart);
					return;
				}

				updateDashboardInstallmentsChart(points);
			}

			function renderDashboardInstallmentsChart (history)
			{
				if (!dashboardInstallmentsChartCanvas || typeof Chart === 'undefined')
				{
					return;
				}

				if (dashboardInstallmentsResizeObserver)
				{
					dashboardInstallmentsResizeObserver.disconnect();
					dashboardInstallmentsResizeObserver = null;
				}

				if (dashboardInstallmentsChartInstance)
				{
					dashboardInstallmentsChartInstance.destroy();
					dashboardInstallmentsChartInstance = null;
				}

				dashboardInstallmentsSourceHistory = Array.isArray(history) ? history.slice() : [];
				dashboardInstallmentsLastBarCount = 0;
				refreshDashboardInstallmentsChart();

				if (dashboardInstallmentsWrap && typeof ResizeObserver !== 'undefined')
				{
					dashboardInstallmentsResizeObserver = new ResizeObserver(function ()
					{
						if (!dashboardInstallmentsSourceHistory.length)
						{
							return;
						}

						const filtered = filterDashboardInstallmentsHistory(dashboardInstallmentsSourceHistory);
						const points = getDashboardInstallmentsVisiblePoints(filtered);
						if (points.length === 0)
						{
							return;
						}

						if (points.length !== dashboardInstallmentsLastBarCount)
						{
							refreshDashboardInstallmentsChart();
						}
						else if (dashboardInstallmentsChartInstance)
						{
							dashboardInstallmentsChartInstance.resize();
						}
					});
					dashboardInstallmentsResizeObserver.observe(dashboardInstallmentsWrap);
				}
			}

			function closeDashboardModal ()
			{
				if (!dashboardModalOverlay)
				{
					return;
				}

				dashboardModalOverlay.classList.remove('is-visible');
				destroyDashboardCharts();
				if (!finrapModalOverlay || !finrapModalOverlay.classList.contains('is-visible'))
				{
					document.body.style.overflow = '';
				}
			}

			function openProjectDashboard ()
			{
				if (!dashboardModalOverlay || activeProjectNo === '')
				{
					return;
				}

				if (dashboardModalTitle)
				{
					dashboardModalTitle.textContent = i18n['index.js.dashboard_modal_title'].replace('%s', activeProjectNo);
				}

				if (dashboardStatus)
				{
					dashboardStatus.textContent = i18n['index.js.dashboard_loading'];
					dashboardStatus.hidden = false;
				}

				if (dashboardContent)
				{
					dashboardContent.hidden = true;
				}

				destroyDashboardCharts();
				dashboardModalOverlay.classList.add('is-visible');
				document.body.style.overflow = 'hidden';

				postForm(dashboardApiUrl(), {
					company: companySelect.value,
					project_no: activeProjectNo,
					debug_all_reports: debugAllReports ? '1' : '0'
				}).then(function (json)
				{
					if (!json.ok)
					{
						if (dashboardStatus)
						{
							dashboardStatus.textContent = json.error || i18n['index.js.dashboard_load_failed'];
							dashboardStatus.hidden = false;
						}
						if (dashboardContent)
						{
							dashboardContent.hidden = true;
						}
						return;
					}

					const dashboard = json.dashboard || {};
					if (!dashboardHasAnyContent(dashboard))
					{
						if (dashboardStatus)
						{
							dashboardStatus.textContent = i18n['index.js.dashboard_empty'];
							dashboardStatus.hidden = false;
						}
						if (dashboardContent)
						{
							dashboardContent.hidden = true;
						}
						return;
					}

					if (dashboardStatus)
					{
						dashboardStatus.hidden = true;
					}
					if (dashboardContent)
					{
						dashboardContent.hidden = false;
					}

					renderDashboardCharts(dashboard);
				}).catch(function (error)
				{
					if (dashboardStatus)
					{
						dashboardStatus.textContent = i18n['index.js.network_error'].replace('%s', error.message);
						dashboardStatus.hidden = false;
					}
					if (dashboardContent)
					{
						dashboardContent.hidden = true;
					}
				});
			}

			function renderProject (project, reportsPayload)
			{
				const no = String((project && project.No) || activeProjectNo || '').trim();
				currentProjectData = project || {};
				activeProjectNo = no;
				applyReportsPayload(Array.isArray(reportsPayload) ? { reports: reportsPayload } : (reportsPayload || {}), false);
				projectArea.innerHTML = '';

				const card = document.createElement('div');
				card.className = 'project-card';

				const titleRow = document.createElement('div');
				titleRow.className = 'project-title-row';

				const title = document.createElement('h2');
				title.className = 'project-title';
				title.textContent = no + ' - ' + String((project && project.Description) || '');
				titleRow.appendChild(title);

				const dashboardBtn = document.createElement('button');
				dashboardBtn.className = 'btn btn-dashboard';
				dashboardBtn.type = 'button';
				dashboardBtn.textContent = i18n['index.js.dashboard_btn'];
				dashboardBtn.addEventListener('click', openProjectDashboard);
				titleRow.appendChild(dashboardBtn);

				card.appendChild(titleRow);

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

				const reportsToolbar = document.createElement('div');
				reportsToolbar.className = 'reports-toolbar';

				const reportsLabel = document.createElement('label');
				reportsLabel.textContent = i18n['index.js.reports_label'];
				reportsToolbar.appendChild(reportsLabel);

				const autoReportsToggle = document.createElement('label');
				autoReportsToggle.className = 'auto-reports-toggle';
				const autoReportsCheckbox = document.createElement('input');
				autoReportsCheckbox.type = 'checkbox';
				autoReportsCheckbox.checked = showAutoReports;
				autoReportsCheckbox.addEventListener('change', function ()
				{
					showAutoReports = autoReportsCheckbox.checked;
					saveAutoReportsPreference(showAutoReports);
					reloadReportListPage();
				});
				autoReportsToggle.appendChild(autoReportsCheckbox);
				autoReportsToggle.appendChild(document.createTextNode(i18n['index.js.show_auto_reports']));
				reportsToolbar.appendChild(autoReportsToggle);
				card.appendChild(reportsToolbar);

				const reportList = document.createElement('ul');
				reportList.className = 'report-list';
				card.appendChild(reportList);
				renderReportList(reportList, currentReports);

				generateBtn.addEventListener('click', function ()
				{
					setStatus(i18n['index.js.status.generating'], false);
					generateBtn.disabled = true;
					showLoader('generate', i18n['index.js.status.generate_subtitle'].replace('%s', no));
					postForm('index.php?action=generate_report', {
						company: companySelect.value,
						project_no: activeProjectNo,
						include_auto_reports: showAutoReports ? '1' : '0'
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
						renderProject(project, json);
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
					project_no: projectNo,
					include_auto_reports: showAutoReports ? '1' : '0'
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
					renderProject(json.project || {}, json);
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
			refreshCompaniesInBackground();

			if (confirmDeleteStep1No)
			{
				confirmDeleteStep1No.addEventListener('click', closeDeleteModals);
			}

			if (confirmDeleteStep2No)
			{
				confirmDeleteStep2No.addEventListener('click', closeDeleteModals);
			}

			if (confirmDeleteStep3No)
			{
				confirmDeleteStep3No.addEventListener('click', closeDeleteModals);
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
					if (confirmDeleteStep2)
					{
						confirmDeleteStep2.classList.remove('is-visible');
					}

					if (pendingDeleteIsAutoReport && confirmDeleteStep3)
					{
						confirmDeleteStep3.classList.add('is-visible');
						return;
					}

					executeDeleteReport();
				});
			}

			if (confirmDeleteStep3Yes)
			{
				confirmDeleteStep3Yes.addEventListener('click', function ()
				{
					executeDeleteReport();
				});
			}

			if (reportCommentsClose)
			{
				reportCommentsClose.addEventListener('click', closeReportCommentsModal);
			}

			if (reportCommentsSend)
			{
				reportCommentsSend.addEventListener('click', sendReportComment);
			}

			if (reportCommentsInput)
			{
				reportCommentsInput.addEventListener('keydown', function (event)
				{
					if (event.key === 'Enter' && !event.shiftKey)
					{
						event.preventDefault();
						sendReportComment();
					}
				});
			}

			if (reportCommentsOverlay)
			{
				reportCommentsOverlay.addEventListener('click', function (event)
				{
					if (event.target === reportCommentsOverlay)
					{
						closeReportCommentsModal();
					}
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

			if (dashboardModalClose)
			{
				dashboardModalClose.addEventListener('click', closeDashboardModal);
			}

			if (dashboardModalOverlay)
			{
				dashboardModalOverlay.addEventListener('click', function (event)
				{
					if (event.target === dashboardModalOverlay)
					{
						closeDashboardModal();
					}
				});
			}

			window.addEventListener('keydown', function (event)
			{
				if (event.key === 'Escape' && reportCommentsOverlay && reportCommentsOverlay.classList.contains('is-visible'))
				{
					if (editingCommentId !== '')
					{
						editingCommentId = '';
						renderCommentsLog();
						return;
					}

					closeReportCommentsModal();
					return;
				}

				if (event.key === 'Escape' && ((confirmDeleteStep1 && confirmDeleteStep1.classList.contains('is-visible')) || (confirmDeleteStep2 && confirmDeleteStep2.classList.contains('is-visible')) || (confirmDeleteStep3 && confirmDeleteStep3.classList.contains('is-visible'))))
				{
					closeDeleteModals();
					return;
				}

				if (event.key === 'Escape' && dashboardModalOverlay && dashboardModalOverlay.classList.contains('is-visible'))
				{
					closeDashboardModal();
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
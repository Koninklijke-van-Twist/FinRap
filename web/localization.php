<?php

/**
 * Constants
 */

const FLAG_SVGS = [
    'nl' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#AE1C28"/><rect width="900" height="400" fill="#fff"/><rect width="900" height="200" fill="#fff"/><rect width="900" height="200" y="0" fill="#AE1C28"/><rect width="900" height="200" y="200" fill="#fff"/><rect width="900" height="200" y="400" fill="#21468B"/></svg>',
    'en' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 40"><clipPath id="a"><path d="M0 0v40h60V0z"/></clipPath><clipPath id="b"><path d="M30 20h30v20zv20H0zH0V0zV0h30z"/></clipPath><g clip-path="url(#a)"><path d="M0 0v40h60V0z" fill="#012169"/><path d="M0 0l60 40m0-40L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0l60 40m0-40L0 40" clip-path="url(#b)" stroke="#C8102E" stroke-width="5"/><path d="M30 0v40M0 20h60" stroke="#fff" stroke-width="13"/><path d="M30 0v40M0 20h60" stroke="#C8102E" stroke-width="8"/></g></svg>',
    'de' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 3"><rect width="5" height="3" y="0" fill="#000"/><rect width="5" height="2" y="1" fill="#D00"/><rect width="5" height="1" y="2" fill="#FFCE00"/></svg>',
    'fr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#ED2939"/><rect width="600" height="600" fill="#fff"/><rect width="300" height="600" fill="#002395"/></svg>',
];

const SUPPORTED_LANGUAGES = [
    'nl' => ['flag' => '🇳🇱', 'label' => 'Nederlands'],
    'en' => ['flag' => '🇬🇧', 'label' => 'English'],
    'de' => ['flag' => '🇩🇪', 'label' => 'Deutsch'],
    'fr' => ['flag' => '🇫🇷', 'label' => 'Français'],
];

const LOCALE_BY_LANG = [
    'nl' => 'nl-NL',
    'en' => 'en-GB',
    'de' => 'de-DE',
    'fr' => 'fr-FR',
];

const TRANSLATIONS = [
    'nl' => [
        'lang.menu_aria' => 'Taal kiezen',
        'lang.switch_to' => 'Schakel naar %s',

        'app.title' => 'FinRap',

        'index.hero.title' => 'Financieel Rapport',
        'index.hero.subtitle' => 'Kies bedrijf, zoek project, genereer de huidige stand en open een opgeslagen rapport.',
        'index.label.company' => 'Bedrijf',
        'index.label.project_no' => 'Projectnummer',
        'index.placeholder.project_no' => 'Bijv. P12345',
        'index.btn.find' => 'Zoek project in BC',
        'index.status.none' => 'Nog geen project gezocht.',
        'index.recent.title' => 'Recent opgezochte projecten',
        'index.loader.finrap' => 'FinRap laden',
        'index.loader.search' => 'Project zoeken in BC',
        'index.loader.wait' => 'Even geduld...',
        'index.loader.prepare' => 'Voorbereiden...',
        'index.loader.done' => 'Klaar.',
        'index.loader.step.search_connect' => 'Verbinding met omgeving maken',
        'index.loader.step.search_fetch' => 'Projectgegevens ophalen',
        'index.loader.step.search_cache' => 'Gecachte maanden laden',
        'index.loader.step.gen_verify' => 'Project verifiëren',
        'index.loader.step.gen_finance' => 'Financiële data ophalen',
        'index.loader.step.gen_costs' => 'Kostenregels opbouwen',
        'index.loader.step.gen_save' => 'Rapport opslaan',
        'index.loader.step.gen_open' => 'Rapport in modal openen',
        'index.modal.title' => 'Financieel Rapport',
        'index.modal.print' => 'Print',
        'index.modal.close' => 'Sluiten',
        'index.modal.report_iframe' => 'Financieel rapport',
        'index.delete.step1.body' => 'Je staat op het punt een Financieel Rapport te verwijderen. Dit is permanent. Weet je het zeker?',
        'index.delete.step2.body' => 'Het verwijderen van een Financieel Rapport kan niet ongedaan gemaakt worden! Weet je het echt zeker?',
        'index.btn.yes' => 'Ja',
        'index.btn.no' => 'Nee',
        'index.js.customer' => 'Debiteur: %s%s',
        'index.js.customer_unavailable' => 'Debiteur: niet beschikbaar',
        'index.js.generate_label' => 'Genereer huidige stand',
        'index.js.generate_btn' => 'Genereer rapport en open',
        'index.js.reports_label' => 'Bestaande rapporten',
        'index.js.reports_empty' => 'Nog geen opgeslagen rapporten.',
        'index.js.btn.open' => 'Open',
        'index.js.report_modal_title' => 'Financieel Rapport %s',
        'index.js.recent_empty' => 'Nog geen projecten gevonden in BC.',
        'index.js.recent_unknown_company' => 'Onbekend bedrijf',
        'index.js.unknown_moment' => 'Onbekend moment',
        'index.js.status.enter_project' => 'Voer een projectnummer in.',
        'index.js.status.searching' => 'Project zoeken in BC...',
        'index.js.status.search_subtitle' => 'Project %s opzoeken',
        'index.js.status.not_found' => 'Project niet gevonden.',
        'index.js.status.found' => 'Project gevonden: %s',
        'index.js.status.generating' => 'Genereren van huidige stand...',
        'index.js.status.generate_failed' => 'Genereren mislukt.',
        'index.js.status.generated' => 'Rapport gegenereerd.',
        'index.js.status.generate_subtitle' => 'FinRap voor %s - huidige stand',
        'index.js.status.report_ready' => 'Rapport gereed, wordt geopend...',
        'index.js.status.delete_failed' => 'Rapport verwijderen mislukt.',
        'index.js.status.deleted' => 'Rapport verwijderd.',
        'index.js.network_error' => 'Netwerkfout: %s',
        'index.js.loader_ellipsis' => '%s...',

        'error.company_invalid' => 'Kies een geldig bedrijf.',
        'error.save_preference_failed' => 'Opslaan van gebruikersvoorkeur is mislukt.',
        'error.project_no_required' => 'Voer een projectnummer in.',
        'error.project_not_found' => 'Project niet gevonden in BC.',
        'error.find_project_failed' => 'Project zoeken mislukt: %s',
        'error.invalid_input' => 'Ongeldige invoer.',
        'error.project_no_missing' => 'Projectnummer ontbreekt.',
        'error.save_report_failed' => 'Opslaan van het rapport is mislukt.',
        'error.generate_failed' => 'Genereren mislukt: %s',
        'error.delete_report_failed' => 'Rapport verwijderen mislukt of bestaat niet.',

        'report.error.invalid_params' => 'Ongeldige parameters. Open dit rapport via de startpagina.',
        'report.error.not_found' => 'Geen opgeslagen rapport gevonden voor dit project. Genereer eerst een rapport op de startpagina.',
        'report.order_number' => 'Ordernummer',
        'report.order_reference' => 'Orderreferentie',
        'report.description' => 'Omschrijving',
        'report.created_at' => 'Rapport gemaakt op',
        'report.customer' => 'Klant',
        'report.project_manager' => 'Projectleider',
        'report.order_type' => 'Ordertype',
        'report.order_date' => 'Orderdatum',
        'report.completed_date' => 'Afrondingsdatum',
        'report.sales_manager' => 'Salesmanager',
        'report.contract_value' => 'Contractwaarde',
        'report.total_direct_cost' => 'Totale directe kosten',
        'report.gross_profit' => 'Brutowinst',
        'report.variance' => 'Verschil budget - EAC',
        'report.order_result' => 'Orderresultaat',
        'report.installments_invoiced' => 'Gefactureerde termijnen',
        'report.installments_received' => 'Ontvangen termijnen',
        'report.tooltip.contract_value' => 'Totale contractwaarde met klant',
        'report.tooltip.total_direct_cost' => 'Totale verwachte directe kosten',
        'report.tooltip.gross_profit' => 'Contractwaarde minus directe kosten',
        'report.tooltip.variance' => 'Verschil tussen begrote en verwachte kosten',
        'report.tooltip.order_result' => 'Netto winst na alle kosten en verwachtingen',
        'report.tooltip.installments_invoiced' => 'Totaal gefactureerde bedrag tot nu toe',
        'report.tooltip.installments_received' => 'Totaal ontvangen betalingen tot nu toe',
        'report.col.cost_group_code' => 'Kostengroepcode',
        'report.col.cost_group_description' => 'Kostengroep omschrijving',
        'report.col.budget_cost' => 'Budget kosten',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Geboekte kosten',
        'report.col.entered_obligations' => 'Ingevoerde verplichtingen',
        'report.col.variance_budget_eac' => 'Verschil budget - EAC',
        'report.tooltip.col.budget_cost' => 'Begrote kosten voor deze kostengroep',
        'report.tooltip.col.eac' => 'Estimate At Completion: totale verwachte kosten aan einde project',
        'report.tooltip.col.booked_cost' => 'Werkelijk geboekte kosten tot nu toe',
        'report.tooltip.col.entered_obligations' => 'Gereserveerde bedragen voor bestellingen/verplichtingen',
        'report.tooltip.col.variance_budget_eac' => 'Verschil tussen begroting en verwachte kosten',
        'report.block.hours_margins' => 'Uren & marges',
        'report.block.expected_outcomes' => 'Verwachte uitkomsten',
        'report.block.installments' => 'Termijnfacturen',
        'report.hours.budget' => 'Budget uren',
        'report.hours.estimated' => 'Geschatte uren',
        'report.hours.booked' => 'Geboekte uren',
        'report.hours.to_go' => 'Resterende uren',
        'report.hours.gross_profit_pct' => 'Brutowinst %',
        'report.hours.order_result_pct' => 'Orderresultaat %',
        'report.hours.variance_pct' => 'Verschil budget - EAC %',
        'report.tooltip.hours.budget' => 'Gepland aantal uren voor het project',
        'report.tooltip.hours.estimated' => 'Verwacht aantal uren op basis van huidige prognose',
        'report.tooltip.hours.booked' => 'Werkelijk geboekt aantal uren tot nu toe',
        'report.tooltip.hours.to_go' => 'Resterende uren: geschat minus geboekt',
        'report.tooltip.hours.gross_profit_pct' => 'Brutowinst in procenten van contractwaarde',
        'report.tooltip.hours.order_result_pct' => 'Netto winst in procenten van contractwaarde',
        'report.tooltip.hours.variance_pct' => 'Verschil tussen budget en EAC in procenten van contractwaarde',
        'report.exp.variance' => 'Verw. verschil budget - EAC',
        'report.exp.order_result' => 'Verwacht orderresultaat',
        'report.exp.ipr_result' => 'IPR-resultaat',
        'report.exp.poc' => 'POC',
        'report.exp.poc_calc' => 'POC-berekening',
        'report.tooltip.exp.variance' => 'Verwachte kosten minus begrote kosten',
        'report.tooltip.exp.order_result' => 'Contractwaarde minus verwachte kosten',
        'report.tooltip.exp.ipr_result' => 'Gerealiseerde winst uit WIP-berekening',
        'report.tooltip.exp.poc' => 'Voortgang in % van het project',
        'report.tooltip.exp.poc_calc' => 'Geboekte kosten totaal gedeeld door EAC totaal, met budgetkosten als fallback',
        'report.termijn.empty' => 'Geen termijnfacturen gevonden.',
        'report.termijn.label' => 'Termijn %d',
        'report.termijn.status.invoiced' => 'Gefactureerd',
        'report.termijn.status.open' => 'Openstaand',
        'report.tooltip.no_source' => 'Geen broninformatie beschikbaar',
        'report.tooltip.fallback' => ' (fallback: ',
        'report.tooltip.fallback_close' => ')',

        'format.hours' => '%s u',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Onbekend',

        'month.01' => 'Januari', 'month.02' => 'Februari', 'month.03' => 'Maart', 'month.04' => 'April',
        'month.05' => 'Mei', 'month.06' => 'Juni', 'month.07' => 'Juli', 'month.08' => 'Augustus',
        'month.09' => 'September', 'month.10' => 'Oktober', 'month.11' => 'November', 'month.12' => 'December',
        'month_lc.01' => 'januari', 'month_lc.02' => 'februari', 'month_lc.03' => 'maart', 'month_lc.04' => 'april',
        'month_lc.05' => 'mei', 'month_lc.06' => 'juni', 'month_lc.07' => 'juli', 'month_lc.08' => 'augustus',
        'month_lc.09' => 'september', 'month_lc.10' => 'oktober', 'month_lc.11' => 'november', 'month_lc.12' => 'december',
    ],

    'en' => [
        'lang.menu_aria' => 'Choose language',
        'lang.switch_to' => 'Switch to %s',

        'app.title' => 'FinRap',

        'index.hero.title' => 'Financial Report',
        'index.hero.subtitle' => 'Select a company, search for a project, generate the current status and open a saved report.',
        'index.label.company' => 'Company',
        'index.label.project_no' => 'Project number',
        'index.placeholder.project_no' => 'e.g. P12345',
        'index.btn.find' => 'Search project in BC',
        'index.status.none' => 'No project searched yet.',
        'index.recent.title' => 'Recently searched projects',
        'index.loader.finrap' => 'Loading FinRap',
        'index.loader.search' => 'Searching project in BC',
        'index.loader.wait' => 'Please wait...',
        'index.loader.prepare' => 'Preparing...',
        'index.loader.done' => 'Done.',
        'index.loader.step.search_connect' => 'Connecting to environment',
        'index.loader.step.search_fetch' => 'Fetching project data',
        'index.loader.step.search_cache' => 'Loading cached months',
        'index.loader.step.gen_verify' => 'Verifying project',
        'index.loader.step.gen_finance' => 'Fetching financial data',
        'index.loader.step.gen_costs' => 'Building cost lines',
        'index.loader.step.gen_save' => 'Saving report',
        'index.loader.step.gen_open' => 'Opening report in modal',
        'index.modal.title' => 'Financial Report',
        'index.modal.print' => 'Print',
        'index.modal.close' => 'Close',
        'index.modal.report_iframe' => 'Financial report',
        'index.delete.step1.body' => 'You are about to delete a Financial Report. This is permanent. Are you sure?',
        'index.delete.step2.body' => 'Deleting a Financial Report cannot be undone! Are you absolutely sure?',
        'index.btn.yes' => 'Yes',
        'index.btn.no' => 'No',
        'index.js.customer' => 'Customer: %s%s',
        'index.js.customer_unavailable' => 'Customer: not available',
        'index.js.generate_label' => 'Generate current status',
        'index.js.generate_btn' => 'Generate report and open',
        'index.js.reports_label' => 'Existing reports',
        'index.js.reports_empty' => 'No saved reports yet.',
        'index.js.btn.open' => 'Open',
        'index.js.report_modal_title' => 'Financial Report %s',
        'index.js.recent_empty' => 'No projects found in BC yet.',
        'index.js.recent_unknown_company' => 'Unknown company',
        'index.js.unknown_moment' => 'Unknown time',
        'index.js.status.enter_project' => 'Enter a project number.',
        'index.js.status.searching' => 'Searching project in BC...',
        'index.js.status.search_subtitle' => 'Searching project %s',
        'index.js.status.not_found' => 'Project not found.',
        'index.js.status.found' => 'Project found: %s',
        'index.js.status.generating' => 'Generating current status...',
        'index.js.status.generate_failed' => 'Generation failed.',
        'index.js.status.generated' => 'Report generated.',
        'index.js.status.generate_subtitle' => 'FinRap for %s - current status',
        'index.js.status.report_ready' => 'Report ready, opening...',
        'index.js.status.delete_failed' => 'Failed to delete report.',
        'index.js.status.deleted' => 'Report deleted.',
        'index.js.network_error' => 'Network error: %s',
        'index.js.loader_ellipsis' => '%s...',

        'error.company_invalid' => 'Choose a valid company.',
        'error.save_preference_failed' => 'Failed to save user preference.',
        'error.project_no_required' => 'Enter a project number.',
        'error.project_not_found' => 'Project not found in BC.',
        'error.find_project_failed' => 'Project search failed: %s',
        'error.invalid_input' => 'Invalid input.',
        'error.project_no_missing' => 'Project number is missing.',
        'error.save_report_failed' => 'Failed to save the report.',
        'error.generate_failed' => 'Generation failed: %s',
        'error.delete_report_failed' => 'Failed to delete report or it does not exist.',

        'report.error.invalid_params' => 'Invalid parameters. Open this report from the home page.',
        'report.error.not_found' => 'No saved report found for this project. Generate a report from the home page first.',
        'report.order_number' => 'Order number',
        'report.order_reference' => 'Order reference',
        'report.description' => 'Description',
        'report.created_at' => 'Report created on',
        'report.customer' => 'Customer',
        'report.project_manager' => 'Project manager',
        'report.order_type' => 'Order type',
        'report.order_date' => 'Order date',
        'report.completed_date' => 'Completed date',
        'report.sales_manager' => 'Sales manager',
        'report.contract_value' => 'Contract value',
        'report.total_direct_cost' => 'Total direct cost',
        'report.gross_profit' => 'Gross profit',
        'report.variance' => 'Variance budget - EAC',
        'report.order_result' => 'Order result',
        'report.installments_invoiced' => 'Installments invoiced',
        'report.installments_received' => 'Installments received',
        'report.tooltip.contract_value' => 'Total contract value with customer',
        'report.tooltip.total_direct_cost' => 'Total expected direct costs',
        'report.tooltip.gross_profit' => 'Contract value minus direct costs',
        'report.tooltip.variance' => 'Difference between budgeted and expected costs',
        'report.tooltip.order_result' => 'Net profit after all costs and expectations',
        'report.tooltip.installments_invoiced' => 'Total invoiced amount to date',
        'report.tooltip.installments_received' => 'Total payments received to date',
        'report.col.cost_group_code' => 'Cost group code',
        'report.col.cost_group_description' => 'Cost group description',
        'report.col.budget_cost' => 'Budget cost',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Booked cost',
        'report.col.entered_obligations' => 'Entered obligations',
        'report.col.variance_budget_eac' => 'Variance budget - EAC',
        'report.tooltip.col.budget_cost' => 'Budgeted costs for this cost group',
        'report.tooltip.col.eac' => 'Estimate At Completion: total expected costs at project end',
        'report.tooltip.col.booked_cost' => 'Actual booked costs to date',
        'report.tooltip.col.entered_obligations' => 'Reserved amounts for orders/obligations',
        'report.tooltip.col.variance_budget_eac' => 'Difference between budget and expected costs',
        'report.block.hours_margins' => 'Hours & margins',
        'report.block.expected_outcomes' => 'Expected outcomes',
        'report.block.installments' => 'Installment invoices',
        'report.hours.budget' => 'Budget hours',
        'report.hours.estimated' => 'Estimated hours',
        'report.hours.booked' => 'Booked hours',
        'report.hours.to_go' => 'Hours to go',
        'report.hours.gross_profit_pct' => 'Gross profit %',
        'report.hours.order_result_pct' => 'Order result %',
        'report.hours.variance_pct' => 'Variance budget - EAC %',
        'report.tooltip.hours.budget' => 'Planned number of hours for the project',
        'report.tooltip.hours.estimated' => 'Expected hours based on current forecast',
        'report.tooltip.hours.booked' => 'Actual booked hours to date',
        'report.tooltip.hours.to_go' => 'Remaining hours: estimated minus booked',
        'report.tooltip.hours.gross_profit_pct' => 'Gross profit as a percentage of contract value',
        'report.tooltip.hours.order_result_pct' => 'Net profit as a percentage of contract value',
        'report.tooltip.hours.variance_pct' => 'Difference between budget and EAC as a percentage of contract value',
        'report.exp.variance' => 'Exp. variance budget - EAC',
        'report.exp.order_result' => 'Expected order result',
        'report.exp.ipr_result' => 'IPR result',
        'report.exp.poc' => 'POC',
        'report.exp.poc_calc' => 'POC calc.',
        'report.tooltip.exp.variance' => 'Expected costs minus budgeted costs',
        'report.tooltip.exp.order_result' => 'Contract value minus expected costs',
        'report.tooltip.exp.ipr_result' => 'Realised profit from WIP calculation',
        'report.tooltip.exp.poc' => 'Progress as a percentage of the project',
        'report.tooltip.exp.poc_calc' => 'Total booked cost divided by total EAC, with budget cost as fallback',
        'report.termijn.empty' => 'No installment invoices found.',
        'report.termijn.label' => 'Installment %d',
        'report.termijn.status.invoiced' => 'Invoiced',
        'report.termijn.status.open' => 'Outstanding',
        'report.tooltip.no_source' => 'No source information available',
        'report.tooltip.fallback' => ' (fallback: ',
        'report.tooltip.fallback_close' => ')',

        'format.hours' => '%s h',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Unknown',

        'month.01' => 'January', 'month.02' => 'February', 'month.03' => 'March', 'month.04' => 'April',
        'month.05' => 'May', 'month.06' => 'June', 'month.07' => 'July', 'month.08' => 'August',
        'month.09' => 'September', 'month.10' => 'October', 'month.11' => 'November', 'month.12' => 'December',
        'month_lc.01' => 'January', 'month_lc.02' => 'February', 'month_lc.03' => 'March', 'month_lc.04' => 'April',
        'month_lc.05' => 'May', 'month_lc.06' => 'June', 'month_lc.07' => 'July', 'month_lc.08' => 'August',
        'month_lc.09' => 'September', 'month_lc.10' => 'October', 'month_lc.11' => 'November', 'month_lc.12' => 'December',
    ],

    'de' => [
        'lang.menu_aria' => 'Sprache wählen',
        'lang.switch_to' => 'Wechseln zu %s',

        'app.title' => 'FinRap',

        'index.hero.title' => 'Finanzbericht',
        'index.hero.subtitle' => 'Unternehmen wählen, Projekt suchen, aktuellen Stand erzeugen und gespeicherten Bericht öffnen.',
        'index.label.company' => 'Unternehmen',
        'index.label.project_no' => 'Projektnummer',
        'index.placeholder.project_no' => 'z. B. P12345',
        'index.btn.find' => 'Projekt in BC suchen',
        'index.status.none' => 'Noch kein Projekt gesucht.',
        'index.recent.title' => 'Zuletzt gesuchte Projekte',
        'index.loader.finrap' => 'FinRap wird geladen',
        'index.loader.search' => 'Projekt in BC suchen',
        'index.loader.wait' => 'Bitte warten...',
        'index.loader.prepare' => 'Vorbereiten...',
        'index.loader.done' => 'Fertig.',
        'index.loader.step.search_connect' => 'Verbindung zur Umgebung herstellen',
        'index.loader.step.search_fetch' => 'Projektdaten abrufen',
        'index.loader.step.search_cache' => 'Gecachte Monate laden',
        'index.loader.step.gen_verify' => 'Projekt prüfen',
        'index.loader.step.gen_finance' => 'Finanzdaten abrufen',
        'index.loader.step.gen_costs' => 'Kostenzeilen aufbauen',
        'index.loader.step.gen_save' => 'Bericht speichern',
        'index.loader.step.gen_open' => 'Bericht im Modal öffnen',
        'index.modal.title' => 'Finanzbericht',
        'index.modal.print' => 'Drucken',
        'index.modal.close' => 'Schließen',
        'index.modal.report_iframe' => 'Finanzbericht',
        'index.delete.step1.body' => 'Sie sind dabei, einen Finanzbericht zu löschen. Dies ist dauerhaft. Sind Sie sicher?',
        'index.delete.step2.body' => 'Das Löschen eines Finanzberichts kann nicht rückgängig gemacht werden! Sind Sie wirklich sicher?',
        'index.btn.yes' => 'Ja',
        'index.btn.no' => 'Nein',
        'index.js.customer' => 'Debitor: %s%s',
        'index.js.customer_unavailable' => 'Debitor: nicht verfügbar',
        'index.js.generate_label' => 'Aktuellen Stand erzeugen',
        'index.js.generate_btn' => 'Bericht erzeugen und öffnen',
        'index.js.reports_label' => 'Vorhandene Berichte',
        'index.js.reports_empty' => 'Noch keine gespeicherten Berichte.',
        'index.js.btn.open' => 'Öffnen',
        'index.js.report_modal_title' => 'Finanzbericht %s',
        'index.js.recent_empty' => 'Noch keine Projekte in BC gefunden.',
        'index.js.recent_unknown_company' => 'Unbekanntes Unternehmen',
        'index.js.unknown_moment' => 'Unbekannter Zeitpunkt',
        'index.js.status.enter_project' => 'Geben Sie eine Projektnummer ein.',
        'index.js.status.searching' => 'Projekt in BC suchen...',
        'index.js.status.search_subtitle' => 'Projekt %s suchen',
        'index.js.status.not_found' => 'Projekt nicht gefunden.',
        'index.js.status.found' => 'Projekt gefunden: %s',
        'index.js.status.generating' => 'Aktuellen Stand erzeugen...',
        'index.js.status.generate_failed' => 'Erzeugen fehlgeschlagen.',
        'index.js.status.generated' => 'Bericht erzeugt.',
        'index.js.status.generate_subtitle' => 'FinRap für %s - aktueller Stand',
        'index.js.status.report_ready' => 'Bericht bereit, wird geöffnet...',
        'index.js.status.delete_failed' => 'Bericht löschen fehlgeschlagen.',
        'index.js.status.deleted' => 'Bericht gelöscht.',
        'index.js.network_error' => 'Netzwerkfehler: %s',
        'index.js.loader_ellipsis' => '%s...',

        'error.company_invalid' => 'Wählen Sie ein gültiges Unternehmen.',
        'error.save_preference_failed' => 'Speichern der Benutzereinstellung fehlgeschlagen.',
        'error.project_no_required' => 'Geben Sie eine Projektnummer ein.',
        'error.project_not_found' => 'Projekt in BC nicht gefunden.',
        'error.find_project_failed' => 'Projektsuche fehlgeschlagen: %s',
        'error.invalid_input' => 'Ungültige Eingabe.',
        'error.project_no_missing' => 'Projektnummer fehlt.',
        'error.save_report_failed' => 'Speichern des Berichts fehlgeschlagen.',
        'error.generate_failed' => 'Erzeugen fehlgeschlagen: %s',
        'error.delete_report_failed' => 'Bericht löschen fehlgeschlagen oder existiert nicht.',

        'report.error.invalid_params' => 'Ungültige Parameter. Öffnen Sie diesen Bericht über die Startseite.',
        'report.error.not_found' => 'Kein gespeicherter Bericht für dieses Projekt gefunden. Erzeugen Sie zuerst einen Bericht auf der Startseite.',
        'report.order_number' => 'Auftragsnummer',
        'report.order_reference' => 'Auftragsreferenz',
        'report.description' => 'Beschreibung',
        'report.created_at' => 'Bericht erstellt am',
        'report.customer' => 'Kunde',
        'report.project_manager' => 'Projektleiter',
        'report.order_type' => 'Auftragstyp',
        'report.order_date' => 'Auftragsdatum',
        'report.completed_date' => 'Abschlussdatum',
        'report.sales_manager' => 'Vertriebsleiter',
        'report.contract_value' => 'Vertragswert',
        'report.total_direct_cost' => 'Gesamte direkte Kosten',
        'report.gross_profit' => 'Bruttogewinn',
        'report.variance' => 'Abweichung Budget - EAC',
        'report.order_result' => 'Auftragsergebnis',
        'report.installments_invoiced' => 'Fakturierte Raten',
        'report.installments_received' => 'Erhaltene Raten',
        'report.tooltip.contract_value' => 'Gesamter Vertragswert mit Kunde',
        'report.tooltip.total_direct_cost' => 'Gesamte erwartete direkte Kosten',
        'report.tooltip.gross_profit' => 'Vertragswert minus direkte Kosten',
        'report.tooltip.variance' => 'Unterschied zwischen budgetierten und erwarteten Kosten',
        'report.tooltip.order_result' => 'Nettogewinn nach allen Kosten und Erwartungen',
        'report.tooltip.installments_invoiced' => 'Gesamter fakturierter Betrag bis heute',
        'report.tooltip.installments_received' => 'Gesamte erhaltene Zahlungen bis heute',
        'report.col.cost_group_code' => 'Kostengruppencode',
        'report.col.cost_group_description' => 'Kostengruppenbeschreibung',
        'report.col.budget_cost' => 'Budgetkosten',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Gebuchte Kosten',
        'report.col.entered_obligations' => 'Erfasste Verpflichtungen',
        'report.col.variance_budget_eac' => 'Abweichung Budget - EAC',
        'report.tooltip.col.budget_cost' => 'Budgetierte Kosten für diese Kostengruppe',
        'report.tooltip.col.eac' => 'Estimate At Completion: erwartete Gesamtkosten am Projektende',
        'report.tooltip.col.booked_cost' => 'Tatsächlich gebuchte Kosten bis heute',
        'report.tooltip.col.entered_obligations' => 'Reservierte Beträge für Bestellungen/Verpflichtungen',
        'report.tooltip.col.variance_budget_eac' => 'Unterschied zwischen Budget und erwarteten Kosten',
        'report.block.hours_margins' => 'Stunden & Margen',
        'report.block.expected_outcomes' => 'Erwartete Ergebnisse',
        'report.block.installments' => 'Ratenrechnungen',
        'report.hours.budget' => 'Budgetstunden',
        'report.hours.estimated' => 'Geschätzte Stunden',
        'report.hours.booked' => 'Gebuchte Stunden',
        'report.hours.to_go' => 'Verbleibende Stunden',
        'report.hours.gross_profit_pct' => 'Bruttogewinn %',
        'report.hours.order_result_pct' => 'Auftragsergebnis %',
        'report.hours.variance_pct' => 'Abweichung Budget - EAC %',
        'report.tooltip.hours.budget' => 'Geplante Stunden für das Projekt',
        'report.tooltip.hours.estimated' => 'Erwartete Stunden basierend auf aktueller Prognose',
        'report.tooltip.hours.booked' => 'Tatsächlich gebuchte Stunden bis heute',
        'report.tooltip.hours.to_go' => 'Verbleibende Stunden: geschätzt minus gebucht',
        'report.tooltip.hours.gross_profit_pct' => 'Bruttogewinn in Prozent des Vertragswerts',
        'report.tooltip.hours.order_result_pct' => 'Nettogewinn in Prozent des Vertragswerts',
        'report.tooltip.hours.variance_pct' => 'Unterschied zwischen Budget und EAC in Prozent des Vertragswerts',
        'report.exp.variance' => 'Erw. Abweichung Budget - EAC',
        'report.exp.order_result' => 'Erwartetes Auftragsergebnis',
        'report.exp.ipr_result' => 'IPR-Ergebnis',
        'report.exp.poc' => 'POC',
        'report.exp.poc_calc' => 'POC-Berechnung',
        'report.tooltip.exp.variance' => 'Erwartete Kosten minus budgetierte Kosten',
        'report.tooltip.exp.order_result' => 'Vertragswert minus erwartete Kosten',
        'report.tooltip.exp.ipr_result' => 'Realisierter Gewinn aus WIP-Berechnung',
        'report.tooltip.exp.poc' => 'Fortschritt in % des Projekts',
        'report.tooltip.exp.poc_calc' => 'Gebuchte Kosten gesamt geteilt durch EAC gesamt, mit Budgetkosten als Fallback',
        'report.termijn.empty' => 'Keine Ratenrechnungen gefunden.',
        'report.termijn.label' => 'Rate %d',
        'report.termijn.status.invoiced' => 'Fakturiert',
        'report.termijn.status.open' => 'Offen',
        'report.tooltip.no_source' => 'Keine Quellinformationen verfügbar',
        'report.tooltip.fallback' => ' (Fallback: ',
        'report.tooltip.fallback_close' => ')',

        'format.hours' => '%s Std.',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Unbekannt',

        'month.01' => 'Januar', 'month.02' => 'Februar', 'month.03' => 'März', 'month.04' => 'April',
        'month.05' => 'Mai', 'month.06' => 'Juni', 'month.07' => 'Juli', 'month.08' => 'August',
        'month.09' => 'September', 'month.10' => 'Oktober', 'month.11' => 'November', 'month.12' => 'Dezember',
        'month_lc.01' => 'Januar', 'month_lc.02' => 'Februar', 'month_lc.03' => 'März', 'month_lc.04' => 'April',
        'month_lc.05' => 'Mai', 'month_lc.06' => 'Juni', 'month_lc.07' => 'Juli', 'month_lc.08' => 'August',
        'month_lc.09' => 'September', 'month_lc.10' => 'Oktober', 'month_lc.11' => 'November', 'month_lc.12' => 'Dezember',
    ],

    'fr' => [
        'lang.menu_aria' => 'Choisir la langue',
        'lang.switch_to' => 'Passer en %s',

        'app.title' => 'FinRap',

        'index.hero.title' => 'Rapport financier',
        'index.hero.subtitle' => 'Choisissez une société, recherchez un projet, générez l\'état actuel et ouvrez un rapport enregistré.',
        'index.label.company' => 'Société',
        'index.label.project_no' => 'Numéro de projet',
        'index.placeholder.project_no' => 'p. ex. P12345',
        'index.btn.find' => 'Rechercher le projet dans BC',
        'index.status.none' => 'Aucun projet recherché pour le moment.',
        'index.recent.title' => 'Projets recherchés récemment',
        'index.loader.finrap' => 'Chargement de FinRap',
        'index.loader.search' => 'Recherche du projet dans BC',
        'index.loader.wait' => 'Veuillez patienter...',
        'index.loader.prepare' => 'Préparation...',
        'index.loader.done' => 'Terminé.',
        'index.loader.step.search_connect' => 'Connexion à l\'environnement',
        'index.loader.step.search_fetch' => 'Récupération des données du projet',
        'index.loader.step.search_cache' => 'Chargement des mois en cache',
        'index.loader.step.gen_verify' => 'Vérification du projet',
        'index.loader.step.gen_finance' => 'Récupération des données financières',
        'index.loader.step.gen_costs' => 'Construction des lignes de coûts',
        'index.loader.step.gen_save' => 'Enregistrement du rapport',
        'index.loader.step.gen_open' => 'Ouverture du rapport dans la fenêtre',
        'index.modal.title' => 'Rapport financier',
        'index.modal.print' => 'Imprimer',
        'index.modal.close' => 'Fermer',
        'index.modal.report_iframe' => 'Rapport financier',
        'index.delete.step1.body' => 'Vous êtes sur le point de supprimer un rapport financier. Cette action est permanente. Êtes-vous sûr ?',
        'index.delete.step2.body' => 'La suppression d\'un rapport financier est irréversible ! Êtes-vous vraiment sûr ?',
        'index.btn.yes' => 'Oui',
        'index.btn.no' => 'Non',
        'index.js.customer' => 'Client : %s%s',
        'index.js.customer_unavailable' => 'Client : non disponible',
        'index.js.generate_label' => 'Générer l\'état actuel',
        'index.js.generate_btn' => 'Générer le rapport et ouvrir',
        'index.js.reports_label' => 'Rapports existants',
        'index.js.reports_empty' => 'Aucun rapport enregistré pour le moment.',
        'index.js.btn.open' => 'Ouvrir',
        'index.js.report_modal_title' => 'Rapport financier %s',
        'index.js.recent_empty' => 'Aucun projet trouvé dans BC pour le moment.',
        'index.js.recent_unknown_company' => 'Société inconnue',
        'index.js.unknown_moment' => 'Moment inconnu',
        'index.js.status.enter_project' => 'Saisissez un numéro de projet.',
        'index.js.status.searching' => 'Recherche du projet dans BC...',
        'index.js.status.search_subtitle' => 'Recherche du projet %s',
        'index.js.status.not_found' => 'Projet introuvable.',
        'index.js.status.found' => 'Projet trouvé : %s',
        'index.js.status.generating' => 'Génération de l\'état actuel...',
        'index.js.status.generate_failed' => 'Échec de la génération.',
        'index.js.status.generated' => 'Rapport généré.',
        'index.js.status.generate_subtitle' => 'FinRap pour %s - état actuel',
        'index.js.status.report_ready' => 'Rapport prêt, ouverture...',
        'index.js.status.delete_failed' => 'Échec de la suppression du rapport.',
        'index.js.status.deleted' => 'Rapport supprimé.',
        'index.js.network_error' => 'Erreur réseau : %s',
        'index.js.loader_ellipsis' => '%s...',

        'error.company_invalid' => 'Choisissez une société valide.',
        'error.save_preference_failed' => 'Échec de l\'enregistrement de la préférence utilisateur.',
        'error.project_no_required' => 'Saisissez un numéro de projet.',
        'error.project_not_found' => 'Projet introuvable dans BC.',
        'error.find_project_failed' => 'Échec de la recherche du projet : %s',
        'error.invalid_input' => 'Saisie invalide.',
        'error.project_no_missing' => 'Numéro de projet manquant.',
        'error.save_report_failed' => 'Échec de l\'enregistrement du rapport.',
        'error.generate_failed' => 'Échec de la génération : %s',
        'error.delete_report_failed' => 'Échec de la suppression du rapport ou rapport inexistant.',

        'report.error.invalid_params' => 'Paramètres invalides. Ouvrez ce rapport depuis la page d\'accueil.',
        'report.error.not_found' => 'Aucun rapport enregistré trouvé pour ce projet. Générez d\'abord un rapport depuis la page d\'accueil.',
        'report.order_number' => 'Numéro de commande',
        'report.order_reference' => 'Référence commande',
        'report.description' => 'Description',
        'report.created_at' => 'Rapport créé le',
        'report.customer' => 'Client',
        'report.project_manager' => 'Chef de projet',
        'report.order_type' => 'Type de commande',
        'report.order_date' => 'Date de commande',
        'report.completed_date' => 'Date de clôture',
        'report.sales_manager' => 'Responsable commercial',
        'report.contract_value' => 'Valeur du contrat',
        'report.total_direct_cost' => 'Coût direct total',
        'report.gross_profit' => 'Marge brute',
        'report.variance' => 'Écart budget - EAC',
        'report.order_result' => 'Résultat commande',
        'report.installments_invoiced' => 'Acomptes facturés',
        'report.installments_received' => 'Acomptes reçus',
        'report.tooltip.contract_value' => 'Valeur totale du contrat avec le client',
        'report.tooltip.total_direct_cost' => 'Coûts directs totaux attendus',
        'report.tooltip.gross_profit' => 'Valeur du contrat moins coûts directs',
        'report.tooltip.variance' => 'Différence entre coûts budgétés et attendus',
        'report.tooltip.order_result' => 'Profit net après tous les coûts et attentes',
        'report.tooltip.installments_invoiced' => 'Montant total facturé à ce jour',
        'report.tooltip.installments_received' => 'Paiements totaux reçus à ce jour',
        'report.col.cost_group_code' => 'Code groupe de coûts',
        'report.col.cost_group_description' => 'Description groupe de coûts',
        'report.col.budget_cost' => 'Coût budget',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Coût comptabilisé',
        'report.col.entered_obligations' => 'Engagements saisis',
        'report.col.variance_budget_eac' => 'Écart budget - EAC',
        'report.tooltip.col.budget_cost' => 'Coûts budgétés pour ce groupe de coûts',
        'report.tooltip.col.eac' => 'Estimate At Completion : coûts totaux attendus en fin de projet',
        'report.tooltip.col.booked_cost' => 'Coûts réellement comptabilisés à ce jour',
        'report.tooltip.col.entered_obligations' => 'Montants réservés pour commandes/engagements',
        'report.tooltip.col.variance_budget_eac' => 'Différence entre budget et coûts attendus',
        'report.block.hours_margins' => 'Heures & marges',
        'report.block.expected_outcomes' => 'Résultats attendus',
        'report.block.installments' => 'Factures d\'acompte',
        'report.hours.budget' => 'Heures budget',
        'report.hours.estimated' => 'Heures estimées',
        'report.hours.booked' => 'Heures comptabilisées',
        'report.hours.to_go' => 'Heures restantes',
        'report.hours.gross_profit_pct' => 'Marge brute %',
        'report.hours.order_result_pct' => 'Résultat commande %',
        'report.hours.variance_pct' => 'Écart budget - EAC %',
        'report.tooltip.hours.budget' => 'Nombre d\'heures planifiées pour le projet',
        'report.tooltip.hours.estimated' => 'Heures attendues selon la prévision actuelle',
        'report.tooltip.hours.booked' => 'Heures réellement comptabilisées à ce jour',
        'report.tooltip.hours.to_go' => 'Heures restantes : estimées moins comptabilisées',
        'report.tooltip.hours.gross_profit_pct' => 'Marge brute en pourcentage de la valeur du contrat',
        'report.tooltip.hours.order_result_pct' => 'Profit net en pourcentage de la valeur du contrat',
        'report.tooltip.hours.variance_pct' => 'Différence entre budget et EAC en pourcentage de la valeur du contrat',
        'report.exp.variance' => 'Écart attendu budget - EAC',
        'report.exp.order_result' => 'Résultat commande attendu',
        'report.exp.ipr_result' => 'Résultat IPR',
        'report.exp.poc' => 'POC',
        'report.exp.poc_calc' => 'Calcul POC',
        'report.tooltip.exp.variance' => 'Coûts attendus moins coûts budgétés',
        'report.tooltip.exp.order_result' => 'Valeur du contrat moins coûts attendus',
        'report.tooltip.exp.ipr_result' => 'Profit réalisé selon le calcul WIP',
        'report.tooltip.exp.poc' => 'Avancement en % du projet',
        'report.tooltip.exp.poc_calc' => 'Coût comptabilisé total divisé par EAC total, avec coût budget en secours',
        'report.termijn.empty' => 'Aucune facture d\'acompte trouvée.',
        'report.termijn.label' => 'Acompte %d',
        'report.termijn.status.invoiced' => 'Facturé',
        'report.termijn.status.open' => 'En attente',
        'report.tooltip.no_source' => 'Aucune information source disponible',
        'report.tooltip.fallback' => ' (secours : ',
        'report.tooltip.fallback_close' => ')',

        'format.hours' => '%s h',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Inconnu',

        'month.01' => 'Janvier', 'month.02' => 'Février', 'month.03' => 'Mars', 'month.04' => 'Avril',
        'month.05' => 'Mai', 'month.06' => 'Juin', 'month.07' => 'Juillet', 'month.08' => 'Août',
        'month.09' => 'Septembre', 'month.10' => 'Octobre', 'month.11' => 'Novembre', 'month.12' => 'Décembre',
        'month_lc.01' => 'janvier', 'month_lc.02' => 'février', 'month_lc.03' => 'mars', 'month_lc.04' => 'avril',
        'month_lc.05' => 'mai', 'month_lc.06' => 'juin', 'month_lc.07' => 'juillet', 'month_lc.08' => 'août',
        'month_lc.09' => 'septembre', 'month_lc.10' => 'octobre', 'month_lc.11' => 'novembre', 'month_lc.12' => 'décembre',
    ],
];

/**
 * Functies
 */

function getUserPrefsPath(string $email): ?string
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $dir = __DIR__ . '/../data/user_prefs';
    $filename = preg_replace('/[^a-z0-9._\-]/', '_', $email) . '.json';
    return $dir . '/' . $filename;
}

function loadUserPrefs(string $email): array
{
    $path = getUserPrefsPath($email);
    if ($path === null || !is_file($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveUserPref(string $email, string $key, mixed $value): void
{
    $path = getUserPrefsPath($email);
    if ($path === null) {
        return;
    }
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $prefs = loadUserPrefs($email);
    $prefs[$key] = $value;
    file_put_contents($path, json_encode($prefs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function getCurrentLanguage(): string
{
    $lang = (string) ($_SESSION['lang'] ?? 'nl');
    return array_key_exists($lang, SUPPORTED_LANGUAGES) ? $lang : 'nl';
}

function getHtmlLang(): string
{
    return getCurrentLanguage();
}

function getDateLocale(): string
{
    $lang = getCurrentLanguage();
    return LOCALE_BY_LANG[$lang] ?? 'nl-NL';
}

/**
 * Geeft de vertaling voor $key in de actieve taal.
 * Extra $args worden via sprintf ingevoegd (voor %d, %s, etc.).
 */
function LOC(string $key, mixed ...$args): string
{
    $lang = getCurrentLanguage();
    $translations = TRANSLATIONS[$lang] ?? TRANSLATIONS['nl'];
    $string = $translations[$key] ?? (TRANSLATIONS['nl'][$key] ?? $key);

    return $args !== [] ? sprintf($string, ...$args) : $string;
}

function localizationFlagSvg(string $lang): string
{
    $svg = FLAG_SVGS[$lang] ?? '';
    if ($svg === '') {
        return '';
    }

    $safeLang = preg_replace('/[^a-z0-9]/', '', $lang) ?? $lang;
    return str_replace(
        ['id="a"', 'url(#a)', 'id="b"', 'url(#b)'],
        ['id="flag-' . $safeLang . '-a"', 'url(#flag-' . $safeLang . '-a)', 'id="flag-' . $safeLang . '-b"', 'url(#flag-' . $safeLang . '-b)'],
        $svg
    );
}

function localizationUrlWithLang(string $lang): string
{
    $params = $_GET;
    unset($params['lang']);
    $params['lang'] = $lang;
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
    $query = http_build_query($params);
    return $path . ($query !== '' ? '?' . $query : '');
}

function localizationJsTranslations(array $keys): string
{
    $payload = [];
    foreach ($keys as $key) {
        $payload[$key] = LOC($key);
    }

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function renderLanguageSwitcherStyles(): void
{
    echo <<<'CSS'
<style>
.lang-switcher {
    position: fixed;
    top: 12px;
    right: 12px;
    z-index: 5000;
    font-family: inherit;
}
.lang-switcher-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 30px;
    padding: 0;
    border: 1px solid rgba(0, 82, 155, 0.25);
    border-radius: 6px;
    background: #ffffff;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
    cursor: pointer;
}
.lang-switcher-toggle:hover {
    background: #f2f9ff;
}
.lang-switcher-toggle svg {
    width: 28px;
    height: auto;
    display: block;
    border-radius: 2px;
    overflow: hidden;
}
.lang-switcher-menu {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 160px;
    margin: 0;
    padding: 6px;
    list-style: none;
    background: #ffffff;
    border: 1px solid #c9d7eb;
    border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
    display: none;
}
.lang-switcher.is-open .lang-switcher-menu {
    display: block;
}
.lang-switcher-item a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    color: var(--kvt-text, #1f2937);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}
.lang-switcher-item a:hover {
    background: #edf7ff;
}
.lang-switcher-item.is-active a {
    background: #e6f4ff;
}
.lang-switcher-item svg {
    width: 24px;
    height: auto;
    flex-shrink: 0;
    border-radius: 2px;
    overflow: hidden;
}
@media print {
    .lang-switcher {
        display: none !important;
    }
}
</style>
CSS;
}

function renderLanguageSwitcher(): void
{
    $current = getCurrentLanguage();
    $menuAria = htmlspecialchars(LOC('lang.menu_aria'), ENT_QUOTES);

    echo '<div class="lang-switcher" data-lang-switcher>';
    echo '<button type="button" class="lang-switcher-toggle" aria-haspopup="true" aria-expanded="false" aria-label="' . $menuAria . '">';
    echo localizationFlagSvg($current);
    echo '</button>';
    echo '<ul class="lang-switcher-menu" role="menu">';

    foreach (SUPPORTED_LANGUAGES as $code => $meta) {
        if ($code === $current) {
            continue;
        }

        $label = (string) ($meta['label'] ?? $code);
        $href = htmlspecialchars(localizationUrlWithLang($code), ENT_QUOTES);
        $title = htmlspecialchars(LOC('lang.switch_to', $label), ENT_QUOTES);

        echo '<li class="lang-switcher-item" role="none">';
        echo '<a role="menuitem" href="' . $href . '" title="' . $title . '">';
        echo localizationFlagSvg($code);
        echo '<span>' . htmlspecialchars($label) . '</span>';
        echo '</a>';
        echo '</li>';
    }

    echo '</ul>';
    echo '</div>';
}

function renderLanguageSwitcherScript(): void
{
    echo <<<'JS'
<script>
(function () {
    document.querySelectorAll('[data-lang-switcher]').forEach(function (root) {
        var toggle = root.querySelector('.lang-switcher-toggle');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = root.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            root.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });

        root.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });
})();
</script>
JS;
}

/**
 * Page load
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (!isset($_SESSION['lang'])) {
    $prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    if ($prefEmail !== '') {
        $savedPrefs = loadUserPrefs($prefEmail);
        if (isset($savedPrefs['lang']) && array_key_exists($savedPrefs['lang'], SUPPORTED_LANGUAGES)) {
            $_SESSION['lang'] = $savedPrefs['lang'];
        }
    }
}

if (!isset($_SESSION['lang']) || !array_key_exists((string) $_SESSION['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['lang'] = 'nl';
}

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $requestedLang = (string) $_GET['lang'];
    $langChanged = $requestedLang !== getCurrentLanguage();
    $_SESSION['lang'] = $requestedLang;
    $prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    if ($prefEmail !== '' && $langChanged) {
        saveUserPref($prefEmail, 'lang', $requestedLang);
    }

    $isApiAction = isset($_GET['action']) && trim((string) $_GET['action']) !== '';
    if (!$isApiAction && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
        $params = $_GET;
        unset($params['lang']);
        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
        $query = http_build_query($params);
        header('Location: ' . $path . ($query !== '' ? '?' . $query : ''));
        exit;
    }
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

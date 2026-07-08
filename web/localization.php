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
        'index.delete.step3.body' => 'Als u een automatische nachtelijke rapportage verwijdert, zal dit de accuratie van het projectdashboard van dit project verminderen.',
        'index.generate.missing_task_rows.body' => 'Taakregels van dit project ontbreken; het rapport wordt daardoor mogelijk onjuist weergegeven. Wilt u doorgaan?',
        'index.btn.yes' => 'Ja',
        'index.btn.no' => 'Nee',
        'index.js.customer' => 'Debiteur: %s%s',
        'index.js.customer_unavailable' => 'Debiteur: niet beschikbaar',
        'index.js.generate_label' => 'Genereer huidige stand',
        'index.js.generate_btn' => 'Genereer rapport en open',
        'index.js.reports_label' => 'Bestaande rapporten',
        'index.js.show_auto_reports' => 'Toon automatische dagelijkse rapportages',
        'index.js.reports_empty' => 'Nog geen opgeslagen rapporten.',
        'index.js.reports_empty_filtered' => 'Geen handmatige rapporten zichtbaar. Schakel automatische rapportages in om meer te zien.',
        'index.js.reports_load_more' => 'Toon oudere rapporten (%s van %s)',
        'index.js.reports_loading_more' => 'Oudere rapporten laden...',
        'index.js.btn.open' => 'Open',
        'index.js.comments_btn' => '💬%s',
        'index.js.comments_modal_title' => 'Opmerkingen',
        'index.js.comments_loading' => 'Opmerkingen laden...',
        'index.js.comments_empty' => 'Nog geen opmerkingen op dit rapport.',
        'index.js.comments_send' => 'Verstuur',
        'index.js.comments_save' => 'Opslaan',
        'index.js.comments_cancel' => 'Annuleren',
        'index.js.comments_edit' => 'Bewerken',
        'index.js.comments_edited' => 'bewerkt',
        'index.js.comments_placeholder' => 'Typ een opmerking...',
        'index.js.comments_load_failed' => 'Opmerkingen laden mislukt.',
        'index.js.comments_send_failed' => 'Opmerking versturen mislukt.',
        'index.js.comments_update_failed' => 'Opmerking bewerken mislukt.',
        'index.js.dashboard_btn' => 'Projectdashboard',
        'index.js.dashboard_modal_title' => 'Projectdashboard %s',
        'index.js.dashboard_loading' => 'Dashboardgegevens laden...',
        'index.js.dashboard_empty' => 'Nog geen dashboardgegevens beschikbaar voor dit project.',
        'index.js.dashboard_load_failed' => 'Dashboard laden mislukt.',
        'index.js.dashboard_chart_poc_title' => 'POC over tijd',
        'index.js.dashboard_chart_poc_baseline' => 'POC Baseline',
        'index.js.dashboard_chart_poc_eac' => 'POC EAC',
        'index.js.dashboard_chart_y_axis' => 'POC (%)',
        'index.js.dashboard_chart_cost_title' => 'Kostenverdeling (geboekt)',
        'index.js.dashboard_chart_cost_subtitle' => 'Op basis van het meest recente rapport',
        'index.js.dashboard_chart_eac_title' => 'Kostenverdeling (EAC)',
        'index.js.dashboard_chart_invoiced_title' => 'Gefactureerd per kostengroep',
        'index.js.dashboard_chart_installments_title' => 'Ontvangen termijnen',
        'index.js.dashboard_chart_cost_major' => 'Totaalregel',
        'index.js.dashboard_chart_cost_subtotal' => 'Subtotaalregel',
        'index.js.dashboard_latest_report_note' => 'Meest recente rapport: %s (%s)',
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
        'error.dashboard_failed' => 'Dashboard laden mislukt: %s',
        'error.delete_report_failed' => 'Rapport verwijderen mislukt of bestaat niet.',
        'error.report_not_found' => 'Rapport niet gevonden.',
        'error.comment_auth_required' => 'Je moet ingelogd zijn om opmerkingen te plaatsen.',
        'error.comment_empty' => 'Voer een opmerking in.',
        'error.comment_save_failed' => 'Opmerking opslaan mislukt.',
        'error.comment_update_failed' => 'Opmerking bewerken mislukt.',
        'error.report_overrides_locked' => 'Dit is niet het meest recente rapport. EAC-wijzigingen zijn alleen toegestaan op het nieuwste rapport.',

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
        'report.header.type' => 'Type',
        'report.total_direct_cost' => 'VC Kosten',
        'report.gross_profit' => 'Brutowinst',
        'report.variance' => 'Verschil VC - EAC',
        'report.order_result' => 'Orderresultaat',
        'report.installments_invoiced' => 'Gefactureerde termijnen',
        'report.installments_received' => 'Ontvangen termijnen',
        'report.tooltip.contract_value' => 'Som van LVS_Contract_Total_Price_2 uit ProjectTaken voor subregels onder totaalregel 000 (zonder meerwerk)',
        'report.tooltip.header.type' => 'PRJ voor het project; overige waarden voor meerwerk (LVS_Job_Change_Order_No)',
        'report.tooltip.header.change_order_contract' => 'regels met LVS_Job_Change_Order_No',
        'report.tooltip.total_direct_cost' => 'Waarden van totaalregel 000 (kostengroeptabel)',
        'report.tooltip.total_budget_revenue' => 'Total_Price_LCY uit JobBaselineLines op totaalregel 000 (Type = GB-rekening, No = 800000). Excl. BTW.',
        'report.tooltip.gross_profit' => 'Contractwaarde minus directe kosten',
        'report.tooltip.variance' => 'Verschil tussen begrote en verwachte kosten',
        'report.tooltip.order_result' => 'Brutowinst plus verschil tussen begrote en verwachte kosten',
        'report.tooltip.installments_invoiced' => 'Som van Contract_Invoiced_Price uit ProjectTaken (PRJ-regel: zonder LVS_Job_Change_Order_No)',
        'report.tooltip.installments_received' => 'Som van ontvangen bedragen uit klantposten gekoppeld aan het project (Amount_LCY - Remaining_Amt_LCY). Excl. BTW.',
        'report.col.cost_group_code' => 'Kostengroepcode',
        'report.col.cost_group_description' => 'Kostengroep omschrijving',
        'report.col.budget_cost' => 'VC Kosten',
        'report.col.budget_revenue' => 'VC Opbrengsten',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Geboekte kosten',
        'report.col.entered_obligations' => 'Ingevoerde verplichtingen',
        'report.col.invoiced_amount' => 'Gefactureerd',
        'report.col.variance_budget_eac' => 'Verschil VC - EAC',
        'report.tooltip.col.budget_cost' => 'Voorcalculatie-kosten (LVS_Baseline_Total_Cost) uit ProjectTaken voor deze kostengroep',
        'report.tooltip.col.budget_revenue' => 'Total_Price_LCY uit JobBaselineLines wanneer Type = GB-rekening en No = 800000. Excl. BTW.',
        'report.tooltip.col.contract_value' => 'LVS_Contract_Total_Price_2 uit ProjectTaken voor deze kostengroep (incl. meerwerk)',
        'report.tooltip.col.eac' => 'Estimate At Completion: totale verwachte kosten aan einde project',
        'report.tooltip.col.booked_cost' => 'Werkelijk geboekte kosten tot nu toe (JobLedgerEntries.Total_Cost_LCY). Excl. BTW.',
        'report.tooltip.col.entered_obligations' => 'Geregistreerde inkoopbedragen (LVS_Registered_Purchases_Amt) uit ProjectTaken voor deze kostengroep',
        'report.tooltip.col.invoiced_amount' => 'Bedragen gefactureerd aan de klant.',
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
        'report.hours.variance_pct' => 'Verschil VC - EAC %',
        'report.tooltip.hours.budget' => 'Gepland aantal uren voor het project',
        'report.tooltip.hours.estimated' => 'Verwacht aantal uren op basis van huidige prognose',
        'report.tooltip.hours.booked' => 'Werkelijk geboekt aantal uren tot nu toe',
        'report.tooltip.hours.to_go' => 'Resterende uren: geschat minus geboekt',
        'report.tooltip.hours.gross_profit_pct' => 'Brutowinst in procenten van contractwaarde',
        'report.tooltip.hours.order_result_pct' => 'Netto winst in procenten van contractwaarde',
        'report.tooltip.hours.variance_pct' => 'Verschil tussen budget en EAC in procenten van contractwaarde',
        'report.exp.variance' => 'Verw. verschil VC - EAC',
        'report.exp.order_result' => 'Verwacht orderresultaat',
        'report.exp.ipr_result' => 'Liquiditeit',
        'report.exp.poc_baseline' => 'POC Baseline',
        'report.exp.poc_eac' => 'POC EAC',
        'report.modal.temp_notice' => 'Let op: Deze waarde wordt binnenkort in BC bijgehouden. Invoer op deze pagina is tijdelijk.',
        'report.modal.eac_title' => 'EAC invoeren',
        'report.modal.value_label' => 'Waarde',
        'report.overrides.read_only_notice' => 'Dit is een ouder rapport. EAC-waarden zijn alleen-lezen; wijzigingen zijn alleen mogelijk op het meest recente rapport.',
        'report.btn.save' => 'Opslaan',
        'report.btn.cancel' => 'Annuleren',
        'report.tooltip.exp.variance' => 'Verschil tussen voorcalculatie en EAC (VC Kosten - EAC)',
        'report.tooltip.exp.order_result' => 'Contractwaarde minus verwachte kosten',
        'report.tooltip.exp.ipr_result' => 'Ontvangen termijnen minus geboekte kosten',
        'report.tooltip.exp.poc_baseline' => 'Geboekte kosten gedeeld door basislijn voorcalculatie, als percentage',
        'report.tooltip.exp.poc_eac' => 'Geboekte kosten gedeeld door EAC, als percentage',
        'report.termijn.empty' => 'Geen termijnfacturen gevonden.',
        'report.termijn.label' => 'Termijn %d',
        'report.termijn.status.not_invoiced' => 'Niet Gefactureerd',
        'report.termijn.status.invoiced' => 'Gefactureerd',
        'report.termijn.status.paid' => 'Betaald',
        'report.termijn.status.open' => 'Openstaand',
        'report.termijn.date.posting' => 'Factuur',
        'report.termijn.date.due' => 'Verval',
        'report.termijn.date.paid' => 'Betaald',
        'report.termijn.date.planned' => 'Gepland',
        'report.tooltip.termijn.status' => 'Niet Gefactureerd; Gefactureerd bij match Sales_LCY in Customer_Ledger_Entries; Betaald bij gevulde Closed_at_Date',
        'report.tooltip.no_source' => 'Geen broninformatie beschikbaar',
        'report.tooltip.fallback' => ' (fallback: ',
        'report.tooltip.fallback_close' => ')',
        'report.tooltip.vat.excl_suffix' => ' Excl. BTW.',
        'report.tooltip.vat.incl_suffix' => ' Incl. BTW.',

        'format.hours' => '%s u',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Onbekend',

        'month.01' => 'Januari', 'month.02' => 'Februari', 'month.03' => 'Maart', 'month.04' => 'April',
        'month.05' => 'Mei', 'month.06' => 'Juni', 'month.07' => 'Juli', 'month.08' => 'Augustus',
        'month.09' => 'September', 'month.10' => 'Oktober', 'month.11' => 'November', 'month.12' => 'December',
        'month_lc.01' => 'januari', 'month_lc.02' => 'februari', 'month_lc.03' => 'maart', 'month_lc.04' => 'april',
        'month_lc.05' => 'mei', 'month_lc.06' => 'juni', 'month_lc.07' => 'juli', 'month_lc.08' => 'augustus',
        'month_lc.09' => 'september', 'month_lc.10' => 'oktober', 'month_lc.11' => 'november', 'month_lc.12' => 'december',
        'month_abbr.01' => 'jan', 'month_abbr.02' => 'feb', 'month_abbr.03' => 'mrt', 'month_abbr.04' => 'apr',
        'month_abbr.05' => 'mei', 'month_abbr.06' => 'jun', 'month_abbr.07' => 'jul', 'month_abbr.08' => 'aug',
        'month_abbr.09' => 'sep', 'month_abbr.10' => 'okt', 'month_abbr.11' => 'nov', 'month_abbr.12' => 'dec',
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
        'index.delete.step3.body' => 'If you delete an automatic nightly report, this will reduce the accuracy of this project\'s dashboard.',
        'index.generate.missing_task_rows.body' => 'Task lines for this project are missing; the report may therefore be displayed incorrectly. Do you want to continue?',
        'index.btn.yes' => 'Yes',
        'index.btn.no' => 'No',
        'index.js.customer' => 'Customer: %s%s',
        'index.js.customer_unavailable' => 'Customer: not available',
        'index.js.generate_label' => 'Generate current status',
        'index.js.generate_btn' => 'Generate report and open',
        'index.js.reports_label' => 'Existing reports',
        'index.js.show_auto_reports' => 'Show automatic daily reports',
        'index.js.reports_empty' => 'No saved reports yet.',
        'index.js.reports_empty_filtered' => 'No manual reports visible. Enable automatic reports to see more.',
        'index.js.reports_load_more' => 'Show older reports (%s of %s)',
        'index.js.reports_loading_more' => 'Loading older reports...',
        'index.js.btn.open' => 'Open',
        'index.js.comments_btn' => '💬%s',
        'index.js.comments_modal_title' => 'Comments',
        'index.js.comments_loading' => 'Loading comments...',
        'index.js.comments_empty' => 'No comments on this report yet.',
        'index.js.comments_send' => 'Send',
        'index.js.comments_save' => 'Save',
        'index.js.comments_cancel' => 'Cancel',
        'index.js.comments_edit' => 'Edit',
        'index.js.comments_edited' => 'edited',
        'index.js.comments_placeholder' => 'Type a comment...',
        'index.js.comments_load_failed' => 'Failed to load comments.',
        'index.js.comments_send_failed' => 'Failed to send comment.',
        'index.js.comments_update_failed' => 'Failed to update comment.',
        'index.js.dashboard_btn' => 'Project dashboard',
        'index.js.dashboard_modal_title' => 'Project dashboard %s',
        'index.js.dashboard_loading' => 'Loading dashboard data...',
        'index.js.dashboard_empty' => 'No dashboard data available for this project yet.',
        'index.js.dashboard_load_failed' => 'Failed to load dashboard.',
        'index.js.dashboard_chart_poc_title' => 'POC over time',
        'index.js.dashboard_chart_poc_baseline' => 'POC Baseline',
        'index.js.dashboard_chart_poc_eac' => 'POC EAC',
        'index.js.dashboard_chart_y_axis' => 'POC (%)',
        'index.js.dashboard_chart_cost_title' => 'Cost breakdown (booked)',
        'index.js.dashboard_chart_cost_subtitle' => 'Based on the latest report',
        'index.js.dashboard_chart_eac_title' => 'Cost breakdown (EAC)',
        'index.js.dashboard_chart_invoiced_title' => 'Invoiced by cost group',
        'index.js.dashboard_chart_installments_title' => 'Installments received',
        'index.js.dashboard_chart_cost_major' => 'Total row',
        'index.js.dashboard_chart_cost_subtotal' => 'Subtotal row',
        'index.js.dashboard_latest_report_note' => 'Latest report: %s (%s)',
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
        'error.dashboard_failed' => 'Failed to load dashboard: %s',
        'error.delete_report_failed' => 'Failed to delete report or it does not exist.',
        'error.report_not_found' => 'Report not found.',
        'error.comment_auth_required' => 'You must be signed in to post comments.',
        'error.comment_empty' => 'Enter a comment.',
        'error.comment_save_failed' => 'Failed to save comment.',
        'error.comment_update_failed' => 'Failed to update comment.',
        'error.report_overrides_locked' => 'This is not the most recent report. EAC changes are only allowed on the latest report.',

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
        'report.header.type' => 'Type',
        'report.total_direct_cost' => 'PC Costs',
        'report.gross_profit' => 'Gross profit',
        'report.variance' => 'Variance PC - EAC',
        'report.order_result' => 'Order result',
        'report.installments_invoiced' => 'Installments invoiced',
        'report.installments_received' => 'Installments received',
        'report.tooltip.contract_value' => 'Sum of LVS_Contract_Total_Price_2 from ProjectTaken for sub-rows under total row 000 (excluding change orders)',
        'report.tooltip.header.type' => 'PRJ for the project; other values for extra work (LVS_Job_Change_Order_No)',
        'report.tooltip.header.change_order_contract' => 'lines with LVS_Job_Change_Order_No',
        'report.tooltip.total_direct_cost' => 'Values from total row 000 (cost group table)',
        'report.tooltip.total_budget_revenue' => 'Total_Price_LCY from JobBaselineLines on total row 000 (Type = GB-rekening, No = 800000). Excl. VAT.',
        'report.tooltip.gross_profit' => 'Contract value minus direct costs',
        'report.tooltip.variance' => 'Difference between budgeted and expected costs',
        'report.tooltip.order_result' => 'Gross profit plus difference between budgeted and expected costs',
        'report.tooltip.installments_invoiced' => 'Sum of Contract_Invoiced_Price from ProjectTaken (PRJ row: without LVS_Job_Change_Order_No)',
        'report.tooltip.installments_received' => 'Sum of received amounts from customer ledger entries linked to the project (Amount_LCY - Remaining_Amt_LCY). Excl. VAT.',
        'report.col.cost_group_code' => 'Cost group code',
        'report.col.cost_group_description' => 'Cost group description',
        'report.col.budget_cost' => 'PC Costs',
        'report.col.budget_revenue' => 'PC Revenue',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Booked cost',
        'report.col.entered_obligations' => 'Entered obligations',
        'report.col.invoiced_amount' => 'Invoiced',
        'report.col.variance_budget_eac' => 'Variance PC - EAC',
        'report.tooltip.col.budget_cost' => 'Pre-calculation costs (LVS_Baseline_Total_Cost) from ProjectTaken for this cost group',
        'report.tooltip.col.budget_revenue' => 'Total_Price_LCY from JobBaselineLines when Type = GB-rekening and No = 800000. Excl. VAT.',
        'report.tooltip.col.contract_value' => 'LVS_Contract_Total_Price_2 from ProjectTaken for this cost group (incl. change orders)',
        'report.tooltip.col.eac' => 'Estimate At Completion: total expected costs at project end',
        'report.tooltip.col.booked_cost' => 'Actual booked costs to date (JobLedgerEntries.Total_Cost_LCY). Excl. VAT.',
        'report.tooltip.col.entered_obligations' => 'Registered purchase amounts (LVS_Registered_Purchases_Amt) from ProjectTaken for this cost group',
        'report.tooltip.col.invoiced_amount' => 'Amounts invoiced to the customer.',
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
        'report.hours.variance_pct' => 'Variance PC - EAC %',
        'report.tooltip.hours.budget' => 'Planned number of hours for the project',
        'report.tooltip.hours.estimated' => 'Expected hours based on current forecast',
        'report.tooltip.hours.booked' => 'Actual booked hours to date',
        'report.tooltip.hours.to_go' => 'Remaining hours: estimated minus booked',
        'report.tooltip.hours.gross_profit_pct' => 'Gross profit as a percentage of contract value',
        'report.tooltip.hours.order_result_pct' => 'Net profit as a percentage of contract value',
        'report.tooltip.hours.variance_pct' => 'Difference between budget and EAC as a percentage of contract value',
        'report.exp.variance' => 'Exp. variance PC - EAC',
        'report.exp.order_result' => 'Expected order result',
        'report.exp.ipr_result' => 'Liquidity',
        'report.exp.poc_baseline' => 'POC Baseline',
        'report.exp.poc_eac' => 'POC EAC',
        'report.modal.temp_notice' => 'Note: This value will soon be maintained in BC. Entry on this page is temporary.',
        'report.modal.eac_title' => 'Enter EAC',
        'report.modal.value_label' => 'Value',
        'report.overrides.read_only_notice' => 'This is an older report. EAC values are read-only; changes are only possible on the most recent report.',
        'report.btn.save' => 'Save',
        'report.btn.cancel' => 'Cancel',
        'report.tooltip.exp.variance' => 'Difference between pre-calculation and EAC (PC Costs - EAC)',
        'report.tooltip.exp.order_result' => 'Contract value minus expected costs',
        'report.tooltip.exp.ipr_result' => 'Installments received minus booked cost',
        'report.tooltip.exp.poc_baseline' => 'Booked cost divided by baseline pre-calculation, as a percentage',
        'report.tooltip.exp.poc_eac' => 'Booked cost divided by EAC, as a percentage',
        'report.termijn.empty' => 'No installment invoices found.',
        'report.termijn.label' => 'Installment %d',
        'report.termijn.status.not_invoiced' => 'Not invoiced',
        'report.termijn.status.invoiced' => 'Invoiced',
        'report.termijn.status.paid' => 'Paid',
        'report.termijn.status.open' => 'Outstanding',
        'report.termijn.date.posting' => 'Invoice',
        'report.termijn.date.due' => 'Due',
        'report.termijn.date.paid' => 'Paid',
        'report.termijn.date.planned' => 'Planned',
        'report.tooltip.termijn.status' => 'Not invoiced; Invoiced when Sales_LCY matches in Customer_Ledger_Entries; Paid when Closed_at_Date is filled',
        'report.tooltip.no_source' => 'No source information available',
        'report.tooltip.fallback' => ' (fallback: ',
        'report.tooltip.fallback_close' => ')',
        'report.tooltip.vat.excl_suffix' => ' Excl. VAT.',
        'report.tooltip.vat.incl_suffix' => ' Incl. VAT.',

        'format.hours' => '%s h',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Unknown',

        'month.01' => 'January', 'month.02' => 'February', 'month.03' => 'March', 'month.04' => 'April',
        'month.05' => 'May', 'month.06' => 'June', 'month.07' => 'July', 'month.08' => 'August',
        'month.09' => 'September', 'month.10' => 'October', 'month.11' => 'November', 'month.12' => 'December',
        'month_lc.01' => 'January', 'month_lc.02' => 'February', 'month_lc.03' => 'March', 'month_lc.04' => 'April',
        'month_lc.05' => 'May', 'month_lc.06' => 'June', 'month_lc.07' => 'July', 'month_lc.08' => 'August',
        'month_lc.09' => 'September', 'month_lc.10' => 'October', 'month_lc.11' => 'November', 'month_lc.12' => 'December',
        'month_abbr.01' => 'Jan', 'month_abbr.02' => 'Feb', 'month_abbr.03' => 'Mar', 'month_abbr.04' => 'Apr',
        'month_abbr.05' => 'May', 'month_abbr.06' => 'Jun', 'month_abbr.07' => 'Jul', 'month_abbr.08' => 'Aug',
        'month_abbr.09' => 'Sep', 'month_abbr.10' => 'Oct', 'month_abbr.11' => 'Nov', 'month_abbr.12' => 'Dec',
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
        'index.delete.step3.body' => 'Wenn Sie einen automatischen Nachtbericht löschen, verringert dies die Genauigkeit des Projektdashboards für dieses Projekt.',
        'index.generate.missing_task_rows.body' => 'Aufgabenzeilen für dieses Projekt fehlen; der Bericht wird daher möglicherweise falsch angezeigt. Möchten Sie fortfahren?',
        'index.btn.yes' => 'Ja',
        'index.btn.no' => 'Nein',
        'index.js.customer' => 'Debitor: %s%s',
        'index.js.customer_unavailable' => 'Debitor: nicht verfügbar',
        'index.js.generate_label' => 'Aktuellen Stand erzeugen',
        'index.js.generate_btn' => 'Bericht erzeugen und öffnen',
        'index.js.reports_label' => 'Vorhandene Berichte',
        'index.js.show_auto_reports' => 'Automatische Tagesberichte anzeigen',
        'index.js.reports_empty' => 'Noch keine gespeicherten Berichte.',
        'index.js.reports_empty_filtered' => 'Keine manuellen Berichte sichtbar. Aktivieren Sie automatische Berichte, um mehr zu sehen.',
        'index.js.reports_load_more' => 'Ältere Berichte anzeigen (%s von %s)',
        'index.js.reports_loading_more' => 'Ältere Berichte werden geladen...',
        'index.js.btn.open' => 'Öffnen',
        'index.js.comments_btn' => '💬%s',
        'index.js.comments_modal_title' => 'Kommentare',
        'index.js.comments_loading' => 'Kommentare werden geladen...',
        'index.js.comments_empty' => 'Noch keine Kommentare zu diesem Bericht.',
        'index.js.comments_send' => 'Senden',
        'index.js.comments_save' => 'Speichern',
        'index.js.comments_cancel' => 'Abbrechen',
        'index.js.comments_edit' => 'Bearbeiten',
        'index.js.comments_edited' => 'bearbeitet',
        'index.js.comments_placeholder' => 'Kommentar eingeben...',
        'index.js.comments_load_failed' => 'Kommentare laden fehlgeschlagen.',
        'index.js.comments_send_failed' => 'Kommentar senden fehlgeschlagen.',
        'index.js.comments_update_failed' => 'Kommentar bearbeiten fehlgeschlagen.',
        'index.js.dashboard_btn' => 'Projektdashboard',
        'index.js.dashboard_modal_title' => 'Projektdashboard %s',
        'index.js.dashboard_loading' => 'Dashboard-Daten werden geladen...',
        'index.js.dashboard_empty' => 'Für dieses Projekt sind noch keine Dashboard-Daten verfügbar.',
        'index.js.dashboard_load_failed' => 'Dashboard konnte nicht geladen werden.',
        'index.js.dashboard_chart_poc_title' => 'POC im Zeitverlauf',
        'index.js.dashboard_chart_poc_baseline' => 'POC Baseline',
        'index.js.dashboard_chart_poc_eac' => 'POC EAC',
        'index.js.dashboard_chart_y_axis' => 'POC (%)',
        'index.js.dashboard_chart_cost_title' => 'Kostenverteilung (gebucht)',
        'index.js.dashboard_chart_cost_subtitle' => 'Basierend auf dem neuesten Bericht',
        'index.js.dashboard_chart_eac_title' => 'Kostenverteilung (EAC)',
        'index.js.dashboard_chart_invoiced_title' => 'Fakturiert pro Kostengruppe',
        'index.js.dashboard_chart_installments_title' => 'Erhaltene Raten',
        'index.js.dashboard_chart_cost_major' => 'Summenzeile',
        'index.js.dashboard_chart_cost_subtotal' => 'Zwischensummenzeile',
        'index.js.dashboard_latest_report_note' => 'Neuester Bericht: %s (%s)',
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
        'error.dashboard_failed' => 'Dashboard laden fehlgeschlagen: %s',
        'error.delete_report_failed' => 'Bericht löschen fehlgeschlagen oder existiert nicht.',
        'error.report_not_found' => 'Bericht nicht gefunden.',
        'error.comment_auth_required' => 'Sie müssen angemeldet sein, um Kommentare zu schreiben.',
        'error.comment_empty' => 'Geben Sie einen Kommentar ein.',
        'error.comment_save_failed' => 'Kommentar speichern fehlgeschlagen.',
        'error.comment_update_failed' => 'Kommentar bearbeiten fehlgeschlagen.',
        'error.report_overrides_locked' => 'Dies ist nicht der neueste Bericht. EAC-Änderungen sind nur im aktuellsten Bericht erlaubt.',

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
        'report.header.type' => 'Typ',
        'report.total_direct_cost' => 'VK Kosten',
        'report.gross_profit' => 'Bruttogewinn',
        'report.variance' => 'Abweichung VK - EAC',
        'report.order_result' => 'Auftragsergebnis',
        'report.installments_invoiced' => 'Fakturierte Raten',
        'report.installments_received' => 'Erhaltene Raten',
        'report.tooltip.contract_value' => 'Summe von LVS_Contract_Total_Price_2 aus ProjectTaken für Unterzeilen unter Gesamtzeile 000 (ohne Nachträge)',
        'report.tooltip.header.type' => 'PRJ für das Projekt; weitere Werte für Mehrarbeit (LVS_Job_Change_Order_No)',
        'report.tooltip.header.change_order_contract' => 'Zeilen mit LVS_Job_Change_Order_No',
        'report.tooltip.total_direct_cost' => 'Werte aus Gesamtzeile 000 (Kostengruppentabelle)',
        'report.tooltip.total_budget_revenue' => 'Total_Price_LCY aus JobBaselineLines auf Gesamtzeile 000 (Type = GB-rekening, No = 800000). Exkl. MwSt.',
        'report.tooltip.gross_profit' => 'Vertragswert minus direkte Kosten',
        'report.tooltip.variance' => 'Unterschied zwischen budgetierten und erwarteten Kosten',
        'report.tooltip.order_result' => 'Bruttogewinn plus Unterschied zwischen budgetierten und erwarteten Kosten',
        'report.tooltip.installments_invoiced' => 'Summe von Contract_Invoiced_Price aus ProjectTaken (PRJ-Zeile: ohne LVS_Job_Change_Order_No)',
        'report.tooltip.installments_received' => 'Summe der erhaltenen Beträge aus Debitorenposten zum Projekt (Amount_LCY - Remaining_Amt_LCY). Exkl. MwSt.',
        'report.col.cost_group_code' => 'Kostengruppencode',
        'report.col.cost_group_description' => 'Kostengruppenbeschreibung',
        'report.col.budget_cost' => 'VK Kosten',
        'report.col.budget_revenue' => 'VK Erlöse',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Gebuchte Kosten',
        'report.col.entered_obligations' => 'Erfasste Verpflichtungen',
        'report.col.invoiced_amount' => 'Fakturiert',
        'report.col.variance_budget_eac' => 'Abweichung VK - EAC',
        'report.tooltip.col.budget_cost' => 'Vorkalkulationskosten (LVS_Baseline_Total_Cost) aus ProjectTaken für diese Kostengruppe',
        'report.tooltip.col.budget_revenue' => 'Total_Price_LCY aus JobBaselineLines wenn Type = GB-rekening und No = 800000. Exkl. MwSt.',
        'report.tooltip.col.contract_value' => 'LVS_Contract_Total_Price_2 aus ProjectTaken für diese Kostengruppe (inkl. Nachträge)',
        'report.tooltip.col.eac' => 'Estimate At Completion: erwartete Gesamtkosten am Projektende',
        'report.tooltip.col.booked_cost' => 'Tatsächlich gebuchte Kosten bis heute (JobLedgerEntries.Total_Cost_LCY). Exkl. MwSt.',
        'report.tooltip.col.entered_obligations' => 'Registrierte Einkaufsbeträge (LVS_Registered_Purchases_Amt) aus ProjectTaken für diese Kostengruppe',
        'report.tooltip.col.invoiced_amount' => 'An den Kunden fakturierte Beträge.',
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
        'report.hours.variance_pct' => 'Abweichung VK - EAC %',
        'report.tooltip.hours.budget' => 'Geplante Stunden für das Projekt',
        'report.tooltip.hours.estimated' => 'Erwartete Stunden basierend auf aktueller Prognose',
        'report.tooltip.hours.booked' => 'Tatsächlich gebuchte Stunden bis heute',
        'report.tooltip.hours.to_go' => 'Verbleibende Stunden: geschätzt minus gebucht',
        'report.tooltip.hours.gross_profit_pct' => 'Bruttogewinn in Prozent des Vertragswerts',
        'report.tooltip.hours.order_result_pct' => 'Nettogewinn in Prozent des Vertragswerts',
        'report.tooltip.hours.variance_pct' => 'Unterschied zwischen Budget und EAC in Prozent des Vertragswerts',
        'report.exp.variance' => 'Erw. Abweichung VK - EAC',
        'report.exp.order_result' => 'Erwartetes Auftragsergebnis',
        'report.exp.ipr_result' => 'Liquidität',
        'report.exp.poc_baseline' => 'POC Baseline',
        'report.exp.poc_eac' => 'POC EAC',
        'report.modal.temp_notice' => 'Hinweis: Dieser Wert wird demnächst in BC gepflegt. Die Eingabe auf dieser Seite ist vorübergehend.',
        'report.modal.eac_title' => 'EAC eingeben',
        'report.modal.value_label' => 'Wert',
        'report.overrides.read_only_notice' => 'Dies ist ein älterer Bericht. EAC-Werte sind schreibgeschützt; Änderungen sind nur im neuesten Bericht möglich.',
        'report.btn.save' => 'Speichern',
        'report.btn.cancel' => 'Abbrechen',
        'report.tooltip.exp.variance' => 'Unterschied zwischen Vorkalkulation und EAC (VK Kosten - EAC)',
        'report.tooltip.exp.order_result' => 'Vertragswert minus erwartete Kosten',
        'report.tooltip.exp.ipr_result' => 'Erhaltene Raten minus gebuchte Kosten',
        'report.tooltip.exp.poc_baseline' => 'Gebuchte Kosten geteilt durch Basislinie Vorkalkulation, als Prozentsatz',
        'report.tooltip.exp.poc_eac' => 'Gebuchte Kosten geteilt durch EAC, als Prozentsatz',
        'report.termijn.empty' => 'Keine Ratenrechnungen gefunden.',
        'report.termijn.label' => 'Rate %d',
        'report.termijn.status.not_invoiced' => 'Nicht fakturiert',
        'report.termijn.status.invoiced' => 'Fakturiert',
        'report.termijn.status.paid' => 'Bezahlt',
        'report.termijn.status.open' => 'Offen',
        'report.termijn.date.posting' => 'Rechnung',
        'report.termijn.date.due' => 'Fällig',
        'report.termijn.date.paid' => 'Bezahlt',
        'report.termijn.date.planned' => 'Geplant',
        'report.tooltip.termijn.status' => 'Nicht fakturiert; Fakturiert bei Übereinstimmung Sales_LCY in Customer_Ledger_Entries; Bezahlt bei ausgefülltem Closed_at_Date',
        'report.tooltip.no_source' => 'Keine Quellinformationen verfügbar',
        'report.tooltip.fallback' => ' (Fallback: ',
        'report.tooltip.fallback_close' => ')',
        'report.tooltip.vat.excl_suffix' => ' Exkl. MwSt.',
        'report.tooltip.vat.incl_suffix' => ' Inkl. MwSt.',

        'format.hours' => '%s Std.',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Unbekannt',

        'month.01' => 'Januar', 'month.02' => 'Februar', 'month.03' => 'März', 'month.04' => 'April',
        'month.05' => 'Mai', 'month.06' => 'Juni', 'month.07' => 'Juli', 'month.08' => 'August',
        'month.09' => 'September', 'month.10' => 'Oktober', 'month.11' => 'November', 'month.12' => 'Dezember',
        'month_lc.01' => 'Januar', 'month_lc.02' => 'Februar', 'month_lc.03' => 'März', 'month_lc.04' => 'April',
        'month_lc.05' => 'Mai', 'month_lc.06' => 'Juni', 'month_lc.07' => 'Juli', 'month_lc.08' => 'August',
        'month_lc.09' => 'September', 'month_lc.10' => 'Oktober', 'month_lc.11' => 'November', 'month_lc.12' => 'Dezember',
        'month_abbr.01' => 'Jan', 'month_abbr.02' => 'Feb', 'month_abbr.03' => 'Mär', 'month_abbr.04' => 'Apr',
        'month_abbr.05' => 'Mai', 'month_abbr.06' => 'Jun', 'month_abbr.07' => 'Jul', 'month_abbr.08' => 'Aug',
        'month_abbr.09' => 'Sep', 'month_abbr.10' => 'Okt', 'month_abbr.11' => 'Nov', 'month_abbr.12' => 'Dez',
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
        'index.delete.step3.body' => 'Si vous supprimez un rapport nocturne automatique, cela réduira la précision du tableau de bord de ce projet.',
        'index.generate.missing_task_rows.body' => 'Les lignes de tâches de ce projet sont absentes ; le rapport peut donc être affiché de manière incorrecte. Voulez-vous continuer ?',
        'index.btn.yes' => 'Oui',
        'index.btn.no' => 'Non',
        'index.js.customer' => 'Client : %s%s',
        'index.js.customer_unavailable' => 'Client : non disponible',
        'index.js.generate_label' => 'Générer l\'état actuel',
        'index.js.generate_btn' => 'Générer le rapport et ouvrir',
        'index.js.reports_label' => 'Rapports existants',
        'index.js.show_auto_reports' => 'Afficher les rapports quotidiens automatiques',
        'index.js.reports_empty' => 'Aucun rapport enregistré pour le moment.',
        'index.js.reports_empty_filtered' => 'Aucun rapport manuel visible. Activez les rapports automatiques pour en voir plus.',
        'index.js.btn.open' => 'Ouvrir',
        'index.js.comments_btn' => '💬%s',
        'index.js.comments_modal_title' => 'Commentaires',
        'index.js.comments_loading' => 'Chargement des commentaires...',
        'index.js.comments_empty' => 'Pas encore de commentaires sur ce rapport.',
        'index.js.comments_send' => 'Envoyer',
        'index.js.comments_save' => 'Enregistrer',
        'index.js.comments_cancel' => 'Annuler',
        'index.js.comments_edit' => 'Modifier',
        'index.js.comments_edited' => 'modifié',
        'index.js.comments_placeholder' => 'Saisir un commentaire...',
        'index.js.comments_load_failed' => 'Échec du chargement des commentaires.',
        'index.js.comments_send_failed' => 'Échec de l\'envoi du commentaire.',
        'index.js.comments_update_failed' => 'Échec de la modification du commentaire.',
        'index.js.dashboard_btn' => 'Tableau de bord projet',
        'index.js.dashboard_modal_title' => 'Tableau de bord projet %s',
        'index.js.dashboard_loading' => 'Chargement des données du tableau de bord...',
        'index.js.dashboard_empty' => 'Aucune donnée de tableau de bord disponible pour ce projet.',
        'index.js.dashboard_load_failed' => 'Échec du chargement du tableau de bord.',
        'index.js.dashboard_chart_poc_title' => 'POC dans le temps',
        'index.js.dashboard_chart_poc_baseline' => 'POC Baseline',
        'index.js.dashboard_chart_poc_eac' => 'POC EAC',
        'index.js.dashboard_chart_y_axis' => 'POC (%)',
        'index.js.dashboard_chart_cost_title' => 'Répartition des coûts (comptabilisés)',
        'index.js.dashboard_chart_cost_subtitle' => 'Basé sur le rapport le plus récent',
        'index.js.dashboard_chart_eac_title' => 'Répartition des coûts (EAC)',
        'index.js.dashboard_chart_invoiced_title' => 'Facturé par groupe de coûts',
        'index.js.dashboard_chart_installments_title' => 'Acomptes reçus',
        'index.js.dashboard_chart_cost_major' => 'Ligne totale',
        'index.js.dashboard_chart_cost_subtotal' => 'Ligne sous-total',
        'index.js.dashboard_latest_report_note' => 'Rapport le plus récent : %s (%s)',
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
        'error.dashboard_failed' => 'Échec du chargement du tableau de bord : %s',
        'error.delete_report_failed' => 'Échec de la suppression du rapport ou rapport inexistant.',
        'error.report_not_found' => 'Rapport introuvable.',
        'error.comment_auth_required' => 'Vous devez être connecté pour publier des commentaires.',
        'error.comment_empty' => 'Saisissez un commentaire.',
        'error.comment_save_failed' => 'Échec de l\'enregistrement du commentaire.',
        'error.comment_update_failed' => 'Échec de la modification du commentaire.',
        'error.report_overrides_locked' => 'Ce n\'est pas le rapport le plus récent. Les modifications EAC ne sont autorisées que sur le dernier rapport.',

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
        'report.header.type' => 'Type',
        'report.total_direct_cost' => 'Coûts VC',
        'report.gross_profit' => 'Marge brute',
        'report.variance' => 'Écart VC - EAC',
        'report.order_result' => 'Résultat commande',
        'report.installments_invoiced' => 'Acomptes facturés',
        'report.installments_received' => 'Acomptes reçus',
        'report.tooltip.contract_value' => 'Somme de LVS_Contract_Total_Price_2 depuis ProjectTaken pour les sous-lignes sous la ligne totale 000 (hors avenants)',
        'report.tooltip.header.type' => 'PRJ pour le projet; autres valeurs pour travaux supplémentaires (LVS_Job_Change_Order_No)',
        'report.tooltip.header.change_order_contract' => 'lignes avec LVS_Job_Change_Order_No',
        'report.tooltip.total_direct_cost' => 'Valeurs de la ligne totale 000 (tableau des groupes de coûts)',
        'report.tooltip.total_budget_revenue' => 'Total_Price_LCY depuis JobBaselineLines sur la ligne totale 000 (Type = GB-rekening, No = 800000). HT.',
        'report.tooltip.gross_profit' => 'Valeur du contrat moins coûts directs',
        'report.tooltip.variance' => 'Différence entre coûts budgétés et attendus',
        'report.tooltip.order_result' => 'Marge brute plus écart entre coûts budgétés et attendus',
        'report.tooltip.installments_invoiced' => 'Somme de Contract_Invoiced_Price depuis ProjectTaken (ligne PRJ : sans LVS_Job_Change_Order_No)',
        'report.tooltip.installments_received' => 'Somme des montants reçus des écritures client liées au projet (Amount_LCY - Remaining_Amt_LCY). HT.',
        'report.col.cost_group_code' => 'Code groupe de coûts',
        'report.col.cost_group_description' => 'Description groupe de coûts',
        'report.col.budget_cost' => 'Coûts VC',
        'report.col.budget_revenue' => 'Revenus VC',
        'report.col.eac' => 'EAC',
        'report.col.booked_cost' => 'Coût comptabilisé',
        'report.col.entered_obligations' => 'Engagements saisis',
        'report.col.invoiced_amount' => 'Facturé',
        'report.col.variance_budget_eac' => 'Écart VC - EAC',
        'report.tooltip.col.budget_cost' => 'Coûts de précalcul (LVS_Baseline_Total_Cost) depuis ProjectTaken pour ce groupe de coûts',
        'report.tooltip.col.budget_revenue' => 'Total_Price_LCY depuis JobBaselineLines lorsque Type = GB-rekening et No = 800000. HT.',
        'report.tooltip.col.contract_value' => 'LVS_Contract_Total_Price_2 depuis ProjectTaken pour ce groupe de coûts (avenants inclus)',
        'report.tooltip.col.eac' => 'Estimate At Completion : coûts totaux attendus en fin de projet',
        'report.tooltip.col.booked_cost' => 'Coûts réellement comptabilisés à ce jour (JobLedgerEntries.Total_Cost_LCY). HT.',
        'report.tooltip.col.entered_obligations' => 'Montants d\'achats enregistrés (LVS_Registered_Purchases_Amt) depuis ProjectTaken pour ce groupe de coûts',
        'report.tooltip.col.invoiced_amount' => 'Montants facturés au client.',
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
        'report.hours.variance_pct' => 'Écart VC - EAC %',
        'report.tooltip.hours.budget' => 'Nombre d\'heures planifiées pour le projet',
        'report.tooltip.hours.estimated' => 'Heures attendues selon la prévision actuelle',
        'report.tooltip.hours.booked' => 'Heures réellement comptabilisées à ce jour',
        'report.tooltip.hours.to_go' => 'Heures restantes : estimées moins comptabilisées',
        'report.tooltip.hours.gross_profit_pct' => 'Marge brute en pourcentage de la valeur du contrat',
        'report.tooltip.hours.order_result_pct' => 'Profit net en pourcentage de la valeur du contrat',
        'report.tooltip.hours.variance_pct' => 'Différence entre budget et EAC en pourcentage de la valeur du contrat',
        'report.exp.variance' => 'Écart attendu VC - EAC',
        'report.exp.order_result' => 'Résultat commande attendu',
        'report.exp.ipr_result' => 'Liquidité',
        'report.exp.poc_baseline' => 'POC Baseline',
        'report.exp.poc_eac' => 'POC EAC',
        'report.modal.temp_notice' => 'Attention : cette valeur sera bientôt gérée dans BC. La saisie sur cette page est temporaire.',
        'report.modal.eac_title' => 'Saisir EAC',
        'report.modal.value_label' => 'Valeur',
        'report.overrides.read_only_notice' => 'Il s\'agit d\'un rapport plus ancien. Les valeurs EAC sont en lecture seule ; les modifications ne sont possibles que sur le rapport le plus récent.',
        'report.btn.save' => 'Enregistrer',
        'report.btn.cancel' => 'Annuler',
        'report.tooltip.exp.variance' => 'Différence entre précalcul et EAC (Coûts VC - EAC)',
        'report.tooltip.exp.order_result' => 'Valeur du contrat moins coûts attendus',
        'report.tooltip.exp.ipr_result' => 'Acomptes reçus moins coûts comptabilisés',
        'report.tooltip.exp.poc_baseline' => 'Coût comptabilisé divisé par la précalcul de base, en pourcentage',
        'report.tooltip.exp.poc_eac' => 'Coût comptabilisé divisé par EAC, en pourcentage',
        'report.termijn.empty' => 'Aucune facture d\'acompte trouvée.',
        'report.termijn.label' => 'Acompte %d',
        'report.termijn.status.not_invoiced' => 'Non facturé',
        'report.termijn.status.invoiced' => 'Facturé',
        'report.termijn.status.paid' => 'Payé',
        'report.termijn.status.open' => 'En attente',
        'report.termijn.date.posting' => 'Facture',
        'report.termijn.date.due' => 'Échéance',
        'report.termijn.date.paid' => 'Payé',
        'report.termijn.date.planned' => 'Planifié',
        'report.tooltip.termijn.status' => 'Non facturé ; Facturé si Sales_LCY correspond dans Customer_Ledger_Entries ; Payé si Closed_at_Date est renseigné',
        'report.tooltip.no_source' => 'Aucune information source disponible',
        'report.tooltip.fallback' => ' (secours : ',
        'report.tooltip.fallback_close' => ')',
        'report.tooltip.vat.excl_suffix' => ' HT.',
        'report.tooltip.vat.incl_suffix' => ' TTC.',

        'format.hours' => '%s h',
        'format.percent' => '%s%%',
        'datetime.unknown' => 'Inconnu',

        'month.01' => 'Janvier', 'month.02' => 'Février', 'month.03' => 'Mars', 'month.04' => 'Avril',
        'month.05' => 'Mai', 'month.06' => 'Juin', 'month.07' => 'Juillet', 'month.08' => 'Août',
        'month.09' => 'Septembre', 'month.10' => 'Octobre', 'month.11' => 'Novembre', 'month.12' => 'Décembre',
        'month_lc.01' => 'janvier', 'month_lc.02' => 'février', 'month_lc.03' => 'mars', 'month_lc.04' => 'avril',
        'month_lc.05' => 'mai', 'month_lc.06' => 'juin', 'month_lc.07' => 'juillet', 'month_lc.08' => 'août',
        'month_lc.09' => 'septembre', 'month_lc.10' => 'octobre', 'month_lc.11' => 'novembre', 'month_lc.12' => 'décembre',
        'month_abbr.01' => 'janv.', 'month_abbr.02' => 'févr.', 'month_abbr.03' => 'mars', 'month_abbr.04' => 'avr.',
        'month_abbr.05' => 'mai', 'month_abbr.06' => 'juin', 'month_abbr.07' => 'juil.', 'month_abbr.08' => 'août',
        'month_abbr.09' => 'sept.', 'month_abbr.10' => 'oct.', 'month_abbr.11' => 'nov.', 'month_abbr.12' => 'déc.',
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

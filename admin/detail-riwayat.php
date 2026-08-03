<?php
/**
 * Admin - Detail Riwayat Diagnosa
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/../config/security.php';

// Cek login dan role admin
requireAdminUser('../login.php');

require_once '../config/database.php';
require_once '../config/certainty.php';
require_once __DIR__ . '/../includes/disease-education.php';

$riwayat_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$return_page = max(1, filter_input(INPUT_GET, 'return_page', FILTER_VALIDATE_INT) ?: 1);
$return_search = limitedText($_GET['return_search'] ?? '', 100);
$return_query = ['page' => $return_page];
if ($return_search !== '') {
    $return_query['search'] = $return_search;
}
$detail_back_url = 'riwayat_diagnosis.php?' . http_build_query($return_query);
$history_report_url = 'laporan/laporan-hasil-diagnosis.php?' . http_build_query([
    'scope' => 'history',
    'riwayat_id' => $riwayat_id,
    'return_page' => $return_page,
    'return_search' => $return_search,
]);

// Get riwayat detail
$riwayat = fetchOne('SELECT r.*, u.nama, u.email FROM riwayat r JOIN users u ON r.id_user = u.id WHERE r.id_riwayat = ?', [$riwayat_id]);

if (!$riwayat) {
    header('Location: riwayat_diagnosis.php');
    exit;
}

// Get penyakit detail
$penyakit = fetchOne('SELECT * FROM penyakit WHERE nama_penyakit = ?', [$riwayat['penyakit_tertinggi']]);

// Get gejala yang dipilih
$detail_gejala = json_decode($riwayat['detail_gejala'], true);

$page_title = 'Detail Riwayat Diagnosa';
$tanggal = new DateTime($riwayat['tanggal']);
$cf_percentage = round($riwayat['nilai_cf'] * 100, 1);
$certainty_interpretation = getCertaintyInterpretation($riwayat['nilai_cf']);
$solution_text = trim((string) ($penyakit['solusi'] ?? 'Belum ada saran penanganan.'));
$solution_lines = preg_split('/\R+/u', $solution_text) ?: [$solution_text];
$solution_intro = [];
$solution_steps = [];

foreach ($solution_lines as $solution_line) {
    $solution_line = trim($solution_line);
    if ($solution_line === '') {
        continue;
    }

    if (preg_match('/^\d+[\.\)]\s*(.+)$/u', $solution_line, $matches)) {
        $solution_steps[] = trim($matches[1]);
        continue;
    }

    $solution_intro[] = $solution_line;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title><?php echo $page_title; ?> - AVES</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="assets/admin-theme.css?v=20260731-5" rel="stylesheet">
    <link href="../assets/disease-education.css?v=20260729-3" rel="stylesheet">
    <style>
        html,
        body.admin-detail-history-page {
            height: 100%;
        }

        body.admin-detail-history-page {
            display: flex;
            min-height: 100dvh;
            flex-direction: column;
            overflow: hidden;
        }

        body.admin-detail-history-page > header {
            position: relative;
            flex: 0 0 auto;
        }

        body.admin-detail-history-page > main.history-detail-main {
            display: flex;
            width: min(1540px, 100%);
            min-height: 0;
            flex: 1 1 auto;
            flex-direction: column;
            padding: 18px 24px !important;
            overflow: hidden;
        }

        .history-header-actions {
            min-width: 0;
        }

        .history-admin-identity {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 10px;
            background: rgba(255, 255, 255, .08);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }

        .history-back-button,
        .history-print-button {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            transition: background-color .15s ease, transform .15s ease;
        }

        .history-back-button {
            border: 1px solid rgba(255, 255, 255, .28);
            background: rgba(255, 255, 255, .1);
            color: #fff;
        }

        .history-back-button:hover {
            background: rgba(255, 255, 255, .18);
            transform: translateY(-1px);
        }

        .detail-card-heading {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 12px;
        }

        .detail-card-heading h3 {
            margin: 0 !important;
        }

        .detail-card-actions {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 7px;
        }

        .detail-action-button {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 13px;
            border: 1px solid #c9d9ec;
            border-radius: 10px;
            background: #fff;
            color: #315779;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .detail-action-button:hover {
            border-color: #92b8e2;
            background: #f1f7fe;
            color: #0756a5;
        }

        .detail-action-button--back {
            width: 104px;
            height: 40px;
            min-height: 40px;
            flex: 0 0 104px;
            gap: 8px;
            padding: 0 .75rem;
            border-color: rgba(255, 255, 255, .28);
            border-radius: 9px;
            background: rgba(255, 255, 255, .08);
            color: #fff;
            box-shadow: none;
        }

        .detail-action-button--back:hover {
            border-color: rgba(255, 255, 255, .45);
            background: rgba(255, 255, 255, .16);
            color: #fff;
            box-shadow: none;
        }

        .detail-action-button__back-icon {
            display: inline-flex;
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            line-height: 1;
        }

        .detail-action-button--primary {
            border-color: #0867c5;
            background: #0867c5;
            color: #fff;
        }

        .detail-action-button--primary:hover {
            border-color: #0756a5;
            background: #0756a5;
            color: #fff;
        }

        .diagnosis-hero {
            display: grid;
            flex: 0 0 auto;
            grid-template-columns: minmax(300px, 1.2fr) minmax(430px, 1.7fr) auto;
            align-items: center;
            gap: 12px 24px;
            margin: 0 0 14px !important;
            padding: 13px 22px !important;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 16px !important;
            background: linear-gradient(135deg, #0f2d5a 0%, #154a84 100%) !important;
            color: #fff !important;
            box-shadow: 0 10px 28px rgba(15, 45, 90, .16);
        }

        .diagnosis-hero h2 {
            margin: 2px 0 0 !important;
            color: #fff !important;
            font-size: clamp(25px, 2.5vw, 36px) !important;
            line-height: 1.15;
        }

        .diagnosis-hero__eyebrow,
        .diagnosis-hero__latin,
        .diagnosis-hero__label {
            margin: 0;
            color: #c9dbf2 !important;
        }

        .diagnosis-hero__eyebrow {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .diagnosis-hero__latin {
            margin-top: 4px;
            font-size: 13px;
            font-style: italic;
        }

        .diagnosis-hero__id {
            grid-column: 3;
            grid-row: 1;
            align-self: start;
            text-align: right;
        }

        .diagnosis-hero__id strong {
            display: block;
            margin-top: 1px;
            font-size: 26px;
            line-height: 1.15;
        }

        .diagnosis-hero__meta {
            display: grid;
            grid-column: 2;
            grid-row: 1;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .diagnosis-hero__meta-item {
            min-width: 0;
            padding: 9px 12px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 10px;
            background: rgba(255, 255, 255, .08);
        }

        .diagnosis-hero__meta-item strong {
            display: block;
            margin-top: 1px;
            overflow: hidden;
            font-size: 16px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .history-detail-grid {
            display: grid;
            min-height: 0;
            flex: 1 1 auto;
            grid-template-columns: minmax(0, 2fr) minmax(350px, .95fr);
            grid-template-rows:
                minmax(118px, .78fr)
                minmax(148px, .7fr)
                minmax(150px, 1fr);
            gap: 14px;
            margin: 0 !important;
        }

        .history-detail-primary,
        .history-detail-sidebar {
            display: contents;
        }

        .history-card {
            min-width: 0;
            padding: 16px 18px !important;
            overflow: hidden;
        }

        .history-card h3 {
            margin-bottom: 10px !important;
            font-size: 18px !important;
        }

        .history-description-card {
            grid-column: 1;
            grid-row: 1;
        }

        .history-description-card p {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            font-size: 14px;
            line-height: 1.55;
        }

        .history-solution-card {
            grid-column: 1;
            grid-row: 2 / 4;
            min-height: 0;
        }

        .history-solution-card > p {
            margin-bottom: 6px !important;
            font-size: 11px;
            line-height: 1.24;
            column-count: 2;
            column-gap: 22px;
            column-rule: 1px solid #e2e8f0;
            column-fill: balance;
        }

        .history-patient-card {
            grid-column: 2;
            grid-row: 1;
        }

        .history-patient-card .patient-details {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
        }

        .history-patient-card .patient-details > div {
            min-width: 0;
            padding: 8px 9px;
            border-radius: 9px;
            background: #f4f7fb;
        }

        .history-patient-card .patient-details p {
            overflow: hidden;
            margin: 0;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .history-certainty-card {
            grid-column: 2;
            grid-row: 2;
            padding-top: 13px !important;
            padding-bottom: 13px !important;
        }

        .history-certainty-card h3 {
            margin-bottom: 6px !important;
            font-size: 17px !important;
        }

        .history-certainty-card .certainty-content {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px 14px;
        }

        .history-certainty-card .certainty-bar {
            grid-column: 1 / -1;
        }

        .history-certainty-card .certainty-note {
            grid-column: 1 / -1;
            margin: 0 !important;
            padding: 6px 9px !important;
        }

        .history-symptoms-card {
            display: flex;
            min-height: 0;
            grid-column: 2;
            grid-row: 3;
            flex-direction: column;
        }

        .history-symptoms-card h3 {
            flex: 0 0 auto;
        }

        .symptom-scroll {
            min-height: 0;
            flex: 1 1 auto;
            overflow-x: hidden;
            overflow-y: auto;
            border: 1px solid #e0e8f2;
            border-radius: 9px;
            scrollbar-color: #8ba9cc transparent;
            scrollbar-width: thin;
        }

        .symptom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .symptom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .symptom-scroll::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #8ba9cc;
        }

        .symptom-scroll thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .symptom-scroll th,
        .symptom-scroll td {
            padding: 8px 9px !important;
        }

        .history-solution-card .disease-education {
            display: grid;
            grid-template-areas:
                "heading heading"
                "cards cards"
                "warning source";
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 10px;
            margin-top: 6px;
            padding-top: 6px;
        }

        .history-solution-card .disease-education__heading {
            display: flex;
            grid-area: heading;
            align-items: center;
            gap: 10px;
            margin-bottom: 0;
        }

        .history-solution-card .disease-education__heading h3 {
            margin: 0 !important;
            font-size: 14px !important;
        }

        .history-solution-card .disease-education__eyebrow {
            flex: 0 0 auto;
            margin: 0;
            font-size: 9px;
        }

        .history-solution-card .disease-education__heading p,
        .history-solution-card .education-card__body p,
        .history-solution-card .disease-education__source p {
            font-size: 11px;
            line-height: 1.35;
        }

        .history-solution-card .disease-education__heading > p {
            display: none;
        }

        .history-solution-card .disease-education__grid {
            grid-area: cards;
            gap: 10px;
        }

        .history-solution-card .education-card {
            display: grid;
            height: 88px;
            min-height: 88px;
            grid-template-columns: clamp(118px, 31%, 150px) minmax(0, 1fr);
        }

        .history-solution-card .education-card__image {
            width: 100%;
            height: 100%;
            min-height: 0;
            aspect-ratio: auto;
            border-right: 1px solid #d6e1ee;
            border-bottom: 0;
        }

        .history-solution-card .education-card__body {
            min-width: 0;
            padding: 6px 8px;
        }

        .history-solution-card .education-card__label {
            max-width: 100%;
            margin-bottom: 2px;
            padding: 2px 5px;
            overflow: hidden;
            font-size: 7px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .history-solution-card .education-card__body h4 {
            margin-bottom: 2px;
            overflow: hidden;
            font-size: 12px !important;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .history-solution-card .education-card__body p {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            font-size: 9px;
            line-height: 1.2;
        }

        .history-solution-card .disease-education__warning,
        .history-solution-card .disease-education__source {
            margin-top: 0;
            padding: 6px 8px;
        }

        .history-solution-card .disease-education__warning {
            grid-area: warning;
        }

        .history-solution-card .disease-education__source {
            grid-area: source;
        }

        .history-solution-card .disease-education__warning p {
            font-size: 8.5px;
            line-height: 1.18;
        }

        .history-solution-card .disease-education__source p {
            font-size: 8.5px;
            line-height: 1.18;
        }

        .history-solution-card .disease-education__image-note {
            display: none;
        }

        /* Tata letak tab agar informasi tetap lengkap tanpa mengecilkan teks. */
        .history-solution-card {
            display: flex;
            flex-direction: column;
        }

        .treatment-tabs {
            display: flex;
            min-height: 0;
            flex: 1 1 auto;
            flex-direction: column;
        }

        .treatment-tablist {
            display: flex;
            flex: 0 0 auto;
            gap: 6px;
            padding: 4px;
            border: 1px solid #dbe5f1;
            border-radius: 10px;
            background: #f3f7fc;
        }

        .treatment-tab {
            display: inline-flex;
            min-height: 34px;
            flex: 1 1 0;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 6px 12px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #53657d;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .treatment-tab[aria-selected="true"] {
            background: #fff;
            color: #0867c5;
            box-shadow: 0 2px 8px rgba(15, 45, 90, .1);
        }

        .treatment-tab:focus-visible {
            outline: 3px solid rgba(8, 103, 197, .2);
            outline-offset: 1px;
        }

        .treatment-panel {
            min-height: 0;
            flex: 1 1 auto;
            padding-top: 10px;
        }

        .treatment-panel[hidden] {
            display: none !important;
        }

        .solution-intro {
            margin: 0 0 7px;
            color: #42536a;
            font-size: 13px;
            font-weight: 700;
        }

        .solution-steps {
            display: grid;
            margin: 0;
            padding: 0;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 12px;
            list-style: none;
            counter-reset: solution-step;
        }

        .solution-steps li {
            position: relative;
            min-height: 48px;
            padding: 9px 10px 9px 43px;
            border: 1px solid #dce6f2;
            border-radius: 9px;
            background: #f8fafd;
            color: #26364b;
            font-size: 12px;
            line-height: 1.35;
            counter-increment: solution-step;
        }

        .solution-steps li::before {
            position: absolute;
            top: 9px;
            left: 10px;
            display: grid;
            width: 24px;
            height: 24px;
            place-items: center;
            border-radius: 50%;
            background: #e6f1ff;
            color: #0867c5;
            font-size: 11px;
            font-weight: 800;
            content: counter(solution-step);
        }

        .solution-fallback {
            margin: 0;
            color: #26364b;
            font-size: 13px;
            line-height: 1.55;
        }

        .treatment-panel--visual .disease-education {
            display: grid;
            margin: 0;
            padding: 0;
            border: 0;
            grid-template-areas:
                "heading heading"
                "condition warning"
                "medicine source";
            grid-template-columns: minmax(0, 1.55fr) minmax(250px, .9fr);
            gap: 8px 10px;
        }

        .treatment-panel--visual .disease-education__heading {
            grid-area: heading;
        }

        .treatment-panel--visual .disease-education__heading h3 {
            font-size: 15px !important;
        }

        .treatment-panel--visual .disease-education__grid {
            display: contents;
        }

        .treatment-panel--visual .education-card:nth-child(1) {
            grid-area: condition;
        }

        .treatment-panel--visual .education-card:nth-child(2) {
            grid-area: medicine;
        }

        .treatment-panel--visual .education-card {
            display: grid;
            min-height: 88px;
            align-self: stretch;
            overflow: hidden;
            grid-template-columns: 132px minmax(0, 1fr);
        }

        .treatment-panel--visual .education-card__image {
            width: 100%;
            height: 100%;
            min-height: 0;
            aspect-ratio: auto;
            border-right: 1px solid #d6e1ee;
            border-bottom: 0;
        }

        .treatment-panel--visual .education-card__body {
            min-width: 0;
            padding: 7px 9px;
        }

        .treatment-panel--visual .education-card__label {
            max-width: 100%;
            margin-bottom: 3px;
            padding: 2px 6px;
            overflow: hidden;
            font-size: 8px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .treatment-panel--visual .education-card__body h4 {
            margin-bottom: 3px;
            overflow: hidden;
            font-size: 13px !important;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .treatment-panel--visual .education-card__body p {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            font-size: 11px;
            line-height: 1.3;
        }

        .treatment-panel--visual .disease-education__warning {
            grid-area: warning;
        }

        .treatment-panel--visual .disease-education__source {
            grid-area: source;
        }

        .treatment-panel--visual .disease-education__warning,
        .treatment-panel--visual .disease-education__source {
            align-self: stretch;
            margin: 0;
            padding: 9px 10px;
        }

        .treatment-panel--visual .disease-education__warning p,
        .treatment-panel--visual .disease-education__source p {
            font-size: 10.5px;
            line-height: 1.3;
        }

        /* Struktur utama sederhana: satu kartu detail dan satu kartu gejala. */
        .history-detail-grid.simple-detail-grid {
            grid-template-columns: minmax(0, 2fr) minmax(360px, .9fr);
            grid-template-rows: minmax(0, 1fr);
        }

        .diagnosis-detail-card {
            display: flex;
            min-height: 0;
            grid-column: 1;
            grid-row: 1;
            flex-direction: column;
        }

        .diagnosis-detail-card > h3 {
            flex: 0 0 auto;
        }

        .diagnosis-detail-card .treatment-tabs {
            min-height: 0;
            overflow: hidden;
        }

        .diagnosis-detail-card .treatment-tab {
            min-height: 40px;
            font-size: 13px;
        }

        .diagnosis-detail-card .treatment-panel {
            overflow-x: hidden;
            overflow-y: auto;
            padding-right: 6px;
            scrollbar-color: #8ba9cc transparent;
            scrollbar-width: thin;
            overscroll-behavior: contain;
        }

        .diagnosis-detail-card .treatment-panel::-webkit-scrollbar {
            width: 6px;
        }

        .diagnosis-detail-card .treatment-panel::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #8ba9cc;
        }

        .diagnosis-detail-card .treatment-panel::-webkit-scrollbar-track {
            background: transparent;
        }

        .diagnosis-description-panel {
            padding: 18px 4px 0;
        }

        .diagnosis-description-panel .description-lead {
            margin: 0 0 14px;
            color: #1f3047;
            font-size: 16px;
            line-height: 1.65;
        }

        .diagnosis-description-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid #cfe0f3;
            border-radius: 10px;
            background: #f3f8fe;
            color: #4d6079;
            font-size: 12px;
            line-height: 1.45;
        }

        .diagnosis-description-note .material-symbols-outlined {
            flex: 0 0 auto;
            color: #0867c5;
        }


        .diagnosis-detail-card .solution-steps {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .diagnosis-detail-card .solution-steps li {
            min-height: 46px;
            font-size: 13px;
            line-height: 1.4;
        }

        .diagnosis-symptoms-card {
            display: flex;
            min-height: 0;
            grid-column: 2;
            grid-row: 1;
            flex-direction: column;
        }

        .diagnosis-confidence {
            flex: 0 0 auto;
            margin-bottom: 12px;
            padding: 12px;
            border: 1px solid #d6e3f3;
            border-radius: 10px;
            background: #f5f9fe;
        }

        .diagnosis-confidence__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .diagnosis-confidence__top span {
            color: #596b82;
            font-size: 12px;
        }

        .diagnosis-confidence__top strong {
            color: #0867c5;
            font-size: 24px;
            line-height: 1;
        }

        .diagnosis-confidence__bar {
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: #dce5ef;
        }

        .diagnosis-confidence__bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #58a0f6, #1768df);
        }

        .diagnosis-confidence__label {
            display: block;
            margin-top: 7px;
            color: #155fc5;
            font-size: 12px;
            font-weight: 800;
        }

        .diagnosis-symptoms-card .symptom-scroll {
            min-height: 0;
            flex: 1 1 auto;
        }

        .diagnosis-hero__meta-secondary {
            display: block;
            margin-top: 2px;
            overflow: hidden;
            color: #d7e5f5;
            font-size: 11px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .diagnosis-hero__meta-time {
            display: block;
            margin-top: 2px;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.25;
        }

        @media (min-width: 1101px) and (min-height: 850px) {
            .treatment-tab {
                min-height: 38px;
                font-size: 13px;
            }

            .solution-intro,
            .solution-fallback {
                font-size: 14px;
            }

            .solution-steps {
                gap: 10px 14px;
            }

            .solution-steps li {
                min-height: 58px;
                padding: 11px 12px 11px 48px;
                font-size: 13px;
                line-height: 1.42;
            }

            .solution-steps li::before {
                top: 11px;
                left: 12px;
                width: 26px;
                height: 26px;
            }

            .treatment-panel--visual .education-card {
                min-height: 104px;
                grid-template-columns: 145px minmax(0, 1fr);
            }

            .treatment-panel--visual .education-card__body p {
                font-size: 11.5px;
                line-height: 1.35;
            }

            .treatment-panel--visual .disease-education__warning p,
            .treatment-panel--visual .disease-education__source p {
                font-size: 11px;
                line-height: 1.35;
            }

            .diagnosis-detail-card .solution-steps li {
                min-height: 62px;
                font-size: 14px;
                line-height: 1.48;
            }

            .diagnosis-description-panel .description-lead {
                font-size: 17px;
            }
        }

        @media (max-width: 1100px) {
            html,
            body.admin-detail-history-page {
                height: auto;
            }

            body.admin-detail-history-page {
                display: block;
                min-height: 100dvh;
                overflow-y: auto;
            }

            body.admin-detail-history-page > main.history-detail-main {
                display: block;
                overflow: visible;
                padding: 18px 16px 60px !important;
            }

            .history-detail-grid {
                display: grid;
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }

            .history-detail-grid.simple-detail-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
            }

            .history-detail-primary,
            .history-detail-sidebar {
                display: contents;
            }

            .diagnosis-hero {
                grid-template-columns: minmax(0, 1fr) auto;
            }

            .diagnosis-hero__id {
                grid-column: 2;
                grid-row: 1;
            }

            .diagnosis-hero__meta {
                grid-column: 1 / -1;
                grid-row: auto;
            }

            .history-description-card,
            .history-solution-card,
            .history-patient-card,
            .history-certainty-card,
            .history-symptoms-card {
                grid-column: 1;
                grid-row: auto;
            }

            .diagnosis-detail-card,
            .diagnosis-symptoms-card {
                grid-column: 1;
                grid-row: auto;
            }

            .diagnosis-detail-card {
                min-height: 480px;
            }

            .diagnosis-detail-card .treatment-tabs,
            .diagnosis-detail-card .treatment-panel {
                overflow: visible;
            }

            .diagnosis-symptoms-card {
                min-height: 380px;
            }

            .history-description-card { order: 1; }
            .history-patient-card { order: 2; }
            .history-certainty-card { order: 3; }
            .history-solution-card { order: 4; }
            .history-symptoms-card {
                order: 5;
                min-height: 320px;
            }

            .symptom-scroll {
                max-height: 320px;
            }

            .treatment-panel--visual .disease-education {
                grid-template-areas:
                    "heading"
                    "condition"
                    "medicine"
                    "warning"
                    "source";
                grid-template-columns: 1fr;
            }

            .diagnosis-detail-card .treatment-tab {
                font-size: 12px;
            }
        }

        @media (max-width: 640px) {
            .history-header-actions {
                gap: 7px !important;
            }

            .history-header-admin,
            .history-print-button span:last-child,
            .history-back-button span:last-child {
                display: none;
            }

            .history-print-button,
            .history-back-button {
                width: 42px;
                padding: 0;
            }

            .diagnosis-hero {
                grid-template-columns: 1fr;
                padding: 15px !important;
            }

            .diagnosis-hero__id {
                position: absolute;
                grid-column: 1;
                right: 15px;
                text-align: right;
            }

            .diagnosis-hero__meta {
                grid-column: 1;
                grid-template-columns: 1fr;
            }

            .history-patient-card .patient-details {
                grid-template-columns: 1fr;
            }

            .diagnosis-detail-card .treatment-tab {
                min-width: 42px;
                padding-inline: 8px;
                font-size: 11px;
            }

            .diagnosis-detail-card .treatment-tab .material-symbols-outlined {
                display: none;
            }

            .detail-action-button {
                width: 34px;
                padding: 0;
            }

            .detail-action-button--back {
                width: 40px;
                flex-basis: 40px;
            }

            .detail-action-button__label {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
            }

            .history-admin-identity span:last-child {
                display: none;
            }
        }
    </style>
</head>
<body class="admin-shell admin-sidebar-shell admin-detail-history-page bg-background text-on-surface font-body-md">

<?php require __DIR__ . '/includes/sidebar.php'; ?>

<!-- Header -->
<header class="bg-blue-600 text-white sticky top-0 shadow-sm z-40">
    <div class="flex justify-between items-center px-6 py-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
            <a
                href="<?php echo htmlspecialchars($detail_back_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                class="detail-action-button detail-action-button--back"
                title="Kembali ke Riwayat Diagnosis"
            >
                <span class="detail-action-button__back-icon" aria-hidden="true">&larr;</span>
                <span class="detail-action-button__label">Kembali</span>
            </a>
            <h1 class="text-2xl font-bold text-white">Detail Riwayat Diagnosis</h1>
        </div>
        <div class="admin-header-actions">
            <a href="../logout.php" onclick="confirmLogout('../logout.php', event)" class="admin-header-logout">Keluar</a>
        </div>
    </div>
</header>

<main class="history-detail-main">

    <!-- Diagnosis Result Hero -->
    <section class="diagnosis-hero">
        <div>
            <p class="diagnosis-hero__eyebrow">HASIL ANALISIS</p>
            <h2><?php echo htmlspecialchars($riwayat['penyakit_tertinggi']); ?></h2>
            <p class="diagnosis-hero__latin"><?php echo htmlspecialchars($penyakit['nama_latin'] ?? '-'); ?></p>
        </div>
        <div class="diagnosis-hero__id">
            <p class="diagnosis-hero__label">ID Laporan</p>
            <strong>#<?php echo str_pad($riwayat_id, 3, '0', STR_PAD_LEFT); ?></strong>
        </div>

        <div class="diagnosis-hero__meta">
            <div class="diagnosis-hero__meta-item">
                <p class="diagnosis-hero__label">Tanggal Diagnosis</p>
                <strong><?php echo $tanggal->format('d M Y'); ?></strong>
                <time
                    class="diagnosis-hero__meta-time"
                    datetime="<?php echo htmlspecialchars($tanggal->format(DateTimeInterface::ATOM)); ?>"
                ><?php echo $tanggal->format('H:i'); ?> WIB</time>
            </div>
            <div class="diagnosis-hero__meta-item">
                <p class="diagnosis-hero__label">Pengguna</p>
                <strong><?php echo htmlspecialchars($riwayat['nama']); ?></strong>
                <span class="diagnosis-hero__meta-secondary"><?php echo htmlspecialchars($riwayat['email']); ?></span>
            </div>
        </div>
    </section>

    <!-- Dua area utama: detail diagnosis dan gejala. -->
    <div class="history-detail-grid simple-detail-grid">
        <section class="history-card diagnosis-detail-card bg-white rounded-lg shadow">
            <div class="detail-card-heading">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">clinical_notes</span>
                    Detail Diagnosis
                </h3>
                <div class="detail-card-actions" aria-label="Tindakan laporan">
                    <a
                        href="<?php echo htmlspecialchars($history_report_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                        class="detail-action-button detail-action-button--primary"
                        title="Cetak laporan"
                    >
                        <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                        <span class="detail-action-button__label">Cetak</span>
                    </a>
                </div>
            </div>

            <div class="treatment-tabs" data-treatment-tabs>
                <div class="treatment-tablist" role="tablist" aria-label="Detail diagnosis">
                    <button
                        class="treatment-tab"
                        id="diagnosis-tab-description"
                        type="button"
                        role="tab"
                        aria-selected="true"
                        aria-controls="diagnosis-panel-description"
                    >
                        <span class="material-symbols-outlined text-base" aria-hidden="true">description</span>
                        Deskripsi
                    </button>
                    <button
                        class="treatment-tab"
                        id="diagnosis-tab-steps"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        aria-controls="diagnosis-panel-steps"
                        tabindex="-1"
                    >
                        <span class="material-symbols-outlined text-base" aria-hidden="true">format_list_numbered</span>
                        Penanganan
                    </button>
                    <button
                        class="treatment-tab"
                        id="diagnosis-tab-visual"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        aria-controls="diagnosis-panel-visual"
                        tabindex="-1"
                    >
                        <span class="material-symbols-outlined text-base" aria-hidden="true">image</span>
                        Visual &amp; Obat
                    </button>
                </div>

                <section
                    class="treatment-panel diagnosis-description-panel"
                    id="diagnosis-panel-description"
                    role="tabpanel"
                    aria-labelledby="diagnosis-tab-description"
                >
                    <p class="description-lead">
                        <?php echo htmlspecialchars($penyakit['deskripsi'] ?? 'Tidak ada deskripsi'); ?>
                    </p>
                    <div class="diagnosis-description-note">
                        <span class="material-symbols-outlined" aria-hidden="true">info</span>
                        <span>
                            Hasil ini merupakan diagnosis awal berdasarkan gejala dan perhitungan Certainty Factor.
                            Pemeriksaan dokter hewan tetap diperlukan untuk memastikan kondisi burung.
                        </span>
                    </div>
                </section>

                <section
                    class="treatment-panel treatment-panel--steps"
                    id="diagnosis-panel-steps"
                    role="tabpanel"
                    aria-labelledby="diagnosis-tab-steps"
                    hidden
                >
                    <?php if ($solution_intro !== []): ?>
                        <p class="solution-intro"><?php echo htmlspecialchars(implode(' ', $solution_intro)); ?></p>
                    <?php endif; ?>

                    <?php if ($solution_steps !== []): ?>
                        <ol class="solution-steps">
                            <?php foreach ($solution_steps as $solution_step): ?>
                                <li><?php echo htmlspecialchars($solution_step); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="solution-fallback"><?php echo nl2br(htmlspecialchars($solution_text)); ?></p>
                    <?php endif; ?>
                </section>

                <section
                    class="treatment-panel treatment-panel--visual"
                    id="diagnosis-panel-visual"
                    role="tabpanel"
                    aria-labelledby="diagnosis-tab-visual"
                    hidden
                >
                    <?php
                    renderDiseaseEducation(
                        $penyakit['kode_penyakit'] ?? '',
                        $penyakit['nama_penyakit'] ?? ''
                    );
                    ?>
                </section>

            </div>
        </section>

        <aside class="history-card history-symptoms-card diagnosis-symptoms-card bg-white rounded-lg shadow">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">fact_check</span>
                Gejala Teridentifikasi
            </h3>

            <div class="diagnosis-confidence" aria-label="Tingkat kepastian diagnosis">
                <div class="diagnosis-confidence__top">
                    <span>Certainty Factor</span>
                    <strong><?php echo $cf_percentage; ?>%</strong>
                </div>
                <div class="diagnosis-confidence__bar" aria-hidden="true">
                    <span style="width: <?php echo $cf_percentage; ?>%"></span>
                </div>
                <span class="diagnosis-confidence__label">
                    <?php echo htmlspecialchars($certainty_interpretation); ?>
                </span>
            </div>

            <div class="symptom-scroll">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2 text-left">KODE</th>
                            <th class="px-4 py-2 text-left">NAMA GEJALA</th>
                            <th class="px-4 py-2 text-center">TINGKAT YAKIN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_gejala as $id_gejala => $cf_user):
                            $gejala = fetchOne('SELECT kode_gejala, nama_gejala FROM gejala WHERE id_gejala = ?', [$id_gejala]);
                            $kode_gejala = $gejala['kode_gejala'] ?? ('ID-' . $id_gejala);
                            $nama_gejala = $gejala['nama_gejala'] ?? 'Gejala dari basis pengetahuan lama';
                        ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                        <?php echo htmlspecialchars($kode_gejala); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($nama_gejala); ?></td>
                                <td class="px-4 py-3 text-center font-semibold">
                                    <?php echo htmlspecialchars(getUserCertaintyLabel($cf_user)); ?>
                                    (<?php echo number_format((float) $cf_user, 1, ',', ''); ?>)
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </aside>
    </div>

</main>

<script>
document.querySelectorAll('[data-treatment-tabs]').forEach(function(tabGroup) {
    const tabs = Array.from(tabGroup.querySelectorAll('[role="tab"]'));
    const panels = Array.from(tabGroup.querySelectorAll('[role="tabpanel"]'));

    function activateTab(nextTab) {
        tabs.forEach(function(tab) {
            const selected = tab === nextTab;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
        });

        panels.forEach(function(panel) {
            panel.hidden = panel.id !== nextTab.getAttribute('aria-controls');
        });
    }

    tabs.forEach(function(tab, index) {
        tab.addEventListener('click', function() {
            activateTab(tab);
        });

        tab.addEventListener('keydown', function(event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextIndex = (index + direction + tabs.length) % tabs.length;
            tabs[nextIndex].focus();
            activateTab(tabs[nextIndex]);
        });
    });
});

</script>

<?php $logout_url = '../logout.php'; include __DIR__ . '/../includes/logout-confirmation.php'; ?>

</body>
</html>

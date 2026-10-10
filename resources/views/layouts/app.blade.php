<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        @yield(
            'title',
            'Senador Coco Lumber and Construction Supply'
        )
    </title>

    <style>
        /* =========================================================
           GLOBAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            background: #f5f7fb;
            color: #182033;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .shell {
            min-height: 100vh;
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 218px;
            background: #0d192d;
            color: #ffffff;
            padding: 22px 10px;
            z-index: 10;
            overflow-y: auto;

            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .brand-logo {
            display: block;
            width: 96px;
            height: 96px;
            margin: 0 auto 8px;
            border-radius: 50%;
            object-fit: cover;
        }

        .role {
            color: #72a7ff;
            font-size: 11px;
            font-weight: 700;
            padding: 6px 10px 26px;
        }


        /* =========================================================
           NAVIGATION
        ========================================================= */

        .nav a,
        .logout {
            display: block;
            width: 100%;
            padding: 11px 14px;
            margin: 3px 0;
            border-radius: 6px;
            color: #d7deea;
            text-decoration: none;
            font-size: 13px;
            background: transparent;
            border: 0;
            text-align: left;
            cursor: pointer;

            transition:
                background .18s ease,
                color .18s ease;
        }

        .nav a:hover,
        .nav a.active {
            background: #2468ee;
            color: #ffffff;
        }

        .nav {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .logout {
            margin-top: auto;
        }

        .logout:hover {
            background: rgba(255, 255, 255, .08);
            color: #ffffff;
        }


        /* =========================================================
           MAIN CONTENT
        ========================================================= */

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        .main {
            margin-left: 218px;
            padding: 26px 30px;
            height: 100vh;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .app-date-time {
            position: absolute;
            top: 22px;
            right: 30px;
            z-index: 4;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 3px;
            color: #0f172a;
            line-height: 1.2;
            text-align: right;
            pointer-events: none;
        }

        .app-date-time .app-time {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: .2px;
        }

        .app-date-time .app-date {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
        }

        .app-backup-status {
            position: absolute;
            top: 36px;
            right: 265px;
            z-index: 7;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border: 0 !important;
            border-radius: 999px !important;
            background: #ffffff !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .12) !important;
            cursor: default;
        }

        .app-backup-status-dot {
            width: 10px;
            height: 10px;
            display: block;
            border-radius: 999px;
        }

        .app-backup-status.is-online .app-backup-status-dot {
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, .14);
        }

        .app-backup-status.is-offline .app-backup-status-dot {
            background: #94a3b8;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, .16);
        }

        .app-backup-status-text {
            position: absolute;
            top: 25px;
            right: 50%;
            min-width: max-content;
            padding: 7px 10px;
            border-radius: 9px;
            color: #ffffff;
            background: #0f172a;
            font-size: 12px;
            font-weight: 800;
            opacity: 0;
            pointer-events: none;
            transform: translateX(50%) translateY(-4px);
            transition: opacity .16s ease, transform .16s ease;
        }

        .app-backup-status:hover .app-backup-status-text {
            opacity: 1;
            transform: translateX(50%) translateY(0);
        }

        .app-date-time-spacer {
            display: none;
        }

        .app-module-heading {
            min-height: 48px;
            margin-bottom: 18px;
            padding-right: 230px;
            display: block !important;
            visibility: visible !important;
        }

        .app-module-heading h1 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -.3px;
            display: block !important;
            visibility: visible !important;
        }

        .app-module-heading p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            display: block !important;
            visibility: visible !important;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 34px;
        }

        .top h1 {
            font-size: 27px;
            margin: 0 0 4px;
        }

        .muted {
            color: #7b879d;
            font-size: 12px;
        }

        .who {
            font-size: 12px;
            color: #69758b;
            font-weight: 700;
        }


        /* =========================================================
           CARDS
        ========================================================= */

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }


        /* =========================================================
           TOOLBAR
        ========================================================= */

        .toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 26px;
            gap: 10px;
        }


        /* =========================================================
           BUTTONS
        ========================================================= */

        .btn {
            border: 0;
            border-radius: 7px;
            padding: 11px 20px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;

            transition:
                transform .15s ease,
                opacity .15s ease,
                box-shadow .15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .primary {
            background: #2468ee;
            color: #ffffff;
        }

        .secondary {
            background: #46536a;
            color: #ffffff;
        }

        .success {
            background: #12a957;
            color: #ffffff;
        }

        .danger {
            background: #dc3545;
            color: #ffffff;
        }

        .light {
            background: #eef2f8;
            color: #243047;
        }

        .small {
            padding: 7px 10px;
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .table th {
            background: #e9eef7;
            color: #758198;
            font-size: 11px;
            text-align: left;
            padding: 13px;
        }

        .table td {
            padding: 14px 13px;
            border-bottom: 1px solid #edf0f5;
            font-size: 12px;
        }


        /* =========================================================
           BADGES
        ========================================================= */

        .badge {
            font-size: 11px;
            padding: 5px 8px;
            border-radius: 999px;
            background: #eef2f7;
        }

        .low {
            color: #b42318;
        }


        /* =========================================================
           DASHBOARD
        ========================================================= */

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .stat {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
        }

        .stat b {
            font-size: 26px;
            display: block;
            margin-top: 8px;
        }


        /* =========================================================
           FORMS
        ========================================================= */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #dfe5ee;
            background: #f5f7fb;
            border-radius: 7px;
            padding: 11px;
            outline: none;

            transition:
                border-color .18s ease,
                box-shadow .18s ease,
                background .18s ease;
        }

        .input:focus,
        select:focus,
        textarea:focus {
            border-color: #2468ee;
            background: #ffffff;
            box-shadow:
                0 0 0 3px rgba(36, 104, 238, .08);
        }


        /* =========================================================
           ALERTS
        ========================================================= */

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 18px;
            background: #eaf8ef;
            color: #18733a;
            font-size: 13px;
        }

        .alert.err {
            background: #fff0f0;
            color: #a52323;
        }


        /* =========================================================
           TABS
        ========================================================= */

        .tabs {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 4px;
            margin-bottom: 18px;
            border-bottom: 1px solid #dbe4f0;
        }

        .tabs a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border: 1px solid #e1e7ef;
            border-bottom: 0;
            border-radius: 10px 10px 0 0;
            background: #eef3f9;
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .tabs a.active {
            background: #ffffff;
            color: #2563eb;
            border-color: #dbe4f0;
            box-shadow: 0 -1px 0 rgba(15, 23, 42, .03);
        }


        /* =========================================================
           SALES
        ========================================================= */

        .order-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 22px;
        }

        .product-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f5f7fb;
            border-radius: 9px;
            margin: 10px 0;
        }


        /* =========================================================
           ACTIONS
        ========================================================= */

        .actions {
            display: flex;
            gap: 6px;
        }

        .note {
            font-size: 11px;
            color: #7b879d;
            margin-top: 24px;
        }


        /* =========================================================
           LOGIN SUCCESS MODAL
        ========================================================= */

        .login-success-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;

            background: rgba(13, 25, 45, .62);
            backdrop-filter: blur(4px);

            animation: overlayFadeIn .18s ease;
        }

        .login-success-modal {
            width: 100%;
            max-width: 420px;

            background: #ffffff;
            border-radius: 18px;

            box-shadow:
                0 28px 80px rgba(15, 23, 42, .28);

            overflow: hidden;

            animation: modalOpen .23s ease-out;
        }

        .login-success-content {
            padding: 38px 34px 32px;
            text-align: center;
        }

        .login-success-icon {
            width: 68px;
            height: 68px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 21px;

            border-radius: 50%;

            background: #dcfce7;
            color: #16a34a;
        }

        .login-success-modal h2 {
            margin: 0 0 9px;

            color: #182033;

            font-size: 23px;
            font-weight: 800;
        }

        .login-success-welcome {
            margin: 0 0 10px;

            color: #2468ee;

            font-size: 14px;
            font-weight: 700;
        }

        .login-success-description {
            margin: 0 auto 18px;

            max-width: 330px;

            color: #7b879d;

            font-size: 12px;
            line-height: 1.65;
        }

        .login-success-role {
            display: inline-block;

            margin-bottom: 25px;

            padding: 6px 12px;

            border-radius: 999px;

            background: #eef4ff;
            color: #2468ee;

            font-size: 11px;
            font-weight: 800;
        }

        .login-success-button {
            width: 100%;

            border: 0;
            border-radius: 8px;

            padding: 14px 20px;

            background: #2468ee;
            color: #ffffff;

            font-size: 12px;
            font-weight: 800;

            cursor: pointer;

            transition:
                background .18s ease,
                transform .15s ease,
                box-shadow .18s ease;
        }

        .login-success-button:hover {
            background: #1d5edc;

            transform: translateY(-1px);

            box-shadow:
                0 8px 20px rgba(36, 104, 238, .20);
        }


        /* =========================================================
           LOGOUT CONFIRMATION MODAL
        ========================================================= */

        .logout-modal-overlay {
            position: fixed;
            inset: 0;

            z-index: 100000;

            display: none;
            align-items: center;
            justify-content: center;

            padding: 20px;

            background: rgba(13, 25, 45, .62);
            backdrop-filter: blur(4px);
        }

        .logout-modal-overlay.show {
            display: flex;

            animation: overlayFadeIn .18s ease;
        }

        .logout-modal {
            width: 100%;
            max-width: 420px;

            background: #ffffff;

            border-radius: 18px;

            box-shadow:
                0 28px 80px rgba(15, 23, 42, .30);

            overflow: hidden;

            animation: modalOpen .23s ease-out;
        }

        .logout-modal-content {
            padding: 36px 32px 30px;

            text-align: center;
        }

        .logout-modal-icon {
            width: 64px;
            height: 64px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 20px;

            border-radius: 50%;

            background: #fff1f2;
            color: #dc3545;
        }

        .logout-modal h2 {
            margin: 0 0 10px;

            color: #182033;

            font-size: 23px;
            font-weight: 800;
        }

        .logout-modal-description {
            margin: 0 auto;

            max-width: 340px;

            color: #7b879d;

            font-size: 13px;
            line-height: 1.65;
        }

        .logout-system-name {
            display: block;

            margin-top: 10px;

            color: #46536a;

            font-weight: 700;
        }

        .logout-modal-actions {
            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 10px;

            margin-top: 28px;
        }

        .logout-cancel-button,
        .logout-confirm-button {
            width: 100%;

            border: 0;
            border-radius: 8px;

            padding: 13px 16px;

            font-size: 12px;
            font-weight: 800;

            cursor: pointer;

            transition:
                transform .15s ease,
                background .18s ease,
                opacity .18s ease;
        }

        .logout-cancel-button {
            background: #eef2f8;
            color: #46536a;
        }

        .logout-cancel-button:hover {
            background: #e1e7f0;
            transform: translateY(-1px);
        }

        .logout-confirm-button {
            background: #dc3545;
            color: #ffffff;
        }

        .logout-confirm-button:hover {
            background: #c92d3c;
            transform: translateY(-1px);
        }

        .logout-confirm-button:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }


        /* =========================================================
           MODAL ANIMATIONS
        ========================================================= */

        @keyframes overlayFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalOpen {
            from {
                opacity: 0;

                transform:
                    translateY(14px)
                    scale(.97);
            }

            to {
                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);
            }
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media(max-width: 900px) {

            .sidebar {
                position: static;
                width: 100%;
            }

            .main {
                margin-left: 0;
            }

            .grid {
                grid-template-columns: 1fr 1fr;
            }

            .order-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .nav {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
            }

            .nav a {
                width: auto;
            }

            .logout {
                width: auto;
                margin-top: 3px;
            }

            .top {
                margin-top: 10px;
            }
        }

        @media(max-width: 600px) {

            .main {
                padding: 20px 16px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .top {
                gap: 15px;
                flex-direction: column;
            }

            .toolbar {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .card {
                padding: 18px;
            }

            .login-success-content,
            .logout-modal-content {
                padding: 30px 23px 25px;
            }

            .logout-modal-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .app-date-time {
                position: static;
                align-items: flex-start;
                margin-bottom: 14px;
            }

            .app-backup-status {
                top: 36px;
                right: 30px;
            }

            .app-module-heading {
                min-height: 0;
                padding-right: 0;
            }

            .app-date-time-spacer {
                display: none;
            }
        }

        .page-sales-report form input[type="search"],
        .page-sales-report form input[name="q"],
        .page-sales-report form input[placeholder*="Search sale"] {
            width: 700px !important;
            min-width: 700px !important;
            max-width: 700px !important;
            flex: 0 0 700px !important;
            margin-right: 0 !important;
        }

        .page-sales-report form {
            width: 100% !important;
            justify-content: flex-start !important;
        }

        .page-sales-report form > *:first-child {
            margin-left: 0 !important;
        }

        .page-sales-report form input[type="search"]:first-child,
        .page-sales-report form input[name="q"]:first-child,
        .page-sales-report form input[placeholder*="Search sale"]:first-child {
            margin-left: 0 !important;
        }

        @media print {
            .app-date-time,
            .app-date-time-spacer {
                display: none !important;
            }
        }

        .page-activity .main,
        .page-users .main,
        .page-backup .main {
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .06), transparent 30%),
                #f4f7fb;
        }

        .page-activity .main > :not(.app-date-time):not(.app-date-time-spacer):not(.app-module-heading),
        .page-users .main > :not(.app-date-time):not(.app-date-time-spacer):not(.app-module-heading),
        .page-backup .main > :not(.app-date-time):not(.app-date-time-spacer):not(.app-module-heading):not(.app-backup-status) {
            border-radius: 16px;
        }

        .page-activity form,
        .page-users form,
        .page-backup form {
            border: 1px solid #e5ebf3;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .page-activity input,
        .page-activity select,
        .page-users input,
        .page-users select,
        .page-backup input,
        .page-backup select,
        input[type="date"] {
            min-height: 42px;
            border: 1px solid #d9e2ef;
            border-radius: 10px;
            background: #f8fbff;
            color: #0f172a;
            font-size: 14px;
            font-weight: 500;
        }

        input[type="date"] {
            min-width: 170px;
            padding: 0 14px;
            line-height: 42px;
            box-sizing: border-box;
            appearance: none;
            -webkit-appearance: none;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            display: block;
            width: 18px;
            height: 18px;
            margin-left: 8px;
            opacity: .75;
            cursor: pointer;
        }

        body:has(.sidebar a.active[href*="sales-report"]) input[type="date"],
        body:has(.sidebar a.active[href*="sales/report"]) input[type="date"],
        body:has(.sidebar a.active[href*="report"]) input[type="date"] {
            display: inline-flex;
            align-items: center;
            height: 42px;
            min-width: 150px;
            padding: 0 14px !important;
            vertical-align: middle;
        }

        .page-sales-report input[type="date"] {
            width: 170px !important;
            min-width: 170px !important;
            max-width: 170px !important;
            padding: 0 10px 0 14px !important;
            color: #0f172a !important;
            text-indent: 0;
            background-color: #f8fbff;
            background-image: none;
            cursor: pointer;
        }

        .page-sales-report input[type="date"]::-webkit-datetime-edit,
        .page-sales-report input[type="date"]::-webkit-datetime-edit-fields-wrapper,
        .page-sales-report input[type="date"]::-webkit-datetime-edit-text,
        .page-sales-report input[type="date"]::-webkit-datetime-edit-month-field,
        .page-sales-report input[type="date"]::-webkit-datetime-edit-day-field,
        .page-sales-report input[type="date"]::-webkit-datetime-edit-year-field {
            color: #0f172a;
        }

        .page-sales-report input[type="date"]::-webkit-calendar-picker-indicator {
            display: block !important;
            width: 18px;
            height: 18px;
            margin: 0 0 0 8px;
            opacity: .75;
            cursor: pointer;
        }

        .page-sales-report form input[type="search"],
        .page-sales-report form input[name="q"],
        .page-sales-report form input[placeholder*="Search sale"] {
            width: 38% !important;
            min-width: 320px;
            flex: 0 0 38% !important;
            margin-right: auto;
        }

        .page-sales-report form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .page-sales-report form button,
        .page-sales-report form a {
            flex: 0 0 auto;
        }

        .page-activity button,
        .page-activity .btn,
        .page-users button,
        .page-users .btn,
        .page-backup button,
        .page-backup .btn {
            min-height: 40px;
            border-radius: 10px;
            font-weight: 800;
        }

        .page-activity table,
        .page-users table,
        .page-backup table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .page-activity thead th,
        .page-users thead th,
        .page-backup thead th {
            background: #e8eef8;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .page-activity tbody td,
        .page-users tbody td,
        .page-backup tbody td {
            color: #0f172a;
            font-size: 13px;
            font-weight: 500;
            border-bottom: 1px solid #edf2f7;
        }

        .page-activity tbody tr:hover,
        .page-users tbody tr:hover,
        .page-backup tbody tr:hover {
            background: #f8fbff;
        }

        .page-activity .main > div:has(table),
        .page-users .main > div:has(table) {
            overflow: hidden;
            border: 1px solid #e5ebf3;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .page-activity .main > div:has(table) {
            max-height: calc(100vh - 230px);
            overflow-y: auto;
        }

        .page-users .main > div:has(table) {
            max-height: calc(100vh - 220px);
            overflow-y: auto;
        }

        .page-backup .main {
            display: flex;
            flex-direction: column;
        }

        .page-backup .main > div:not(.app-date-time):not(.app-date-time-spacer):not(.app-module-heading):not(.app-backup-status) {
            border: 1px solid #e5ebf3;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .role-salesclerk .sidebar a[href*="dashboard"],
        .role-salesclerk .sidebar a[href*="sales-report"],
        .role-salesclerk .sidebar a[href*="sales/report"] {
            display: none !important;
        }

        .role-salesclerk .sidebar {
            display: none !important;
        }

        .role-salesclerk .main {
            margin-left: 0 !important;
            padding: 24px 28px !important;
            height: 100vh;
            overflow: hidden;
        }

        .role-salesclerk .app-module-heading {
            min-height: 58px;
            margin-bottom: 8px;
            padding-right: 330px;
            display: flex !important;
            align-items: center;
            gap: 14px;
            justify-content: flex-start;
        }

        .role-salesclerk .app-module-heading h1,
        .role-salesclerk .app-module-heading p {
            display: none !important;
        }

        .role-salesclerk .app-module-heading::before {
            content: none;
            display: none;
        }

        .role-salesclerk .app-module-heading::after {
            content: none;
            display: none;
        }

        .salesclerk-top-logo {
            display: block;
            width: 78px;
            height: 78px;
            object-fit: contain;
            margin-left: 0;
            margin-right: auto;
            position: static !important;
            transform: none !important;
        }

        .salesclerk-signout {
            position: absolute;
            top: 82px;
            right: 30px;
            z-index: 6;
            display: none;
        }

        .role-salesclerk .salesclerk-signout {
            display: block;
        }

        .salesclerk-signout button {
            min-height: 38px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #ef4444;
            padding: 0;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: none;
        }

        .signout-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(2px);
        }

        .signout-modal-overlay.is-open {
            display: flex;
        }

        .signout-modal {
            width: min(420px, 100%);
            border-radius: 18px;
            background: #fff;
            padding: 26px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, .28);
            text-align: center;
        }

        .signout-modal-icon {
            display: grid;
            width: 48px;
            height: 48px;
            place-items: center;
            margin: 0 auto 16px;
            border-radius: 999px;
            background: #fee2e2;
            color: #ef4444;
            font-size: 22px;
            font-weight: 900;
        }

        .signout-modal h2 {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
        }

        .signout-modal p {
            margin: 10px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.45;
        }

        .signout-modal-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
        }

        .signout-modal-actions button {
            min-width: 110px;
            min-height: 42px;
            border: 0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .signout-cancel {
            background: #eef2f7;
            color: #0f172a;
        }

        .signout-confirm {
            background: #ef4444;
            color: #fff;
        }

        .role-salesclerk .app-date-time {
            right: 30px;
        }

        .user-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(2px);
        }

        .user-modal-overlay.is-open {
            display: flex;
        }

        .user-modal {
            width: min(720px, 100%);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 24px 80px rgba(15, 23, 42, .28);
        }

        .user-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 24px 28px 14px;
            border-bottom: 1px solid #edf2f7;
        }

        .user-modal-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
        }

        .user-modal-header p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .user-modal-close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            font-size: 22px;
            cursor: pointer;
        }

        .user-modal-body {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            padding: 22px 28px 10px;
        }

        .user-modal-field.full {
            grid-column: 1 / -1;
        }

        .user-modal-field label {
            display: block;
            margin-bottom: 8px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
        }

        .user-modal-field input,
        .user-modal-field select {
            width: 100%;
            min-height: 44px;
            border: 1px solid #d9e2ef;
            border-radius: 10px;
            background: #f8fbff;
            color: #0f172a;
            padding: 0 14px;
            font-size: 14px;
            font-weight: 500;
        }

        .user-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 18px 28px 28px;
        }

        @media (max-width: 720px) {
            .user-modal-body {
                grid-template-columns: 1fr;
            }
        }
</style>
</head>

<body class="@if(auth()->check() && strtolower(str_replace([' ', '_', '-'], '', auth()->user()->role)) === 'salesclerk') role-salesclerk @endif @if(request()->routeIs('dashboard') || request()->routeIs('dashboard.*') || request()->is('dashboard')) page-dashboard @endif @if(request()->is('sales-report*') || request()->is('sales/report*') || request()->routeIs('sales.report')) page-sales-report @endif @if(request()->is('activity*')) page-activity @endif @if(request()->is('users*')) page-users @endif @if(request()->is('backup*')) page-backup @endif">

<div class="shell">

    @auth

        {{-- =====================================================
             SIDEBAR
        ====================================================== --}}

        <aside class="sidebar">

            <img
                class="brand-logo"
                src="{{ asset('images/senador-coco-logo.png') }}"
                alt="Senador Coco logo"
            >

            <div class="role">

                {{
                    auth()->user()->role === 'OWNER'
                        ? 'Owner'
                        : 'Sales Clerk'
                }}

            </div>


            <nav class="nav">

                {{-- =================================================
                     OWNER
                ================================================== --}}

                @if(auth()->user()->role === 'OWNER')

                    <a
                        class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"
                    >
                        Dashboard
                    </a>

                    <a
                        class="{{ request()->routeIs('sales.create', 'sales.index', 'sales.store') ? 'active' : '' }}"
                        href="{{ route('sales.create') }}"
                    >
                        Sales Management
                    </a>

                    <a
                        class="{{ request()->routeIs('products.*', 'categories.*', 'units.*', 'inventory.*') ? 'active' : '' }}"
                        href="{{ route('products.index') }}"
                    >
                        Product Management
                    </a>

                    <a
                        class="{{ request()->routeIs('suppliers.*', 'purchases.*') ? 'active' : '' }}"
                        href="{{ route('suppliers.index') }}"
                    >
                        Supplier & Purchasing
                    </a>

                    <a
                        class="{{ request()->routeIs('sales.report') ? 'active' : '' }}"
                        href="{{ route('sales.report') }}"
                    >
                        Sales Report
                    </a>

                    <a
                        class="{{ request()->routeIs('activity.*') ? 'active' : '' }}"
                        href="{{ route('activity.index') }}"
                    >
                        Activity Logs
                    </a>

                    <a
                        class="{{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}"
                    >
                        User Management
                    </a>

                    <a
                        class="{{ request()->routeIs('backup.*') ? 'active' : '' }}"
                        href="{{ route('backup.index') }}"
                    >
                        Backup & Recovery
                    </a>

                @else

                    {{-- =================================================
                         SALES CLERK
                    ================================================== --}}

                    <a
                        class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"
                    >
                        Dashboard
                    </a>

                    <a
                        class="{{ request()->routeIs('sales.create', 'sales.index', 'sales.store') ? 'active' : '' }}"
                        href="{{ route('sales.create') }}"
                    >
                        Sales Management
                    </a>

                    <a
                        class="{{ request()->routeIs('sales.report') ? 'active' : '' }}"
                        href="{{ route('sales.report') }}"
                    >
                        Sales Report
                    </a>

                @endif


                {{-- =================================================
                     LOGOUT BUTTON
                ================================================== --}}

                <button
                    type="button"
                    class="logout"
                    id="openLogoutModal"
                >
                    Sign Out
                </button>


                {{-- Actual POST logout form --}}
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    id="logoutForm"
                    style="display:none;"
                >
                    @csrf
                </form>

            </nav>

        </aside>

    @endauth


    {{-- =========================================================
         MAIN CONTENT
    ========================================================== --}}

        <main class="main">
            @auth
                @php
                    if (request()->is('/') || request()->is('*dashboard*') || request()->routeIs('*dashboard*')) {
                        $moduleTitle = 'Dashboard';
                        $moduleDescription = 'Overview of today’s store activity.';
                    } elseif (request()->is('sales-report*') || request()->is('sales/report*') || request()->routeIs('sales.report')) {
                        $moduleTitle = 'Sales Report';
                        $moduleDescription = 'Review completed sales, totals, and transaction records.';
                    } elseif (request()->is('sales*') || request()->is('delivery*')) {
                        $moduleTitle = 'Sales Management';
                        $moduleDescription = 'Record sales, deliveries, and customer orders.';
                    } elseif (request()->is('products*') || request()->is('categories*') || request()->is('units*') || request()->is('inventory*')) {
                        $moduleTitle = 'Product Management';
                        $moduleDescription = 'Manage products, categories, units, and inventory.';
                    } elseif (request()->is('suppliers*') || request()->is('purchases*')) {
                        $moduleTitle = 'Supplier & Purchasing';
                        $moduleDescription = 'Manage suppliers and record material purchases.';
                    } elseif (request()->is('activity*')) {
                        $moduleTitle = 'Activity Logs';
                        $moduleDescription = 'Review user and transaction activity recorded by the system.';
                    } elseif (request()->is('users*')) {
                        $moduleTitle = 'User Management';
                        $moduleDescription = 'Manage authorized Owner and Sales Clerk accounts.';
                    } elseif (request()->is('backup*')) {
                        $moduleTitle = 'Backup & Recovery';
                        $moduleDescription = 'Create backups and restore valid database files.';
                    } else {
                        $moduleTitle = null;
                        $moduleDescription = null;
                    }
                @endphp

                @if($moduleTitle)
                    <div class="app-module-heading no-print">
                        <h1>{{ $moduleTitle }}</h1>
                        <p>{{ $moduleDescription }}</p>
                    </div>
                @endif

                <div class="app-date-time no-print" aria-live="polite">
                    <span class="app-time" id="appCurrentTime">{{ now()->format('g:i:s A') }}</span>
                    <span class="app-date" id="appCurrentDate">{{ now()->format('l, M d, Y') }}</span>
                </div>

                @php
                    $globalOnlineBackupPath = rtrim((string) config('services.online_backup.path'), '\\/');
                    $globalOnlineBackupReady = filled($globalOnlineBackupPath)
                        && \Illuminate\Support\Facades\File::isDirectory($globalOnlineBackupPath);
                @endphp

                <div
                    class="app-backup-status {{ $globalOnlineBackupReady ? 'is-online' : 'is-offline' }} no-print"
                    data-backup-folder-ready="{{ $globalOnlineBackupReady ? 'true' : 'false' }}"
                >
                    <span class="app-backup-status-dot"></span>
                    <span class="app-backup-status-text">
                        {{ $globalOnlineBackupReady ? 'Online backup ready' : 'Offline backup only' }}
                    </span>
                </div>
                <div class="app-date-time-spacer no-print" aria-hidden="true"></div>
            @endauth

        @if(session('success') && !request()->routeIs('sales.create', 'sales.index'))

            <div class="alert">
                {{ session('success') }}
            </div>

        @endif


        @if(session('error'))

            <div class="alert err">
                {{ session('error') }}
            </div>

        @endif


        @if($errors->any())

            <div class="alert err">
                {{ $errors->first() }}
            </div>

        @endif


        @yield('content')

    </main>

</div>


{{-- =============================================================
     LOGIN SUCCESS MODAL
============================================================== --}}

@auth

    @if(session('login_success'))

        <div
            id="loginSuccessModal"
            class="login-success-overlay"
            role="dialog"
            aria-modal="true"
            aria-labelledby="loginSuccessTitle"
        >

            <div class="login-success-modal">

                <div class="login-success-content">

                    <div class="login-success-icon">

                        <svg
                            width="32"
                            height="32"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <polyline
                                points="20 6 9 17 4 12"
                            ></polyline>
                        </svg>

                    </div>


                    <h2 id="loginSuccessTitle">
                        Login Successful
                    </h2>


                    <p class="login-success-welcome">
                        {{ session('login_success') }}
                    </p>


                    <p class="login-success-description">
                        You have successfully signed in to the
                        Senador Coco Lumber and Construction Supply
                        Inventory and Sales Management System.
                    </p>


                    <div class="login-success-role">

                        {{
                            auth()->user()->role === 'OWNER'
                                ? 'OWNER'
                                : 'SALES CLERK'
                        }}

                    </div>


                    <button
                        type="button"
                        class="login-success-button"
                        id="closeLoginSuccessModal"
                    >
                        CONTINUE
                    </button>

                </div>

            </div>

        </div>

    @endif

@endauth


{{-- =============================================================
     LOGOUT CONFIRMATION MODAL
============================================================== --}}

@auth

    <div
        id="logoutModal"
        class="logout-modal-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logoutModalTitle"
        aria-hidden="true"
    >

        <div class="logout-modal">

            <div class="logout-modal-content">

                <div class="logout-modal-icon">

                    <svg
                        width="30"
                        height="30"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path
                            d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                        ></path>

                        <polyline
                            points="16 17 21 12 16 7"
                        ></polyline>

                        <line
                            x1="21"
                            y1="12"
                            x2="9"
                            y2="12"
                        ></line>
                    </svg>

                </div>


                <h2 id="logoutModalTitle">
                    Sign Out?
                </h2>


                <p class="logout-modal-description">

                    Are you sure you want to sign out?

                    <span class="logout-system-name">
                        Senador Coco Lumber and Construction Supply<br>
                        Inventory and Sales Management System
                    </span>

                </p>


                <div class="logout-modal-actions">

                    <button
                        type="button"
                        class="logout-cancel-button"
                        id="cancelLogout"
                    >
                        CANCEL
                    </button>


                    <button
                        type="button"
                        class="logout-confirm-button"
                        id="confirmLogout"
                    >
                        SIGN OUT
                    </button>

                </div>

            </div>

        </div>

    </div>

@endauth


{{-- =============================================================
     PAGE-SPECIFIC SCRIPTS
============================================================== --}}

    @auth
        @if(strtolower(str_replace([' ', '_', '-'], '', auth()->user()->role)) === 'salesclerk')
            <form class="salesclerk-signout" method="POST" action="{{ url('/logout') }}">
                @csrf
                <button type="submit">Sign Out</button>
            </form>
        @endif

        <div class="signout-modal-overlay" id="signoutConfirmModal" aria-hidden="true">
            <div class="signout-modal" role="dialog" aria-modal="true" aria-labelledby="signoutConfirmTitle">
                <div class="signout-modal-icon">!</div>
                <h2 id="signoutConfirmTitle">Sign out?</h2>
                <p>Are you sure you want to sign out of your account?</p>
                <div class="signout-modal-actions">
                    <button type="button" class="signout-cancel" data-cancel-signout>Cancel</button>
                    <button type="button" class="signout-confirm" data-confirm-signout>Sign Out</button>
                </div>
            </div>
        </div>

        @if(request()->is('users*'))
            <div class="user-modal-overlay" id="userCreateModal" aria-hidden="true">
                <form class="user-modal" method="POST" action="{{ url('/users') }}">
                    @csrf

                    <div class="user-modal-header">
                        <div>
                            <h2>Add User</h2>
                            <p>Create an authorized account for the system.</p>
                        </div>
                        <button type="button" class="user-modal-close" data-close-user-modal aria-label="Close">&times;</button>
                    </div>

                    <div class="user-modal-body">
                        <div class="user-modal-field">
                            <label for="modal_first_name">First Name</label>
                            <input id="modal_first_name" name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name">
                        </div>

                        <div class="user-modal-field">
                            <label for="modal_last_name">Last Name</label>
                            <input id="modal_last_name" name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name">
                        </div>

                        <div class="user-modal-field">
                            <label for="modal_username">Username</label>
                            <input id="modal_username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username">
                        </div>

                        <div class="user-modal-field">
                            <label for="modal_role">Role</label>
                            <select id="modal_role" name="role" required>
                                <option value="salesclerk" @selected(old('role') === 'salesclerk')>Sales Clerk</option>
                                <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                            </select>
                        </div>

                        <div class="user-modal-field">
                            <label for="modal_password">Password</label>
                            <input id="modal_password" name="password" type="password" required autocomplete="new-password">
                        </div>

                        <div class="user-modal-field">
                            <label for="modal_password_confirmation">Confirm Password</label>
                            <input id="modal_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="user-modal-footer">
                        <button type="button" class="btn" data-close-user-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        @endif
    @endauth

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[pattern="\\d{11}"], input[pattern="09\\d{9}"], input[data-digits-only], input[name*="contact_number"]').forEach((input) => {
                input.addEventListener('input', () => {
                    const maxLength = Number(input.getAttribute('maxlength')) || 11;
                    input.value = input.value.replace(/\D/g, '').slice(0, maxLength);
                });
            });

            document.querySelectorAll('input[step="0.01"], input[data-decimal-places="2"]').forEach((input) => {
                input.addEventListener('input', () => {
                    const originalValue = input.value;
                    const originalPosition = input.selectionStart ?? originalValue.length;
                    let value = originalValue.replace(/[^\d.]/g, '');
                    const firstDotIndex = value.indexOf('.');

                    if (firstDotIndex !== -1) {
                        value = value.slice(0, firstDotIndex + 1)
                            + value.slice(firstDotIndex + 1).replace(/\./g, '');

                        const [whole, decimal = ''] = value.split('.');
                        value = `${whole}.${decimal.slice(0, 2)}`;
                    }

                    if (value !== originalValue) {
                        const removedBeforeCursor = originalValue
                            .slice(0, originalPosition)
                            .replace(/[\d.]/g, '')
                            .length;
                        const nextPosition = Math.max(0, originalPosition - removedBeforeCursor);

                        input.value = value;

                        if (input.type !== 'number') {
                            input.setSelectionRange(
                                Math.min(nextPosition, value.length),
                                Math.min(nextPosition, value.length)
                            );
                        }
                    }
                });
            });

            if (document.body.classList.contains('role-salesclerk')) {
                const moduleHeading = document.querySelector('.app-module-heading');
                const sidebarLogo = document.querySelector('.sidebar img');

                if (moduleHeading && sidebarLogo && !moduleHeading.querySelector('.salesclerk-top-logo')) {
                    const logo = sidebarLogo.cloneNode(true);
                    logo.classList.add('salesclerk-top-logo');
                    logo.removeAttribute('width');
                    logo.removeAttribute('height');
                    moduleHeading.prepend(logo);
                }
            }

            const backupStatus = document.querySelector('.app-backup-status');

            if (backupStatus) {
                const backupStatusText = backupStatus.querySelector('.app-backup-status-text');
                const backupFolderReady = backupStatus.dataset.backupFolderReady === 'true';

                const updateBackupStatus = () => {
                    const browserOnline = navigator.onLine;
                    const isReady = browserOnline && backupFolderReady;

                    backupStatus.classList.toggle('is-online', isReady);
                    backupStatus.classList.toggle('is-offline', !isReady);

                    if (backupStatusText) {
                        backupStatusText.textContent = isReady
                            ? 'Online backup ready'
                            : 'Offline backup only';
                    }
                };

                updateBackupStatus();
                window.addEventListener('online', updateBackupStatus);
                window.addEventListener('offline', updateBackupStatus);
            }

            const timeElement = document.getElementById('appCurrentTime');
            const dateElement = document.getElementById('appCurrentDate');

            if (!timeElement || !dateElement) {
                return;
            }

            const updateDateTime = () => {
                const currentDate = new Date();

                timeElement.textContent = currentDate.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                });

                dateElement.textContent = currentDate.toLocaleDateString('en-US', {
                    weekday: 'long',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                });
            };

            updateDateTime();
            setInterval(updateDateTime, 1000);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('userCreateModal');

            if (!modal) {
                return;
            }

            const openModal = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                modal.querySelector('input, select, button')?.focus();
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            };

            document.querySelectorAll('a, button').forEach((element) => {
                if (element.textContent.trim().toLowerCase().includes('add user')) {
                    element.addEventListener('click', (event) => {
                        event.preventDefault();
                        openModal();
                    });
                }
            });

            modal.querySelectorAll('[data-close-user-modal]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('signoutConfirmModal');
            const signoutForm = document.querySelector('.salesclerk-signout');
            let confirmedSignout = false;

            if (!modal || !signoutForm) {
                return;
            }

            const openModal = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                modal.querySelector('[data-confirm-signout]')?.focus();
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            };

            signoutForm.addEventListener('submit', (event) => {
                if (confirmedSignout) {
                    return;
                }

                event.preventDefault();
                openModal();
            });

            modal.querySelector('[data-cancel-signout]')?.addEventListener('click', closeModal);

            modal.querySelector('[data-confirm-signout]')?.addEventListener('click', () => {
                confirmedSignout = true;
                signoutForm.submit();
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        });
    </script>

    @include('layouts.auto-filters')

    @stack('scripts')


{{-- =============================================================
     LOGIN SUCCESS MODAL SCRIPT
============================================================== --}}

@auth

    @if(session('login_success'))

        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const modal =
                        document.getElementById(
                            'loginSuccessModal'
                        );

                    const closeButton =
                        document.getElementById(
                            'closeLoginSuccessModal'
                        );


                    if (
                        !modal ||
                        !closeButton
                    ) {
                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Lock Background Scrolling
                    |--------------------------------------------------------------------------
                    */

                    document.body.style.overflow =
                        'hidden';


                    /*
                    |--------------------------------------------------------------------------
                    | Close Login Success Modal
                    |--------------------------------------------------------------------------
                    */

                    function closeLoginSuccessModal() {

                        modal.style.transition =
                            'opacity .18s ease';

                        modal.style.opacity =
                            '0';


                        setTimeout(
                            function () {

                                modal.remove();

                                document.body.style.overflow =
                                    '';

                            },
                            180
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Continue
                    |--------------------------------------------------------------------------
                    */

                    closeButton.addEventListener(
                        'click',
                        closeLoginSuccessModal
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Enter Key
                    |--------------------------------------------------------------------------
                    */

                    document.addEventListener(
                        'keydown',
                        function (event) {

                            if (
                                event.key === 'Enter' &&
                                document.body.contains(modal)
                            ) {

                                event.preventDefault();

                                closeLoginSuccessModal();
                            }

                        }
                    );


                    closeButton.focus();

                }
            );
        </script>

    @endif

@endauth


{{-- =============================================================
     LOGOUT MODAL SCRIPT
============================================================== --}}

@auth

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const openButton =
                    document.getElementById(
                        'openLogoutModal'
                    );

                const modal =
                    document.getElementById(
                        'logoutModal'
                    );

                const cancelButton =
                    document.getElementById(
                        'cancelLogout'
                    );

                const confirmButton =
                    document.getElementById(
                        'confirmLogout'
                    );

                const logoutForm =
                    document.getElementById(
                        'logoutForm'
                    );


                if (
                    !openButton ||
                    !modal ||
                    !cancelButton ||
                    !confirmButton ||
                    !logoutForm
                ) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Open Logout Modal
                |--------------------------------------------------------------------------
                */

                function openLogoutModal() {

                    modal.classList.add(
                        'show'
                    );

                    modal.setAttribute(
                        'aria-hidden',
                        'false'
                    );

                    document.body.style.overflow =
                        'hidden';

                    cancelButton.focus();
                }


                /*
                |--------------------------------------------------------------------------
                | Close Logout Modal
                |--------------------------------------------------------------------------
                */

                function closeLogoutModal() {

                    modal.classList.remove(
                        'show'
                    );

                    modal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                    document.body.style.overflow =
                        '';

                    openButton.focus();
                }


                /*
                |--------------------------------------------------------------------------
                | Confirm Logout
                |--------------------------------------------------------------------------
                */

                function confirmLogout() {

                    confirmButton.disabled =
                        true;

                    cancelButton.disabled =
                        true;

                    confirmButton.textContent =
                        'SIGNING OUT...';


                    logoutForm.submit();
                }


                /*
                |--------------------------------------------------------------------------
                | Open
                |--------------------------------------------------------------------------
                */

                openButton.addEventListener(
                    'click',
                    openLogoutModal
                );


                /*
                |--------------------------------------------------------------------------
                | Cancel
                |--------------------------------------------------------------------------
                */

                cancelButton.addEventListener(
                    'click',
                    closeLogoutModal
                );


                /*
                |--------------------------------------------------------------------------
                | Confirm
                |--------------------------------------------------------------------------
                */

                confirmButton.addEventListener(
                    'click',
                    confirmLogout
                );


                /*
                |--------------------------------------------------------------------------
                | Click Outside
                |--------------------------------------------------------------------------
                */

                modal.addEventListener(
                    'click',
                    function (event) {

                        if (
                            event.target === modal
                        ) {
                            closeLogoutModal();
                        }

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Keyboard
                |--------------------------------------------------------------------------
                */

                document.addEventListener(
                    'keydown',
                    function (event) {

                        if (
                            event.key === 'Escape' &&
                            modal.classList.contains(
                                'show'
                            )
                        ) {

                            closeLogoutModal();
                        }

                    }
                );

            }
        );
    </script>

@endauth

</body>
</html>

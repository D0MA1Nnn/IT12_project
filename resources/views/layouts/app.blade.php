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

        .brand {
            font-size: 20px;
            font-weight: 800;
            padding: 0 10px;
        }

        .sub {
            font-size: 11px;
            color: #9aa7ba;
            padding: 7px 10px;
            line-height: 1.45;
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

        .main {
            margin-left: 218px;
            padding: 26px 30px;
            min-height: 100vh;
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
            gap: 10px;
            margin-bottom: 26px;
        }

        .tabs a {
            padding: 11px 20px;
            border-radius: 7px;
            background: #ffffff;
            color: #354052;
            text-decoration: none;
            font-size: 12px;
        }

        .tabs a.active {
            background: #2468ee;
            color: #ffffff;
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

    </style>
</head>

<body>

<div class="shell">

    @auth

        {{-- =====================================================
             SIDEBAR
        ====================================================== --}}

        <aside class="sidebar">

            <div class="brand">
                SENADOR COCO
            </div>

            <div class="sub">
                Lumber and Construction Supply<br>
                Inventory & Sales Management System
            </div>

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
                        class="{{ request()->routeIs('sales.*') ? 'active' : '' }}"
                        href="{{ route('sales.index') }}"
                    >
                        Sales Management
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
                        class="{{ request()->routeIs('sales.*') ? 'active' : '' }}"
                        href="{{ route('sales.index') }}"
                    >
                        Sales Management
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

        @if(session('success'))

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
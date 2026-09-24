<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Sign In - Senador Coco</title>

    <style>
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
        input {
            font: inherit;
        }

        /* =========================================================
           PAGE
        ========================================================= */

        .page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 43% 57%;
        }

        /* =========================================================
           LEFT SIDE
        ========================================================= */

        .hero {
            position: relative;
            overflow: hidden;
            background: #0d192d;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            border: 70px solid rgba(255,255,255,.018);
            left: -190px;
            top: -160px;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 460px;
            height: 460px;
            border-radius: 50%;
            border: 90px solid rgba(255,255,255,.018);
            right: -260px;
            bottom: -250px;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 34px;
            margin: 0 0 10px;
            letter-spacing: -.7px;
        }

        .hero p {
            color: #72a7ff;
            font-size: 16px;
            margin: 0;
        }

        .hero-security {
            margin-top: 30px;
            color: #9eb5d9;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* =========================================================
           RIGHT SIDE
        ========================================================= */

        .right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .card {
            width: 390px;
            background: #fff;
            padding: 46px 48px;
            border-radius: 14px;
            box-shadow:
                0 8px 30px rgba(15, 23, 42, .035);
        }

        .card h2 {
            font-size: 32px;
            margin: 0 0 4px;
            letter-spacing: -.5px;
        }

        .muted {
            color: #7c879b;
            font-size: 13px;
            margin-bottom: 30px;
        }

        /* =========================================================
           ALERTS
        ========================================================= */

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            border-radius: 8px;
            padding: 11px 12px;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .alert-error {
            color: #b42318;
            background: #fff3f2;
            border: 1px solid #ffd5d2;
        }

        .alert-warning {
            color: #92400e;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }

        .alert-success {
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .alert-icon {
            flex-shrink: 0;
            font-weight: 800;
        }

        /* =========================================================
           FORM
        ========================================================= */

        .field {
            margin: 0 0 22px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .field input {
            width: 100%;
            border: 1px solid transparent;
            outline: none;
            background: #f3f6fa;
            border-radius: 8px;
            padding: 14px;
            transition:
                border-color .2s,
                box-shadow .2s,
                background .2s;
        }

        .field input:focus {
            background: #fff;
            border-color: #2468ee;
            box-shadow: 0 0 0 3px rgba(36,104,238,.10);
        }

        .field input:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .password-input {
            padding-right: 66px !important;
        }

        .show-password {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #2468ee;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            padding: 7px;
        }

        /* =========================================================
           BUTTON
        ========================================================= */

        .btn {
            width: 100%;
            border: 0;
            background: #2468ee;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
            transition:
                background .2s,
                transform .15s,
                opacity .2s;
        }

        .btn:hover:not(:disabled) {
            background: #1d5edc;
            transform: translateY(-1px);
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: .55;
            transform: none;
        }

        .login-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #98a2b3;
        }

        /* =========================================================
           MODAL
        ========================================================= */

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;

            display: none;
            align-items: center;
            justify-content: center;

            padding: 20px;

            background: rgba(8, 17, 34, .60);
            backdrop-filter: blur(3px);
        }

        .modal-backdrop.show {
            display: flex;
        }

        .modal {
            width: 100%;
            max-width: 410px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;

            box-shadow:
                0 24px 70px rgba(0,0,0,.25);

            animation: modalOpen .2s ease-out;
        }

        @keyframes modalOpen {
            from {
                opacity: 0;
                transform: translateY(10px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-body {
            padding: 30px;
            text-align: center;
        }

        .modal-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 18px;
            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
            font-weight: 800;
        }

        .modal-icon.warning {
            color: #b45309;
            background: #fef3c7;
        }

        .modal-icon.success {
            color: #15803d;
            background: #dcfce7;
        }

        .modal h3 {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .modal p {
            margin: 0;
            color: #7c879b;
            font-size: 13px;
            line-height: 1.6;
        }

        .countdown {
            font-size: 36px;
            font-weight: 800;
            color: #b42318;
            margin: 18px 0 8px;
            letter-spacing: 1px;
        }

        .modal-actions {
            padding: 0 30px 28px;
        }

        .modal-btn {
            width: 100%;
            border: 0;
            background: #2468ee;
            color: #fff;
            padding: 13px;
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
        }

        .modal-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media(max-width:800px) {
            .page {
                grid-template-columns: 1fr;
            }

            .hero {
                min-height: 220px;
                padding: 35px;
            }

            .hero-security {
                display: none;
            }

            .right {
                padding: 25px;
            }

            .card {
                width: 100%;
                max-width: 420px;
            }
        }

        @media(max-width:480px) {
            .hero {
                min-height: 180px;
                padding: 28px;
            }

            .hero h1 {
                font-size: 27px;
            }

            .right {
                padding: 18px;
            }

            .card {
                padding: 35px 27px;
            }

            .card h2 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="page">

    {{-- LEFT SIDE --}}
    <section class="hero">

        <div class="hero-content">

            <h1>SENADOR COCO</h1>

            <p>
                Sales and Inventory Management System
            </p>

            <div class="hero-security">
                🔒 Controlled access for authorized users
            </div>

        </div>

    </section>


    {{-- LOGIN SIDE --}}
    <section class="right">

        <div class="card">

            <h2>Sign In</h2>

            <div class="muted">
                Authorized users only
            </div>


            {{-- VALIDATION ERRORS --}}
            @if($errors->any())

                <div class="alert alert-error">

                    <span class="alert-icon">!</span>

                    <div>
                        {{ $errors->first() }}
                    </div>

                </div>

            @endif


            {{-- LOGIN ERROR --}}
            @if(session('login_error'))

                <div class="alert alert-error">

                    <span class="alert-icon">!</span>

                    <div>
                        {{ session('login_error') }}
                    </div>

                </div>

            @endif


            {{-- ATTEMPT WARNING --}}
            @if(session('attempt_warning'))

                <div class="alert alert-warning">

                    <span class="alert-icon">!</span>

                    <div>
                        {{ session('attempt_warning') }}
                    </div>

                </div>

            @endif


            {{-- LOGOUT SUCCESS --}}
            @if(session('logout_success'))

                <div class="alert alert-success">

                    <span class="alert-icon">✓</span>

                    <div>
                        {{ session('logout_success') }}
                    </div>

                </div>

            @endif


            <form
                method="POST"
                action="{{ route('login.submit') }}"
                id="loginForm"
            >

                @csrf


                {{-- USERNAME --}}
                <div class="field">

                    <label for="username">
                        Username
                    </label>

                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="{{ old('username') }}"
                        placeholder="Enter username"
                        autocomplete="username"
                        maxlength="100"
                        required
                        autofocus
                    >

                </div>


                {{-- PASSWORD --}}
                <div class="field">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrap">

                        <input
                            class="password-input"
                            id="password"
                            type="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="show-password"
                            id="passwordToggle"
                        >
                            SHOW
                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn"
                    id="loginButton"
                >
                    SIGN IN
                </button>

            </form>


            <div class="login-footer">
                Senador Coco Lumber and Construction Supply
            </div>

        </div>

    </section>

</div>


{{-- =========================================================
     COOLDOWN MODAL
========================================================= --}}

<div
    class="modal-backdrop"
    id="cooldownModal"
>

    <div class="modal">

        <div class="modal-body">

            <div class="modal-icon warning">
                !
            </div>

            <h3>
                Login Temporarily Locked
            </h3>

            <p>
                Too many incorrect login attempts were detected.
                For security, please wait before trying again.
            </p>

            <div
                class="countdown"
                id="countdown"
            >
                00:30
            </div>

            <p>
                The login form will become available when the
                cooldown ends.
            </p>

        </div>


        <div class="modal-actions">

            <button
                type="button"
                class="modal-btn"
                id="tryAgainButton"
                disabled
            >
                PLEASE WAIT
            </button>

        </div>

    </div>

</div>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const form =
            document.getElementById('loginForm');

        const username =
            document.getElementById('username');

        const password =
            document.getElementById('password');

        const loginButton =
            document.getElementById('loginButton');

        const passwordToggle =
            document.getElementById('passwordToggle');

        const cooldownModal =
            document.getElementById('cooldownModal');

        const countdown =
            document.getElementById('countdown');

        const tryAgainButton =
            document.getElementById('tryAgainButton');


        /* =====================================================
           PASSWORD SHOW/HIDE
        ===================================================== */

        passwordToggle.addEventListener(
            'click',
            function () {

                if (password.type === 'password') {

                    password.type = 'text';

                    passwordToggle.textContent =
                        'HIDE';

                } else {

                    password.type = 'password';

                    passwordToggle.textContent =
                        'SHOW';
                }
            }
        );


        /* =====================================================
           PREVENT DOUBLE SUBMIT
        ===================================================== */

        form.addEventListener(
            'submit',
            function () {

                loginButton.disabled = true;

                loginButton.textContent =
                    'SIGNING IN...';
            }
        );


        /* =====================================================
           LOCKOUT INFORMATION FROM AUTHCONTROLLER
        ===================================================== */

        let remainingSeconds =
            {{ (int) session('lockout_seconds', 0) }};

        const isLocked =
            {{ session('login_locked') ? 'true' : 'false' }};


        /* =====================================================
           FORMAT TIMER
        ===================================================== */

        function formatTime(seconds) {

            seconds =
                Math.max(
                    0,
                    Math.floor(seconds)
                );

            const minutes =
                Math.floor(seconds / 60);

            const remaining =
                seconds % 60;

            return String(minutes)
                .padStart(2, '0') +
                ':' +
                String(remaining)
                    .padStart(2, '0');
        }


        /* =====================================================
           DISABLE FORM
        ===================================================== */

        function disableForm() {

            username.disabled = true;

            password.disabled = true;

            passwordToggle.disabled = true;

            loginButton.disabled = true;

            loginButton.textContent =
                'TEMPORARILY LOCKED';
        }


        /* =====================================================
           ENABLE FORM
        ===================================================== */

        function enableForm() {

            username.disabled = false;

            password.disabled = false;

            passwordToggle.disabled = false;

            loginButton.disabled = false;

            loginButton.textContent =
                'SIGN IN';

            tryAgainButton.disabled = false;

            tryAgainButton.textContent =
                'TRY AGAIN';
        }


        /* =====================================================
           COOLDOWN
        ===================================================== */

        if (
            isLocked &&
            remainingSeconds > 0
        ) {

            disableForm();

            cooldownModal.classList.add(
                'show'
            );

            countdown.textContent =
                formatTime(
                    remainingSeconds
                );


            const interval =
                setInterval(
                    function () {

                        remainingSeconds--;

                        countdown.textContent =
                            formatTime(
                                remainingSeconds
                            );


                        if (
                            remainingSeconds <= 0
                        ) {

                            clearInterval(
                                interval
                            );

                            remainingSeconds = 0;

                            countdown.textContent =
                                '00:00';

                            enableForm();
                        }

                    },
                    1000
                );
        }


        /* =====================================================
           TRY AGAIN
        ===================================================== */

        tryAgainButton.addEventListener(
            'click',
            function () {

                if (
                    remainingSeconds > 0
                ) {
                    return;
                }

                cooldownModal.classList.remove(
                    'show'
                );

                password.value = '';

                password.focus();
            }
        );

    }
);
</script>

</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') | @yield('title')</title>
    <style>
        :root {
            --sky: #bdeef2;
            --sky-deep: #9edbe2;
            --ink: #131313;
            --muted: #33545a;
            --gold: #f8cf3a;
            --gold-dark: #dbad11;
            --gold-light: #ffe781;
            --shadow: rgba(12, 45, 56, 0.18);
            --panel: rgba(255, 255, 255, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.45), transparent 28%),
                radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.28), transparent 24%),
                linear-gradient(180deg, var(--sky) 0%, var(--sky-deep) 100%);
            color: var(--ink);
        }

        .error-shell {
            width: min(860px, 100%);
            padding: clamp(28px, 5vw, 48px);
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0.08));
            box-shadow: 0 24px 60px var(--shadow);
            text-align: center;
            backdrop-filter: blur(6px);
        }

        .key-wrap {
            position: relative;
            width: 170px;
            height: 245px;
            margin: 0 auto 18px;
            animation: float 3.4s ease-in-out infinite;
        }

        .key-head {
            position: absolute;
            top: 0;
            left: 50%;
            width: 108px;
            height: 108px;
            border-radius: 50%;
            transform: translateX(-50%);
            background: var(--gold);
            box-shadow: 10px 0 0 var(--gold-dark);
        }

        .key-hole {
            position: absolute;
            top: 20px;
            left: 50%;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            transform: translateX(-50%);
            background: var(--sky);
            box-shadow: 6px 0 0 rgba(255, 255, 255, 0.75);
        }

        .eye {
            position: absolute;
            top: 57px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #1f1f1f;
        }

        .eye::after {
            content: "";
            position: absolute;
            top: 2px;
            left: 2px;
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: #fff;
        }

        .eye.left {
            left: 60px;
        }

        .eye.right {
            right: 50px;
        }

        .mouth {
            position: absolute;
            top: 77px;
            left: 50%;
            width: 20px;
            height: 10px;
            border: 3px solid #1f1f1f;
            border-bottom: 0;
            border-radius: 18px 18px 0 0;
            transform: translateX(-50%);
        }

        .key-body {
            position: absolute;
            top: 92px;
            left: 50%;
            width: 60px;
            height: 150px;
            border-radius: 12px;
            transform: translateX(-50%);
            background: linear-gradient(90deg, var(--gold) 0 74%, var(--gold-light) 74% 100%);
            box-shadow: 10px 0 0 var(--gold-dark);
        }

        .key-body::before {
            content: "";
            position: absolute;
            left: 14px;
            top: 20px;
            width: 6px;
            height: 110px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.34);
        }

        .tooth {
            position: absolute;
            right: 26px;
            bottom: 18px;
            width: 26px;
            height: 70px;
            background: var(--gold);
            clip-path: polygon(0 0, 100% 0, 100% 18%, 56% 30%, 100% 44%, 100% 58%, 50% 71%, 100% 84%, 100% 100%, 0 100%);
            box-shadow: 10px 0 0 var(--gold-dark);
        }

        .arm {
            position: absolute;
            top: 110px;
            width: 22px;
            height: 78px;
            border: 3px solid #1f1f1f;
            border-color: #1f1f1f transparent transparent transparent;
            border-radius: 50%;
            opacity: 0.9;
        }

        .arm.left {
            left: 28px;
            transform: rotate(96deg);
        }

        .arm.right {
            right: 20px;
            transform: rotate(-84deg);
        }

        .code {
            margin: 0;
            font-size: clamp(4rem, 11vw, 5.6rem);
            line-height: 0.95;
            letter-spacing: -0.06em;
            font-weight: 900;
        }

        .title {
            margin: 8px 0 0;
            font-size: clamp(2rem, 5vw, 3rem);
            line-height: 1;
            font-weight: 900;
            text-transform: uppercase;
        }

        .message {
            max-width: 560px;
            margin: 18px auto 0;
            font-size: clamp(1rem, 2.3vw, 1.45rem);
            line-height: 1.45;
            color: var(--muted);
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
            padding: 14px 20px;
            border-radius: 999px;
            border: 2px solid transparent;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .btn-primary {
            background: #171717;
            color: #fff;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.16);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.45);
            border-color: rgba(19, 19, 19, 0.12);
            color: #171717;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            box-shadow: 0 16px 28px rgba(0, 0, 0, 0.2);
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @media (max-width: 640px) {
            .error-shell {
                border-radius: 22px;
            }

            .key-wrap {
                transform: scale(0.88);
                margin-bottom: 8px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="error-shell">
        <div class="key-wrap" aria-hidden="true">
            <div class="key-head">
                <div class="key-hole"></div>
                <span class="eye left"></span>
                <span class="eye right"></span>
                <span class="mouth"></span>
            </div>
            <div class="arm left"></div>
            <div class="arm right"></div>
            <div class="key-body"></div>
            <div class="tooth"></div>
        </div>

        <h1 class="code">@yield('code')</h1>
        <p class="title">@yield('title')</p>
        <p class="message">@yield('message')</p>

        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/') }}">Ir al inicio</a>
            <a class="btn btn-secondary" href="{{ url()->previous() }}">Volver</a>
        </div>
    </main>
</body>
</html>
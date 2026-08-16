<!DOCTYPE html>
<html lang="fr">
<head>
    <script>
        (function () {
            var stored = localStorage.getItem('wa-otp-theme');
            var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'WhatsApp OTP')</title>
    <style>
        :root {
            color-scheme: light;
            --accent: #FF2D20;
            --bg: #ffffff;
            --surface: #f6f6f9;
            --text: #16161d;
            --text-muted: #6b7280;
            --border: #00000022;
            --border-soft: #00000014;
            --input-bg: #ffffff;
            --input-border: #00000038;
            --nav-link: #52525f;
        }
        html[data-theme="dark"] {
            color-scheme: dark;
            --bg: #08080c;
            --surface: #131319;
            --text: #e7e7f0;
            --text-muted: #9c9cb5;
            --border: #ffffff26;
            --border-soft: #ffffff17;
            --input-bg: #0d0d13;
            --input-border: #ffffff2e;
            --nav-link: #c3c3d6;
        }
        * { box-sizing: border-box; }
        html { background: var(--bg); }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 720px;
            margin: 3rem auto;
            padding: 0 1.5rem;
            line-height: 1.5;
            background: var(--bg);
            color: var(--text);
        }
        body.docs-page { max-width: 1180px; }
        a { color: var(--accent); }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; }
        h1 { font-size: 1.4rem; margin: 0; }
        h1 a { color: var(--text); text-decoration: none; }
        nav a { color: var(--nav-link); margin-right: 1rem; font-size: .9rem; text-decoration: none; }
        nav a:hover { color: var(--text); text-decoration: underline; }
        #theme-toggle {
            width: 2.1rem; height: 2.1rem; padding: 0; border-radius: 999px;
            background: transparent; border: 1px solid var(--border); color: var(--text);
            font-size: 1rem; line-height: 1; cursor: pointer; display: inline-flex;
            align-items: center; justify-content: center;
        }
        #theme-toggle:hover { border-color: var(--accent); }
        .card { border: 1px solid var(--border); background: var(--surface); border-radius: 10px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .8rem; font-weight: 600; }
        .badge.ready { background: #16a34a33; color: #16a34a; }
        .badge.qr { background: #eab30833; color: #a16207; }
        .badge.down { background: #dc262633; color: #dc2626; }
        .badge.other { background: #6b728033; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        td, th { text-align: left; padding: .4rem 0; border-bottom: 1px solid var(--border-soft); font-size: .9rem; }
        input[type=text], input[type=email], input[type=password] {
            width: 100%; padding: .5rem .6rem; border: 1px solid var(--input-border); border-radius: 6px;
            font-size: 1rem; background: var(--input-bg); color: var(--text);
        }
        button {
            background: #16a34a; color: white; border: none; border-radius: 6px;
            padding: .55rem 1rem; font-size: .95rem; cursor: pointer;
        }
        button.secondary { background: transparent; border: 1px solid var(--border); color: var(--text); }
        button.danger { background: #dc2626; }
        .error { color: #dc2626; font-size: .85rem; }
        .flash { background: #16a34a1a; border: 1px solid #16a34a55; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.5rem; }
        code { background: #8882; padding: .1rem .35rem; border-radius: 4px; word-break: break-all; font-size: .9em; }
        pre { background: #1e1e2e; color: #e2e2f0; padding: 1rem 1.1rem; border-radius: 8px; overflow-x: auto; font-size: .85rem; line-height: 1.6; margin: .75rem 0 1.25rem; }
        pre code { background: none; padding: 0; color: inherit; word-break: normal; }
        img.qr { max-width: 260px; display: block; margin: 1rem auto; background: #fff; border-radius: 8px; padding: .5rem; }

        /* Documentation page — Laravel-docs-style two-column layout */
        .docs-shell { display: flex; align-items: flex-start; gap: 3.5rem; }
        .docs-sidebar {
            position: sticky; top: 1.5rem; flex: 0 0 230px;
            max-height: calc(100vh - 3rem); overflow-y: auto;
            font-size: .85rem; padding-right: .5rem; padding-bottom: 2rem;
        }
        .docs-sidebar .group-title {
            text-transform: uppercase; letter-spacing: .06em; font-size: .72rem;
            font-weight: 700; color: var(--text-muted); margin: 1.6rem 0 .5rem;
        }
        .docs-sidebar .group-title:first-child { margin-top: 0; }
        .docs-sidebar ul { list-style: none; margin: 0; padding: 0; }
        .docs-sidebar li a {
            display: block; padding: .32rem 0 .32rem .8rem; margin-left: -1px;
            border-left: 2px solid transparent; color: var(--text); text-decoration: none; opacity: .72;
        }
        .docs-sidebar li a:hover { opacity: 1; }
        .docs-sidebar li a.active {
            border-left-color: var(--accent); color: var(--accent); opacity: 1; font-weight: 600;
        }
        .docs-sidebar .sub a { padding-left: 1.6rem; font-size: .82rem; }
        .docs-main { flex: 1; min-width: 0; max-width: 720px; }
        .docs-main .lead { font-size: 1.05rem; color: var(--text-muted); margin: -.25rem 0 2.5rem; }
        .docs-main h2 {
            font-size: 1.45rem; margin: 3rem 0 1rem; padding-bottom: .6rem;
            border-bottom: 1px solid var(--border); scroll-margin-top: 1.5rem;
        }
        .docs-main h2:first-of-type { margin-top: 0; }
        .docs-main h3 { font-size: 1.12rem; margin: 2rem 0 .75rem; scroll-margin-top: 1.5rem; }
        .docs-main p, .docs-main li { font-size: .95rem; }
        .docs-main ul, .docs-main ol { padding-left: 1.3rem; }
        .heading-anchor {
            opacity: 0; margin-left: .4rem; color: var(--accent); text-decoration: none;
            font-weight: 400; font-size: .8em; transition: opacity .1s;
        }
        h2:hover .heading-anchor, h3:hover .heading-anchor { opacity: 1; }
        .note {
            border-left: 3px solid #eab308; background: #eab30814; padding: .75rem 1rem;
            border-radius: 0 8px 8px 0; margin: 1rem 0 1.5rem; font-size: .9rem;
        }
        .status-table code { white-space: nowrap; }

        /* Reusable labeled code block (x-code-block component) — always dark, both themes */
        .code-block { margin: .9rem 0 1.5rem; }
        .code-block__lang {
            display: inline-block; font-size: .68rem; text-transform: uppercase; letter-spacing: .07em;
            color: #a8a8c0; background: #1e1e2e; padding: .35rem .9rem; border-radius: 6px 6px 0 0;
        }
        .code-block__lang + pre { margin-top: 0; border-top-left-radius: 0; }

        @media (max-width: 860px) {
            .docs-shell { flex-direction: column; gap: 1.5rem; }
            .docs-sidebar { position: static; width: 100%; max-height: none; overflow: visible; }
            .docs-main { max-width: 100%; }
        }

        /* Dashboard & Clés API — laravel.com homepage-style hero */
        body.app-page { max-width: none; margin: 0; padding: 0; }
        body.app-page header { max-width: 1080px; margin: 0 auto; padding: 2rem 1.5rem 0; }
        .app-shell {
            max-width: 1080px; margin: 0 auto; padding: 1rem 1.5rem 6rem;
            position: relative; isolation: isolate;
        }
        .app-shell::before {
            content: ""; position: absolute; z-index: -1; pointer-events: none;
            top: -140px; left: 50%; transform: translateX(-50%);
            width: 780px; height: 420px; border-radius: 50%;
            background: radial-gradient(closest-side, #ff2d2026, transparent 70%);
            filter: blur(10px);
        }
        .app-hero { text-align: center; padding: 3rem 0 3.25rem; }
        .app-hero .eyebrow {
            color: var(--accent); text-transform: uppercase; letter-spacing: .14em;
            font-size: .75rem; font-weight: 700; margin: 0 0 .85rem;
        }
        .app-hero h1 {
            font-size: 2.5rem; line-height: 1.15; margin: 0 0 .85rem; color: var(--text); letter-spacing: -.02em;
        }
        .app-hero p { color: var(--text-muted); font-size: 1.05rem; max-width: 34rem; margin: 0 auto; }
        .app-hero code { background: #80808026; color: var(--text); }

        .panel {
            background: var(--surface); border: 1px solid var(--border); border-radius: 16px;
            margin-bottom: 1.75rem; overflow: hidden;
        }
        .panel.glow-ready { box-shadow: 0 0 0 1px #16a34a26, 0 24px 60px -28px #16a34a66; }
        .panel.glow-qr { box-shadow: 0 0 0 1px #eab30826, 0 24px 60px -28px #eab30866; }
        .panel.glow-down { box-shadow: 0 0 0 1px #dc262626, 0 24px 60px -28px #dc262666; }
        .panel-titlebar {
            display: flex; align-items: center; gap: .4rem;
            padding: .85rem 1.15rem; border-bottom: 1px solid var(--border-soft);
        }
        .panel-titlebar .dot { width: .55rem; height: .55rem; border-radius: 50%; background: #80808040; }
        .panel-titlebar .dot:nth-child(1) { background: #ff5f56; }
        .panel-titlebar .dot:nth-child(2) { background: #ffbd2e; }
        .panel-titlebar .dot:nth-child(3) { background: #27c93f; }
        .panel-titlebar .label { margin-left: .5rem; font-size: .78rem; color: var(--text-muted); }
        .panel-titlebar .badge { margin-left: auto; }
        .panel-body { padding: 1.75rem; }
        .panel-body p { font-size: .95rem; color: var(--text); }
        .panel-body a { color: var(--accent); text-decoration: none; }
        .panel-body a:hover { text-decoration: underline; }

        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; }
        .feature-card {
            display: block; background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
            padding: 1.6rem; text-decoration: none !important; transition: transform .15s, border-color .15s, box-shadow .15s;
        }
        .feature-card:hover { transform: translateY(-3px); border-color: #ff2d2066; box-shadow: 0 16px 40px -20px #ff2d2055; }
        .feature-card .icon { font-size: 1.6rem; margin-bottom: .8rem; }
        .feature-card h3 { margin: 0 0 .4rem; font-size: 1.05rem; color: var(--text); }
        .feature-card p { margin: 0; font-size: .88rem; color: var(--text-muted); }

        .btn-pill {
            display: inline-flex; align-items: center; justify-content: center; white-space: nowrap;
            background: linear-gradient(180deg, #ff4433, #e0281a); color: #fff !important; border: none;
            padding: .7rem 1.7rem; border-radius: 999px; font-weight: 600; font-size: .9rem; cursor: pointer;
            box-shadow: 0 10px 26px -10px #ff2d20a0; transition: transform .12s, box-shadow .12s;
        }
        .btn-pill:hover { transform: translateY(-1px); box-shadow: 0 14px 30px -8px #ff2d20c0; }
        .btn-outline {
            background: transparent; border: 1px solid var(--border); color: var(--text) !important;
            padding: .5rem 1.2rem; border-radius: 999px; font-size: .82rem; cursor: pointer;
        }
        .btn-outline:hover { border-color: #dc262688; color: #ff8a80 !important; }

        .key-row {
            display: flex; justify-content: space-between; align-items: center; gap: 1rem;
            padding: 1rem 1.2rem; border: 1px solid var(--border-soft); border-radius: 12px;
            background: var(--bg); margin-bottom: .75rem;
        }
        .key-row:last-child { margin-bottom: 0; }
        .key-row .meta { font-size: .8rem; color: var(--text-muted); margin-top: .2rem; }
    </style>
</head>
<body class="@yield('body-class')">
    <header>
        <h1><a href="{{ route('home') }}">WhatsApp OTP</a></h1>
        <div style="display:flex; align-items:center; gap:1rem;">
            @auth
                <nav>
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <a href="{{ route('api-keys.index') }}">Clés API</a>
                    <a href="{{ route('docs') }}">Documentation API</a>
                </nav>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="secondary">Déconnexion</button>
                </form>
            @else
                <nav>
                    <a href="{{ route('docs') }}">Documentation</a>
                </nav>
                <a href="{{ route('login') }}" class="btn-outline" style="text-decoration:none;">Connexion</a>
            @endauth
            <button type="button" id="theme-toggle" aria-label="Changer de thème">🌙</button>
        </div>
    </header>

    @yield('content')

    @stack('scripts')

    <script>
        (function () {
            var btn = document.getElementById('theme-toggle');
            var icon = function (theme) { return theme === 'dark' ? '☀️' : '🌙'; };
            var sync = function () { btn.textContent = icon(document.documentElement.getAttribute('data-theme')); };
            sync();
            btn.addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('wa-otp-theme', next);
                sync();
            });
        })();
    </script>
</body>
</html>

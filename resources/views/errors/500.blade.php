{{-- Automatisch herlaadscherm voor de Railway-cold-start (500). Alleen zichtbaar in productie. --}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Even geduld…</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css2?family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        /* Standaard = donker, net als het Filament-panel (defaultThemeMode: dark). */
        body {
            font: 400 16px/1.6 "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #030712;                 /* Filament dark: gray-950 */
            color: #ffffff;
        }
        .card {
            background: #111827;                 /* Filament dark-surface: gray-900 */
            max-width: 440px;
            width: 100%;
            border-radius: 16px;
            padding: 32px 36px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, .5);
            outline: 1px solid rgba(255, 255, 255, .1);  /* Filament dark ring */
            text-align: center;
        }
        .spinner {
            width: 34px; height: 34px;
            margin: 0 auto 18px;
            border: 3px solid rgba(99, 102, 241, .25);   /* primaire kleur van de app (#6366f1) */
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin .9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 20px; font-weight: 600; margin-bottom: 8px; }
        .muted { color: #9ca3af; }               /* gray-400 */
        .countdown { margin-top: 16px; font-weight: 500; color: #9ca3af; }
        .btn {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 20px;
            border: 0;
            border-radius: 10px;
            background: #6366f1;
            color: #fff;
            font: 600 14px "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            cursor: pointer;
        }
        .btn:hover { background: #4f46e5; }
        [hidden] { display: none !important; }

        /* Licht thema — alleen toegepast als de gebruiker dat in de app koos. */
        html.light body {
            background: #f9fafb;                 /* Filament light: gray-50 */
            color: #030712;
        }
        html.light .card {
            background: #ffffff;
            outline: 1px solid rgba(2, 6, 23, .05);       /* Filament light ring */
        }
        html.light .muted,
        html.light .countdown { color: #6b7280; }        /* gray-500 */
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner"></div>
        <h1>De server wordt wakker</h1>
        <p class="muted">De gratis server stond even uit en start nu op. We proberen het automatisch opnieuw — dit duurt meestal een paar seconden.</p>
        <div class="countdown" id="countdown">Opnieuw proberen over <span id="seconds">6</span>…</div>
        <button class="btn" id="retry" hidden>Opnieuw proberen</button>
    </div>

    <script>
        (function () {
            // Koppel het thema aan Filament: donker is de standaard van het panel.
            // 'light' en 'system' volgen de keuze die in de app is opgeslagen.
            var theme;
            try {
                theme = window.localStorage.getItem('theme') || 'dark';
            } catch (error) {
                theme = 'dark';
            }
            if (theme === 'system') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            if (theme === 'light') {
                document.documentElement.classList.add('light');
            }

            var KEY = 'stageuren.errorRetries';
            var MAX_AUTO = 3;
            var secondsLeft = 6;
            var retries = 0;
            try {
                retries = parseInt(window.sessionStorage.getItem(KEY) || '0', 10);
            } catch (error) {}

            function reload() {
                try {
                    window.sessionStorage.setItem(KEY, String(retries + 1));
                } catch (error) {}
                window.location.reload();
            }

            var secondsEl = document.getElementById('seconds');
            var countdownEl = document.getElementById('countdown');
            var retryBtn = document.getElementById('retry');

            if (retries >= MAX_AUTO) {
                countdownEl.hidden = true;
                retryBtn.hidden = false;
                retryBtn.addEventListener('click', function () {
                    try {
                        window.sessionStorage.removeItem(KEY);
                    } catch (error) {}
                    reload();
                });
                return;
            }

            window.setTimeout(function () {
                reload();
            }, secondsLeft * 1000);

            var timer = window.setInterval(function () {
                secondsLeft -= 1;
                if (secondsLeft <= 0) {
                    window.clearInterval(timer);
                    return;
                }
                secondsEl.textContent = secondsLeft;
            }, 1000);
        })();
    </script>
</body>
</html>
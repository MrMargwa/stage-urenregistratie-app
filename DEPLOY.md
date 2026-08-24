# Deployen naar Railway

Deze app draait online op Railway met twee services in één project:

| Service | Wat het doet | Kosten |
|---|---|---|
| **App** (PHP 8.4 via Nixpacks) | host de Laravel-app | binnen je $5-trial, daarna $1/maand (Free-plan) |
| **MySQL** | beheerde MySQL-database | idem |

Deployen gaat automatisch: elke push naar `main` triggert een nieuwe build.

---

## Stap 1 — Project aanmaken

1. Ga naar <https://railway.app> en log in met GitHub.
2. **New Project** → **Deploy from GitHub repo** → selecteer `MrMargwa/stage-urenregistratie-app`.
3. In hetzelfde project: **+ New** → **Database** → **MySQL**.
   Railway maakt de database aan met alle variabelen automatisch — hier hoef je niets aan te passen.

## Stap 2 — Variables op de app-service

Open de **app-service** (niet de database!) → tab **Variables** en voeg toe:

| Key | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | genereer lokaal: `php artisan key:generate --show` |
| `DB_CONNECTION` | `mysql` |
| `DB_URL` | `${{MySQL.MYSQL_URL}}` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `CACHE_STORE` | `database` |

> ⚠️ `${{MySQL.MYSQL_URL}}` verwijst naar de database-service. Heet jouw database-service anders (bijv. `mysql` of `database`), pas dan het eerste deel aan: `${{<servicenaam>.MYSQL_URL}}`.

Elke wijziging in Variables triggert automatisch een herstart.

## Stap 3 — Domein genereren

1. App-service → **Settings** → **Networking** → **Generate Domain** (poort laat je standaard).
2. Railway geeft een URL zoals `https://stage-urenregistratie-app-production.up.railway.app`.
3. Voeg die URL toe als variable: `APP_URL` = `https://<jouw-domein>` → app deployt opnieuw.

## Stap 4 — Eerste build controleren

De build gebruikt `nixpacks.toml` uit de repo:

- PHP 8.4 mét de `intl`-extensie (verplicht voor Filament)
- `composer install --no-dev`
- `npm ci && npm run build` (Vite-assets)
- Start: `php artisan migrate --force && php artisan serve` — migraties draaien dus automatisch bij elke start

Bouwt het mis? Tab **Deployments** → klik op de build → logs lezen.

## Stap 5 — Admin-gebruiker aanmaken

De online database is vers/leeg. Open de app-service → tab **Shell** (of gebruik `railway shell` via CLI) en voer uit:

```bash
php artisan tinker --execute="App\Models\User::create(['name' => 'Justin', 'email' => 'jouw@email.nl', 'password' => Illuminate\Support\Facades\Hash::make('jij-sterke-wachtwoord')]);"
```

Log daarna in op `<jouw-domein>/admin`.

## Hoe verder werkt het vanaf nu

- Elke `git push origin main` → Railway bouwt en deployt automatisch.
- Migraties draaien bij elke containerstart (`migrate --force` in het start-commando).
- Exports werken direct (`QUEUE_CONNECTION=sync`, geen worker nodig).

## Troubleshooting

| Probleem | Oplossing |
|---|---|
| Build-log noemt `railpack` en faalt op `php >=8.4.1` / `ext-intl missing` | Railway gebruikte de verkeerde builder — `railway.json` in de repo forceert Nixpacks. Staat die er niet in? Zet hem dan handmatig: app-service → **Settings** → **Build** → Builder → **Nixpacks**, en redeploy. |
| `ParseError ... vendor/phpunit/.../Version.php` of setup toont `php83.withExtensions` | Nixpacks koos PHP 8.3 doordat `composer.json` `"php": "^8.3"` eiste (lockfile heeft ≥8.4.1 nodig). Opgelost door `"php": "^8.4"` + install-fase met `--no-dev`. |
| `does not provide an export named 'styleText'` in de build-fase | Nixpacks gebruikte Node 18, maar Vite 8 vereist Node ≥20.19. Opgelost via `NIXPACKS_NODE_VERSION = '22'` (nixpacks.toml) + `"engines"` in package.json. |
| `Class "Filament\PanelProvider" not found` of intl-fout | Build gebruikte geen `nixpacks.toml` — check of het bestand gepusht is en trigger een redeploy (**Deployments** → ⋯ → Redeploy). |
| `No application encryption key has been specified` | `APP_KEY` ontbreekt — stap 2. |
| `Connection refused` / SQLSTATE[HY000] [2002] | `DB_URL`-referentie klopt niet met de servicenaam van de database — stap 2, let op `${{...}}`. |
| Witte pagina / CSS mist | Vite-build gefaald — deployment-logs checken. |
| 404 op alles | `APP_URL` niet gezet na Generate Domain — stap 3.3. |

## Goed om te weten

- De MySQL-service heet standaard `MYSQL_DATABASE=railway` — dat is gewoon de naam van de database, prima om zo te laten.
- Heb je ooit ergens je root-wachtwoord gedeeld? Verwijder dan de DB-service en maak hem opnieuw aan (nieuwe wachtwoorden worden automatisch gegenereerd), of roteer het wachtwoord via de variables van de DB-service.

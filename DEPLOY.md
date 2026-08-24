# Deployen naar Railway

Deze app draait online op Railway met twee services in één project:

| Service | Wat het doet | Kosten |
|---|---|---|
| **App** (PHP 8.4 via Nixpacks) | host de Laravel-app | binnen je $5-trial, daarna $1/maand (Free-plan) |
| **PostgreSQL** | beheerde database (first-party Railway-service, direct in het menu) | idem |

Deployen gaat automatisch: elke push naar `main` triggert een nieuwe build.

---

## Stap 1 — Project aanmaken

1. Ga naar <https://railway.app> en log in met GitHub.
2. **New Project** → **Deploy from GitHub repo** → selecteer `MrMargwa/stage-urenregistratie-app`.
3. In hetzelfde project: **+ New** → **Database** → **PostgreSQL**.
   Railway maakt de database aan met alle variabelen automatisch — hier hoef je niets aan te passen.

> ⚠️ Kies **PostgreSQL**, niet MySQL: de PHP-build (libmariadb-client) ondersteunt de `caching_sha2_password`-auth van MySQL 8 niet, waardoor de verbinding met een MySQL-database weigert. MariaDB staat niet meer in het standaardmenu van Railway (alleen nog als community-template). PostgreSQL is first-party en werkt out-of-the-box — Laravel abstraheert het verschil volledig weg (`DB_CONNECTION=pgsql`). De migraties bevatten geen MySQL-specifieke code, dus ze draaien 1-op-1 op Postgres. Lokaal blijf je gewoon met Docker + MySQL ontwikkelen.

## Stap 2 — Variables op de app-service

Open de **app-service** (niet de database!) → tab **Variables** en voeg toe:

| Key | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | genereer lokaal: `php artisan key:generate --show` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | `${{Postgres.DATABASE_URL}}` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `CACHE_STORE` | `database` |
| `ADMIN_EMAIL` | jouw admin e-mailadres |
| `ADMIN_PASSWORD` | sterk wachtwoord voor de admin-user |
| `ADMIN_NAME` | (optioneel) weergavenaam, standaard `Admin` |

> ⚠️ `${{Postgres.DATABASE_URL}}` verwijst naar de database-service. Heet jouw database-service anders (bijv. `postgres` of `database`), pas dan het eerste deel aan: `${{<servicenaam>.DATABASE_URL}}`.

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
- Start (uit `railway.json` — dit overschrijft het startcommando van Nixpacks): wachtlus tot de database online is → `migrate --force` → admin-user seeden via `AdminSeeder` → `artisan serve` met meerdere workers op `$PORT`

Bouwt het mis? Tab **Deployments** → klik op de build → logs lezen.

## Stap 5 — Admin-gebruiker (automatisch)

De online database is vers/leeg, maar de admin-user wordt **automatisch aangemaakt** bij elke start: het startcommando draait `AdminSeeder`, die via `updateOrCreate` de gebruiker aanmaakt/bijwerkt op basis van de variables `ADMIN_EMAIL` + `ADMIN_PASSWORD` (zie stap 2). Geen variables gezet? Dan slaat de seeder netjes over.

Wachtwoord wijzigen? Pas gewoon `ADMIN_PASSWORD` aan in Railway — bij de volgende herstart wordt de gebruiker bijgewerkt.

Log daarna in op `<jouw-domein>/admin`.

## Hoe verder werkt het vanaf nu

- Elke `git push origin main` → Railway bouwt en deployt automatisch.
- Migraties draaien bij elke containerstart (met wachtlus tot de database online is).
- Exports werken direct (`QUEUE_CONNECTION=sync`, geen worker nodig).

## Troubleshooting

| Probleem | Oplossing |
|---|---|
| Inloggen lukt, maar daarna `403 Forbidden` op `/admin` | Het `User`-model implementeert het `FilamentUser`-contract niet — Filament weigert dan élke user in productie (lokaal met `APP_ENV=local` lijkt het te werken). Fix: `User extends Authenticatable implements FilamentUser` mét `canAccessPanel(): bool` (staat in de repo). |
| `Application failed to respond` op het domein | App draait niet (meer) of Railway routeert naar de verkeerde poort. Check: 1) deployment-logs — draait `artisan serve` en op welke poort? 2) app-service → **Settings → Networking** → domein → **target port** moet gelijk zijn aan de luisterpoort uit de logs (bijv. `8080`). 3) Stond de container midden in een herstart (crash-loop)? Zie de wachtlus-fix hieronder. |
| Build-log noemt `railpack` en faalt op `php >=8.4.1` / `ext-intl missing` | Railway gebruikte de verkeerde builder — `railway.json` in de repo forceert Nixpacks. Staat die er niet in? Zet hem dan handmatig: app-service → **Settings** → **Build** → Builder → **Nixpacks**, en redeploy. |
| `ParseError ... vendor/phpunit/.../Version.php` of setup toont `php83.withExtensions` | Nixpacks koos PHP 8.3 doordat `composer.json` `"php": "^8.3"` eiste (lockfile heeft ≥8.4.1 nodig). Opgelost door `"php": "^8.4"` + install-fase met `--no-dev`. |
| `does not provide an export named 'styleText'` in de build-fase | Nixpacks gebruikte Node 18, maar Vite 8 vereist Node ≥20.19. Opgelost via `NIXPACKS_NODE_VERSION = '22'` (nixpacks.toml) + `"engines"` in package.json. |
| `SQLSTATE[HY000] [2054] authentication method unknown [caching_sha2_password]` bij starten | Er staat nog een **MySQL**-service verbonden — de PHP-build (libmariadb) ondersteunt die auth niet. Vervang door PostgreSQL: verwijder de MySQL-service, + New → Database → PostgreSQL, en wijs `DB_URL` naar `${{<servicenaam>.DATABASE_URL}}` met `DB_CONNECTION=pgsql`. |
| `SQLSTATE[HY000] [2002] Connection refused` of `SQLSTATE[08006] [7] ... database system is starting up` bij starten | Race tussen app- en DB-start (database nog niet online). Het start-commando in `nixpacks.toml` heeft een wachtlus (`until php artisan migrate --force; do sleep 5; done`) die dit opvangt — zie je het nog, check dan of de nieuwste deployment de lus bevat. |
| `could not find driver` of `Call to undefined function pg_connect()` bij migreren | De `pdo_pgsql`-extensie ontbreekt in de build — check of `nixpacks.toml` `php84Extensions.pdo_pgsql` in de setup-fase heeft staan en trigger een redeploy (**Deployments** → ⋯ → Redeploy). |
| `Class "Filament\PanelProvider" not found` of intl-fout | Build gebruikte geen `nixpacks.toml` — check of het bestand gepusht is en trigger een redeploy (**Deployments** → ⋯ → Redeploy). |
| `No application encryption key has been specified` | `APP_KEY` ontbreekt — stap 2. |
| `Connection refused` / SQLSTATE[HY000] [2002] | `DB_URL`-referentie klopt niet met de servicenaam van de database — stap 2, let op `${{...}}`. |
| Witte pagina / CSS mist | Vite-build gefaald — deployment-logs checken. |
| 404 op alles | `APP_URL` niet gezet na Generate Domain — stap 3.3. |

## Goed om te weten

- De database heet standaard `railway` (variabele `PGDATABASE` / `DATABASE_NAME`) — dat is gewoon de naam van de database, prima om zo te laten.
- Heb je ooit ergens je DB-wachtwoord gedeeld? Verwijder dan de DB-service en maak hem opnieuw aan (nieuwe wachtwoorden worden automatisch gegenereerd), of roteer het wachtwoord via de variables van de DB-service.

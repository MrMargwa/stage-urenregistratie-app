# Deployen naar Railway

Deze app draait online op Railway met twee services in één project:

| Service | Wat het doet | Kosten |
|---|---|---|
| **App** (PHP 8.4 via Nixpacks) | host de Laravel-app via Nginx + PHP-FPM | binnen je $5-trial, daarna $1/maand (Free-plan) |
| **PostgreSQL** | beheerde database (first-party Railway-service, direct in het menu) | idem |
| **Redis** (aanbevolen, optioneel) | in-memory cache + sessions + exports-queue | binnen je $5-trial |

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
| `SESSION_DRIVER` | `redis` (of `database` zonder Redis-service) |
| `QUEUE_CONNECTION` | `sync` (of `redis` voor async exports) |
| `CACHE_STORE` | `redis` (of `database` zonder Redis-service) |
| `SEED_ADMIN_PASSWORD` | sterk wachtwoord voor `admin@admin.com` (geen standaard in productie gebruiken!) |
| `SEED_USER_PASSWORD` | (optioneel) wachtwoord voor de testaccount, standaard `Welkom1!23` |
| `REDIS_URL` | `${{Redis.REDIS_URL}}` — **alleen** als je een Redis-service toevoegt |

> ⚠️ `${{Postgres.DATABASE_URL}}` verwijst naar de database-service. Heet jouw database-service anders (bijv. `postgres` of `database`), pas dan het eerste deel aan: `${{<servicenaam>.DATABASE_URL}}`.
>
> 💡 **Waarom Redis?** Zonder Redis slaat de app cache en sessions op in PostgreSQL. Elke pagina-laad is dan meerdere extra DB-rondes over het netwerk (trager). Met Redis zijn dat snelle in-memory reads. Lokaal merk je het verschil niet (MySQL op localhost), online wél — dit is naast Nginx/PHP-FPM de grootste snelheidswinst. Wil je het eenvoudig houden, dan volstaat `database` ook prima.

Elke wijziging in Variables triggert automatisch een herstart.

## Stap 3 — (Optioneel) Redis toevoegen voor snelheid

1. Zelfde project → **+ New** → **Database** → **Redis**.
2. Railway maakt op de **Redis-service** automatisch verbindingsvariabelen aan
   (`REDIS_URL`, `REDISHOST`, `REDISPORT`, `REDISPASSWORD`, `REDISUSER`) — daar hoef je niets aan te doen.
3. Zet op de **app-service** de variable:
   - `REDIS_URL` = `${{Redis.REDIS_URL}}` (of `${{<servicenaam>.REDIS_URL}}` als je Redis anders heet).
     Dit is een **service-referentie**: Railway vult automatisch de interne URL van je Redis-service in.
     Niet zelf de URL in elkaar zetten, gewoon deze referentie gebruiken.
4. Zet `CACHE_STORE=redis` en `SESSION_DRIVER=redis` (en optioneel `QUEUE_CONNECTION=redis`).
5. De build in `nixpacks.toml` installeert al `php84Extensions.redis`, dus niets extra's nodig in code.

**Werkt de verbinding?** In de runtime-logs (of via `railway run`) kun je testen met:

```bash
redis-cli -u "$REDIS_URL" ping
# of via Laravel:
php artisan tinker --execute="dump(Cache::store('redis')->put('test', 1, 10));"
```

> ⚠️ Denk aan een **order van schakelen**: zet eerst de Redis-service + `REDIS_URL` erbij, laat de app
> herstarten, en **pas daarna** `CACHE_STORE`/`SESSION_DRIVER` naar `redis` omzetten. Anders slaat de app
> sessies nergens op en word je uitgelogd. (Praktisch: zet alles in één keer en laat één deploy
> volledig afronden.)

Geen Redis (alles via PostgreSQL) werkt ook prima — het is puur een snelheidsoptimalisatie.

## Stap 4 — Domein genereren

1. App-service → **Settings** → **Networking** → **Generate Domain** (poort laat je standaard).
2. Railway geeft een URL zoals `https://stage-urenregistratie-app-production.up.railway.app`.
3. Voeg die URL toe als variable: `APP_URL` = `https://<jouw-domein>` → app deployt opnieuw.

## Stap 5 — Eerste build controleren

De build gebruikt `nixpacks.toml` uit de repo:

- PHP 8.4 mét de `intl`- en `redis`-extensies (intl is verplicht voor Filament, redis alleen nodig als je de Redis-service gebruikt)
- `composer install --no-dev`
- `npm ci && npm run build` (Vite-assets)
- **Serving:** de app draait via **Nginx + PHP-FPM** (het native startcommando van de Nixpacks-PHP-provider, meerdere PHP-processen) — niet meer via de single-threaded `php artisan serve`.
- Migraties + admin-seeding draaien in de **pre-deploy-stap** (`railway/pre-deploy.sh`, via `preDeployCommand` in `railway.json`), met wachtlus tot de database online is.

Bouwt het mis? Tab **Deployments** → klik op de build → logs lezen.

## Stap 6 — Admin-gebruiker (automatisch)

De pre-deploy-stap draait `php artisan db:seed --force`, die via de `UsersSeeder` de standaardaccounts
aanmaakt/bijwerkt (idempotent `updateOrCreate` op vaste e-mailadressen):

- `admin@admin.com` (rol `admin`)
- `testaccount01@example.com` (rol `user`)

Wachtwoorden komen uit omgevingsvariabelen, met een dev-standaard als fallback:

| Variable | Gebruiker | Standaard (lokaal) |
|---|---|---|
| `SEED_ADMIN_PASSWORD` | `admin@admin.com` | `Welkom1!23` |
| `SEED_USER_PASSWORD` | `testaccount01@example.com` | `Welkom1!23` |

> ⚠️ Omdat de fallback-wachtwoorden in de repo staan, zet je in Railway (productie) altijd
> `SEED_ADMIN_PASSWORD` op een sterk wachtwoord. Op die manier is je admin-account niet met een
> publiek bekend wachtwoord beveiligd. Wachtwoord wijzigen? Pas de variable aan → redeploy → de
> seeder werkt het account bij.

Log daarna in op `<jouw-domein>/dashboard`.

## Hoe verder werkt het vanaf nu

- Elke `git push origin main` → Railway bouwt en deployt automatisch.
- Migraties draaien bij elke deploy in de pre-deploy-stap (met wachtlus tot de database online is).
- De standaardaccounts (`admin@admin.com`, `testaccount01@example.com`) worden bij elke deploy via `UsersSeeder` bijgewerkt (idempotent, verwijdert niets).
- Exports werken direct (`QUEUE_CONNECTION=sync`, geen worker nodig).
- De app wordt geserved door Nginx + PHP-FPM (multi-process) in plaats van de single-threaded `php artisan serve` — dit is de grootste snelheidswinst ten opzichte van voorheen.

## Nieuwe functionaliteit & migratie-gedrag (aug 2026)

De app is multi-user geworden. Wat er bij het deployen van deze versie met je bestaande data gebeurt:

| Wijziging | Effect op bestaande data |
|---|---|
| Kolom `role` op `users` | Bestaande accounts krijgen default `user`. De `UsersSeeder` zorgt dat `admin@admin.com` de rol `admin` houdt. |
| Kolom `user_id` op `time_entries` | Bestaande uren worden tijdens de migratie automatisch gekoppeld aan jouw admin-account, zodat niets zoekraakt of "eigenaarloos" wordt. |
| Kolommen `theme_mode` + `accent_color` op `users` | Krijgen defaults (`dark` / `cyan`); gedrag verandert niet. |
| Kolom `workbook_linked_at` op `users` | Nullable, geen effect op bestaande data. Gebruikers koppelen zelf hun Excel-werkblad. |

**Let op:** het persoonlijke Excel-werkblad staat in de container-opslag (`storage/app/private`),
die op Railway vluchtig is. Dat is bewust geen probleem: het werkblad wordt bij elke mutatie én
bij het downloaden opnieuw uit de database gegenereerd, dus het is altijd actueel.

**Regel voor toekomstige migraties:** altijd additief (nieuwe kolommen met `default()` of nullable,
nieuwe tabellen). Nooit kolommen droppen of data herschrijven in een migratie — dan blijft
deployen zonder dataverlies gegarandeerd.

> ✅ **Je gebruikersdata is veilig bij elke deploy.** De pre-deploy-stap (`railway/pre-deploy.sh`) draait
> alleen `php artisan migrate --force` (additief, verwijdert nooit data) en `php artisan db:seed` via de
> `UsersSeeder` (idempotent `updateOrCreate` op vaste e-mailadressen, verwijdert nooit bestaande data).
> Er wordt **nooit** `migrate:fresh`, `migrate:refresh` of een reset-seed gedraaid op Railway — zo hou je
> de ingevulde stage-uren van alle users intact. Alleen de accounts met een van de twee vaste
> e-mailadressen (`admin@admin.com`, `testaccount01@example.com`) worden bijgewerkt/gewaarborgd.

Nieuw sinds deze versie:

- Gebruikersbeheer door admins (accounts aanmaken/bewerken; geen self-registration)
- Iedereen ziet alleen eigen uren; admins zien alles
- Instellingenpagina: account beheren + thema (donker/licht/systeem) en accentkleur
- Excel-export (.xlsx) en **Excel-synchronisatie**: upload een bestand en de app werkt je uren bij,
  maakt nieuwe aan en kan (optioneel) overtollige regels verwijderen

## Troubleshooting

| Probleem | Oplossing |
|---|---|
| Inloggen lukt, maar daarna `403 Forbidden` op `/admin` | Het `User`-model implementeert het `FilamentUser`-contract niet — Filament weigert dan élke user in productie (lokaal met `APP_ENV=local` lijkt het te werken). Fix: `User extends Authenticatable implements FilamentUser` mét `canAccessPanel(): bool` (staat in de repo). |
| `500 Internal Server Error` op `/admin/time-entries` | De tabel-filters gebruikten MySQL-only functies (`DATE_FORMAT`, `YEARWEEK`) die PostgreSQL niet kent. Gefixt: maanden/weken worden nu in PHP berekend (Carbon) en gefilterd via portabele `whereBetween`-queries in `TimeEntriesTable`. |
| `Application failed to respond` op het domein | App draait niet (meer) of Railway routeert naar de verkeerde poort. Check: 1) deployment-logs — draait Nginx op welke poort? 2) app-service → **Settings → Networking** → domein → **target port** moet gelijk zijn aan de luisterpoort ($PORT / 8080). 3) Stond de container midden in een herstart (crash-loop)? Zie de wachtlus-fix hieronder. |
| Build-log noemt `railpack` en faalt op `php >=8.4.1` / `ext-intl missing` | Railway gebruikte de verkeerde builder — `railway.json` in de repo forceert Nixpacks. Staat die er niet in? Zet hem dan handmatig: app-service → **Settings** → **Build** → Builder → **Nixpacks**, en redeploy. |
| `ParseError ... vendor/phpunit/.../Version.php` of setup toont `php83.withExtensions` | Nixpacks koos PHP 8.3 doordat `composer.json` `"php": "^8.3"` eiste (lockfile heeft ≥8.4.1 nodig). Opgelost door `"php": "^8.4"` + install-fase met `--no-dev`. |
| `does not provide an export named 'styleText'` in de build-fase | Nixpacks gebruikte Node 18, maar Vite 8 vereist Node ≥20.19. Opgelost via `NIXPACKS_NODE_VERSION = '22'` (nixpacks.toml) + `"engines"` in package.json. |
| `SQLSTATE[HY000] [2054] authentication method unknown [caching_sha2_password]` bij starten | Er staat nog een **MySQL**-service verbonden — de PHP-build (libmariadb) ondersteunt die auth niet. Vervang door PostgreSQL: verwijder de MySQL-service, + New → Database → PostgreSQL, en wijs `DB_URL` naar `${{<servicenaam>.DATABASE_URL}}` met `DB_CONNECTION=pgsql`. |
| `SQLSTATE[HY000] [2002] Connection refused` of `SQLSTATE[08006] [7] ... database system is starting up` bij starten | Race tussen app- en DB-start (database nog niet online). De pre-deploy-stap (`railway/pre-deploy.sh`) heeft een wachtlus (`until php artisan migrate --force; do sleep 5; done`) die dit opvangt — zie je het nog, check dan of de nieuwste deployment de lus bevat. |
| `could not find driver` of `Call to undefined function pg_connect()` bij migreren | De `pdo_pgsql`-extensie ontbreekt in de build — check of `nixpacks.toml` `php84Extensions.pdo_pgsql` in de setup-fase heeft staan en trigger een redeploy (**Deployments** → ⋯ → Redeploy). |
| `Class "Filament\PanelProvider" not found` of intl-fout | Build gebruikte geen `nixpacks.toml` — check of het bestand gepusht is en trigger een redeploy (**Deployments** → ⋯ → Redeploy). |
| `No application encryption key has been specified` | `APP_KEY` ontbreekt — stap 2. |
| `Connection refused` / SQLSTATE[HY000] [2002] | `DB_URL`-referentie klopt niet met de servicenaam van de database — stap 2, let op `${{...}}`. |
| Witte pagina / CSS mist | Vite-build gefaald — deployment-logs checken. |
| 404 op alles | `APP_URL` niet gezet na Generate Domain — stap 3.3. |

## Goed om te weten

- De database heet standaard `railway` (variabele `PGDATABASE` / `DATABASE_NAME`) — dat is gewoon de naam van de database, prima om zo te laten.
- Heb je ooit ergens je DB-wachtwoord gedeeld? Verwijder dan de DB-service en maak hem opnieuw aan (nieuwe wachtwoorden worden automatisch gegenereerd), of roteer het wachtwoord via de variables van de DB-service.

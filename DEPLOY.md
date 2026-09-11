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
| `LOG_CHANNEL` | `stderr` (Railway logt naar stderr; `single`/file-logging is vluchtig en verdwijnt) |
| `SESSION_DRIVER` | `redis` (of `database` zonder Redis-service) |
| `QUEUE_CONNECTION` | `sync` (of `redis` voor async exports) |
| `CACHE_STORE` | `redis` (of `database` zonder Redis-service) |
| `SESSION_SECURE_COOKIE` | `true` — de app draait altijd op HTTPS; zo wordt de sessiecookie nooit over onversleuteld verkeer verstuurd |
| `REDIS_URL` | `${{Redis.REDIS_URL}}` — **alleen** als je een Redis-service toevoegt |

> ⚠️ `${{Postgres.DATABASE_URL}}` verwijst naar de database-service. Heet jouw database-service anders (bijv. `postgres` of `database`), pas dan het eerste deel aan: `${{<servicenaam>.DATABASE_URL}}`.
>
> 🔎 **Deploy-crash: `connection refused 127.0.0.1:5432 ... database laravel`?** Dit betekent dat `DB_URL` (of `DB_HOST`/`DB_PORT`/`DB_DATABASE`) **niet** (goed) op de app-service staat. Zonder `DB_URL` valt Laravel terug op de defaults `127.0.0.1:5432`/`laravel`/`root` uit `config/database.php:90-94` — vandaar `laravel` als databasenaam. Je gebruikt de key **`DB_URL`** (niet `DATABASE_URL`, die leest Laravel niet uit deze config). Controleer drie dingen:
> 1. De value is van de vorm `${{<servicenaam>.DATABASE_URL}}` **zonder aanhalingstekens** eromheen (een `"${{...}}"` wordt als letterlijke tekst doorgegeven en is een ongeldige URL).
> 2. `<servicenaam>` is **exact** de servicenaam van je Postgres-service (hoofdlettergevoelig, bijv. `Postgres-5c9fa272-...`).
> 3. De variable staat op de **app-service**, niet op de database-service.
>
> 💡 **Waarom Redis?** Zonder Redis slaat de app cache en sessions op in PostgreSQL. Elke pagina-laad is dan meerdere extra DB-rondes over het netwerk (trager). Met Redis zijn dat snelle in-memory reads. Lokaal merk je het verschil niet (MySQL op localhost), online wél — dit is naast Nginx/PHP-FPM de grootste snelheidswinst. Wil je het eenvoudig houden, dan volstaat `database` ook prima.
>
> 🚀 **Gratis snelheidswinsten zonder Redis** (staan al in deze repo):
> 1. **OPcache** — ingeschakeld via `nixpacks.toml` (`php84Extensions.opcache`). PHP hercompiled op die manier geen code-bundels bij elke request. Dit is de grootste gratis PHP-winst op Railway.
> 2. **Gecachte config/routes/views** — draait al in `railway/pre-deploy.sh`.
> 3. **`LOG_CHANNEL=stderr`** — zet dit in Railway; file-logging is vluchtig en onzichtbaar in de Railway-logs.
> 4. **Minder DB-queries per request** — het dashboard laadt nu de week-totalen efficiënter, en admin-views eager-loaden gerelateerde records (geen N+1).

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

1. App-service → **Settings** → **Networking** → **Generate Domain**. De **target port** moet gelijk zijn aan de poort waarop de app luistert: **8000** (zie `start.sh`). Railway stelt het domein in met target port `8000`, dus meestal hoef je niets te wijzigen. Pas de target port **niet** automatisch aan naar een handmatige `PORT`-variabele — die kun je beter weglaten.
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

## Stap 6 — Admin-account (wordt automatisch aangemaakt)

In de pre-deploy-stap draait **altijd** `php artisan migrate --force`, gevolgd door
`php artisan db:seed --force`. De `UsersSeeder` is **idempotent** en doet bij elke deploy één van
twee dingen:

- Staat er nog geen admin-account? Dan maakt hij `admin@admin.com` aan (rol `admin`) met het
  **standaard wachtwoord `Admin1!23`**. Via de optionele variables `SEED_ADMIN_EMAIL` en
  `SEED_ADMIN_PASSWORD` (zie `config/seeding.php`) kun je beide standaardwaarden overschrijven.
- Bestaat de admin al? Dan laat hij alles met rust — ook een online gewijzigd wachtwoord blijft
  bewaard en wordt nooit teruggezet.

> ⚠️ **Wijzig het admin-wachtwoord direct na de eerste login** via Instellingen → Mijn account.
> De seeder is verder een no-op zodra de admin bestaat; er zijn géén `RUN_SEED`-flags nodig.
> Daarna voeg je zelf meer gebruikers toe via Beheer → Gebruikers.

Log daarna in op `<jouw-domein>/dashboard`.

## Hoe verder werkt het vanaf nu

- Elke `git push origin main` → Railway bouwt en deployt automatisch.
- Migraties draaien bij elke deploy in de pre-deploy-stap (met wachtlus tot de database online is).
- De seeder draait bij elke deploy mee en is een no-op zodra de admin-account bestaat — je data en
  gewijzigde wachtwoorden blijven intact.
- Exports werken direct (`QUEUE_CONNECTION=sync`, geen worker nodig).
- De app wordt geserved door Nginx + PHP-FPM (multi-process) in plaats van de single-threaded `php artisan serve` — dit is de grootste snelheidswinst ten opzichte van voorheen.

## Migraties & data

Het volledige schema wordt opgebouwd uit vijf nette **`create_`-migraties** in eindstate:

| Migratie | Tabellen |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_08_21_085424_create_time_entries_table` | `time_entries` |
| `2026_08_27_101950_create_exports_table` | `exports` (Filament-exports) |

> In september 2026 zijn de eerder losse `alter_/add_/update_/drop_`-migraties samengevoegd tot
> deze eindstate. De app stond toen nog maar net in productie, dus beide databases zijn opnieuw
> opgebouwd: lokaal met `php artisan migrate:fresh --seed` (in de Docker-app-container) en op
> Railway door de database even te resetten. De Railway pre-deploy draait daarna gewoon
> `php artisan migrate --force` en bouwt het schema vanaf nul op.

**Rule voor de toekomst:** wijzig het schema alleen nog via **additieve** migraties — nieuwe
kolommen met `default()` of nullable, nieuwe tabellen. Nooit kolommen droppen of data herschrijven
in een migratie — zo blijft deployen zonder dataverlies gegarandeerd.

> ✅ **Je gebruikersdata is veilig bij elke deploy.** De pre-deploy-stap (`railway/pre-deploy.sh`) draait
> altijd `php artisan migrate --force` (additief, verwijdert nooit data) en daarna `db:seed` — die seeder
> is idempotent (maakt alleen de admin aan als die ontbreekt). Er wordt **nooit** `migrate:fresh`,
> `migrate:refresh` of een reset-seed gedraaid op Railway — zo hou je de ingevulde stage-uren van
> alle users intact. De enige uitzondering was de eenmalige schema-consolidatie (sept 2026), die
> handmatig is gedaan toen er nog geen echte data stond.

Nieuw sinds deze versie:

- Gebruikersbeheer door admins (accounts aanmaken/bewerken/verwijderen; geen self-registration).
  Bij het verwijderen van een gebruiker worden diens stage-uren ook verwijderd (User::deleting
  event + FK met `cascadeOnDelete` in de database).
- Iedereen — óók de admin — ziet alleen eigen uren.
- Instellingenpagina: account beheren + thema (donker/licht/systeem) + stage-uren doel.
- Excel-export (.xlsx).

## Troubleshooting

| Probleem | Oplossing |
|---|---|
| Inloggen lukt, maar daarna `403 Forbidden` op `/dashboard` | Het `User`-model implementeert het `FilamentUser`-contract niet — Filament weigert dan élke user in productie (lokaal met `APP_ENV=local` lijkt het te werken). Fix: `User extends Authenticatable implements FilamentUser` mét `canAccessPanel(): bool` (staat in de repo). |
| `500 Internal Server Error` op `/dashboard/time-entries` | De tabel-filters gebruikten MySQL-only functies (`DATE_FORMAT`, `YEARWEEK`) die PostgreSQL niet kent. Gefixt: maanden/weken worden nu in PHP berekend (Carbon) en gefilterd via portabele `whereBetween`-queries in `TimeEntriesTable`. |
| `Application failed to respond` op het domein | **Meestal een port-mismatch.** De app (nginx) luistert op **8000** (= domein **target port**). Heeft Railway een handmatige `PORT`-variabele (bv. `8080`) die afwijkt, dan klopt de luisterpoort nooit. Oplossing: verwijder de handmatige `PORT`-variabele en check dat domein → **target port** = `8000`. Deploy-logs moeten `[start] nginx will listen on 0.0.0.0:8000` tonen. Zie je een crash-loop, check dan de wachtlus-fix hieronder. |
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

# Stage Urenregistratie App — Volledige Technische Documentatie

## Inhoudsopgave

1. [Projectoverzicht](#1-projectoverzicht)
2. [Doel van het project](#2-doel-van-het-project)
3. [Voor wie het project bedoeld is](#3-voor-wie-het-project-bedoeld-is)
4. [Kernfunctionaliteiten](#4-kernfunctionaliteiten)
5. [Technologieën en dependencies](#5-technologieën-en-dependencies)
6. [Architectuuroverzicht](#6-architectuuroverzicht)
7. [Hoe het systeem werkt](#7-hoe-het-systeem-werkt)
8. [Projectstructuur](#8-projectstructuur)
9. [Installatie en configuratie](#9-installatie-en-configuratie)
10. [Environment variables](#10-environment-variables)
11. [Database en datamodellen](#11-database-en-datamodellen)
12. [Authenticatie en autorisatie](#12-authenticatie-en-autorisatie)
13. [Rollen en beveiliging](#13-rollen-en-beveiliging)
14. [Frontend en Filament Admin Panel](#14-frontend-en-filament-admin-panel)
15. [Backend — Models, Services en Business Logic](#15-backend--models-services-en-business-logic)
16. [Belangrijkste dataflows](#16-belangrijkste-dataflows)
17. [Excel Export](#17-excel-export)
18. [Excel Synchronisatie (Import)](#18-excel-synchronisatie-import)
19. [Persoonlijk Excel-werkblad](#19-persoonlijk-excel-werkblad)
20. [Routes en Filament Auto-Discovery](#20-routes-en-filament-auto-discovery)
21. [Error handling en logging](#21-error-handling-en-logging)
22. [Testen](#22-testen)
23. [Build en deployment](#23-build-en-deployment)
24. [Veelvoorkomende problemen](#24-veelvoorkomende-problemen)
25. [Onderhoud en uitbreiding](#25-onderhoud-en-uitbreiding)
26. [Technische referentie](#26-technische-referentie)
27. [Conclusie](#27-conclusie)

---

## 1. Projectoverzicht

**Stage Urenregistratie** is een webapplicatie waarmee stagiairs hun werkuren kunnen bijhouden, exporteren naar Excel, en een persoonlijk Excel-werkblad automatisch laten bijwerken.

### Samenvatting in één zin

> Een Laravel 13 + Filament 5 applicatie voor het registreren, exporteren en beheren van stage-uren, met automatische Excel-synchronisatie en multi-user ondersteuning.

### Technologische.samenvatting

| Component | Technologie |
|---|---|
| Backend framework | Laravel 13 (`laravel/framework ^13.17`) |
| Admin panel | Filament 5 (`filament/filament ~5.0`) |
| Programmeertaal | PHP 8.4 |
| Frontend | Blade + Livewire (via Filament) + Tailwind CSS 4 + Alpine.js |
| Database | SQLite (development), MySQL (Docker lokaal), PostgreSQL (productie/Railway) |
| Export library | OpenSpout (direct gebruik) |
| Build tools | Vite 8 + laravel-vite-plugin |
| Test framework | Pest PHP 5 |
| Deployment | Railway (Nixpacks) |

---

## 2. Doel van het project

Het project biedt een eenvoudige, overzichtelijke manier voor stagiairs om hun dagelijkse werkuren bij te houden. Kernpunten:

- **Registreren**: datum, begintijd, eindtijd, pauze en beschrijving per uur-registratie.
- **Automatische duurberekening**: de duur wordt automatisch berekend, inclusief ondersteuning voor uren die over middernacht lopen.
- **Exporteren**: uren exporteren naar een gestylede Excel (.xlsx) file.
- **Synchroniseren**: een bestaand Excel-bestand uploaden en de registraties daarmee synchroniseren (herkennen, bijwerken, aanmaken).
- **Persoonlijk werkblad**: een Excel-werkblad koppelen dat automatisch wordt bijgehouden bij elke wijziging.
- **Multi-user**: iedere gebruiker ziet alleen zijn eigen uren; admins beheren het systeem en maken gebruikers aan.

---

## 3. Voor wie het project bedoeld is

| Doelgroep | Waarvoor |
|---|---|
| **Stagiairs** | dagelijks hun uren bijhouden en exporteren |
| **Begeleiders / admins** | gebruikers aanmaken en het systeem beheren |

Er is geen publieke registratie. Accounts worden aangemaakt door een admin.

---

## 4. Kernfunctionaliteiten

### 4.1 Urenregistratie
- Registraties aanmaken, bewerken en verwijderen via `/dashboard/time-entries`
- Automatische duurberekening (werkt ook over middernacht heen)
- Pauze in minuten wordt van de totale duur afgetrokken

### 4.2 Dashboard
- Weekoverzicht met navigatie (vorige / huidige / volgende week)
- Voortgangsbalk die laat zien hoeveel stage-uren er zijn voltooid
- Snelknop voor het aanmaken van een nieuwe tijdregistratie

### 4.3 Exporteren naar Excel
- "Exporteer week" op het dashboard: exporteert de huidige week
- "Exporteer alles" op het dashboard: exporteert alle uren
- "Exporteren" op de tijdregistraties-lijst: Filament's ingebouwde export-systeem
- Bestanden worden gestreamed naar de browser (geen geheugenbelasting)

### 4.4 Excel Synchronisatie (Import)
- Upload een `.xlsx` of `.csv` bestand
- Herkent automatisch Nederlandse en Engelse kolomnamen
- Bestaande registraties worden bijgewerkt op basis van datum + begintijd
- Nieuwe registraties worden aangemaakt
- Optioneel: registraties die niet in het bestand staan verwijderen
- Gedetailleerd rapport na synchronisatie (aangemaakt / bijgewerkt / verwijderd / overgeslagen / fouten)

### 4.5 Persoonlijk Excel-werkblad
- Koppel één keer je eigen stage-urenwerkblad
- Het bestand wordt automatisch bijgewerkt bij elke wijziging (aanmaken, bewerken, verwijderen, Excel-sync)
- Download het werkblad op elk moment via "Mijn Excel-werkblad"

### 4.6 Instellingen
Via `Instellingen` in de navigatie kan elke gebruiker:
- Naam, e-mailadres en wachtwoord aanpassen
- Themas kiezen: donker (standaard), licht of systeem
- Accentkleur kiezen (16 kleuren beschikbaar) — direct toegepast op de hele app
- Totaal te lopen stage-uren instellen (voor de voortgangsbalk)

### 4.7 Gebruikersbeheer (admins)
- Gebruikers aanmaken, bewerken en verwijderen
- Rol toewijzen (admin, gebruiker, student)
- Alleen zichtbaar en toegankelijk voor admins
- Geen self-registration — accounts worden door een admin aangemaakt

---

## 5. Technologieën en dependencies

### 5.1 Backend (PHP)

| Package | Versie | Doel |
|---|---|---|
| `laravel/framework` | `^13.17` | PHP-framework |
| `filament/filament` | `~5.0` | Admin panel, formulieren, tabellen, exports |
| `laravel/tinker` | `^3.0` | Interactieve PHP-terminal |
| `maatwebsite/excel` | `*` | Excel lezen/schrijven (gebruikt OpenSpout) |
| `octopyid/filament-palette` | `^1.0` | Kleurenpalettes voor Filament |

**Development dependencies:**

| Package | Versie | Doel |
|---|---|---|
| `pestphp/pest` | `^5.1` | Test framework |
| `laravel/pint` | `^1.27` | Code style linter |
| `laravel/pail` | `^1.2.5` | Live logs bekijken |
| `fakerphp/faker` | `^1.23` | Test data genereren |

### 5.2 Frontend

| Package | Versie | Doel |
|---|---|---|
| `tailwindcss` | `^4.0.0` | Utility-first CSS framework |
| `@tailwindcss/vite` | `^4.0.0` | Tailwind integratie met Vite |
| `laravel-vite-plugin` | `^3.1` | Vite integratie voor Laravel |
| `vite` | `^8.0.0` | Build tool |
| `@laravel/multiplex` | `^0.4.1` | (Optioneel) Livewire multiplexing |

### 5.3 Taal

De applicatie is volledig in het Nederlands. De `.env` bevat:
```
APP_LOCALE=nl
APP_FALLBACK_LOCALE=nl
APP_FAKER_LOCALE=nl_NL
```

---

## 6. Architectuuroverzicht

### 6.1 Overzicht van de lagen

```
┌─────────────────────────────────────────────────────────────────┐
│                        GEBRUIKER                                │
│                     (Browser / URL)                              │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                   PUBLIC/INDEX.PHP                               │
│              (Laravel entry point)                               │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│               BOOTSTRAP/APP.PHP                                 │
│  - Routing configureren                                         │
│  - Middleware instellen                                          │
│  - Foutafhandeling configureren                                 │
└──────────────────────────┬──────────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              ▼                         ▼
┌──────────────────────┐  ┌──────────────────────────────────────┐
│   ROUTES/WEB.PHP     │  │    FILAMENT ADMIN PANEL              │
│  - / → redirect      │  │    (AdminPanelProvider)               │
│  - /workbook/download│  │  - Auto-discovery resources/pages     │
│  - /theme            │  │  - Authenticatie middleware            │
└──────────────────────┘  │  - Livewire real-time updates         │
                          └──────────────┬───────────────────────┘
                                         │
                          ┌──────────────┼───────────────────────┐
                          ▼              ▼                       ▼
                    ┌───────────┐ ┌─────────────┐  ┌──────────────────┐
                    │ CONTROLLERS│ │ FILAMENT    │  │  PROVIDERS       │
                    │           │ │ PAGES       │  │  (AppService     │
                    │ Workbook  │ │ Dashboard   │  │   Provider)      │
                    │ Controller│ │ Settings    │  │  Model events    │
                    └─────┬─────┘ │ EditProfile │  │  registreren     │
                          │       └──────┬──────┘  └────────┬─────────┘
                          │              │                   │
                          ▼              ▼                   ▼
                    ┌──────────────────────────────────────────────┐
                    │              SERVICES                         │
                    │  - ExportService      (Excel export)          │
                    │  - WorkbookService    (persoonlijk werkblad)  │
                    │  - TimeEntrySyncService (Excel import)        │
                    └──────────────────────┬───────────────────────┘
                                           │
                          ┌────────────────┼────────────────┐
                          ▼                ▼                ▼
                    ┌──────────┐  ┌──────────────┐  ┌──────────────┐
                    │  MODELS  │  │   POLICIES   │  │   HELPERS    │
                    │  User    │  │ TimeEntry    │  │ Duration     │
                    │  TimeEntry│  │ UserPolicy   │  │ Helper       │
                    └────┬─────┘  └──────────────┘  └──────────────┘
                         │
                         ▼
                    ┌──────────────────────────────────────────────┐
                    │              DATABASE                         │
                    │  - users                                      │
                    │  - time_entries                               │
                    │  - sessions                                   │
                    │  - cache / cache_locks                        │
                    │  - jobs / job_batches / failed_jobs           │
                    │  - password_reset_tokens                      │
                    └──────────────────────────────────────────────┘
```

### 6.2 Verantwoordelijkheden per laag

| Laag | Bestanden | Verantwoordelijkheid |
|---|---|---|
| **Entry point** | `public/index.php` | Ontvangt elk HTTP-verzoek en geeft het door aan Laravel |
| **Bootstrap** | `bootstrap/app.php` | Configuratie van routing, middleware en foutafhandeling |
| **Providers** | `app/Providers/` | Registeren van services en model-event listeners |
| **Routes** | `routes/web.php` | Handmatig gedefinieerde URL-routes (3 stuks) |
| **Filament Panel** | `app/Providers/Filament/AdminPanelProvider.php` | Configuratie van het admin panel (login, thema, resources) |
| **Filament Resources** | `app/Filament/Admin/Resources/` | CRUD-operaties voor TimeEntry en User |
| **Filament Pages** | `app/Filament/Admin/Pages/` | Dashboard, Settings, EditProfile |
| **Filament Actions** | `app/Filament/Admin/Actions/` | Workbook acties, Excel synchronisatie |
| **Controllers** | `app/Http/Controllers/` | Workbook download endpoint |
| **Services** | `app/Services/` | Business logic voor export, sync en workbook |
| **Models** | `app/Models/` | Data-relaties, casts, berekeningen |
| **Policies** | `app/Policies/` | Toegangscontrole per model |
| **Helpers** | `app/Helpers/` | Hulpfuncties (tijd formattering) |
| **Enums** | `app/Enums/` | Type-veilige waarden (rollen) |
| **Database** | `database/` | Migraties, seeders, factories |

---

## 7. Hoe het systeem werkt

### 7.1 Applicatie-opstart sequence

Wanneer een gebruiker de applicatie opent, gebeurt het volgende:

```
1. Browser → HTTP-verzoek naar public/index.php
2. public/index.php → laadt Composer autoloader → bootstrapt Laravel
3. bootstrap/app.php → configureert routing, middleware, foutafhandeling
4. AdminPanelProvider → configureert het Filament admin panel
5. Laravel matching → welke route past bij het verzoek?
   ├─ Handmatige route in routes/web.php → voer die uit
   └─ Geen match → Filament zoekt in het admin panel
6. Filament vindt de juiste Resource of Page
7. Livewire verwerkt de actie (ALS het een AJAX-verzoek is)
8. Blade rendert de HTML
9. CSS (Tailwind) + JavaScript (Alpine.js) worden toegevoegd
10. Response wordt teruggestuurd naar de browser
```

### 7.2 Pagina-verzoeken

#### Onbekende URL als NIET-ingelogd
```
Gebruiker → /willekeurige-url → 404 exception
→ bootstrap/app.php on NotFoundHttpException
→ Redirect naar /dashboard/login
```
*Dit is geconfigureerd in `bootstrap/app.php:25-31`.*

#### Onbekende URL als WEL-ingelogd
```
Gebruiker → /willekeurige-url → 404 exception
→ bootstrap/app.php on NotFoundHttpException
→ Redirect naar /dashboard
```

#### Home page
```
Gebruiker → / → routes/web.php → redirect()->route('filament.admin.pages.dashboard')
→ /dashboard
```

---

## 8. Projectstructuur

```
stage-urenregistratie-app/
├── app/
│   ├── Enums/
│   │   └── Role.php                           # Rollen: student, user, admin
│   ├── Filament/
│   │   ├── Admin/
│   │   │   ├── Actions/
│   │   │   │   ├── SyncTimeEntriesAction.php   # Excel synchronisatie modal
│   │   │   │   └── WorkbookActions.php         # Koppelen/downloaden/ontkoppelen
│   │   │   ├── Pages/
│   │   │   │   ├── Dashboard.php               # Hoofddashboard met weekoverzicht
│   │   │   │   ├── EditProfile.php             # Profiel + stage-uren doel
│   │   │   │   └── Settings.php                # Account + thema + kleur
│   │   │   └── Resources/
│   │   │       ├── TimeEntries/                # CRUD voor tijdregistraties
│   │   │       └── Users/                      # CRUD voor gebruikers (admin-only)
│   │   └── Exports/
│   │       └── TimeEntryExporter.php           # Filament export voor uren
│   ├── Helpers/
│   │   └── DurationHelper.php                  # Minuten → "HH:MM" formattering
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php                  # Abstracte basisklasse
│   │       └── WorkbookController.php          # Download endpoint
│   ├── Models/
│   │   ├── TimeEntry.php                       # Uur-registratie model
│   │   └── User.php                            # Gebruiker model
│   ├── Policies/
│   │   ├── TimeEntryPolicy.php                 # Toegang tot uren
│   │   └── UserPolicy.php                      # Toegang tot gebruikers
│   ├── Providers/
│   │   ├── AppServiceProvider.php              # Model event listeners
│   │   └── Filament/
│   │       └── AdminPanelProvider.php          # Filament panel configuratie
│   └── Services/
│       ├── ExportService.php                   # Excel/CSV export
│       ├── SyncResult.php                      # Resultaat-object sync
│       ├── TimeEntrySyncService.php            # Excel/CSV import
│       └── WorkbookService.php                 # Persoonlijk werkblad beheer
├── bootstrap/
│   ├── app.php                                # Laravel configuratie
│   └── providers.php                          # Geregistreerde service providers
├── config/
│   ├── app.php                                # App naam, timezone, locale
│   ├── auth.php                               # Authenticatie configuratie
│   ├── database.php                           # Database connecties
│   ├── filament-palette.php                   # Kleurenpalettes
│   ├── filesystems.php                        # Storage disks
│   └── ...                                    # Overige standaard Laravel configs
├── database/
│   ├── factories/
│   │   ├── TimeEntryFactory.php               # Test data voor uren
│   │   └── UserFactory.php                    # Test data voor gebruikers
│   ├── migrations/
│   │   ├── *_create_users_table.php           # Users + sessions + password_reset
│   │   ├── *_create_cache_table.php           # Cache tabellen
│   │   ├── *_create_jobs_table.php            # Queue tabellen
│   │   ├── *_create_time_entries_table.php    # Tijdregistraties
│   │   └── *_alter_users_accent_color_*.php   # Accent kleur default aanpassing
│   └── seeders/
│       ├── DatabaseSeeder.php                 # Hoofd seeder
│       └── UsersSeeder.php                    # Standaard admin + test user
├── public/
│   ├── index.php                              # HTTP entry point
│   ├── build/                                 # Vite build output (CSS, JS, fonts)
│   ├── css/filament/                          # Filament framework CSS
│   └── js/filament/                           # Filament framework JS
├── resources/
│   ├── css/app.css                            # Bron: Tailwind + font config
│   ├── js/app.js                              # Bron: (leeg, placeholder)
│   └── views/
│       ├── welcome.blade.php                  # Redirect naar dashboard
│       └── filament/
│           ├── components/
│           │   └── accent-color-picker.blade.php  # Kleurenkiezer component
│           ├── pages/settings.blade.php       # Settings pagina wrapper
│           ├── theme-sync.blade.php           # Thema synchronisatie script
│           ├── theme-switcher.blade.php       # Thema wissel-knop
│           └── widgets/
│               └── progress-bar.blade.php     # Voortgangsbalk component
├── routes/
│   ├── web.php                                # Handmatige routes (3 stuks)
│   └── console.php                            # Artisan commando's
├── tests/
│   ├── Feature/
│   │   ├── AuthAndAccessTest.php              # Authenticatie & toegang tests
│   │   ├── TimeEntrySyncTest.php              # Excel sync tests
│   │   └── WorkbookTest.php                   # Workbook tests
│   ├── Unit/
│   │   └── ExampleTest.php                    # Basis test
│   ├── Pest.php                               # Pest configuratie
│   └── TestCase.php                           # Basis test klasse
├── .env.example                               # Voorbeeld environment variables
├── composer.json                              # PHP dependencies
├── package.json                               # JS dependencies
├── phpunit.xml                                # PHPUnit/Pest configuratie
├── railway.json                               # Railway deployment config
├── nixpacks.toml                              # Nixpacks build config
├── vite.config.js                             # Vite build configuratie
├── DEPLOY.md                                  # Deploy handleiding
└── DOCUMENTATION.md                           # Dit bestand
```

---

## 9. Installatie en configuratie

### 9.1 Vereisten

| Software | Versie | Verplicht |
|---|---|---|
| PHP | >= 8.4 | Ja |
| Composer | >= 2.x | Ja |
| Node.js | >= 22.12 | Ja (voor Vite) |
| npm | >= 10.x | Ja |
| Docker | >= 20.x | Optioneel (voor MySQL lokaal) |
| MySQL | 8.x | Optioneel (alleen lokaal via Docker) |

### 9.2 Stap voor stap installatie

#### Optie A: Met Docker (aanbevolen voor lokaal)

```bash
# 1. Clone de repository
git clone <repo-url>
cd stage-urenregistratie-app

# 2. Maak een .env aan
cp .env.example .env

# 3. Genereer een APP_KEY
php artisan key:generate

# 4. Start de MySQL container
docker compose -f docker-local/docker-compose.yml up -d

# 5. Installeer PHP dependencies
composer install

# 6. Installeer JS dependencies en build
npm install
npm run build

# 7. Voer migraties uit
php artisan migrate --seed

# 8. Start de development server
composer dev
```

> **Let op:** De `docker-local/` map staat in `.gitignore` en wordt niet meegeleverd in de repository. Je moet deze map zelf aanmaken of de数据库instellingen in `.env` aanpassen naar SQLite.

#### Optie B: Zonder Docker (SQLite)

```bash
# 1. Clone de repository
git clone <repo-url>
cd stage-urenregistratie-app

# 2. Maak een .env aan (pas DB_* aan naar SQLite)
cp .env.example .env
# In .env: DB_CONNECTION=sqlite (standaard in .env.example)

# 3. Maak het SQLite bestand aan
touch database/database.sqlite

# 4. Genereer een APP_KEY
php artisan key:generate

# 5. Installeer dependencies
composer install
npm install && npm run build

# 6. Voer migraties uit en seed
php artisan migrate --seed

# 7. Start de development server
composer dev
```

### 9.3 De `composer dev` command

Deze command (uit `composer.json`) start drie processen tegelijk:

```bash
npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" \
  "php artisan serve" \
  "php artisan queue:listen --tries=1 --timeout=0" \
  "npm run dev" \
  --names='server,queue,vite'
```

| Proces | Wat het doet |
|---|---|
| `server` | PHP built-in server op `localhost:8000` |
| `queue` | Luistert naar jobs in de queue (voor exports) |
| `vite` | Vite dev server met hot module replacement |

### 9.4 De `composer test` command

```bash
php artisan config:clear --ansi  # Ververst configuratie cache
php artisan test                  # Voert alle Pest tests uit
```

### 9.5 Inloggen (development)

Na `migrate --seed` kun je inloggen met (standaard dev-wachtwoorden, overschrijfbaar via
`SEED_ADMIN_PASSWORD` / `SEED_USER_PASSWORD`):

| Rol | E-mail | Wachtwoord (standaard) |
|---|---|---|
| Admin | `admin@admin.com` | `Welkom1!23` |
| Gebruiker | `testaccount01@example.com` | `Welkom1!23` |

### 9.6 Builden voor productie

```bash
npm run build
```

Dit genereert gestylede en geminificeerde CSS- en JS-bestanden in `public/build/`.

---

## 10. Environment variables

### 10.1 Essentiële variabelen

| Variabele | Verplicht | Doel | Voorbeeld |
|---|---|---|---|
| `APP_NAME` | Ja | Naam van de applicatie | `StageUrenregistratieApp` |
| `APP_ENV` | Ja | Omgeving: `local` of `production` | `local` |
| `APP_KEY` | Ja | Encryptiesleutel (geheim) | `base64:...` (genereer met `php artisan key:generate`) |
| `APP_DEBUG` | Ja | Debug-modus aan/uit | `true` (lokaal) / `false` (productie) |
| `APP_URL` | Ja | Basis-URL van de app | `http://localhost:8000` |
| `APP_LOCALE` | Ja | Taal van de app | `nl` |
| `DB_CONNECTION` | Ja | Database type | `sqlite` / `mysql` / `pgsql` |

### 10.2 Database variabelen

| Variabele | Verplicht | Doel | Voorbeeld |
|---|---|---|---|
| `DB_HOST` | Bij MySQL/pgsql | Database host | `mysql` (Docker) / `127.0.0.1` |
| `DB_PORT` | Bij MySQL/pgsql | Database poort | `3306` (MySQL) / `5432` (pgsql) |
| `DB_DATABASE` | Bij MySQL/pgsql | Database naam | `stage_urenregistratie_app` |
| `DB_USERNAME` | Bij MySQL/pgsql | Database gebruiker | `stage_urenregistratie_app` |
| `DB_PASSWORD` | Bij MySQL/pgsql | Database wachtwoord (geheim) | `stage_urenregistratie_app` |
| `DB_URL` | Bij productie | Volledige database URL | `${{Postgres.DATABASE_URL}}` |

### 10.3 Session en cache variabelen

| Variabele | Standaard | Doel |
|---|---|---|
| `SESSION_DRIVER` | `database` | Waar sessies worden opgeslagen (`database`, `redis` of `file`) |
| `SESSION_LIFETIME` | `120` | Sessie-duur in minuten |
| `CACHE_STORE` | `database` | Waar cache wordt opgeslagen (`database`, `redis` of `file`) |
| `QUEUE_CONNECTION` | `sync` | Queue driver (`sync` = direct uitvoeren) |

Op Railway is `redis` de snelste keuze voor `SESSION_DRIVER` en `CACHE_STORE`: zonder Redis gaat elke
pagina-laad een paar extra DB-rondes naar PostgreSQL. Lokaal (localhost) merk je daar niets van.

### 10.4 Redis-variabelen

| Variabele | Doel |
|---|---|
| `REDIS_URL` | Volledige Redis URL, op Railway bijv. `${{Redis.REDIS_URL}}`. Overbodig bij lokale MySQL/SQLite-dev (daar gebruik je de losse `REDIS_HOST`/`REDIS_PORT`) |
| `REDIS_CLIENT` | `phpredis` (geïnstalleerd in de build via `nixpacks.toml`) |

Op Railway maakt de Redis-service zelf automatisch variabelen aan (`REDIS_URL`, `REDISHOST`,
`REDISPORT`, `REDISPASSWORD`, `REDISUSER`). Op de **app-service** verwijs je daar simpelweg naar met
een service-referentie: `REDIS_URL = ${{Redis.REDIS_URL}}` (de servicenaam `Redis` vervang je door je
werkelijke naam). Je hoeft de URL nooit zelf te bouwen.

### 10.5 Productie-variabelen (Railway)

| Variabele | Doel |
|---|---|
| `SEED_ADMIN_PASSWORD` | Wachtwoord voor de admin (`admin@admin.com`); altijd zetten in productie, geen standaard gebruiken |
| `SEED_USER_PASSWORD` | (Optioneel) wachtwoord voor de testaccount, standaard `Welkom1!23` |

### 10.6 Waar variabelen worden gebruikt

| Variabele | Gebruikt in |
|---|---|
| `APP_KEY` | Laravel encryptie (sessies, cookies, etc.) |
| `DB_*` | `config/database.php` |
| `SESSION_DRIVER` | `config/session.php` |
| `QUEUE_CONNECTION` | `config/queue.php` |
| `CACHE_STORE` | `config/cache.php` |
| `REDIS_URL` | `config/database.php` |
| `MAIL_*` | `config/mail.php` |

> **Let op:** Toon nooit echte geheimen, API keys, tokens of wachtwoorden in documentatie.

---

## 11. Database en datamodellen

### 11.1 Database-overzicht

De applicatie gebruikt **6 tabellen**:

```
┌─────────────────────────────────────────────────────────────────┐
│                        DATABASE SCHEMA                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  users                    time_entries                           │
│  ┌──────────────────┐     ┌──────────────────────────┐          │
│  │ id (PK)          │     │ id (PK)                  │          │
│  │ name             │     │ user_id (FK → users.id)  │          │
│  │ email (UNIQUE)   │◄────│ date                     │          │
│  │ password         │ 1:N │ start_time               │          │
│  │ role             │     │ end_time                 │          │
│  │ theme_mode       │     │ break_minutes            │          │
│  │ accent_color     │     │ description              │          │
│  │ workbook_linked  │     │ created_at               │          │
│  │ target_hours     │     │ updated_at               │          │
│  │ created_at       │     └──────────────────────────┘          │
│  │ updated_at       │                                           │
│  └──────────────────┘                                           │
│                                                                  │
│  sessions              cache          cache_locks                │
│  ┌──────────────┐     ┌──────────┐   ┌──────────────┐          │
│  │ id (PK)      │     │ key (PK) │   │ key (PK)     │          │
│  │ user_id (FK) │     │ value    │   │ owner        │          │
│  │ ip_address   │     │ expires  │   │ expires      │          │
│  │ user_agent   │     └──────────┘   └──────────────┘          │
│  │ payload      │                                               │
│  │ last_activity│     jobs            job_batches    failed_jobs │
│  └──────────────┘     ┌──────────┐   ┌──────────┐   ┌────────┐ │
│                       │ id (PK)  │   │ id (PK)  │   │ id(PK) │ │
│  password_reset_tokens│ queue    │   │ name     │   │ uuid   │ │
│  ┌──────────────┐     │ payload  │   │ ...      │   │ ...    │ │
│  │ email (PK)   │     │ attempts │   └──────────┘   └────────┘ │
│  │ token        │     └──────────┘                              │
│  │ created_at   │                                               │
│  └──────────────┘                                               │
└─────────────────────────────────────────────────────────────────┘
```

### 11.2 Users tabel

| Kolom | Type | Beschrijving |
|---|---|---|
| `id` | bigint (PK) | Uniek identificatienummer |
| `name` | string | Weergavenaam van de gebruiker |
| `email` | string (UNIQUE) | E-mailadres, wordt gebruikt om in te loggen |
| `password` | string | Gehashed wachtwoord (nooit leesbaar) |
| `role` | string(20) | Rol: `student`, `user` of `admin` |
| `theme_mode` | string(20) | Voorkeur: `dark` (standaard), `light` of `system` |
| `accent_color` | string(20), nullable | Accentkleur (standaard: `amber`) |
| `workbook_linked_at` | timestamp, nullable | Wanneer het Excel-werkblad werd gekoppeld |
| `target_hours` | integer, nullable | Totaal te lopen stage-uren (voor voortgangsbalk) |
| `created_at` | timestamp | Aanmaakdatum |
| `updated_at` | timestamp | Laatste wijziging |

**Waarom `accent_color` nullable is met default `amber`:** In de eerste versie was er geen default. Migratie `2026_08_26_122254` heeft de default toegevoegd en bestaande NULL-waarden bijgewerkt naar `amber`.

### 11.3 Time_entries tabel

| Kolom | Type | Beschrijving |
|---|---|---|
| `id` | bigint (PK) | Uniek identificatienummer |
| `user_id` | bigint (FK → users.id), nullable | Eigenaar van deze registratie |
| `date` | date | Datum van de registratie |
| `start_time` | time | Begintijd (HH:MM) |
| `end_time` | time | Eindtijd (HH:MM) |
| `break_minutes` | integer (default: 0) | Pauzeduur in minuten |
| `description` | text, nullable | Omschrijving van de werkzaamheden |
| `created_at` | timestamp | Aanmaakdatum |
| `updated_at` | timestamp | Laatste wijziging |

**Indexen:**
- `user_id` (enkel)
- `date` (enkel)
- `user_id + date` (samengesteld, voor snelle queries per gebruiker per datum)

**Foreign key:** `user_id` → `users.id` met `nullOnDelete`. Als een gebruiker wordt verwijderd, worden zijn uren niet verwijderd maar komen ze "eigenaarloos" te staan.

### 11.4 Relatie: User → TimeEntries

```
Een User heeft VEEL TimeEntries (1:N)
Een TimeEntry hoort bij ÉÉN User
```

```php
// In User model:
public function timeEntries(): HasMany
{
    return $this->hasMany(TimeEntry::class);
}

// In TimeEntry model:
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

### 11.5 Migratiebeleid

**Gouden regel:** altijd additief. Nieuwe kolommen met `default()` of `nullable`, nieuwe tabellen. Nooit kolommen droppen of data herschrijven in een migratie.

Waarom? Omdat `migrate --force` op productie elke keer opnieuw draait bij het starten van de container. Als je een kolom dropt, verdwijnt de data.

### 11.6 Seeders

De `UsersSeeder` maakt twee gebruikers aan:

| Gebruiker | E-mail | Rol |
|---|---|---|
| Admin | `admin@admin.com` | `admin` |
| Test Account | `testaccount01@example.com` | `user` |

De `DatabaseSeeder` roept alleen `UsersSeeder` aan.

### 11.7 Factories

Voor testen worden factories gebruikt:

- **UserFactory**: genereert willekeurige gebruikers. Methode `->admin()` geeft een admin-rol.
- **TimeEntryFactory**: genereert uren-registraties met een starttijd van 09:00, eindtijd van 17:00, 30 min pauze.

---

## 12. Authenticatie en autorisatie

### 12.1 Authenticatie (inloggen)

Het project gebruikt **Filament's ingebouwde authenticatie**. Er zijn geen custom middleware of controllers voor authenticatie.

**Flow:**
```
1. Gebruiker gaat naar /dashboard/login
2. Filament toont het login formulier
3. Gebruiker vult e-mail + wachtwoord in
4. Laravel controleert of de gegevens kloppen (User model + Hash)
5. Bij succes: sessie wordt aangemaakt in de `sessions` tabel
6. Gebruiker wordt doorgestuurd naar /dashboard
```

**Configuratie:**
- `AdminPanelProvider.php` bevat `->login()` wat de Filament login inschakelt
- `config/auth.php` configureert de `web` guard met Eloquent provider op het `User` model
- Sessies worden opgeslagen in de database (`SESSION_DRIVER=database`)

### 12.2 Autorisatie (toegangscontrole)

Autorisatie wordt geregeld via **Laravel Policies**.

#### TimeEntryPolicy (`app/Policies/TimeEntryPolicy.php`)

| Actie | Regel | Uitleg |
|---|---|---|
| `viewAny` | `true` | Iedereen mag de lijst zien |
| `view` | `$timeEntry->user_id === $user->id` | Alleen eigen uren |
| `create` | `true` | Iedereen mag uren aanmaken |
| `update` | `$timeEntry->user_id === $user->id` | Alleen eigen uren bewerken |
| `delete` | `$timeEntry->user_id === $user->id` | Alleen eigen uren verwijderen |
| `deleteAny` | `true` | Bulk delete is toegestaan |

> **Let op:** Inclusief de admin. De admin kan ANDERMANS uren NIET zien of bewerken. Iedereen heeft toegang tot exact dezelfde functionaliteit voor hun eigen uren.

#### UserPolicy (`app/Policies/UserPolicy.php`)

| Actie | Regel | Uitleg |
|---|---|---|
| `viewAny` | `$user->isAdmin()` | Alleen admins zien de gebruikerslijst |
| `view` | `$user->isAdmin()` | Alleen admins zien gebruikersdetails |
| `create` | `$user->isAdmin()` | Alleen admins mogen gebruikers aanmaken |
| `update` | `$user->isAdmin()` | Alleen admins mogen gebruikers bewerken |
| `delete` | `$user->isAdmin() && $user->id !== $model->id` | Admin mag zichzelf NIET verwijderen |
| `deleteAny` | `$user->isAdmin()` | Bulk delete is toegestaan |

### 12.3 Query-filtering

Naast policies filtert Filament ook op query-niveau. In `TimeEntryResource`:

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $query->where('user_id', auth()->id());
    return $query;
}
```

Dit zorgt ervoor dat de database-query ALTIJD wordt gefilterd op de ingelogde gebruiker, ongeacht de rol.

### 12.4 UserResource toegang

```php
// In UserResource.php:
public static function canAccess(): bool
{
    return auth()->user()?->isAdmin() ?? false;
}
```

De "Gebruikers" navigatie-optie is alleen zichtbaar en toegankelijk voor admins.

---

## 13. Rollen en beveiliging

### 13.1 De drie rollen

De rollen zijn gedefinieerd als een PHP 8.1 Enum in `app/Enums/Role.php`:

```php
enum Role: string
{
    case Student = 'student';
    case User = 'user';
    case Admin = 'admin';
}
```

**Waarom een Enum?**
- Type-veilig: PHP geeft een fout als je een verkeerde waarde gebruikt
- IDE-ondersteuning: autocomplete werkt
- Leesbaarheid: `$user->isAdmin()` is duidelijker dan `$user->role === 'admin'`

### 13.2 Wie mag wat?

| Actie | Student | Gebruiker | Admin |
|---|:---:|:---:|:---:|
| Eigen uren bekijken | Ja | Ja | Ja |
| Eigen uren aanmaken | Ja | Ja | Ja |
| Eigen uren bewerken | Ja | Ja | Ja |
| Eigen uren verwijderen | Ja | Ja | Ja |
| Andermans uren bekijken | Nee | Nee | Nee |
| Andermans uren bewerken | Nee | Nee | Nee |
| Gebruikers beheren | Nee | Nee | Ja |
| Excel export | Ja | Ja | Ja |
| Excel synchroniseren | Ja | Ja | Ja |
| Excel werkblad koppelen | Ja | Ja | Ja |
| Thema instellen | Ja | Ja | Ja |
| Accentkleur instellen | Ja | Ja | Ja |

> **Belangrijk:** De admin-rol is bedoeld om nieuwe gebruikers aan te maken en het systeem te beheren. De admin ziet net als iedereen alleen zijn eigen uren.

### 13.3 FilamentUser contract

Het `User` model implementeert het `FilamentUser` contract:

```php
class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Alle ingelogde gebruikers hebben toegang
    }
}
```

Zonder dit contract weigert Filament ELKE gebruiker in productie (`APP_ENV=production`). Lokaal met `APP_ENV=local` lijkt het te werken zonder dit contract, waardoor dit een veelvoorkomende fout is bij deployment.

---

## 14. Frontend en Filament Admin Panel

### 14.1 Wat is Filament?

Filament is een Laravel-pakket dat een volledig admin panel genereert op basis van PHP-klassen. Het gebruikt **Livewire** voor real-time updates zonder paginaverversing, en **Alpine.js** voor client-side interactiviteit.

### 14.2 Frontend stack

| Component | Technologie | Bestand |
|---|---|---|
| CSS framework | Tailwind CSS 4 | `resources/css/app.css` → `public/build/assets/app-*.css` |
| Build tool | Vite 8 | `vite.config.js` |
| Lettertype | Instrument Sans (400, 500, 600) | Via Bunny Fonts in Vite config |
| JavaScript | Alpine.js (via Filament/Livewire) | `public/js/filament/**/*.js` |
| Real-time updates | Livewire (via Filament) | Ingebouwd in Filament |

### 14.3 Admin Panel configuratie

Het admin panel wordt geconfigureerd in `app/Providers/Filament/AdminPanelProvider.php`:

| Instelling | Waarde | Betekenis |
|---|---|---|
| `id` | `'admin'` | Interne identificatie van het panel |
| `path` | `'dashboard'` | Alle URLs beginnen met `/dashboard` |
| `login()` | ingeschakeld | Login pagina staat op `/dashboard/login` |
| `defaultThemeMode` | `ThemeMode::Dark` | Standaard thema is donker |
| `themeSwitcher(false)` | uitgeschakeld | Thema-wissel gebeurt via custom UI |

**Auto-discovery:**
```php
->discoverResources(in: app_path('Filament/Admin/Resources'))
->discoverPages(in: app_path('Filament/Admin/Pages'))
->discoverWidgets(in: app_path('Filament/Admin/Widgets'))
```

Filament scant automatisch deze mappen en maakt routes aan voor elke Resource en Page.

### 14.4 Filament Resources

#### TimeEntryResource

| Pagina | Klasse | URL | Functie |
|---|---|---|---|
| Lijst | `ListTimeEntries` | `/dashboard/time-entries` | Overzicht van alle uren + header acties |
| Aanmaken | `CreateTimeEntry` | `/dashboard/time-entries/create` | Nieuw uur toevoegen |
| Bewerken | `EditTimeEntry` | `/dashboard/time-entries/{id}/edit` | Bestaand uur aanpassen + verwijderen |

**TimeEntryForm** (formulier-velden):
- `user_id` (Select): alleen zichtbaar voor admins
- `date` (DatePicker): verplicht, formaat dd-mm-YYYY
- `start_time` (TimePicker): verplicht, formaat H:i
- `end_time` (TimePicker): verplicht, na of gelijk aan start_time
- `break_minutes` (TextInput): numeriek, 0-1440, default 0
- `description` (Textarea): optioneel, 3 regels

**TimeEntriesTable** (tabel-kolomnen):
- Gebruiker (alleen zichtbaar voor admins)
- Datum, Begintijd, Eindtijd, Pauze, Beschrijving (gelimiteerd op 40 tekens)
- Duur (berekend via `DurationHelper::formatMinutes()`)

**Query filtering:**
```php
// TimeEntryResource.php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $query->where('user_id', auth()->id());
    return $query;
}
```

**Automatische user_id toewijzing:**
```php
// CreateTimeEntry.php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['user_id'] = auth()->id();
    return $data;
}
```

Dit voorkomt dat een gebruiker het `user_id` veld kan manipuleren.

#### UserResource

| Pagina | Klasse | URL | Functie |
|---|---|---|---|
| Lijst | `ListUsers` | `/dashboard/users` | Overzicht van alle gebruikers |
| Aanmaken | `CreateUser` | `/dashboard/users/create` | Nieuwe gebruiker toevoegen |
| Bewerken | `EditUser` | `/dashboard/users/{id}/edit` | Gebruiker aanpassen + verwijderen |

**UserForm** (formulier-velden):
- `name` (TextInput): verplicht
- `email` (TextInput): verplicht, uniek
- `role` (Select): alle Rollen-cases, default `user`
- `password` (TextInput): verplicht bij aanmaken, minimaal 8 tekens
- `password_confirmation` (TextInput): verplicht bij aanmaken

**UsersTable** (tabel-kolomnen):
- Naam, E-mailadres, Rol (badge met kleur), Aantal urenregistraties, Aanmaakdatum
- Filter op rol

### 14.5 Filament Pages

#### Dashboard (`app/Filament/Admin/Pages/Dashboard.php`)

Het dashboard toont:
1. **Voortgangsbalk** — toont voortgang van stage-uren (via `target_hours`)
2. **Weeknavigatie** — vorige / huidige / volgende week knoppen
3. **Weeklabel** — "26 aug – 01 sep 2026 (week 35)"
4. **Totaal deze week** — som van alle uren in de huidige week
5. **Embedded Table** — tabel met alle uren van de geselecteerde week
6. **Export-knoppen** — "Exporteer week" en "Exporteer alles"

**Weeknavigatie werkt met Livewire state:**
```php
public ?string $weekStart = null;

public function mount(): void
{
    $this->weekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
}

public function previousWeek(): void
{
    $this->weekStart = Carbon::parse($this->weekStart)
        ->subWeek()->startOfWeek()->format('Y-m-d');
}
```

Als je op "Vorige week" klikt, verandert `$weekStart` en wordt de pagina live ververst door Livewire.

**De tabel wordt geladen via:**
```php
private function getWeekEntries(): \Illuminate\Support\Collection
{
    $start = Carbon::parse($this->weekStart);
    $end = $start->copy()->endOfWeek();

    return TimeEntry::where('user_id', auth()->id())
        ->whereBetween('date', [$start, $end])
        ->orderBy('date')
        ->orderBy('start_time')
        ->get();
}
```

#### Settings (`app/Filament/Admin/Pages/Settings.php`)

Biedt de volgende velden:
- **Naam** (TextInput): verplicht
- **E-mailadres** (TextInput): verplicht, uniek
- **Nieuw wachtwoord** (TextInput): optioneel, minimaal 8 tekens
- **Accentkleur** (View component): 16 kleuren om uit te kiezen

Na opslaan wordt een `accent-color-changed` event gestuurd, waarna de pagina wordt herladen.

#### EditProfile (`app/Filament/Admin/Pages/EditProfile.php`)

Extends Filament's standaard EditProfile pagina en voegt toe:
- **Totaal te lopen uren** (TextInput): numeriek, 1-9999

Dit wordt gebruikt voor de voortgangsbalk op het dashboard.

### 14.6 Filament Actions

#### WorkbookActions (`app/Filament/Admin/Actions/WorkbookActions.php`)

Deze class bepaalt welke knoppen er in de header van de tijdregistraties-lijst verschijnen:

**Als het werkblad NIET gekoppeld is:**
- "Excel koppelen" knop → opent modal → `WorkbookService::link()`

**Als het werkblad WEL gekoppeld is:**
- "Mijn Excel-werkblad" groep:
  - "Downloaden (.xlsx)" → opent download URL in nieuw tabblad
  - "Werkblad ontkoppelen" → bevestigings-modal → `WorkbookService::unlink()`

#### SyncTimeEntriesAction (`app/Filament/Admin/Actions/SyncTimeEntriesAction.php`)

Toont een modal met:
- **FileUpload**: accepteert `.xlsx`, `.xls`, `.csv` (max 8MB)
- **Select (user_id)**: alleen zichtbaar voor admins, kies voor wie je sync't
- **Toggle (delete_missing)**: verwijder registraties die niet in het bestand staan

Na upload: `TimeEntrySyncService::syncFromFile()` → toont resultaat-rapport.

### 14.7 Custom Blade views

| Bestand | Doel |
|---|---|
| `resources/views/filament/widgets/progress-bar.blade.php` | Voortgangsbalk op dashboard |
| `resources/views/filament/theme-sync.blade.php` | Synchroniseert thema naar localStorage |
| `resources/views/filament/theme-switcher.blade.php` | Thema-wisselknop (donker/licht/systeem) |
| `resources/views/filament/components/accent-color-picker.blade.php` | Kleurenkiezer (16 kleuren) |
| `resources/views/filament/pages/settings.blade.php` | Wrapper voor Settings pagina |
| `resources/views/welcome.blade.php` | Redirect naar dashboard |

---

## 15. Backend — Models, Services en Business Logic

### 15.1 Models

#### User (`app/Models/User.php`)

**Wat het is:** Het centrale model voor gebruikers. Het Representeert een account in het systeem.

**Waarom het bestaat:** Het slaat gebruikersgegevens op en biedt methods om te controleren of een gebruiker admin is, hoeveel uren hij heeft gelogd, en welke kleur/thema hij gebruikt.

**Belangrijke eigenschappen:**

| Eigenschap | Type | Doel |
|---|---|---|
| `THEME_MODES` | const array | `['dark', 'light', 'system']` |
| `ACCENT_COLORS` | const array | 16 kleuren met Filament Color waarden |
| `$fillable` | array | Via PHP 8 attribute: alle velden die massaal mogen worden bijgewerkt |
| `$hidden` | array | Via PHP 8 attribute: `password` en `remember_token` |

**Belangrijke methods:**

| Method | Doel | Geeft terug |
|---|---|---|
| `isAdmin()` | Controleert of de gebruiker admin is | `bool` |
| `hasLinkedWorkbook()` | Controleert of een Excel-werkblad is gekoppeld | `bool` |
| `primaryColor()` | Haalt de Filament kleur op op basis van `accent_color` | `array` |
| `exportColors()` | Haalt HEX-kleuren op voor Excel-headers | `array` met `bg` en `font` |
| `timeEntries()` | Relatie: alle tijdregistraties van deze gebruiker | `HasMany` |
| `totalLoggedMinutes()` | Totaal aantal gelogde minuten | `int` |
| `totalLoggedHoursFormatted()` | Totaal als "HH:MM" string | `string` |
| `canAccessPanel()` | FilamentUser contract: altijd `true` | `bool` |

**Casts:**
```php
protected function casts(): array
{
    return [
        'role' => Role::class,              // String → Enum
        'email_verified_at' => 'datetime',  // String → Carbon DateTime
        'password' => 'hashed',             // Automatisch hashen bij opslaan
        'workbook_linked_at' => 'datetime', // String → Carbon DateTime
    ];
}
```

#### TimeEntry (`app/Models/TimeEntry.php`)

**Wat het is:** Een enkele uur-registratie.

**Waarom het bestaat:** Het slaat de tijdgegevens van een werksessie op en berekent automatisch de duur.

**Belangrijke eigenschappen:**

| Eigenschap | Doel |
|---|---|
| `$fillable` | `user_id`, `date`, `start_time`, `end_time`, `break_minutes`, `description` |
| `$casts` | `date` → date, `start_time`/`end_time` → datetime:H:i, `break_minutes` → integer |

**De `duration` attribute (het belangrijkste stukje):**

```php
protected function duration(): Attribute
{
    return Attribute::get(function () {
        // Bereken het aantal minuten tussen start en eind
        $minutes = (int) round($this->start_time->diffInMinutes($this->end_time));

        // Als de uren over middernacht gaan (bijv. 22:00 → 06:00)
        if ($minutes < 0) {
            $minutes += 1440;  // 1440 minuten = 24 uur
        }

        // Trek de pauze eraf
        return max(0, $minutes - $this->break_minutes);
    });
}
```

**Waarom 1440?** Omdat er 24 × 60 = 1440 minuten in een dag zitten. Als je om 22:00 begint en om 06:00 eindigt, geeft `diffInMinutes` een negatief getal (-960). Door 1440 op te tellen krijg je 480 minuten (8 uur).

**Validatie in de boot method:**
```php
public static function boot(): void
{
    parent::boot();

    static::saving(function (TimeEntry $entry): void {
        if ($entry->start_time && $entry->end_time) {
            if ($entry->end_time->lt($entry->start_time)) {
                throw ValidationException::withMessages([
                    'end_time' => 'De eindtijd kan niet voor de begintijd liggen.',
                ]);
            }
        }
    });
}
```

> **Let op:** Deze validatie voorkomt dat eindtijd voor begintijd ligt, MAAR de `duration` attribute ondersteunt WEL uren over middernacht. Dit lijkt contradictoir maar is bewust: de validatie voorkomt onbedoelde invoer, terwijl de duration attribute flexibel is voor bewuste invoer (bijv. nachtwerk).

### 15.2 Helpers

#### DurationHelper (`app/Helpers/DurationHelper.php`)

**Wat het is:** Een simpele helper class voor tijdformattering.

**Waarom het bestaat:** Het voorkomt dat `sprintf('%02d:%02d', ...)` overal in de code wordt herhaald.

**Methods:**

| Method | Input | Output | Voorbeeld |
|---|---|---|---|
| `formatMinutes(int)` | minuten | "HH:MM" | `450` → `"07:30"` |
| `formatSeconds(int)` | seconden | "HH:MM" | `27000` → `"07:30"` |

**Waar wordt het gebruikt:**
- `Dashboard.php`: totalen per dag en per week
- `User.php`: `totalLoggedHoursFormatted()`
- `ExportService.php`: "Duur" kolom in exports
- `TimeEntryExporter.php`: export kolommen
- `WorkbookService.php`: duur in het werkblad

### 15.3 Services

#### ExportService (`app/Services/ExportService.php`)

**Wat het is:** Verantwoordelijk voor het exporteren van uren naar Excel (.xlsx) of CSV.

**Waarom het bestaat:** Het scheidt de export-logica van de controllers/pages, zodat deze op meerdere plekken kan worden hergebruikt (Dashboard + ListTimeEntries).

**Methods:**

| Method | Doel |
|---|---|
| `getEntriesForWeek(User, string)` | Haalt uren op voor een specifieke week |
| `getAllEntries(User)` | Haalt alle uren op van een gebruiker |
| `exportToCsv(Collection, string)` | Exporteert naar CSV (streamed) |
| `exportToXlsx(Collection, string, ?array)` | Exporteert naar XLSX (streamed, met gekleurde headers) |

**Geëxporteerde kolommen:**
| Kolom | Bron | Formaat |
|---|---|---|
| Weeknummer | `$entry->date->isoWeek()` | integer |
| Datum | `$entry->date` | `d-m-Y` |
| Begintijd | `$entry->start_time` | `H:i` |
| Eindtijd | `$entry->end_time` | `H:i` |
| Pauze (min) | `$entry->break_minutes` | integer |
| Duur | `$entry->duration` | `HH:MM` (via DurationHelper) |
| Beschrijving | `$entry->description` | text |

**Waarom streamed?** Omdat bestanden groot kunnen worden. Met streaming wordt het bestand direct naar de browser gestuurd, zonder dat alles in het geheugen wordt geladen.

**Excel-opmaak:** De headers worden gestyled met de accentkleur van de gebruiker (achtergrond + witte tekst + ondergrens).

#### TimeEntrySyncService (`app/Services/TimeEntrySyncService.php`)

**Wat het is:** Het meest complexe onderdeel. Importeert uren uit Excel of CSV.

**Waarom het bestaat:** Het maakt het mogelijk om een bestaand Excel-bestand te uploaden en de registraties daarmee te synchroniseren, in plaats van alles handmatig in te voeren.

**Flow:**
```
Gebruiker uploadt bestand
    ↓
SyncTimeEntriesAction ontvangt het bestand
    ↓
TimeEntrySyncService::syncFromFile() wordt aangeroepen
    ↓
1. readRows() — leest het bestand (xlsx/csv) via OpenSpout
2. detectColumnMap() — vindt kolommen op basis van kopregel-aliassen
3. Voor elke data-rij:
   a. extractAttributes() — parsed datum, tijden, pauze, beschrijving
   b. validateAttributes() — controleert of waarden leesbaar zijn
   c. Match op datum + begintijd → bijwerken of aanmaken
4. Optioneel: deleteMissing — verwijder entries die niet in het bestand staan
5. Werk het persoonlijke werkblad bij (na de transactie)
6. Geef SyncResult terug
```

**Kolom-detectie (HEADER_ALIASES):**

De service herkent zowel Nederlandse als Engelse kolomnamen:

| Veld | Herkende namen |
|---|---|
| `date` | datum, date, dag, werkdag |
| `start_time` | begintijd, begin, starttijd, start, van, vanaf |
| `end_time` | eindtijd, eind, einde, end, tot, totmet, tm |
| `break_minutes` | pauze, pauzeminuten, pauzemin, break, breakminutes, pauzeduur |
| `description` | beschrijving, omschrijving, description, werkzaamheden, activiteit, notities, opmerking, opmerkingen |

Kopregels worden genormaliseerd: kleine letters, spaties/streepjes/punten verwijderd, accenten omgezet.

**Date-formats die worden herkend:**
- `Y-m-d` (2026-08-26)
- `d-m-Y` (26-08-2026)
- `d/m/Y` (26/08/2026)
- `d-m-y` (26-08-26)
- `d/m/y` (26/08/26)
- Fallback: `Carbon::parse()` (herkent veel formaten)

**Tijd-formats die worden herkend:**
- `H:i` (09:00)
- `H:i:s` (09:00:00)
- `H` (9) → wordt 09:00
- Float (0.5) → wordt 12:00 (Excel tijd-formaat)
- Punt of komma als scheidingsteken (09.00, 9,5)

**Transactie:** De hele sync wordt uitgevoerd in één database-transactie. Als er iets misgaat, wordt alles teruggedraaid.

**Bulk-optimalisatie:** Tijdens de sync wordt `WorkbookService::withoutAutoRefresh()` gebruikt om te voorkomen dat het Excel-werkblad bij elke individuele wijziging wordt ververst. Na de sync wordt het werkblad één keer ververst.

#### SyncResult (`app/Services/SyncResult.php`)

**Wat het is:** Een simpel data-object dat het resultaat van een sync bijhoudt.

| Eigenschap | Type | Doel |
|---|---|---|
| `$created` | int | Aantal nieuw aangemaakte registraties |
| `$updated` | int | Aantal bijgewerkte registraties |
| `$deleted` | int | Aantal verwijderde registraties |
| `$skipped` | int | Aantal overgeslagen rijen (fouten) |
| `$errors` | array<string> | Foutmeldingen per rij |

#### WorkbookService (`app/Services/WorkbookService.php`)

**Wat het is:** Beheert het persoonlijke Excel-werkblad per gebruiker.

**Waarom het bestaat:** Elke gebruiker heeft een eigen Excel-bestand dat automatisch wordt bijgehouden. Dit maakt het eenvoudig om de huidige stand van zaken te downloaden without dat je telkens moet exporteren.

**Opslag:** `storage/app/private/workbooks/{user_id}/stage-uren.xlsx`

> **Belangrijk:** Op Railway (productie) is de opslag **vluchtig**. Het bestand verdwijnt bij herstart, maar wordt automatisch opnieuw gegenereerd bij elke mutatie of download.

**Methods:**

| Method | Doel |
|---|---|
| `link(User)` | Koppelt een werkblad: zet `workbook_linked_at` + genereer het bestand |
| `unlink(User)` | Ontkoppelt: zet `workbook_linked_at` = null + verwijder het bestand |
| `refresh(User)` | Ververs het werkblad (alleen als gekoppeld) |
| `refreshQuietly(?User)` | Ververs stil (negeert als `$autoRefreshDisabled` = true) |
| `withoutAutoRefresh(callable)` | Schakelt auto-refresh tijdelijk uit (voor bulk-operaties) |
| `generate(User)` | Genereert het volledige Excel-bestand |
| `exists(User)` | Controleert of het bestand bestaat |
| `absolutePath(User)` | Geeft het volledige pad terug |
| `downloadName(User)` | Geeft de bestandsnaam terug: `stage-uren-{slug}.xlsx` |

**Automatische refresh (model events):**

In `AppServiceProvider` worden model events geregistreerd:

```php
TimeEntry::saved(fn (TimeEntry $entry) => $workbooks->refreshQuietly($entry->user));
TimeEntry::deleted(fn (TimeEntry $entry) => $workbooks->refreshQuietly($entry->user));
```

Zodra een `TimeEntry` wordt opgeslagen of verwijderd, wordt het werkblad automatisch ververst.

**Werkblad-inhoud:**

| Kolom | Inhoud |
|---|---|
| Datum | `d-m-Y` |
| Begintijd | `H:i` |
| Eindtijd | `H:i` |
| Pauze (min) | integer |
| Duur | `HH:MM` (via DurationHelper) |
| Beschrijving | tekst |

De laatste rij is een **totaalregel** met het totaal aantal uren.

---

## 16. Belangrijkste dataflows

### 16.1 Een uur toevoegen

```
Startpunt: Gebruiker klikt "+ Tijdregistratie" op het dashboard

1. Browser → GET /dashboard/time-entries/create
2. Filament → CreateTimeEntry pagina
3. TimeEntryForm toont het formulier:
   - datum, begintijd, eindtijd, pauze, beschrijving
4. Gebruiker vult in en klikt "Opslaan"
5. Livewire → POST verzoek naar de server
6. CreateTimeEntry::mutateFormDataBeforeCreate():
   → $data['user_id'] = auth()->id()
7. Laravel validatie (TimeEntry boot):
   → eindtijd kan niet voor begintijd liggen
8. TimeEntry::create($data) → opslaan in database
9. Model event 'saved' wordt afgevuurd
10. AppServiceProvider → TimeEntry::saved listener
11. WorkbookService::refreshQuietly($entry->user):
    → Als gebruiker gekoppeld werkblad heeft → regenerate XLSX
12. Livewire response → browser wordt live ververst
13. Gebruiker ziet het nieuwe uur in de lijst
```

### 16.2 Een uur bewerken

```
Startpunt: Gebruiker klikt op een uur in de lijst en klikt "Bewerken"

1. Browser → GET /dashboard/time-entries/{id}/edit
2. Filament → EditTimeEntry pagina
3. TimeEntryForm wordt gevuld met bestaande gegevens
4. Gebruiker past aan en klikt "Opslaan"
5. Livewire → PUT verzoek
6. EditTimeEntry::mutateFormDataBeforeSave():
   → $data['user_id'] = auth()->id()
7. TimeEntry::update($data)
8. Model event 'saved' → WorkbookService::refreshQuietly()
9. Response → browser ververst
```

### 16.3 Dashboard laden

```
Startpunt: Gebruiker opent /dashboard

1. Browser → GET /dashboard
2. Filament → Dashboard pagina
3. Dashboard::mount():
   → $this->weekStart = Carbon::now()->startOfWeek()
4. Dashboard content:
   a. progress-bar view:
      → $user->totalLoggedMinutes() / $user->target_hours
      → Bereken percentage en resterende uren
   b. Button row: vorige/huidige/volgende week + aanmaak-knop
   c. Weeklabel: "26 aug – 01 sep 2026 (week 35)"
   d. Totaal: "Totaal deze week: 32:30"
   e. Embedded table:
      → TimeEntry::where('user_id', auth()->id())
        ->whereBetween('date', [$start, $end])
        ->get()
   f. Export-knoppen
5. Blade rendert HTML + Tailwind CSS
6. Response naar browser
```

### 16.4 Excel synchroniseren

```
Startpunt: Gebruiker klikt "Excel synchroniseren" op de tijdregistraties-lijst

1. SyncTimeEntriesAction → toont modal
2. Gebruiker uploadt .xlsx bestand + kiest opties
3. Livewire → POST verzoek (inclusief bestand)
4. SyncTimeEntriesAction::action():
   → Haalt bestand op via Storage::disk('local')
   → Bepaalt voor wie de sync is (eigen ID of gekozen user)
5. TimeEntrySyncService::syncFromFile($user, $path, $deleteMissing)
6. readRows($path) → OpenSpout leest het bestand
7. detectColumnMap($rows) → vindt kolomposities
8. DB::transaction():
   → Haalt bestaande entries op (keyed op "datum|begintijd")
   → Voor elke data-rij:
     a. extractAttributes() → parsed waarden
     b. validateAttributes() → controleert fouten
     c. Match key = "datum|begintijd"
     d. Bestaand? → update. Nieuw? → create.
     e. Optioneel: deleteMissing → delete WHERE NOT IN touched IDs
9. WorkbookService::refresh($user) → ververs werkblad
10. Bestand verwijderen uit sync-uploads map
11. SyncResult → toont rapport in notification
```

### 16.5 Thema wijzigen

```
Startpunt: Gebruiker klikt op het maan/zon/monitor-icoon in de header

1. Alpine.js toggle() wordt aangeroepen
2. Thema wordt berekend (dark → light → system → dark)
3. CSS class 'dark' wordt toegevoegd/verwijderd op <html>
4. localStorage.setItem('theme', newTheme)
5. Fetch POST naar /theme:
   → Route::post('/theme', ...)
   → Valideert: theme moet dark/light/system zijn
   → $request->user()->update(['theme_mode' => $theme])
6. Response: { ok: true, theme: 'light' }
7. De theme-sync blade view:
   → localStorage.setItem('theme', ...)
   → Zorgt ervoor dat het thema wordt toegepast bij paginaverversing
```

---

## 17. Excel Export

Er zijn twee manieren om uren te exporteren:

### 17.1 Via het Dashboard (ExportService)

**Knoppen:**
- "Exporteer week" → `Dashboard::exportWeek()`
- "Exporteer alles" → `Dashboard::exportAll()`

**Flow:**
```
Gebruiker klikt "Exporteer week"
    ↓
Dashboard::exportWeek()
    ↓
ExportService::getEntriesForWeek($user, $weekStart)
    ↓
ExportService::exportToXlsx($entries, $filename, $colors)
    ↓
OpenSpout Writer opent in browser mode
    ↓
Header rij (met accentkleur) + data rijen
    ↓
StreamedResponse → bestand wordt gedownload
```

### 17.2 Via de Tijdregistraties-lijst (Filament Exporter)

**Knop:** "Exporteren" in de header

**Flow:**
```
Gebruiker klikt "Exporteren"
    ↓
Filament ExportAction met TimeEntryExporter
    ↓
TimeEntryExporter::getColumns() → definieert kolommen
    ↓
Filament genereert XLSX
    ↓
Notificatie: "Export is klaar, X rijen geëxporteerd"
    ↓
Download automatisch gestart
```

### 17.3 Vergelijking

| Feature | Dashboard Export | Filament Export |
|---|---|---|
| Bestandsnaam | `uren_week_YYYY-MM-DD.xlsx` | `time_entries_export.xlsx` |
| Kolom-selectie | Alles | Alles |
| Styling | Accentkleur headers | Standaard |
| Scope | Week of alles | Alle zichtbare entries |
| Methode | OpenSpout direct | Filament's ingebouwde systeem |

---

## 18. Excel Synchronisatie (Import)

Zie [sectie 15.3 — TimeEntrySyncService](#153-services) voor de volledige technische uitleg.

### Samenvatting van het proces

1. **Upload** een `.xlsx` of `.csv` bestand (max 8MB)
2. **Kolom-detectie**: de service herkent automatisch de kopregel (NL/EN)
3. **Verwerking per rij**:
   - Match op **datum + begintijd**
   - Bestaand → bijwerken
   - Nieuw → aanmaken
   - Fout → overslaan + rapporteren
4. **Optioneel**: verwijder registraties die niet in het bestand staan
5. **Resultaat-rapport**: aangemaakt / bijgewerkt / verwijderd / overgeslagen / fouten

### Voorbeeld van een geldig Excel-bestand

| Datum | Begintijd | Eindtijd | Pauze (minuten) | Beschrijving |
|---|---|---|---|---|
| 26-08-2026 | 09:00 | 17:00 | 30 | Aan de app gewerkt |
| 27-08-2026 | 08:30 | 16:30 | 45 | Meetings |

Dit werkt ook met Engelse kopregels:

| Date | Start | End | Break | Description |
|---|---|---|---|---|
| 2026-08-28 | 10:15 | 18:45 | 45 | English works too |

---

## 19. Persoonlijk Excel-werkblad

### Wat is het?

Een Excel-bestand per gebruiker dat automatisch wordt bijgehouden. Het bevat alle uren van de gebruiker plus een totaalregel.

### Koppelen

1. Gebruiker klikt "Excel koppelen" op de tijdregistraties-lijst
2. Modal verschijnt met uitleg
3. Gebruiker klikt "Koppelen en genereren"
4. `WorkbookService::link()` wordt aangeroepen
5. `workbook_linked_at` wordt gezet op `now()`
6. Het Excel-bestand wordt gegenereerd
7. Notificatie: "Excel-werkblad gekoppeld"

### Automatische bijwerking

Bij elke wijziging in een `TimeEntry` (aanmaken, bijwerken, verwijderen) wordt het werkblad automatisch ververst via model events in `AppServiceProvider`.

### Downloaden

1. Gebruiker klikt op "Mijn Excel-werkblad" → "Downloaden (.xlsx)"
2. Browser opent `GET /workbook/download`
3. `WorkbookController::download()` wordt aangeroepen
4. `WorkbookService::refresh($user)` → ververs het bestand
5. `response()->download()` → bestand wordt gedownload

### Ontkoppelen

1. Gebruiker klikt "Werkblad ontkoppelen"
2. Bevestigings-modal
3. `WorkbookService::unlink()` → verwijdert het bestand en zet `workbook_linked_at` = null
4. Notificatie: "Excel-werkblad ontkoppeld"

---

## 20. Routes en Filament Auto-Discovery

### 20.1 Handmatige routes (`routes/web.php`)

| Methode | URL | Handler | Middleware | Functie |
|---|---|---|---|---|
| GET | `/` | Closure | geen | Redirect naar dashboard |
| GET | `/workbook/download` | `WorkbookController::download` | auth | Download Excel werkblad |
| POST | `/theme` | Closure | auth | Wijzig thema van gebruiker |

### 20.2 Filament auto-discovery routes

Filament genereert automatisch routes voor alle Resources en Pages in de `Filament/Admin/` mappen:

| Resource/Page | Automatische URL |
|---|---|
| `Dashboard` | `/dashboard` |
| `Settings` | `/dashboard/settings` |
| `EditProfile` | `/dashboard/edit-profile` |
| `TimeEntryResource` (lijst) | `/dashboard/time-entries` |
| `TimeEntryResource` (aanmaken) | `/dashboard/time-entries/create` |
| `TimeEntryResource` (bewerken) | `/dashboard/time-entries/{id}/edit` |
| `UserResource` (lijst) | `/dashboard/users` |
| `UserResource` (aanmaken) | `/dashboard/users/create` |
| `UserResource` (bewerken) | `/dashboard/users/{id}/edit` |

### 20.3 Hoe Filament de URL bepaalt

Filament gebruikt de class name om de URL te bepalen:

```
TimeEntryResource → verwijder "Resource" → TimeEntry → kebab-case → time-entries
Dashboard         → Dashboard → dashboard
Settings          → Settings → settings
```

### 20.4 Bekijken van alle routes

```bash
php artisan route:list
```

---

## 21. Error handling en logging

### 21.1 Foutafhandeling in bootstrap/app.php

**404 Not Found:**
```php
$exceptions->render(function (NotFoundHttpException $e, Request $request) {
    if ($request->isMethod('GET') && !$request->expectsJson() && !$request->is('livewire/*')) {
        return redirect()->to(auth()->check() ? '/dashboard' : '/dashboard/login');
    }
    return null;
});
```

Dit zorgt ervoor dat:
- Niet-ingelogde gebruikers naar de login worden doorgestuurd bij een 404
- Ingelogde gebruikers naar het dashboard worden doorgestuurd bij een 404
- API-verzoeken en Livewire-verzoeken een standaard 404 krijgen

**Authentication Exception:**
```php
$exceptions->render(function (AuthenticationException $e, Request $request) {
    if ($request->expectsJson()) {
        return null;
    }
    return redirect()->guest('/dashboard/login');
});
```

### 21.2 Logging

De logging is geconfigureerd in `config/logging.php`:

| Kanaal | Doel |
|---|---|
| `stack` | Standaard kanaal (bevat `single`) |
| `single` | Alles naar `storage/logs/laravel.log` |
| `daily` | Max 14 bestanden, dagelijks roterend |
| `stderr` | Voor container-omgevingen |

**Log-level:** `debug` (lokaal) — alle berichten worden gelogd.

### 21.3 Debugging hulpmiddelen

```bash
# Bekijk live logs
php artisan pail

# Bekijk de laatste logregels
cat storage/logs/laravel.log | tail -50

# Zoek fouten in de logs
grep "ERROR" storage/logs/laravel.log

# Tinker: test queries direct
php artisan tinker
App\Models\User::all();
App\Models\TimeEntry::where('user_id', 1)->get();
```

### 21.4 Fouten in services

De services gooien specifieke uitzonderingen:

| Service | Fout | Wanneer |
|---|---|---|
| `TimeEntrySyncService` | `RuntimeException` | Ongeldig bestandstype |
| `TimeEntrySyncService` | `RuntimeException` | Geen geldige kolommen gevonden |
| `TimeEntry` model | `ValidationException` | Eindtijd voor begintijd |
| `WorkbookController` | `404` (abort) | Geen gekoppeld werkblad |

---

## 22. Testen

### 22.1 Overzicht

Het project gebruikt **Pest PHP 5** voor testing. Tests draaien op een **SQLite :memory: database** (geconfigureerd in `phpunit.xml`).

### 22.2 Test uitvoeren

```bash
# Alle tests
composer test
# of
php artisan test

# Specifieke test suite
php artisan test --filter=AuthAndAccessTest
php artisan test --filter=TimeEntrySyncTest
php artisan test --filter=WorkbookTest

# Met verbose output
php artisan test -v

# Code style checken
vendor/bin/pint
```

### 22.3 Test suites

#### AuthAndAccessTest (10 tests)
| Test | Wat het controleert |
|---|---|
| `stuurt gasten van de homepage door naar de login` | `/` → redirect naar login |
| `stuurt ingelogde gebruikers door naar het dashboard` | `/` → redirect naar dashboard |
| `stuurt gasten met een 404 door naar de login` | Onbekende URL → login |
| `stuurt ingelogde gebruikers met een 404 door naar het dashboard` | Onbekende URL → dashboard |
| `stuurt uitgelogde gebruikers door` | `/admin/time-entries` → redirect |
| `laat een gewone gebruiker alleen zijn eigen uren zien` | Query filtering werkt |
| `blokkeert de gebruikersbeheer-pagina voor niet-admins` | `/admin/users` → 403 |
| `staat de gebruikersbeheer-pagina toe voor admins` | `/admin/users` → 200 |
| `blokkeert admins voor andermans uren` | Policy check |
| `voorkomt dat een admin zichzelf verwijdert` | UserPolicy::delete |

#### TimeEntrySyncTest (9 tests)
| Test | Wat het controleert |
|---|---|
| `maakt nieuwe registraties aan met NL kopregels` | Basis import werkt |
| `werkt ook met Engelse kopregels` | EN headers worden herkend |
| `werkt bestaande registraties bij` | Update op datum+begintijd |
| `verwijdert registraties met deleteMissing` | Optionele verwijdering |
| `behoudt registraties zonder verwijderoptie` | Standaard gedrag |
| `slaat ongeldige rijen over` | Foutafhandeling |
| `gooit fout bij ontbrekende kolommen` | Validatie |
| `berekent duur correct over middernacht` | Duration attribute |

#### WorkbookTest (6 tests)
| Test | Wat het controleert |
|---|---|
| `genereert werkblad bij koppelen` | Link → XLSX met inhoud |
| `werkt werkblad automatisch bij` | Nieuw uur → update XLSX |
| `stopt met bijwerken na ontkoppelen` | Unlink → geen refresh |
| `laat gekoppelde gebruikers downloaden` | Download endpoint werkt |
| `stuurt niet-gekoppelde gebruikers door` | Geen 404 |
| `ververst werkblad na Excel-sync` | Sync → update XLSX |

### 22.4 Nieuwe test schrijven

```php
// tests/Feature/MijnTest.php
use App\Models\User;
use App\Models\TimeEntry;
use function Pest\Laravel\actingAs;

it('doet iets goeds', function () {
    $user = User::factory()->create();
    $entry = TimeEntry::factory()->for($user)->create();

    actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee($entry->description);
});
```

### 22.5 Test configuratie

In `tests/Pest.php`:
```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
```

Elke Feature-test krijgt automatisch een verse database (`RefreshDatabase` trait).

---

## 23. Build en deployment

### 23.1 Railway deployment

De applicatie draait op Railway met twee services:

| Service | Technologie | Kosten |
|---|---|---|
| **App** (PHP 8.4 via Nixpacks) | Laravel | Binnen $5-trial, daarna $1/maand |
| **PostgreSQL** | Beheerde database | Idem |

**Deploy-trigger:** elke push naar `main`.

### 23.2 Build-proces (nixpacks.toml)

```toml
[variables]
NIXPACKS_PHP_VERSION = '8.4'
NIXPACKS_NODE_VERSION = '22'

[phases.setup]
nixPkgs = ['...', 'php84Extensions.intl', 'php84Extensions.zip', 'php84Extensions.pdo_pgsql']

[phases.install]
cmds = [
    'composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts',
    'npm ci --ignore-scripts'
]

[phases.build]
cmds = ['php artisan package:discover --ansi && npm run build']
```

### 23.3 Start-commando (railway.json)

```json
{
    "deploy": {
        "startCommand": "php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && until php artisan migrate --force; do echo 'Waiting for database...'; sleep 5; done && PHP_CLI_SERVER_WORKERS=4 php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"
    }
}
```

**Volgorde:**
1. Config/route/view cache legen
2. Config/route/view cache opbouwen
3. Wacht tot de database online is (wachtlus)
4. Migraties uitvoeren
5. Start de PHP built-in server

### 23.4 Waarom PostgreSQL en niet MySQL?

Railway gebruikt MySQL 8 met `caching_sha2_password` authenticatie. De PHP-build op Railway gebruikt `libmariadb-client`, die dit authenticatieprotocol niet begrijpt. PostgreSQL heeft dit probleem niet en Laravel abstraheert het verschil volledig weg.

### 23.5 Environment Variables voor Railway

| Variabele | Waarde |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Genereer lokaal: `php artisan key:generate --show` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | `${{Postgres.DATABASE_URL}}` |
| `SESSION_DRIVER` | `redis` (of `database` zonder Redis-service) |
| `QUEUE_CONNECTION` | `sync` |
| `CACHE_STORE` | `redis` (of `database` zonder Redis-service) |
| `SEED_ADMIN_PASSWORD` | Sterk wachtwoord voor de admin (`admin@admin.com`) |

### 23.6 Admin-gebruiker (alleen eerste keer)

De pre-deploy-stap (`railway/pre-deploy.sh`) draait **altijd** `php artisan migrate --force`. De seeder
draait alleen als de variable `RUN_SEED=true` staat (zet hem in bij de allereerste deploy en haal hem
daarna weg). Via de `UsersSeeder` worden dan de standaardaccounts `admin@admin.com` en
`testaccount01@example.com` aangemaakt (idempotent `updateOrCreate` — verwijdert nooit bestaande data).
Wachtwoorden komen uit `SEED_ADMIN_PASSWORD` / `SEED_USER_PASSWORD`.

### 23.7 Optionele builds

```bash
# Local development
composer dev

# Production build
npm run build

# Code style check
vendor/bin/pint
```

---

## 24. Veelvoorkomende problemen

### 24.1 Inloggen lukt, maar daarna 403 Forbidden

**Oorzaak:** Het `User` model implementeert het `FilamentUser` contract niet correct. In productie (`APP_ENV=production`) weigert Filament dan elke gebruiker.

**Oplossing:** Controleer dat `User implements FilamentUser` en `canAccessPanel()` retourneert `true`.

**Locatie:** `app/Models/User.php:23-26`

### 24.2 500 Internal Server Error op tijdregistraties

**Oorzaak:** In eerdere versies werden MySQL-specifieke functies (`DATE_FORMAT`, `YEARWEEK`) gebruikt die niet werken op PostgreSQL.

**Oplossing:** Maanden/weken worden nu in PHP berekend (Carbon) en gefilterd via `whereBetween`.

**Locatie:** `app/Filament/Admin/Pages/Dashboard.php`

### 24.3 Application failed to respond

**Oorzaak:** De app draait niet of Railway routeert naar de verkeerde poort.

**Oplossing:**
1. Check deployment-logs — draait `artisan serve`?
2. Settings → Networking → target port moet gelijk zijn aan de luisterpoort
3. Check of de container in een crash-loop zit

### 24.4 SQLSTATE authentication method unknown

**Oorzaak:** Er staat nog een MySQL-service verbonden terwijl je PostgreSQL moet gebruiken.

**Oplossing:** Verwijder de MySQL-service, maak een PostgreSQL-service aan, en wijs `DB_URL` correct toe.

### 24.5 Connection refused bij starten

**Oorzaak:** Race tussen app- en DB-start (database nog niet online).

**Oplossing:** Het start-commando in `nixpacks.toml` heeft een wachtlus. Check of de nieuwste deployment de lus bevat.

### 24.6 could not find driver / pg_connect

**Oorzaak:** De `pdo_pgsql`-extensie ontbreekt in de build.

**Oplossing:** Check of `nixpacks.toml` `php84Extensions.pdo_pgsql` bevat. Redeploy.

### 24.7 Class "Filament\PanelProvider" not found

**Oorzaak:** Build gebruikte geen `nixpacks.toml`.

**Oplossing:** Check of het bestand gepusht is en trigger een redeploy.

### 24.8 Witte pagina / CSS mist

**Oorzaak:** Vite-build mislukt.

**Oplossing:** Check deployment-logs voor Vite-fouten.

### 24.9 404 op alles

**Oorzaak:** `APP_URL` niet gezet na Generate Domain.

**Oplossing:** Voeg `APP_URL` toe in Railway Variables.

### 24.10 Fouten op een rij

| Symptoom | Waarschijnlijke oorzaak | Oplossing |
|---|---|---|
| `Target class does not exist` | Route verwijst naar niet-bestaande class | Controleer `routes/web.php` |
| `Column not found` | Database kolom bestaat niet | Voer `php artisan migrate` uit |
| `403 Forbidden` | Geen toegang | Controleer Policy |
| `404 Not Found` | Route bestaat niet | `php artisan route:list` |
| `SQLSTATE connection refused` | Database niet bereikbaar | Controleer `.env` |
| `View not found` | Blade view bestaat niet | Controleer `resources/views/` |
| `Class not found` | Autoloader niet ververst | `composer dump-autoload` |

### 24.11 Waar vind ik wat?

| Probleem | Bestand om te bekijken |
|---|---|
| Gebruiker kan niet inloggen | `app/Providers/Filament/AdminPanelProvider.php` |
| Gebruiker ziet andermans uren | `app/Filament/Admin/Resources/TimeEntries/TimeEntryResource.php` (getEloquentQuery) |
| Excel import werkt niet | `app/Services/TimeEntrySyncService.php` |
| Excel download werkt niet | `routes/web.php` + `app/Http/Controllers/WorkbookController.php` |
| Knop doet niks | Browser console (F12) |
| Fout bij deployment | Railway deployment-logs |

---

## 25. Onderhoud en uitbreiding

### 25.1 Nieuwe functionaliteit toevoegen

**Stappen:**
1. Bepaal welke laag moet worden aangepast (Model, Service, Resource, Page)
2. Voeg toe aan de bestaande structuur
3. Voeg een test toe in `tests/Feature/`
4. Voer `vendor/bin/pint` uit voor code style

### 25.2 Nieuwe Filament Resource toevoegen

```php
// 1. Maak de Resource aan
// app/Filament/Admin/Resources/MijnResource/MijnResource.php
class MijnResource extends Resource
{
    protected static ?string $model = MijnModel::class;
    // ...
}

// 2. Maak de Pages aan
// app/Filament/Admin/Resources/MijnResource/Pages/
// - ListMijnRecords.php
// - CreateMijnRecord.php
// - EditMijnRecord.php

// 3. Maak de Form/Table aan
// app/Filament/Admin/Resources/MijnResource/Schemas/MijnForm.php
// app/Filament/Admin/Resources/MijnResource/Tables/MijnTable.php
```

Filament vindt de Resource automatisch via auto-discovery.

### 25.3 Nieuwe database-functionaliteit

1. Maak een migratie: `php artisan make:migration add_x_to_y_table`
2. Pas het Model aan (fillable, casts, relations)
3. Voeg een Policy toe als nodig
4. Werk de Resource/Form/Table bij

### 25.4 Nieuwe API toevoegen

Het project heeft momenteel geen aparte API-laan. Alles loopt via Filament/Livewire. Als je een API wilt toevoegen:

1. Maak een controller aan in `app/Http/Controllers/`
2. Voeg routes toe aan `routes/web.php` (of maak `routes/api.php` aan)
3. Voeg middleware toe indien nodig

### 25.5 Gevoelige onderdelen

| Onderdeel | Waarom gevoelig |
|---|---|
| `TimeEntryResource::getEloquentQuery()` | Als je de WHERE-clause verwijdert, zien gebruikers andermans uren |
| `CreateTimeEntry::mutateFormDataBeforeCreate()` | Als je `$data['user_id'] = auth()->id()` verwijdert, kan een gebruiker uren aanmaken voor een ander |
| `UserPolicy::delete()` | Als je `$user->id !== $model->id` verwijdert, kan een admin zichzelf verwijderen |
| `AdminPanelProvider` | Het Filament panel pad en configuratie |
| Migraties | Altijd additief, nooit destructief |
| `WorkbookService::withoutAutoRefresh()` | Als je dit verwijdert, wordt het werkblad bij elke individuele wijziging ververst (traag) |

### 25.6 Veelgemaakte fouten bij uitbreiden

| Fout | Waarom |
|---|---|
| Vergeten `$fillable` bij te werken | Nieuwe velden worden niet opgeslagen |
| Vergeten Policy aan te passen | Gebruikers krijgen 403 of zien te veel |
| Vergeten query filtering | Gebruikers zien andermans data |
| MySQL-specifieke code schrijven | Werkt niet op PostgreSQL (productie) |
| Kolommen droppen in een migratie | Verwijdert data op productie |

---

## 26. Technische referentie

### 26.1 Alle bestanden op een rij

#### Models
| Bestand | Doel |
|---|---|
| `app/Models/User.php` | Gebruiker model met rolls, kleuren, workbook-relatie |
| `app/Models/TimeEntry.php` | Uur-registratie met automatische duurberekening |

#### Enums
| Bestand | Doel |
|---|---|
| `app/Enums/Role.php` | Rollen: student, user, admin |

#### Helpers
| Bestand | Doel |
|---|---|
| `app/Helpers/DurationHelper.php` | Minuten → "HH:MM" formattering |

#### Services
| Bestand | Doel |
|---|---|
| `app/Services/ExportService.php` | Excel/CSV export (streamed) |
| `app/Services/WorkbookService.php` | Persoonlijk werkblad beheer |
| `app/Services/TimeEntrySyncService.php` | Excel/CSV import met kolom-detectie |
| `app/Services/SyncResult.php` | Resultaat-object voor sync-operaties |

#### Filament Resources
| Bestand | Doel |
|---|---|
| `app/Filament/Admin/Resources/TimeEntries/TimeEntryResource.php` | CRUD configuratie voor uren |
| `app/Filament/Admin/Resources/TimeEntries/Schemas/TimeEntryForm.php` | Formulier voor uren |
| `app/Filament/Admin/Resources/TimeEntries/Tables/TimeEntriesTable.php` | Tabel voor uren |
| `app/Filament/Admin/Resources/TimeEntries/Pages/CreateTimeEntry.php` | Aanmaak-pagina |
| `app/Filament/Admin/Resources/TimeEntries/Pages/EditTimeEntry.php` | Bewerk-pagina |
| `app/Filament/Admin/Resources/TimeEntries/Pages/ListTimeEntries.php` | Lijst-pagina met acties |
| `app/Filament/Admin/Resources/Users/UserResource.php` | CRUD configuratie voor gebruikers |
| `app/Filament/Admin/Resources/Users/Schemas/UserForm.php` | Formulier voor gebruikers |
| `app/Filament/Admin/Resources/Users/Tables/UsersTable.php` | Tabel voor gebruikers |
| `app/Filament/Admin/Resources/Users/Pages/CreateUser.php` | Aanmaak-pagina |
| `app/Filament/Admin/Resources/Users/Pages/EditUser.php` | Bewerk-pagina |
| `app/Filament/Admin/Resources/Users/Pages/ListUsers.php` | Lijst-pagina |

#### Filament Exports
| Bestand | Doel |
|---|---|
| `app/Filament/Exports/TimeEntryExporter.php` | Filament export voor uren |

#### Filament Pages
| Bestand | Doel |
|---|---|
| `app/Filament/Admin/Pages/Dashboard.php` | Hoofddashboard met weekoverzicht |
| `app/Filament/Admin/Pages/Settings.php` | Account + thema + kleur instellingen |
| `app/Filament/Admin/Pages/EditProfile.php` | Profiel + stage-uren doel |

#### Actions
| Bestand | Doel |
|---|---|
| `app/Filament/Admin/Actions/WorkbookActions.php` | Koppelen/downloaden/ontkoppelen werkblad |
| `app/Filament/Admin/Actions/SyncTimeEntriesAction.php` | Excel synchronisatie modal |

#### Policies
| Bestand | Doel |
|---|---|
| `app/Policies/TimeEntryPolicy.php` | Toegang tot uren (eigenaar-only) |
| `app/Policies/UserPolicy.php` | Toegang tot gebruikers (admin-only) |

#### Providers
| Bestand | Doel |
|---|---|
| `app/Providers/AppServiceProvider.php` | Model event listeners (auto-refresh workbook) |
| `app/Providers/Filament/AdminPanelProvider.php` | Filament panel configuratie |

#### Controllers
| Bestand | Doel |
|---|---|
| `app/Http/Controllers/WorkbookController.php` | Download endpoint voor werkblad |

#### Database
| Bestand | Doel |
|---|---|
| `database/migrations/*_create_users_table.php` | Users + sessions + password_reset |
| `database/migrations/*_create_time_entries_table.php` | Tijdregistraties |
| `database/migrations/*_create_cache_table.php` | Cache tabellen |
| `database/migrations/*_create_jobs_table.php` | Queue tabellen |
| `database/migrations/*_alter_users_accent_color_*.php` | Accent kleur default |
| `database/factories/UserFactory.php` | Test data voor gebruikers |
| `database/factories/TimeEntryFactory.php` | Test data voor uren |
| `database/seeders/UsersSeeder.php` | Standaard admin + test user |
| `database/seeders/DatabaseSeeder.php` | Hoofd seeder |

#### Tests
| Bestand | Doel |
|---|---|
| `tests/Feature/AuthAndAccessTest.php` | Authenticatie & toegang (10 tests) |
| `tests/Feature/TimeEntrySyncTest.php` | Excel sync (9 tests) |
| `tests/Feature/WorkbookTest.php` | Workbook (6 tests) |
| `tests/Pest.php` | Pest configuratie |

#### Views (Blade templates)
| Bestand | Doel |
|---|---|
| `resources/views/welcome.blade.php` | Redirect naar dashboard |
| `resources/views/filament/widgets/progress-bar.blade.php` | Voortgangsbalk |
| `resources/views/filament/theme-sync.blade.php` | Thema sync naar localStorage |
| `resources/views/filament/theme-switcher.blade.php` | Thema wissel-knop |
| `resources/views/filament/components/accent-color-picker.blade.php` | Kleurenkiezer |
| `resources/views/filament/pages/settings.blade.php` | Settings pagina wrapper |

#### Configuratie
| Bestand | Doel |
|---|---|
| `config/database.php` | Database connecties (sqlite, mysql, pgsql) |
| `config/filament-palette.php` | Kleurenpalettes (9 thema's) |
| `config/filesystems.php` | Storage disks (local, public, s3) |
| `.env.example` | Voorbeeld environment variables |

#### Deployment
| Bestand | Doel |
|---|---|
| `railway.json` | Railway configuratie + start-commando |
| `nixpacks.toml` | Nixpacks build configuratie (PHP 8.4, Node 22) |
| `DEPLOY.md` | Uitgebreide deploy handleiding |

---

## 27. Conclusie

De Stage Urenregistratie App is een compacte maar goed gestructureerde Laravel + Filament applicatie. De architectuur volgt de standaard Laravel-conventies met een duidelijke scheiding van concerns:

- **Models** bevatten data-relaties en berekeningen
- **Services** bevatten business logic (export, sync, workbook)
- **Filament Resources/Pages** verzorgen de gebruikersinterface
- **Policies** regelen toegangscontrole
- **Providers** koppelen de lagen aan elkaar (model events)

De kracht van het project zit in de integratie van Excel-functionaliteit (zowel export als bidirectionele synchronisatie) en de automatische bijwerking van het persoonlijk werkblad. Dit maakt het tot een praktisch hulpmiddel voor stagiairs om hun uren bij te houden zonder dubbel werk.

Voor toekomstige ontwikkeling: houd altijd het migratiebeleid in gedachten (altijd additief, nooit destructief), test nieuwe functionaliteit, en zorg dat query filtering en policies correct blijven werken.

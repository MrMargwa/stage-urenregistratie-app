# Stage Urenregistratie App — Technische Documentatie

> Levende documentatie bij de code. Voor een korte intro en setup zie `README.md`;
> voor deploy-instructies zie `DEPLOY.md`; voor handmatige tests zie `TESTCHECKLIST.md`.
> **Laatst bijgewerkt:** september 2026.

---

## 1. Wat is dit?

Een Laravel 13 + Filament 5 webapplicatie waarmee een stagiair zijn werkuren per dag
registreert en per week volgt. De netto duur wordt automatisch berekend
(begintijd → eindtijd minus pauze), overlappende blokken worden geweigerd en de eigen
uren zijn als `.xlsx` te exporteren.

De applicatie is een **monolithische Laravel-app** waarin de UI volledig uit het
**Filament-panel** bestaat. De business logic zit in de modellen en policies, niet in
losse controllers.

Wat de app **niet** is: je kunt er geen algemene tijdshechting mee doen — er is één
domein (stage-uren), één doel, en bewust geen nachtdienst-ondersteuning.

---

## 2. Functionaliteiten

### 2.1 Urenregistratie (CRUD)
- `/dashboard/time-entries`: registraties aanmaken, bewerken en verwijderen.
- Velden: `user_id`, `date`, `start_time`, `end_time`, `break_minutes`, `description`.
- De netto duur wordt bij het opslaan berekend en **geschat** in `duration_minutes`
  (zie § 5.3).

### 2.2 Overlap-validatie
Twee registraties van dezelfde gebruiker op dezelfde dag mogen niet overlappen.
De controle zit in `TimeEntry::boot()` (modelniveau), zodat elke schrijfroute
(formulier, seeder, future code) beschermd is.

### 2.3 Weekoverzicht & dashboard
- `/dashboard`: de huidige week met weeklabel, per dag de registraties en totaal uren.
- Navigatie vorige/volgende/huidige week.
- Voortgangsbalk ten opzichte van je stage-doel (`target_hours`, instelbaar in
  Instellingen).

### 2.4 Weekstaat-filter & Excel-export
- In de lijst kun je filteren op "Weekstaat" (weken waarin je zelf uren hebt).
- "Exporteren (.xlsx)" draait **op de gefilterde selectie** (een week, of alle uren
  als de filter leeg is). Zie § 10.

### 2.5 Instellingen
`Instellingen` in het menu: naam, e-mailadres, (optioneel) nieuw wachtwoord
(min. 8 tekens) en het totale stage-uren-doel.

### 2.6 Thema
Het webthema (donker/licht/systeem) en een accentkleur worden ingesteld via de
**Filament UI Switcher** (`FilamentUiSwitcherPlugin` in `AdminPanelProvider`), niet via
een eigen controller. De keuze wordt per gebruiker bewaard in `ui_preferences` (JSON).

### 2.7 Gebruikersbeheer (alleen admin)
`Beheer → Gebruikers`: gebruikers aanmaken, bewerken, rollen toewijzen en verwijderen.
Er is geen self-registration.

### 2.8 Niet beschikbaar
- **Excel/CSV-import** en werkbladkoppeling: bestaat niet (alleen export).
- **Nachtdiensten** (eindtijd vóór begintijd): bewust niet ondersteund; zo'n
  registratie wordt geweigerd met een validatiefout.

---

## 3. Rollen & autorisatie

### 3.1 Rollen
De app kent **twee** rollen (`app/Enums/Role.php`):

```php
enum Role: string
{
    case Student = 'student';
    case Admin   = 'admin';
}
```

- **Student** — de hoofdgebruiker: eigen uren beheren, exporteren, instellingen.
- **Admin** — kan daarnaast via `Beheer → Gebruikers` accounts en rollen beheren.

De historische rol `user` bestaat niet meer (werd zonder functioneel verschil naast
`student` gebruikt); de `role`-kolom default nu direct naar `student`.

### 3.2 Policies
- `TimeEntryPolicy`: een gebruiker mag alleen zijn **eigen** entries bekijken/bewerken/
  verwijderen. `viewAny` is voor alle ingelogde gebruikers.
- `UserPolicy`: alleen admins mogen gebruikers beheren; de **laatste admin** kan niet
  verwijderd worden.

### 3.3 Privacy
Elke tijdsregistratie behoort toe aan één gebruiker; queries filteren altijd op
`auth()->id()` (o.a. via `TimeEntry::ownedBy()`). Ook de admin ziet alleen zijn eigen
uren. Rechtstreeks een URL naar andermans record openen → 404.

---

## 4. Tech stack & dependencies

### Backend
- PHP 8.4, Laravel 13, Filament 5, Eloquent ORM, OpenSpout (XLSX-export).

### Frontend
- Blade, Livewire, Tailwind CSS 4, Vite.

### Databases
| Context | Engine |
|---|---|
| Lokaal (Docker, `docker-local/`) | MySQL |
| Tests (`phpunit.xml`) | SQLite `:memory:` |
| Productie (Railway) | PostgreSQL |

Laravel abstraheert het verschil; de migraties bevatten geen engine-specifieke SQL, dus
ze draaien 1-op-1 op alle drie de engines.

### Development
- Pest PHP (tests), Laravel Pint (code style), Faker, Laravel Pail (log viewer).

---

## 5. Domeinmodel

### 5.1 `User` (`app/Models/User.php`)
- extends `Authenticatable`, implementeert `FilamentUser`, gebruikt
  `HasUiPreferences` (van `filament-ui-switcher`).
- Belangrijk:
  - `canAccessPanel(): bool` → altijd `true`.
  - `isAdmin()` → controleert de rol.
  - `timeEntries()` → `hasMany(TimeEntry::class)`.
  - `totalLoggedMinutes()` → som van `duration_minutes`.
  - `deleting`-event → verwijdert de tijdregistraties **op applicatieniveau** als extra
    bescherming naast de FK-cascade (zie § 6.2).
- Casts: `role` → `Role::class`, `ui_preferences` → `array`.

### 5.2 `TimeEntry` (`app/Models/TimeEntry.php`)
- Velden: `user_id`, `date`, `start_time`, `end_time`, `break_minutes`,
  `duration_minutes`, `description`.
- `boot()` registreert de **overlap-validatie** (`assertNoOverlap()`), ook bij updates.

### 5.3 Duurberekening (`app/Helpers/DurationHelper.php`)
Eén gedeelde formule, gebruikt door het model **en** de migratie die bestaande rijen
herberekent, zodat de logica nooit uit elkaar loopt:

```php
DurationHelper::toMinutes(string $startTime, string $endTime, int $breakMinutes = 0): int
DurationHelper::formatMinutes(int $minutes): string   // "HH:MM"
```

- Formule: `eind − begin − pauze`, minimaal `0`.
- Een combinatie waarbij de eindtijd vóór de begintijd ligt (nachtblok) is **ongeldig**
  en levert `0` minuten. Echte nachtwerk-blokken worden al eerder geblokkeerd door de
  validatie "eindtijd kan niet voor de begintijd liggen".

---

## 6. Database schema

Alle kwijting zit in de migraties (`database/migrations/`). Het schema is geconsolideerd in
**vijf eindstate-`create_`-migraties** (sept 2026; de vroegere `alter_/add_/update_/drop_`-bestanden
zijn samengevoegd):

| Migratie | Tabellen |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_08_21_085424_create_time_entries_table` | `time_entries` |
| `2026_08_27_101950_create_exports_table` | `exports` (Filament-exports) |

Lokaal opbouwen/resetten: `php artisan migrate:fresh --seed`.

### 6.1 `users`
- `id`, `name`, `email` (unique), `password`, `remember_token`, timestamps.
- `role` — enum-string, default `'student'`.
- `theme_mode` — historisch (default `dark`); het actieve thema wordt nu door de
  ui-switcher-plugin in `ui_preferences` bewaard, de kolom blijft bestaan.
- `accent_color` — **historisch**, wordt niet gebruikt.
- `target_hours` — stage-doel (int).
- `ui_preferences` — JSON met per gebruiker de ui-voorkeuren van de plugin.

### 6.2 `time_entries`
- `id`, `date`, `start_time` (time), `end_time` (time), `break_minutes`, `duration_minutes`,
  `description`, timestamps.
- `user_id` is **niet-nullable** en heeft een FK met `cascadeOnDelete`. Daarnaast verwijdert
  het `User::deleting`-event de entries op applicatieniveau — dubbel verzekerd.
- Indexen: `user_id` én een gecombineerde index `(user_id, date)`.

### 6.3 Overige tabellen
- `exports` — door Filament zelf gebruikt voor de export-queue.
- `imports` / `failed_import_rows` — bestaan niet (geen importfunctie in de app).
- `cache`, `jobs` (en varianten) — Laravel-standaard.

### 6.4 Relatie
```text
User ── hasMany ──► TimeEntry   (TimeEntry ── belongsTo ── User)
```

---

## 7. Architecture

Klassieke Laravel-monolith met Filament als enige UI-laag:

```text
Browser
  ↓
public/index.php → bootstrap/app.php → router + middleware
  ↓
Filament pagina / resource
  ↓
Eloquent model + policy
  ↓
Database
```

- Geen microservices, geen repository-layer.
- Business rules leven in modellen (`TimeEntry::boot()`), policies en
  `DurationHelper` — niet in controllers.
- De enige handgeschreven route is `/` → `HomeController`,
  die naar `/dashboard` redirect (`routes/web.php`).

### Directory-structuur (beknopt)
```text
app/
├── Enums/                          Role
├── Filament/
│   ├── Admin/
│   │   ├── Pages/                  Dashboard, Settings
│   │   ├── Resources/
│   │   │   ├── TimeEntries/        Resource + Schemas + Tables
│   │   │   └── Users/              Resource + Schemas + Tables + Pages
│   │   └── Widgets/                (widgets voor het dashboard)
│   └── Exports/                    TimeEntryExporter
├── Helpers/                        DurationHelper
├── Http/Controllers/               HomeController (alleen)
├── Models/                         User, TimeEntry
├── Policies/                       UserPolicy, TimeEntryPolicy
└── Providers/Filament/             AdminPanelProvider
database/
├── factories/, migrations/, seeders/
config/seeding.php                  admin-account via SEED_ADMIN_* env
railway/                            Nixpacks-config + pre-deploy.sh
```

---

## 8. Routes

`php artisan route:list` — de belangrijkste:

```text
GET  /                                → HomeController (redirect naar dashboard)
GET  /dashboard                       Filament dashboard
GET  /dashboard/login                 login
POST /dashboard/logout                logout
GET  /dashboard/settings              Instellingen
GET  /dashboard/time-entries          Tijdregistraties (+ create/edit)
GET  /dashboard/users                 Gebruikersbeheer (admin, + create/edit)
GET  /up                               healthcheck
```

`routes/web.php` is minimaal (alleen de `/` redirect); alle overige routes komen uit
Filament (resource- en paginadetection). Er is géén eigen `/theme`-route meer.

---

## 9. Authenticatie & beveiliging

- Filament-panel met autologin-able login; **niet-ingelogde** bezoekers worden altijd
  naar `/dashboard/login` gestuurd, ook bij 404's.
- Middleware-stack: cookies, session, CSRF (`PreventRequestForgery`),
  `SubstituteBindings`, auth-middleware `Authenticate`.
- Geen self-registration: accounts worden door een admin aangemaakt.
- Roles, policies en eigen-data-queries beschermen de privacy (§ 3).
- In productie (Railway) staat `SESSION_SECURE_COOKIE=true` in de Railway-variables,
  zodat de sessiecookie alleen over HTTPS gaat.
- Validatie: overlap + "eind na begin" op modelniveau; formuliervalidatie
  (e-mail uniek, wachtwoord ≥8, target 1–9999).
- `trustProxies(at: '*')` in `bootstrap/app.php` — bewust zo gelaten zodat Railway
  `https` correct achter een proxy detecteert (`APP_URL`-forcing blijft werken).

---

## 10. Excel-export (`app/Filament/Exports/TimeEntryExporter.php`)

- Draait via Filament's exports-systeem (OpenSpout); tabel `exports` registreert de jobs.
- Kolommen: **Week · Datum · Begintijd · Eindtijd · Pauze (minuten) · Beschrijving · Duur**.
- De duur in de export komt uit `DurationHelper::formatMinutes()` (`u:mm`).
- De kopregel en data-rijen worden gekleurd met de **accentkleur** die de gebruiker in
  de ui-switcher koos (`ui_preferences.ui.color`), eventueel met fallback.

---

## 11. Instellingen (`app/Filament/Admin/Pages/Settings.php`)

- Sectie **Account**: naam, e-mail, nieuw wachtwoord (leeg = ongewijzigd).
- Sectie **Stage**: `target_hours` (min 1, max 9999) voor de voortgangsbalk.
- Opslaan via de knop "Opslaan" of `Ctrl/Cmd+S`.

Thema en accentkleur stel je in via het gebruikersmenu (ui-switcher), niet via deze
pagina.

---

## 12. Dashboard (`app/Filament/Admin/Pages/Dashboard.php`)

- Toont de gekozen week (weeklabel + datumbereik), navigatiepijlen en
  "Huidige week".
- Query: `TimeEntry::ownedBy(auth()->user())->whereBetween('date', [$start, $end])`.
- Totaal-uren-badge en voortgangsbalk t.o.v. `target_hours`.

---

## 13. Tests

Tests draaien op **SQLite `:memory:`** (`phpunit.xml`), Pest-syntax.

```bash
vendor/bin/pest         # alle tests (SQLite :memory:, zie phpunit.xml)
vendor/bin/pint         # code style
```

### Belangrijkste suites
- `tests/Feature/TimeEntryOverlapTest.php` — overlap blokkeren, naast-elkaar toestaan,
  verschillende gebruikers mogen overlappen, updates zonder overlap mogen door.
- `tests/Feature/UserPolicyTest.php` — alleen admins beheren gebruikers; laatste admin
  kan niet verwijderd worden.
- `tests/Feature/UsersSeederTest.php` — seeder is idempotent.
- `tests/Unit/DurationHelperTest.php` — duurberekening + notatie.

---

## 14. Seeder & admin-account

`database/seeders/UsersSeeder.php` (ook $`UsersSeederTest`) maakt **altijd één admin**
aan en is idempotent:

- Bestaat de admin niet? Maak `admin@admin.com` / `Admin1!23` aan (rol `admin`).
- Bestaat de admin wel? Doe niets — een gewijzigd wachtwoord blijft bewaard.

De standaardwaarden komen uit `config/seeding.php` en zijn per omgeving te overschrijven:

```text
SEED_ADMIN_EMAIL    (default: admin@admin.com)
SEED_ADMIN_PASSWORD (default: Admin1!23)
```

In productie draait de seeder in de Railway **pre-deploy-stap** (`railway/pre-deploy.sh`),
na `migrate --force`.

---

## 15. Deployment & omgeving

- **Hosting:** Railway, automatische deploy bij elke push naar `main`.
- **Build:** Nixpacks (`nixpacks.toml`) → PHP 8.4 + intl/redis-extensies,
  `composer install --no-dev`, `npm ci && npm run build`, serving via Nginx + PHP-FPM.
- **Database:** PostgreSQL (productie), MySQL (lokaal Docker), SQLite (tests).
- **Cache/sessies:** Redis (optioneel) of `database`; default CACHE/SESSION/LOGGING
  staan klaar in `DEPLOY.md`.
- **Config-cache:** `config:cache` + route- en view-cache in de pre-deploy-stap. Let
  dus op: seed-instellingen (`SEED_ADMIN_*`) moet je als Railway-variable zetten
  vóór een deploy, omdat de config dan gecacht wordt.

> Zie `DEPLOY.md` voor de exacte Railway-variables (`SESSION_SECURE_COOKIE=true`, e.d.).

---

## 16. Bekende beperkingen & bewuste keuzes

1. **Nachtdiensten** worden niet ondersteund (blokkade bij eind < begin).
2. **Import/werkblad-koppeling** bestaat niet — alleen export.
3. **`accent_color` / `theme_mode`** op `users` zijn historische kolommen; ze staan gewoon
   in de `create_users`-migratie, maar besturen niets meer (thema loopt via `ui_preferences`).
4. **Business rules** zitten bewust in modellen en policies (niet in service-classes);
   dat houdt de code compact en de logica vindbaar.
5. De migraties zijn geconsolideerd als **eindstate-`create_`**-bestanden (sept 2026);
   voor een snelle lokale reset: `php artisan migrate:fresh --seed`. Schema-wijzigingen na
   nu doe je **additief** (nieuwe kolommen met default/nullable, nieuwe tabellen), nooit
   droppen of data herschrijven.

---

## 17. Snelle navigatie (leesvolgorde)

1. `README.md` — intro + lokaal opstarten
2. `routes/web.php` — de enige handmatige route
3. `app/Providers/Filament/AdminPanelProvider.php` — panel + plugins
4. `app/Models/User.php`, `app/Models/TimeEntry.php` — domein
5. `app/Policies/*` — autorisatie
6. `app/Helpers/DurationHelper.php` — duurberekening
7. `app/Filament/Admin/Resources/TimeEntries/` — de kern-resource
8. `database/migrations/` — schema-geschiedenis
9. `DEPLOY.md` — productie
10. `TESTCHECKLIST.md` — handmatige tests
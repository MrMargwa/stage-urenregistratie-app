# Stage Urenregistratie App — volledige technische documentatie

## 1. Executive summary

Dit project is een Laravel 13 + Filament 5 webapplicatie voor het registreren, beheren en exporteren van stage-uren. De kern van de app is een dashboard waarin een gebruiker zijn werkuren per dag en week invoert, bekijkt en exporteert. De applicatie is ontworpen voor een concreet doel: het op een veilige, consistente en gebruiksvriendelijke manier bijhouden van stage-uren zonder losstaande Excel-bestanden of handmatige rapportages.

Het project is een monolithische Laravel-applicatie, maar de gebruikersinterface is volledig opgebouwd met Filament. Daardoor voelt het systeem sterk aan als een admin dashboard, terwijl de echte business logic vooral in de modellen, policies en querylogica zit.

---

## 2. Wat is dit project?

Dit is een webapplicatie voor urenregistratie in een stagecontext.

Het bestaat uit:

- een Laravel backend
- een Filament admin panel
- Eloquent ORM voor database-interactie
- Blade + Livewire voor UI-rendering
- Tailwind CSS voor styling
- Excel-export (TimeEntryExporter)

De app laat een gebruiker toe om:

- werkuren per dag vast te leggen
- pauzes in te voeren
- totaal aantal uren te volgen
- weekoverzichten te bekijken
- data te exporteren naar Excel
- instellingen aan te passen
- toegang te krijgen op basis van rollen

Het is dus geen algemene SaaS-app, geen CRM, geen ERP en ook geen webshop. Het is een compact, doelgerichte applicatie voor tijdregistratie.

---

## 3. Doel van de applicatie

Het doel is om het proces van urenregistratie te vereenvoudigen en te standaardiseren. In plaats van losse Excel-sheets of papieren notities, geeft de app een centrale plek waar een gebruiker:

- een workday registreert
- begintijd en eindtijd invult
- pauze vastlegt
- een omschrijving toevoegt
- de werkduur automatisch laat berekenen
- per week kan zien hoeveel uren zijn gedaan
- de data kan exporteren naar Excel

Het probleem dat deze app oplost is: gebrek aan controle, overzicht en consistentie bij het bijhouden van stage-uren.

---

## 4. Voor wie is deze applicatie bedoeld?

### Gebruiker / stagiair

Dit is de hoofdgebruiker. Hij of zij gebruikt het systeem om:

- uren in te geven
- een week te bekijken
- profiel- en voorkeurinstellingen te wijzigen
- exports te downloaden

### Admin

De admin beheert gebruikers en heeft toegang tot gebruikersbeheer. Hij kan bijvoorbeeld rollen toewijzen en gebruikers aanmaken of beheren.

### Niet-ingelogde gebruiker

Niet-ingelogde gebruikers kunnen niet bij de app. Ze worden altijd doorgestuurd naar de loginpagina.

---

## 5. Belangrijkste functionaliteiten

### 5.1 Urenregistratie

Een gebruiker kan een tijdregistratie aanmaken met:

- datum
- begintijd
- eindtijd
- pauze in minuten
- beschrijving

De netto duur wordt bij het opslaan berekend op basis van begin/eind-tijd minus pauze en opgeslagen in de `duration_minutes`-kolom. De `duration`-accessor levert dezelfde waarde via `DurationHelper`.

### 5.2 Overlap-controle

Een gebruiker kan geen tijdsblok registreren dat overlapt met een al bestaand tijdsblok op dezelfde dag. Deze controle zit in het model zelf.

### 5.3 Weekoverzicht

Het dashboard toont de gekozen week, per dag de registraties en een totaal aantal uren. Dit maakt het gemakkelijk om de voortgang te volgen.

### 5.4 Excel export

Een gebruiker kan zijn data exporteren naar een .xlsx-bestand. De export is afgestemd op de app-ervaring en gebruikt Filament export features.

### 5.5 Excel import — niet beschikbaar

Deze functionaliteit bestaat niet in de huidige versie van de app. Er is geen Excel-import of CSV-import.

### 5.6 Excel werkblad koppeling — niet beschikbaar

Deze functionaliteit (werkblad koppelen) bestaat niet in de huidige versie van de app.

### 5.7 Instellingen

Gebruikers kunnen:

- naam aanpassen
- e-mailadres aanpassen
- wachtwoord wijzigen
- thema kiezen
- totaal te lopen stage-uren instellen

### 5.8 Gebruikersbeheer

Admins kunnen gebruikers beheren, maar dit is beveiligd met policies. Niet iedereen kan zomaar gebruikers beheren.

---

## 6. Technologieën en dependencies

### Backend

De backend is gebaseerd op:

- PHP 8.4
- Laravel 13.17
- Filament 5
- Eloquent ORM

### Development dependencies

- Pest PHP
- Laravel Pint
- Faker
- Laravel Pail

### Frontend

- Blade templates
- Livewire
- Tailwind CSS 4
- Vite

### Database

Lokale ontwikkeling gebruikt SQLite. In Docker draait de app vaak met MySQL. Laravel abstraheert de databasekeuze goed genoeg zodat de app flexibel inzetbaar is.

---

## 7. Architectuur

De app volgt een klassieke Laravel monolith-architectuur met Filament als UIlaag.

```text
Browser
  ↓
HTTP request
  ↓
public/index.php
  ↓
bootstrap/app.php
  ↓
Laravel router + middleware + exception handling
  ↓
Filament page / resource
  ↓
Eloquent model / query
  ↓
Database
  ↓
HTML / JSON / file response
  ↓
Browser
```

Belangrijk:

- Er is geen microservice-architectuur.
- Er is geen diep uitgewerkte repository-layer voor elk use case.
- De app is compact en functioneel.
- De echte business rules liggen vaak in de modellen en policies.

---

## 8. Directory structure

```text
stage-urenregistratie-app/
├── app/
│   ├── Enums/
│   ├── Filament/
│   ├── Helpers/
│   ├── Http/
│   ├── Models/
│   ├── Policies/
│   └── Providers/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
├── tests/
├── .env.example
├── composer.json
├── package.json
├── README.md
├── DOCUMENTATION.md
├── artisan
└── vite.config.js
```

### `app/Models`

Hier zitten de domeinmodellen:

- `User.php`
- `TimeEntry.php`

### `app/Filament`

Dit is het hart van de gebruikersinterface:

- dashboard
- settings page
- resource pages voor tijdregistraties en gebruikers
- export logic

### `app/Policies`

Hier zit de beveiliging:

- `UserPolicy.php`
- `TimeEntryPolicy.php`

### `database/migrations`

Hier staan alle database-schema's. Dit zijn de 'contracten' van de app.

### `routes`

Hier worden HTTP-routes gedefinieerd. In deze app zijn veel routes echter Filament-routes.

---

## 9. Waar begint de applicatie?

Het startpunt van Laravel is standaard:

- `public/index.php`
- daarna `bootstrap/app.php`

### `public/index.php`

Dit is het eerste PHP-bestand dat wordt uitgevoerd wanneer de webserver een verzoek ontvangt. Het bootstrapt Laravel en geeft de request door aan het framework.

### `bootstrap/app.php`

Hier worden de basisinstellingen van de app gevormd:

- routing
- exception handling
- middleware
- health endpoint

In deze app staat ook expliciet de redirect-logic voor onjuiste URLs en niet-ingelogde toegang.

---

## 10. Wat gebeurt er bij `php artisan serve`?

Wanneer je `php artisan serve` uitvoert, doet Laravel ongeveer het volgende:

1. PHP start de Artisan CLI
2. Laravel initialiseert de application container
3. service providers worden geregistreerd
4. configuratie en environment worden geladen
5. de PHP development server start op localhost:8000

Na deze stap is de app live en kan een browser requests sturen.

---

## 11. Wat gebeurt er bij een HTTP-request?

Een typisch request in deze app verloopt zo:

```text
Browser
  ↓
GET /dashboard
  ↓
public/index.php
  ↓
bootstrap/app.php
  ↓
Laravel router
  ↓
Filament dashboard route resolution
  ↓
Dashboard page class
  ↓
mount() + query logic
  ↓
Eloquent query on time_entries
  ↓
HTML response
  ↓
Browser renders page
```

Belangrijk is dat veel requests niet via een handgeschreven controller lopen, maar via Filament routes en pages.

---

## 12. Routes

De werkelijke routes zijn via `php artisan route:list` geverifieerd. De belangrijkste routes zijn:

```text
GET  /                                HomeController
GET  /dashboard                       filament.dashboard.pages.dashboard
GET  /dashboard/login                 filament.dashboard.auth.login
POST /dashboard/logout                filament.dashboard.auth.logout
GET  /dashboard/settings              filament.dashboard.pages.settings
GET  /dashboard/time-entries          filament.dashboard.resources.time-entries.index
GET  /dashboard/time-entries/create   filament.dashboard.resources.time-entries.create
GET  /dashboard/time-entries/{record}/edit
GET  /dashboard/users                 filament.dashboard.resources.users.index
GET  /dashboard/users/create          filament.dashboard.resources.users.create
GET  /dashboard/users/{record}/edit   filament.dashboard.resources.users.edit
POST /theme                           ThemeController
```

### `routes/web.php`

```php
Route::get('/', HomeController::class);
Route::post('/theme', ThemeController::class)->middleware('auth')->name('theme.update');
```

Dus: de homepage redirect naar het dashboard, en een handmatige route zorgt voor thema-updates.

---

## 13. Middleware

In `app/Providers/Filament/AdminPanelProvider.php` staat de panel middleware stack:

- `EncryptCookies`
- `AddQueuedCookiesToResponse`
- `StartSession`
- `AuthenticateSession`
- `ShareErrorsFromSession`
- `PreventRequestForgery`
- `SubstituteBindings`
- `DisableBladeIconComponents`
- `DispatchServingFilamentEvent`

Daarnaast geldt:

- `Authenticate` als authMiddleware

Wat betekent dit?

- sessies worden gecontroleerd
- cookies worden beveiligd
- CSRF wordt gecontroleerd
- niet-ingelogde gebruikers kunnen niet verder

---

## 14. Controllers in deze app

### `HomeController`

`app/Http/Controllers/HomeController.php`

```php
class HomeController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('filament.dashboard.pages.dashboard');
    }
}
```

Deze controller redirect de rootroute naar het dashboard.

### `ThemeController`

`app/Http/Controllers/ThemeController.php`

```php
public function __invoke(Request $request): JsonResponse
{
    $theme = $request->validate([
        'theme' => 'required|in:dark,light,system',
    ])['theme'];

    $request->user()->update(['theme_mode' => $theme]);

    return response()->json(['ok' => true, 'theme' => $theme]);
}
```

Deze controller valideert en slaat het gekozen thema op voor de ingelogde gebruiker.

---

## 15. Models en business logic

### `User`

Bestand: `app/Models/User.php`

De `User` class extends `Authenticatable` en implementeert `FilamentUser`.

Belangrijkste onderdelen:

- `canAccessPanel()` => altijd true
- `isAdmin()` => controleert of de rol admin is
- `timeEntries()` => `hasMany(TimeEntry::class)`
- `totalLoggedMinutes()` => totaal aantal minuten
- `totalLoggedHoursFormatted()` => formaat voor leesbare weergave
- casts: `role` => `Role::class`, `ui_preferences` => `array`

### `TimeEntry`

Bestand: `app/Models/TimeEntry.php`

Dit is het kernmodel van de app. Het representeert één geregistreerde werkperiode.

Velden:

- `user_id`
- `date`
- `start_time`
- `end_time`
- `break_minutes`
- `duration_minutes`
- `description`
- timestamps

#### Duur berekening

```php
protected function duration(): Attribute
{
    return Attribute::get(fn (): int => $this->computeDuration());
}

private function computeDuration(): int
{
    if (! $this->start_time || ! $this->end_time) {
        return 0;
    }

    return DurationHelper::toMinutes(
        $this->start_time->format('H:i'),
        $this->end_time->format('H:i'),
        (int) $this->break_minutes,
    );
}
```

De duur wordt berekend via `App\Helpers\DurationHelper::toMinutes()`:

```text
duur = eindtijd - begintijd - pauze
```

Hierbij wordt ook een blok dat de middernacht overschrijdt (eindtijd vóór begintijd) correct behandeld: er wordt +24 uur opgeteld.

#### Overlap validatie

In het boot-event van `TimeEntry` wordt gecontroleerd of een nieuwe registratie overlap heeft met een bestaande op dezelfde dag. Als dat zo is, wordt een `ValidationException` gegooid.

---

## 16. Database schema

### `users`

Uit `database/migrations/0001_01_01_000000_create_users_table.php` (met uitbreidingen):

- `id`
- `name`
- `email`
- `password`
- `role`
- `theme_mode`
- `accent_color` — historische kolom die in de huidige app niet wordt gebruikt
- `target_hours`
- `ui_preferences` (JSON, toegevoegd in `2026_08_27_084158_add_ui_preferences_to_users_table.php`)
- `remember_token`
- timestamps

### `time_entries`

Uit `database/migrations/2026_08_21_085424_create_time_entries_table.php` (met uitbreidingen):

- `id`
- `user_id`
- `date`
- `start_time`
- `end_time`
- `break_minutes`
- `duration_minutes` (toegevoegd in `2026_09_07_000001_add_duration_minutes_to_time_entries_table.php`)
- `description`
- timestamps

Opmerking: `time_entries.user_id` gebruikt `nullOnDelete` op DB-niveau. Het feitelijk verwijderen
van de stage-uren bij een verwijderde gebruiker gebeurt op **applicatieniveau**: het `User::deleting`
event verwijdert alle bijbehorende `time_entries`. Zo werkt de cascade consistent op SQLite (tests)
én PostgreSQL/MySQL (productie). Er is geen `workbook_linked_at`-kolom (of andere
werkblad-koppelingskolom) in de schema's.

### Relatie

```text
User
  └── hasMany(TimeEntry)

TimeEntry
  └── belongsTo(User)
```

Dit is de kern van de privacy: elke tijdregistratie behoort toe aan één gebruiker.

---

## 17. Authenticatie en authorization

### Authentication flow

Filament biedt de auth-flow. In `AdminPanelProvider` staat:

- panel path = `dashboard`
- login enabled
- auth middleware = `Authenticate`

Niet-ingelogde gebruikers worden doorgestuurd naar `/dashboard/login`.

### Authorization

De toegangsregels staan in de policy classes:

#### `TimeEntryPolicy`

- `viewAny`: alle ingelogde gebruikers
- `view`: alleen eigen entries
- `create`: toegestaan
- `update`: alleen eigen entries
- `delete`: alleen eigen entries

#### `UserPolicy`

- alleen admins mogen gebruikers beheren
- laatste admin kan niet worden verwijderd

---

## 18. Rollen

De rol-enum staat in `app/Enums/Role.php`:

```php
enum Role: string
{
    case Student = 'student';
    case User = 'user';
    case Admin = 'admin';
}
```

Deze enum wordt gebruikt voor gebruikersrechten en UI labels.

---

## 19. Validation en data integrity

De app valideert data op meerdere niveaus.

### Model niveau

- geen overlappende records op dezelfde dag
- `end_time` mag niet voor `start_time` liggen

### Settings form

- email verplicht
- password minimaal 8 tekens als nieuw wachtwoord wordt ingevuld
- `target_hours` numeriek en begrensd

### Request validation

`ThemeController` valideert:

```php
'theme' => 'required|in:dark,light,system'
```

Zo wordt garbage data voorkomen voordat iets in de database komt.

---

## 20. Dashboard en UI flow

### Dashboard

`app/Filament/Admin/Pages/Dashboard.php`

De pagina toont:

- de huidige week
- totaal aantal uren
- per dag de registraties
- navigatie voor vorige/huidige/volgende week

De query is een echte Eloquent query:

```php
TimeEntry::ownedBy(auth()->user())
    ->whereBetween('date', [$start, $end])
    ->orderBy('date')
    ->orderBy('start_time')
    ->get();
```

Hierdoor krijgt elke gebruiker alleen zijn eigen data.

### Settings page

`app/Filament/Admin/Pages/Settings.php`

Hier kan de gebruiker zijn:

- naam
- email
- wachtwoord
- stage-doel

wijzigen.

---

## 21. Filament admin panel

`app/Providers/Filament/AdminPanelProvider.php` configureert het paneel.

Het panel bevat:

- `Dashboard`
- `Settings`
- `TimeEntryResource`
- `UserResource`

Het panel activeert automatisch routes via discovery. Daardoor zijn de pagina's en resources niet handmatig in één groot routebestand te zien.

---

## 22. Excel export

`app/Filament/Exports/TimeEntryExporter.php` is verantwoordelijk voor export. Het maakt een `.xlsx`-bestand met kolommen zoals:

- Week
- Datum
- Begintijd
- Eindtijd
- Pauze
- Beschrijving
- Duur

Het gebruikt OpenSpout en stijl de header/cellen op basis van UI-preferences van de gebruiker.

---

## 23. Excel import en werkblad-koppeling — niet beschikbaar

Deze functionaliteit bestaat niet in de huidige versie van de app. Er is geen Excel- of CSV-import, geen synchronisatie met een bestand, en geen koppeling van een persoonlijk werkblad. De app biedt alleen Excel **export** via `TimeEntryExporter` (zie sectie 22).

---

## 24. Frontend flow

De frontend is niet een React- of Vue-app. De app gebruikt:

- Filament pages
- Livewire
- Blade templates
- Tailwind CSS

Dat betekent dat gebruikersinteractie voornamelijk server-driven is, met moderne UI-componenten en formulieren.

---

## 25. Environment en config

Belangrijkste config-bestanden:

- `.env.example`
- `config/app.php`
- `config/auth.php`
- `config/database.php`
- `config/filesystems.php`

Belangrijkste variabelen:

- `APP_ENV`
- `APP_KEY`
- `APP_URL`
- `DB_CONNECTION`
- `SESSION_DRIVER`
- `QUEUE_CONNECTION`
- `CACHE_STORE`
- `MAIL_MAILER`

---

## 26. Exception handling en logging

Foutafhandeling staat in `bootstrap/app.php`.

Er is custom behavior voor:

- 404 requests
- authentication failures

Bij een GET 404 wordt de app als volgt behandeld:

- ingelogd => redirect naar `/dashboard`
- niet ingelogd => redirect naar `/dashboard/login`

Logging is standaard Laravel logging via configuration; er is geen uitgebreide custom logging layer in de kernapp.

---

## 27. Tests

De tests staan in `tests/`.

### Belangrijkste tests

`tests/Feature/TimeEntryOverlapTest.php`

Deze tests verifiëren dat:

- overlappende registraties worden geblokkeerd
- niet-overlappende registraties toegelaten zijn
- verschillende gebruikers hetzelfde tijdsblok mogen hebben
- updates die geen overlap veroorzaken, mogen door

Command:

```bash
php artisan test
```

---

## 28. Belangrijkste business rules

De belangrijkste business rules zijn:

1. Een gebruiker ziet alleen zijn eigen uren.
2. Een admin kan gebruikers beheren.
3. Een tijdslot mag niet overlappen met een ander tijdslot van dezelfde gebruiker op dezelfde dag.
4. `end_time` mag niet vóór `start_time` liggen.
5. De laatste admin kan niet worden verwijderd.
6. Niet-ingelogde gebruikers worden naar de login gebracht.

Deze regels zitten in:

- `TimeEntry::boot()`
- `TimeEntry::assertNoOverlap()`
- `TimeEntryPolicy`
- `UserPolicy`

---

## 29. Belangrijkste end-to-end flows

### Flow 1: dashboard openen

```text
GET /dashboard
  ↓
route resolves to Filament dashboard page
  ↓
Dashboard::mount()
  ↓
set weekStart
  ↓
Dashboard::getWeekEntries()
  ↓
query filtered by auth()->id()
  ↓
render page with totals
```

### Flow 2: tijdregistratie aanmaken

```text
open create form
  ↓
submit data
  ↓
TimeEntry model save
  ↓
boot hook validates overlap
  ↓
insert into time_entries
```

### Flow 3: settings opslaan

```text
open settings page
  ↓
fill current user data
  ↓
save updates
  ↓
User::update([...])
  ↓
notification shown
```

### Flow 4: exporteren naar Excel

```text
open user entries list
  ↓
click Exporteren (.xlsx)
  ↓
TimeEntryExporter builds workbook
  ↓
download to browser
```

---

## 30. Dependency map

```text
Dashboard Page
  └── TimeEntry model
       └── time_entries table

Settings Page
  └── User model
       └── users table

UserResource
  └── UserPolicy
       └── Role enum

TimeEntryResource
  └── TimeEntryPolicy
       └── User model

ThemeController
  └── authenticated user
       └── theme_mode field in users table
```

---

## 31. Eén volledige request van begin tot eind

Voorbeeld: `GET /dashboard`

```text
1. browser stuurt request naar /dashboard
2. public/index.php bootstrapt Laravel
3. bootstrap/app.php laadt de app-configuratie
4. router matcht de dashboard route
5. Filament page class wordt geladen
6. Dashboard::mount() zet de weekcontext
7. Dashboard::getWeekEntries() voert query uit
8. database retourneert records voor auth()->id()
9. UI rendert de weekoverzichtspagina
10. browser toont de pagina
```

---

## 32. Wat gebeurt er “onder water”?

Het kernidee is eenvoudig:

- de browser vraagt iets aan
- Laravel matcht de route
- Filament kiest de juiste pagina of resource
- de app leest de juiste data uit de database
- policies beschermen tegen verkeerde toegang
- modellen controleren de data-integriteit
- de response wordt teruggestuurd naar de browser

Er is geen ingewikkelde service-mesh of microservice-architectuur. De app is een compacte monolith met een duidelijke gebruikersflow.

---

## 33. Architecture review

### Sterke punten

- duidelijke modellen (`User`, `TimeEntry`)
- strikte privacy via policies en filters
- validatie zit op het juiste niveau
- compacte en overzichtelijke projectstructuur
- tests beschermen de kernbusiness rules

### Zwakke punten

- veel business logic zit in Filament pages in plaats van aparte service classes
- app is sterk afhankelijk van Filament conventions

### Risicovolle onderdelen

- overlap-validatie in model boot hooks
- admin delete logic
- user-specific queries
- Excel export styling

---

## 34. Aanbevolen leesvolgorde voor een nieuwe developer

1. `README.md`
2. `composer.json`
3. `bootstrap/app.php`
4. `routes/web.php`
5. `app/Providers/Filament/AdminPanelProvider.php`
6. `app/Models/User.php`
7. `app/Models/TimeEntry.php`
8. `app/Policies/UserPolicy.php`
9. `app/Policies/TimeEntryPolicy.php`
10. `app/Filament/Admin/Pages/Dashboard.php`
11. `app/Filament/Admin/Pages/Settings.php`
12. `app/Filament/Admin/Resources/TimeEntries/TimeEntryResource.php`
13. `database/migrations/0001_01_01_000000_create_users_table.php`
14. `database/migrations/2026_08_21_085424_create_time_entries_table.php`
15. `tests/Feature/TimeEntryOverlapTest.php`

---

## 35. Mental model

Als je de app op een whiteboard zou uitleggen, ziet het ongeveer zo uit:

```text
Gebruiker
  ↓
Filament Dashboard
  ↓
User + TimeEntry modellen
  ↓
Eloquent queries naar database
  ↓
Policies + validation rules
  ↓
HTML / Excel / response back to browser
```

De applicatie werkt in essentie als volgt:

> Een gebruiker logt in, registreert werkuren, de app berekent de duur, controleert overlap en beschermt privacy, en daarna kan de gebruiker zijn data bekijken, exporteren of aanpassen.

---

## 36. Samenvatting

Deze app is een compacte maar degelijk ontworpen Laravel 13 + Filament 5 applicatie voor urenregistratie. De kern bestaat uit:

- `User` en `TimeEntry` modellen
- user-based access control via policies
- een Filament dashboard
- data-integriteit via modelvalidatie
- uurregistratie, export en overzichtsfuncties

Voor een developer die dit project moet begrijpen, is de belangrijkste mental model:

> het is een monolithische Laravel-app met een privacy-strikte urenregistratie-flow, waarbij de gebruiker zijn eigen werkuren beheert via een Filament dashboard en de echte business logic in modellen en policies zit.

Als je deze documentatie meerdere keren doorleest, krijg je een goed beeld van zowel de technische structuur als de werkelijke business intentie van het project.

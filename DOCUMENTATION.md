# Stage Urenregistratie App

## Welkom! 👋

Dit document is jouw gids door het project. Of je nu net begint of even iets moet opzoeken — na het lezen van deze documentatie weet je precies waar alles staat, hoe het werkt, en waar je moet zijn als er iets misgaat.

**Deze app doet in het kort:** je kunt je stage-uren bijhouden, exporteren naar Excel, en een gekoppeld Excel-bestand automatisch laten bijwerken.

---

## Inhoudsopgave

1. [Hoe dit project in elkaar zit (in 2 minuten)](#1-hoe-dit-project-in-elkaar-zit-in-2-minuten)
2. [Hoe de app start (de magie achter localhost:8000)](#2-hoe-de-app-start-de-magie-achter-localhost8000)
3. [Aan de slag: lokaal ontwikkelen](#3-aan-de-slag-lokaal-ontwikkelen)
4. [De database: wat staat waar?](#4-de-database-wat-staat-waar)
5. [Rollen: wie mag wat?](#5-rollen-wie-mag-wat)
6. [De Models: User en TimeEntry](#6-de-models-user-en-timeentry)
7. [Helpers: DurationHelper](#7-helpers-durationhelper)
8. [Services: waar de magie gebeurt](#8-services-waar-de-magie-gebeurt)
9. [Het Filament Admin Panel](#9-het-filament-admin-panel)
10. [Filament Routing: hoe pagina's automatisch worden gevonden](#10-filament-routing-hoe-paginas-automatisch-worden-gevonden)
11. [Exporteren en Importeren](#11-exporteren-en-importeren)
12. [Deployen naar productie (Railway)](#12-deployen-naar-productie-railway)
13. [Testen schrijven en uitvoeren](#13-testen-schrijven-en-uitvoeren)
14. [Als er iets misgaat: debugging gids](#14-als-er-iets-misgaat-debugging-gids)
15. [Verantwoordelijkheden per rol](#15-verantwoordelijkheden-per-rol)
16. [Alle bestanden op een rij](#16-alle-bestanden-op-een-rij)

---

## 1. Hoe dit project in elkaar zit (in 2 minuten)

Stel je voor: je hebt een website waar je kunt inloggen. Als je ingelogd bent, zie je een dashboard met een overzicht van je uren. Je kunt uren toevoegen, bewerken, en verwijderen. Je kunt ze exporteren naar Excel. En je kunt een Excel-bestand koppelen dat automatisch wordt bijgewerkt.

Dat is precies wat deze app doet. Maar hoe werkt dat technisch?

### De bouwstenen

```
Gebruiker opent de browser
        ↓
Filament Admin Panel (de "website")
        ↓
Laravel Backend (de "engine")
        ↓
Database (waar alles wordt opgeslagen)
```

**Filament** is een pakket dat een mooi admin-panel maakt. Denk aan een dashboard met knoppen, tabellen, en formulieren — zonder dat je zelf HTML hoeft te schrijven. Het werkt met **Livewire**, wat betekent dat de pagina's live reageren zonder dat je de pagina hoeft te herladen.

**Laravel** is het PHP-framework. Het regelt alles achter de schermen: database-connecties, authenticatie (inloggen), routing (welke pagina hoort bij welke URL), en nog veel meer.

**De database** slaat alles op: gebruikers, uren, sessies, etc.

### Hoe data stroomt door de app

```
1. Je klikt op "+ Tijdregistratie"
2. Filament toont een formulier (TimeEntryForm)
3. Je vult de velden in en klikt "Opslaan"
4. Laravel controleert of je mag opslaan (TimeEntryPolicy)
5. Het wordt opgeslagen in de database (TimeEntry model)
6. Er wordt automatisch een event gestuurd (TimeEntry::saved)
7. Het Excel-workbook wordt bijgewerkt (WorkbookService)
8. Je ziet het resultaat op het dashboard
```

### Belangrijkste bestanden (onthoud deze)

| Bestand | Wat het doet | Wanneer je het nodig hebt |
|---|---|---|
| `app/Models/User.php` | Gebruiker gegevens | Als je iets met gebruikers doet |
| `app/Models/TimeEntry.php` | Uren registratie | Als je iets met uren doet |
| `app/Enums/Role.php` | Rollen (admin/gebruiker/student) | Als je toegangscontrole aanpast |
| `app/Helpers/DurationHelper.php` | Tijd formattering | Als je tijd-formaten aanpast |
| `app/Services/WorkbookService.php` | Excel werkblad | Als Excel niet werkt |
| `app/Services/ExportService.php` | Excel/CSV export | Als export niet werkt |
| `app/Services/TimeEntrySyncService.php` | Excel import | Als synchronisatie niet werkt |
| `app/Policies/TimeEntryPolicy.php` | Wie mag wat met uren | Als iemand geen toegang heeft |
| `routes/web.php` | URL routes | Als een link niet werkt |

---

## 2. Hoe de app start (de magie achter localhost:8000)

Als je `docker-compose up --build` uitvoert, gebeurt er heel veel achter de schermen. Hier leggen we precies uit wat er gebeurt, stap voor stap.

### Stap 1: Docker start de containers

```
docker-compose up --build
        ↓
Docker leest docker-compose.yml
        ↓
Twee containers worden gestart:
  1. mysql  → een MySQL database
  2. app    → de Laravel applicatie (PHP 8.4-fpm)
```

De `docker-entrypoint.sh` wordt uitgevoerd voordat de app start:
```bash
composer install --no-interaction --optimize-autoloader
chown -R www-data:www-data storage bootstrap/cache
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
```

### Stap 2: PHP Built-in Server

De app draait op `php artisan serve`. Dit is **geen** Apache of nginx — het is een ingebouwde PHP-server die ideaal is voor development.

```
Gebruiker opent http://localhost:8000
        ↓
PHP Built-in Server ontvangt het verzoek
        ↓
Verwijst naar public/index.php (het entry point)
```

### Stap 3: Het entry point (`public/index.php`)

```php
// public/index.php — beknopt
require __DIR__.'/../vendor/autoload.php';  // Laad alle PHP bestanden
$app = require_once __DIR__.'/../bootstrap/app.php';  // Maak de Laravel app aan
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);  // Verwerk het verzoek
$response->send();  // Stuur het antwoord terug
```

Dit is de HTML-entree van de hele applicatie. Elk verzoek dat binnenkomt, gaat hierdoor.

### Stap 4: De Laravel App (`bootstrap/app.php`)

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',      // Handmatige routes
        commands: __DIR__.'/../routes/console.php',
        health: '/up',                           // Gezondheidscheck
    )
    ->withMiddleware(...)
    ->withExceptions(...)
    ->create();
```

**Wat hier gebeurt:**
- Laravel wordt geconfigureerd met basisinstellingen
- Routes worden geladen uit `routes/web.php`
- Middleware wordt ingesteld (proxy-trust)
- Foutafhandeling wordt geconfigureerd (404 → redirect naar dashboard)

### Stap 5: Filament panel wordt geladen

Naast `routes/web.php` laadt Laravel ook de **Panel Providers** — dit zijn service providers die Filament-panels definiëren. De belangrijkste is `AdminPanelProvider`:

```php
// app/Providers/Filament/AdminPanelProvider.php
$panel
    ->id('admin')
    ->path('dashboard')    // Alle URLs beginnen met /dashboard
    ->login()              // Login pagina staat op /dashboard/login
    ->discoverResources(in: app_path('Filament/Admin/Resources'))
    ->discoverPages(in: app_path('Filament/Admin/Pages'))
```

**Dit is waar de magie gebeurt:** Filament scant automatisch de `Filament/Admin/Resources` en `Filament/Admin/Pages` mappen, en maakt daar routes voor aan. Daarover meer in [sectie 10: Filament Routing](#10-filament-routing-hoe-paginas-automatisch-worden-gevonden).

### Stap 6: Het verzoek wordt verwerkt

```
Gebruiker opent http://localhost:8000/dashboard
        ↓
1. public/index.php ontvangt het verzoek
2. Laravel matching: welke route past?
3. Geen match in web.php → Filament zoekt in z'n panel
4. Match: /dashboard = het Admin panel
5. Controle: is de gebruiker ingelogd?
   - Nee → redirect naar /dashboard/login
   - Ja → door naar stap 6
6. Filament vindt de juiste pagina (Dashboard, Resource, etc.)
7. Livewire verwerkt de actie (als het een AJAX-verzoek is)
8. Blade rendert de HTML
9. CSS + JavaScript worden toegevoegd
10. Antwoord wordt teruggestuurd naar de browser
```

### Het volledige plaatje

```
┌─────────────────────────────────────────────────────────┐
│  Browser (http://localhost:8000)                        │
│  ↓                                                      │
│  PHP Built-in Server (port 8000)                        │
│  ↓                                                      │
│  public/index.php (entry point)                         │
│  ↓                                                      │
│  bootstrap/app.php (Laravel config)                     │
│  ↓                                                      │
│  AdminPanelProvider (Filament panel)                    │
│  ↓                                                      │
│  routes/web.php  ──of──  Filament auto-discovery        │
│  ↓                                                      │
│  Controller / Filament Page / Resource                  │
│  ↓                                                      │
│  Model → Database                                       │
│  ↓                                                      │
│  Response → Browser                                     │
└─────────────────────────────────────────────────────────┘
```

### Waarom werkt localhost:8000?

- De `docker-compose.yml` zet de app-container op poort 8000
- Binnen de container draait `php artisan serve --host=0.0.0.0 --port=8000`
- Docker maakt deze poort beschikbaar op je host-machine
- Dus `localhost:8000` = de PHP-server binnen de container

### Waarom localhost:3307 voor MySQL?

- De MySQL-container draait op de standaard poort 3306 **binnen Docker**
- Docker mapt dit naar poort 3307 op je host-machine
- Zo voorkom je conflicten met eventueel al draaiende MySQL op je computer

---

## 3. Aan de slag: lokaal ontwikkelen

### Docker: je beste vriend

We gebruiken Docker om de app lokaal te draaien. Docker maakt een "container" — een soort mini-computer die alles bevat wat de app nodig heeft.

**Belangrijk:** De `docker-local/` map hoef je NOOIT aan te passen. Die werkt zoals het is. Vertrouw erop.

### Stap voor stap

```bash
# 1. Ga naar de docker map
cd docker-local

# 2. Start de containers
docker-compose up --build

# 3. Wacht tot alles gestart is (duurt een paar minuten de eerste keer)
# Je ziet iets als:
# app_1  | INFO  Server running on [http://0.0.0.0:8000].
# mysql_1 | ready for connections
```

Nu kun je de app openen in je browser: **http://localhost:8000**

### Inloggen

Na de eerste start moet je de database vullen:

```bash
# Open een nieuw terminal-venster (de docker-terminal draait nog)
# Voer dit uit in de project-root (niet in docker-local/):

docker exec -it stage_urenregistratie_app php artisan migrate --seed
```

Nu kun je inloggen met:
- **Email:** admin@admin.com
- **Wachtwoord:** Admin1!23

### De Docker structuur uitgelegd

```
docker-local/
├── docker-compose.yml     # Bepaalt welke containers starten
├── Dockerfile             # Hoe de PHP-container wordt gebouwd
├── docker-entrypoint.sh   # Wat er gebeurt bij het starten
└── php.ini                # PHP instellingen
```

**Wat draait er?**
| Container | Wat is het? | Poort |
|---|---|---|
| `app` | De Laravel applicatie | 8000 |
| `mysql` | De MySQL database | 3307 |

**Waarom poort 3307 en niet 3306?** Omdat je computer misschien al een MySQL draait op 3306. Met 3307 voorkom je conflicten.

### De .env file uitleg

De `.env` bevat instellingen die je NIET in Git wilt zetten (wachtwoorden, sleutels, etc.). Dit is wat je in je `.env` moet hebben voor lokaal:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=mysql              # Dit is de naam van de Docker container
DB_PORT=3306               # Binnen de container is het 3306
DB_DATABASE=stage_urenregistratie_app
DB_USERNAME=stage_urenregistratie_app
DB_PASSWORD=stage_urenregistratie_app

# App
APP_NAME="Stage Urenregistratie"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**Let op:** `DB_HOST=mysql` is de Docker-container naam, niet `127.0.0.1`. Binnen Docker weten containers elkaar te vinden op hun naam.

### Veelgemaakte fouten bij Docker

| Fout | Oplossing |
|---|---|
| `Connection refused` | Docker draait nog niet. Voer `docker-compose up --build` uit |
| `Access denied for user` | `.env` klopt niet. Check of de waarden overeenkomen met `docker-compose.yml` |
| `port already in use` | Iets anders draait op poort 8000. Verander de poort in `docker-compose.yml` |
| Container crasht direct | Check de logs: `docker-compose logs app` |

---

## 4. De database: wat staat waar?

### De users tabel

Dit is waar alle gebruikers worden opgeslagen.

```
users
├── id                  # Uniek nummer (automatisch)
├── name                # Naam van de gebruiker
├── email               # Emailadres (uniek)
├── password            # Gehashed wachtwoord (NOOIT leesbaar!)
├── role                # 'admin', 'gebruiker', of 'student'
├── theme_mode          # 'dark', 'light', of 'system'
├── accent_color        # Kleur voorkeur (bijv. 'blue')
├── workbook_linked_at  # Wanneer Excel werd gekoppeld
├── target_hours        # Hoeveel stage-uren je moet lopen
├── created_at          # Wanneer aangemaakt
└── updated_at          # Wanneer voor het laatst bijgewerkt
```

### De time_entries tabel

Dit is waar alle uren worden opgeslagen.

```
time_entries
├── id                  # Uniek nummer
├── user_id             # Naar welke gebruiker deze hoort
├── date                # Datum (bijv. 2026-08-26)
├── start_time          # Begintijd (bijv. 09:00)
├── end_time            # Eindtijd (bijv. 17:00)
├── break_minutes       # Pauze in minuten (bijv. 30)
├── description         # Wat je hebt gedaan
├── created_at          # Wanneer aangemaakt
└── updated_at          # Wanneer bijgewerkt
```

### Hoe de tabellen aan elkaar gekoppeld zijn

```
┌─────────────┐         ┌─────────────────┐
│    users     │         │   time_entries   │
├─────────────┤    1:N   ├─────────────────┤
│ id (PK)     │◄────────│ user_id (FK)     │
│ name        │         │ date             │
│ email       │         │ start_time       │
│ role        │         │ end_time         │
│ ...         │         │ break_minutes    │
└─────────────┘         │ description      │
                        └─────────────────┘
```

**Wat betekent 1:N?** Eén gebruiker heeft veel uren-registraties. Een uur-registratie hoort bij één gebruiker.

### Hoe je de database bekijkt

```bash
# Open de Laravel tinker (een interactieve PHP terminal)
php artisan tinker

# Bekijk alle gebruikers
App\Models\User::all();

# Bekijk alle uren van een gebruiker
$user = App\Models\User::first();
$user->timeEntries;

# Bekijk het totaal aantal uren
$user->totalLoggedHoursFormatted();
```

---

## 5. Rollen: wie mag wat?

### De drie rollen

| Rol | Waarde in database | Wat het betekent |
|---|---|---|
| Admin | `'admin'` | Kan alles. Er is maar 1 admin. |
| Gebruiker | `'gebruiker'` | Kan alleen zijn eigen uren beheren. |
| Student | `'student'` | Zelfde als gebruiker (voor de toekomst). |

### Hoe rollen werken (technisch)

De rollen staan in een **Enum** (`app/Enums/Role.php`). Een Enum is een verzameling van vaste waarden. Het ziet er zo uit:

```php
enum Role: string
{
    case Student = 'student';
    case Gebruiker = 'gebruiker';
    case Admin = 'admin';
}
```

**Waarom een Enum en niet gewoon string?**
Omdat je dan fouten krijgt als je een verkeerde rol typt. Als je `Role::Admin` typt, weet PHP zeker dat het goed is. Als je `'admin'` typt, kun je een tikfout maken (`'admi'`) en dat merk je pas later.

### Hoe check je of iemand admin is?

```php
// In het User model:
public function isAdmin(): bool
{
    return $this->role === Role::Admin;
}

// Gebruik ergens:
if (auth()->user()->isAdmin()) {
    // De gebruiker is admin
}
```

### Hoe werkt toegangscontrole (Policies)?

Policies zijn regels die bepalen wie wat mag. Ze staan in `app/Policies/`.

**Voorbeeld: TimeEntryPolicy**

```php
public function view(User $user, TimeEntry $timeEntry): bool
{
    // Iedereen mag alleen zijn eigen uren zien
    return $timeEntry->user_id === $user->id;
}
```

**Hoe dit werkt in de praktijk:**
1. Gebruiker klikt op "Tijdregistraties"
2. Laravel roept `TimeEntryPolicy::viewAny()` aan
3. Die geeft `true` terug (iedereen mag de lijst zien)
4. Maar de query wordt gefilterd: iedereen ziet alleen z'n eigen uren

Dit gebeurt in `TimeEntryResource`:
```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    // Iedereen, inclusief admin, ziet alleen z'n eigen uren
    $query->where('user_id', auth()->id());

    return $query;
}
```

### Overzicht: wie mag wat?

| Actie | Admin | Gebruiker | Student |
|---|---|---|---|
| Eigen uren bekijken | ✅ | ✅ | ✅ |
| Eigen uren aanmaken | ✅ | ✅ | ✅ |
| Eigen uren bewerken | ✅ | ✅ | ✅ |
| Eigen uren verwijderen | ✅ | ✅ | ✅ |
| Andermans uren bekijken | ❌ | ❌ | ❌ |
| Andermans uren bewerken | ❌ | ❌ | ❌ |
| Gebruikers beheren | ✅ | ❌ | ❌ |
| Excel export | ✅ | ✅ | ✅ |
| Excel sync | ✅ | ✅ | ✅ |
| Workbook koppelen | ✅ | ✅ | ✅ |

**Let op:** De admin-rol is bedoeld om nieuwe gebruikers aan te maken in het systeem. De admin ziet net als iedereen alleen z'n eigen uren.

---

## 6. De Models: User en TimeEntry

### User Model (`app/Models/User.php`)

Het User Model is het hart van de gebruikers. Het bepaalt wat een gebruiker kan en welke gegevens hij heeft.

**De belangrijkste dingen:**

```php
class User extends Authenticatable implements FilamentUser
{
    // Welke velden mogen worden aangepast
    protected $fillable = [
        'name', 'email', 'password', 'role',
        'theme_mode', 'accent_color', 'workbook_linked_at', 'target_hours'
    ];

    // Hoe velden worden opgeslagen in de database
    protected function casts(): array
    {
        return [
            'role' => Role::class,              // String → Enum
            'email_verified_at' => 'datetime',  // String → DateTime
            'password' => 'hashed',             // Wachtwoord wordt automatisch gehashed
            'workbook_linked_at' => 'datetime',
        ];
    }
}
```

**Wat zijn casts?** Casts vertellen Laravel hoe een waarde moet worden omgezet. Als je `$user->role` opvraagt, krijg je niet de string `'admin'`, maar het Enum-object `Role::Admin`. Handig, want dan kun je `$user->isAdmin()` gebruiken.

**Belangrijke methods:**

```php
// Check of gebruiker admin is
public function isAdmin(): bool
{
    return $this->role === Role::Admin;
}

// Check of Excel werkblad gekoppeld is
public function hasLinkedWorkbook(): bool
{
    return $this->workbook_linked_at !== null;
}

// Haal de totale uren op (in minuten)
public function totalLoggedMinutes(): int
{
    return (int) $this->timeEntries->sum('duration');
}

// Formatted als "HH:MM" (gebruikt DurationHelper)
public function totalLoggedHoursFormatted(): string
{
    return DurationHelper::formatMinutes($this->totalLoggedMinutes());
}

// Haal de primaire kleur op voor het Filament panel
public function primaryColor(): array
{
    return self::ACCENT_COLORS[$this->accent_color] ?? Color::Cyan;
}

// Haal de export kleuren op voor Excel headers
public function exportColors(): array
{
    $colors = [
        'red' => ['bg' => 'FF4444', 'font' => 'FFFFFF'],
        'blue' => ['bg' => '3B82F6', 'font' => 'FFFFFF'],
        // ... alle kleuren
    ];
    return $colors[$this->accent_color] ?? $colors['cyan'];
}
```

**Relaties:**

```php
// Een gebruiker heeft veel tijd-registraties
public function timeEntries(): HasMany
{
    return $this->hasMany(TimeEntry::class);
}
```

### TimeEntry Model (`app/Models/TimeEntry.php`)

Dit model representeert één uur-registratie.

```php
class TimeEntry extends Model
{
    protected $fillable = [
        'user_id', 'date', 'start_time', 'end_time',
        'break_minutes', 'description'
    ];

    protected $casts = [
        'date' => 'date',              // String → Carbon date
        'start_time' => 'datetime:H:i', // String → Carbon time
        'end_time' => 'datetime:H:i',
        'break_minutes' => 'integer',
    ];
}
```

**De duration attribute (het slimste stukje):**

```php
public function duration(): Attribute
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

**Waarom 1440?** Omdat er 24 * 60 = 1440 minuten in een dag zitten. Als je om 22:00 begint en om 06:00 eindigt, geeft `diffInMinutes` een negatief getal. Door 1440 op te tellen krijg je het juiste aantal uren (8 uur = 480 minuten).

### Hoe gebruik je de models?

```bash
# In de tinker:
php artisan tinker

# Maak een nieuwe gebruiker
$user = new App\Models\User;
$user->name = "Test";
$user->email = "test@test.nl";
$user->password = bcrypt("wachtwoord123");
$user->role = App\Enums\Role::Gebruiker;
$user->save();

# Maak een uur-registratie aan
$entry = new App\Models\TimeEntry;
$entry->user_id = $user->id;
$entry->date = "2026-08-26";
$entry->start_time = "09:00";
$entry->end_time = "17:00";
$entry->break_minutes = 30;
$entry->description = "Aan de app gewerkt";
$entry->save();

# Bekijk de duur
$entry->duration;  // 450 (7:30 uur)

# Bekijk alle uren van de gebruiker
$user->timeEntries;

# Bekijk het totaal
$user->totalLoggedHoursFormatted();  // "07:30"
```

---

## 7. Helpers: DurationHelper

### DurationHelper (`app/Helpers/DurationHelper.php`)

Een klein hulpje dat overal in de app wordt gebruikt om tijd-formatten te doen. Hierdoor hoef je niet steeds `sprintf('%02d:%02d', ...)` te typen.

```php
class DurationHelper
{
    // Zet minuten om naar "HH:MM" formaat
    public static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    // Zet seconden om naar "HH:MM" formaat (eerst omrekenen naar minuten)
    public static function formatSeconds(int $seconds): string
    {
        return self::formatMinutes((int) round($seconds / 60));
    }
}
```

**Voorbeelden:**

```php
DurationHelper::formatMinutes(450);   // "07:30"
DurationHelper::formatMinutes(60);    // "01:00"
DurationHelper::formatMinutes(0);     // "00:00"
DurationHelper::formatMinutes(1440);  // "24:00"
```

**Waar wordt dit gebruikt?**
- `Dashboard.php` — voor het tonen van totalen per dag en per week
- `User.php` — voor `totalLoggedHoursFormatted()`
- `ExportService.php` — voor de "Duur" kolom in Excel exports
- `TimeEntryExporter.php` — voor de export kolommen

---

## 8. Services: waar de magie gebeurt

Services zijn PHP-klassen die de "slimme" logica bevatten. Ze doen het echte werk, terwijl de Models alleen data opslaan.

### WorkbookService (`app/Services/WorkbookService.php`)

Dit is verantwoordelijk voor het Excel-werkblad per gebruiker. Het genereert, koppelt, ontkoppelt en verversd het bestand.

**Hoe het werkt:**
1. Gebruiker klikt op "Excel koppelen"
2. `WorkbookService::link()` wordt aangeroepen
3. Het zet `workbook_linked_at` in de database
4. Het genereert een .xlsx bestand met alle uren
5. Bij elke wijziging wordt het bestand automatisch ververst

**De belangrijkste methods:**

```php
// Koppel een werkblad
$workbookService->link($user);
// → Zet workbook_linked_at = now()
// → Genereert het Excel-bestand

// Ontkoppel een werkblad
$workbookService->unlink($user);
// → Zet workbook_linked_at = null
// → Verwijdert het Excel-bestand

// Ververs het werkblad (alleen als gekoppeld)
$workbookService->refresh($user);

// Genereer het Excel-bestand
$workbookService->generate($user);
// → Maakt een .xlsx met alle uren + totaal
```

**Auto-refresh (het slimme):**

In `AppServiceProvider` wordt dit geregeld:

```php
TimeEntry::saved(fn (TimeEntry $entry) => $workbooks->refreshQuietly($entry->user));
TimeEntry::deleted(fn (TimeEntry $entry) => $workbooks->refreshQuietly($entry->user));
```

Dit betekent: zodra een uur wordt opgeslagen of verwijderd, wordt het Excel-bestand automatisch ververst. Handig!

**Maar wat als je 100 uren tegelijk importeert?** Dan wil je niet 100 keer het bestand verversen. Daarom is er `withoutAutoRefresh()`:

```php
WorkbookService::withoutAutoRefresh(function () use ($user) {
    // Hier worden 100 uren aangemaakt
    // Het bestand wordt NIET ververst
});

// Na de callback wordt het bestand één keer ververst
$workbookService->refresh($user);
```

### TimeEntrySyncService (`app/Services/TimeEntrySyncService.php`)

Dit is het meest complexe stuk code in het project. Het importeert uren uit Excel of CSV.

**Hoe het werkt:**

```
1. Lees het bestand (Excel of CSV)
2. Detecteer de kolommen (datum, begintijd, eindtijd, etc.)
3. Voor elke rij:
   a. Parse de datum en tijden
   b. Valideer de gegevens
   c. Check of de entry al bestaat (op datum + begintijd)
   d. Bij bestaande: update de gegevens
   e. Bij nieuw: maak een nieuwe aan
4. Optioneel: verwijder entries die niet in het bestand staan
5. Ververs het Excel-workbook
```

**Kolom-detectie (het slimste deel):**

De service herkent zowel Nederlandse als Engelse kolomnamen:

```php
private const HEADER_ALIASES = [
    'date' => ['datum', 'date', 'dag', 'werkdag'],
    'start_time' => ['begintijd', 'begin', 'starttijd', 'start', 'van', 'vanaf'],
    'end_time' => ['eindtijd', 'eind', 'einde', 'end', 'tot', 'totmet', 'tm'],
    'break_minutes' => ['pauze', 'pauzeminuten', 'break'],
    'description' => ['beschrijving', 'omschrijving', 'description', 'werkzaamheden'],
];
```

Dus je kunt een Excel-bestand hebben met kopregels als "Datum", "Begin", "Eind", "Pauze" — en de service vindt ze automatisch.

**Voorbeeld van een Excel-bestand dat werkt:**

| Datum | Begin | Eind | Pauze | Omschrijving |
|---|---|---|---|---|
| 26-08-2026 | 09:00 | 17:00 | 30 | Aan de app gewerkt |
| 27-08-2026 | 08:30 | 16:30 | 45 | Meetings |

**Maar ook dit werkt:**

| Date | Start | End | Break | Description |
|---|---|---|---|---|
| 2026-08-26 | 9 | 16.5 | 0 | English works too |

De service begrijpt verschillende formaten:
- Datum: `26-08-2026`, `2026-08-26`, `26/08/2026`
- Tijd: `09:00`, `9`, `09.00`, `9,5` (float = 09:30)

### SyncResult (`app/Services/SyncResult.php`)

Dit is een simpel object dat bijhoudt wat er is gebeurd tijdens een sync:

```php
class SyncResult
{
    public int $created = 0;    // Nieuwe uren aangemaakt
    public int $updated = 0;    // Bestaande uren bijgewerkt
    public int $deleted = 0;    // uren verwijderd
    public int $skipped = 0;    // Rijen overgeslagen (fouten)
    public array $errors = [];  // Foutmeldingen

    public function summary(): string
    {
        return "Aangemaakt: {$this->created} · Bijgewerkt: {$this->updated} · ..."
    }
}
```

### ExportService (`app/Services/ExportService.php`)

Dit is verantwoordelijk voor het exporteren van uren naar Excel (.xlsx) of CSV. Het wordt gebruikt door het Dashboard voor de "Exporteer week" en "Exporteer alles" knoppen.

**Hoe het werkt:**

```php
class ExportService
{
    // Haal alle uren op voor een specifieke week
    public function getEntriesForWeek(User $user, string $weekStart): Collection

    // Haal alle uren op van een gebruiker
    public function getAllEntries(User $user): Collection

    // Exporteer naar CSV (streamed)
    public function exportToCsv(Collection $entries, string $filename): StreamedResponse

    // Exporteer naar XLSX (streamed) met gekleurde headers
    public function exportToXlsx(
        Collection $entries,
        string $filename,
        ?array $accentColor = null,
    ): StreamedResponse
}
```

**Voorbeeld van gebruik in Dashboard:**

```php
public function exportWeek(): StreamedResponse
{
    $entries = app(ExportService::class)->getEntriesForWeek(auth()->user(), $this->weekStart);
    $start = Carbon::parse($this->weekStart);

    return app(ExportService::class)->exportToXlsx(
        $entries,
        'uren_week_' . $start->format('Y-m-d') . '.xlsx',
        auth()->user()->exportColors(),  // Gebruik de kleur van de gebruiker
    );
}
```

**Kolommen die worden geëxporteerd:**
| Kolom | Formaat |
|---|---|
| Weeknummer | isoWeek() |
| Datum | d-m-Y |
| Begintijd | H:i |
| Eindtijd | H:i |
| Pauze (min) | number |
| Duur | HH:MM (via DurationHelper) |
| Beschrijving | text |

**Waarom streamed?** Omdat je bestanden groot kunnen worden. Met streaming wordt het bestand direct naar de browser gestuurd, zonder dat alles in het geheugen wordt geladen.

---

## 9. Het Filament Admin Panel

### Wat is Filament?

Filament is een pakket dat een mooi admin-panel maakt voor Laravel. Het bespaart je enorm veel tijd, want je hoeft geen HTML, CSS of JavaScript te schrijven voor de interface.

**Hoe het werkt:**
1. Je definieert een **Resource** (bijv. TimeEntryResource)
2. Filament maakt automatisch een lijst, formulier, en bewerk-pagina
3. Je kunt het aanpassen door methodes te overschrijven

### De Resources

Er zijn twee Resources:

#### TimeEntryResource (de uren)

| Pagina | URL | Wat het doet |
|---|---|---|
| Lijst | `/dashboard/time-entries` | Overzicht van alle uren |
| Aanmaken | `/dashboard/time-entries/create` | Nieuw uur toevoegen |
| Bewerken | `/dashboard/time-entries/{id}/edit` | Bestaand uur aanpassen |

**De query filtering (belangrijk!):**

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    // Iedereen, inclusief admin, ziet alleen z'n eigen uren
    $query->where('user_id', auth()->id());

    return $query;
}
```

Dit zorgt ervoor dat iedereen, inclusief de admin, alleen z'n eigen uren ziet. De admin-rol is bedoeld om nieuwe gebruikers aan te maken, niet om andermans uren in te zien.

#### UserResource (de gebruikers)

| Pagina | URL | Wat het doet |
|---|---|---|
| Lijst | `/dashboard/users` | Overzicht van alle gebruikers |
| Aanmaken | `/dashboard/users/create` | Nieuwe gebruiker toevoegen |
| Bewerken | `/dashboard/users/{id}/edit` | Gebruiker aanpassen |

**Toegang:** Alleen admins. Dit wordt geregeld in:

```php
public static function canAccess(): bool
{
    return auth()->user()?->isAdmin() ?? false;
}
```

### De Pagina's

#### Dashboard

Het dashboard toont:
1. Een voortgangsbalk (hoeveel uren je hebt gelopen)
2. Weeknavigatie (vorige/huidige/volgende week)
3. Een tabel met de uren van die week
4. Export-knoppen (XLSX export via ExportService)

**De weeknavigatie werkt met Livewire:**

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

Als je op "Vorige week" klikt, verandert `$weekStart` en wordt de pagina live ververst.

#### Settings

Hier kun je:
- Je naam en e-mail aanpassen
- Je wachtwoord wijzigen
- Het thema kiezen (donker/licht/systeem)
- Je accentkleur kiezen

#### EditProfile

Hier kun je het totale aantal stage-uren instellen (`target_hours`). Dit wordt gebruikt voor de voortgangsbalk.

### De Actions (actie-knoppen)

#### WorkbookActions

Deze regelt alles rond het Excel-workbook:

```php
// Koppelen
WorkbookActions::linkAction()
// → Toont een modal
// → Gebruiker klikt "Koppelen en genereren"
// → WorkbookService::link() wordt aangeroepen

// Downloaden
WorkbookActions::downloadAction()
// → Link naar workbook.download route
// → Opent in nieuw tabblad

// Ontkoppelen
WorkbookActions::unlinkAction()
// → Bevestigings-modal
// → WorkbookService::unlink() wordt aangeroepen
```

#### SyncTimeEntriesAction

Deze regelt de Excel-import:

```php
SyncTimeEntriesAction::make()
// → Toont een modal met:
//   - FileUpload (Excel of CSV)
//   - Toggle "Verwijder ontbrekende entries"
//   - Toggle "Vervang alle bestaande entries"
// → Na upload: TimeEntrySyncService::syncFromFile()
// → Toont resultaat
```

### Filament Exports

#### TimeEntryExporter (`app/Filament/Exports/TimeEntryExporter.php`)

Dit is een Filament Exporter die wordt gebruikt door de `ExportAction` op de `ListTimeEntries` pagina. Het maakt gebruik van het ingebouwde Filament export-systeem.

```php
class TimeEntryExporter extends Exporter
{
    protected static ?string $model = TimeEntry::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('date')
                ->label('Datum')
                ->formatStateUsing(fn ($state) => $state->format('d-m-Y')),
            ExportColumn::make('start_time')
                ->label('Begintijd')
                ->formatStateUsing(fn ($state) => $state->format('H:i')),
            ExportColumn::make('end_time')
                ->label('Eindtijd')
                ->formatStateUsing(fn ($state) => $state->format('H:i')),
            ExportColumn::make('break_minutes')
                ->label('Pauze (minuten)'),
            ExportColumn::make('description')
                ->label('Beschrijving'),
            ExportColumn::make('duration')
                ->label('Duur')
                ->formatStateUsing(fn ($state) => DurationHelper::formatMinutes($state)),
        ];
    }
}
```

**Wanneer wordt dit gebruikt?** Op de `ListTimeEntries` pagina is er een "Exporteren" knop in de header. Als je daarop klikt, wordt dit Exporter gebruikt om een Excel-bestand te genereren.

---

## 10. Filament Routing: hoe pagina's automatisch worden gevonden

Een van de krachtigste features van Filament is **auto-discovery**. Je hoeft geen routes te schrijven — Filament vindt je Resources en Pages automatisch. Maar hoe werkt dat?

### De AdminPanelProvider

In `app/Providers/Filament/AdminPanelProvider.php` staat:

```php
$panel
    ->id('admin')
    ->path('dashboard')
    ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
    ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
    ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
    ->pages([
        Dashboard::class,
        Settings::class,
    ])
    ->widgets([
        AccountWidget::class,
    ])
```

**Wat doet `discoverResources()`?**
Het scant de map `app/Filament/Admin/Resources` en vindt daar alle Resource-klassen. Elke Resource wordt automatisch omgezet naar drie routes:

| Resource | Pagina | Automatische URL |
|---|---|---|
| `TimeEntryResource` | Lijst | `/dashboard/time-entries` |
| | Aanmaken | `/dashboard/time-entries/create` |
| | Bewerken | `/dashboard/time-entries/{id}/edit` |
| `UserResource` | Lijst | `/dashboard/users` |
| | Aanmaken | `/dashboard/users/create` |
| | Bewerken | `/dashboard/users/{id}/edit` |

**Wat doet `discoverPages()`?**
Het scant de map `app/Filament/Admin/Pages` en vindt daar alle Page-klassen. Elke Page wordt omgezet naar één route:

| Page | Automatische URL |
|---|---|
| `Dashboard` | `/dashboard` |
| `Settings` | `/dashboard/settings` |
| `EditProfile` | `/dashboard/edit-profile` |

### Hoe Filament de URL bepaalt

Filament gebruikt de **class name** om de URL te bepalen:

```
TimeEntryResource → /dashboard/time-entries
UserResource      → /dashboard/users
Dashboard         → /dashboard
Settings          → /dashboard/settings
```

**De regels:**
1. Het pad begint met het panel-pad (`/dashboard`)
2. Dan de Resource/Page naam in **kebab-case** (met streepjes)
3. `Resource` aan het einde wordt weggehaald
4. `Page` aan het einde wordt weggehaald

**Voorbeelden:**
- `TimeEntryResource` → verwijder `Resource` → `TimeEntry` → kebab-case → `time-entries` → `/dashboard/time-entries`
- `ListTimeEntries` → verwijder `List` → `TimeEntries` → kebab-case → `time-entries` → `/dashboard/time-entries` (dezelfde URL!)
- `CreateTimeEntry` → verwijder `Create` → `TimeEntry` → kebab-case → `time-entry` → `/dashboard/time-entry/create`

### Hoe het matcht met het verzoek

```
Gebruiker opent /dashboard/time-entries
        ↓
Laravel zoekt een route die past
        ↓
Geen match in routes/web.php
        ↓
Filament panel gezocht (id: admin)
        ↓
Resource gevonden: TimeEntryResource
        ↓
Pagina: ListTimeEntries (lijst pagina)
        ↓
Gerenderd!
```

### De navigatie (sidebar)

Filament maakt automatisch een sidebar-menu aan op basis van de Resources en Pages:

```php
// In TimeEntryResource:
protected static ?string $navigationLabel = 'Tijdregistraties';
protected static ?string $navigationIcon = 'heroicon-m-clock';

// In UserResource:
protected static ?string $navigationLabel = 'Gebruikers';
protected static ?string $navigationIcon = 'heroicon-m-users';
```

Dit bepaalt wat er in de sidebar staat. De volgorde wordt bepaald door de `navigationSort` eigenschap.

### Handmatige routes vs Filament routes

| Type | Waar gedefinieerd | Voorbeeld |
|---|---|---|
| Handmatig | `routes/web.php` | `Route::get('/', ...)` |
| Filament Resources | Auto-discovery | `TimeEntryResource` → `/dashboard/time-entries` |
| Filament Pages | Auto-discovery | `Dashboard` → `/dashboard` |
| Filament Widgets | Auto-discovery | AccountWidget (geen route, wordt inline geladen) |

### Waarom werkt de redirect van `/` naar `/dashboard`?

In `routes/web.php`:
```php
Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});
```

Dit stuurt iedereen die naar de homepage gaat door naar het dashboard. De route `filament.admin.pages.dashboard` wordt automatisch aangemaakt door Filament.

---

## 11. Exporteren en Importeren

### Excel Export (.xlsx)

Er zijn twee manieren om uren te exporteren:

#### 1. Via het Dashboard (ExportService)

Op het dashboard zijn er twee knoppen:
- **"Exporteer week"** — exporteert de huidige week naar XLSX
- **"Exporteer alles"** — exporteert alle uren naar XLSX

Dit gebruikt `ExportService`:

```php
public function exportWeek(): StreamedResponse
{
    $entries = app(ExportService::class)->getEntriesForWeek(auth()->user(), $this->weekStart);
    $start = Carbon::parse($this->weekStart);

    return app(ExportService::class)->exportToXlsx(
        $entries,
        'uren_week_' . $start->format('Y-m-d') . '.xlsx',
        auth()->user()->exportColors(),
    );
}
```

#### 2. Via de Tijdregistraties lijst (Filament Exporter)

Op de `ListTimeEntries` pagina is er een "Exporteren" knop. Dit gebruikt `TimeEntryExporter` (Filament's ingebouwde export-systeem):

```php
ExportAction::make()
    ->exporter(TimeEntryExporter::class)
    ->formats([ExportFormat::Xlsx])
    ->label('Exporteren')
    ->icon('heroicon-o-arrow-down-tray')
    ->color('danger'),
```

**Kolommen die worden geëxporteerd (beide methodes):**
| Kolom | Formaat |
|---|---|
| Datum | d-m-Y |
| Begintijd | H:i |
| Eindtijd | H:i |
| Pauze (minuten) | number |
| Beschrijving | text |
| Duur | HH:MM (berekend via DurationHelper) |

### CSV Export

CSV export is beschikbaar via `ExportService::exportToCsv()`, maar wordt momenteel niet actief gebruikt in de interface. De voorkeur gaat uit naar XLSX vanwege de betere opmaak.

### Excel Synchronisatie (Import)

Dit is de meest complexe feature. Zie [Services: TimeEntrySyncService](#8-services-waar-de-magie-gebeurt) voor de uitleg.

### Het Workbook

Het workbook is een persoonlijk Excel-bestand per gebruiker. Het wordt:
1. Aangemaakt als je op "Excel koppelen" klikt
2. Automatisch bijgewerkt bij elke wijziging
3. Opgeslagen in `storage/app/private/workbooks/{user_id}/stage-uren.xlsx`

**Belangrijk:** Op Railway (productie) is de opslag **vluchtig**. Dat betekent dat het bestand verdwijnt als de container wordt herstart. Maar dat is geen probleem, want het wordt automatisch opnieuw gegenereerd.

---

## 12. Deployen naar productie (Railway)

### Hoe het werkt

1. Je pusht naar GitHub
2. Railway ziet de push
3. Railway bouwt de app opnieuw
4. De app wordt herstart

### De configuratie

**railway.json:**
```json
{
    "deploy": {
        "startCommand": "php artisan config:clear && ... && php artisan migrate:fresh --force && php artisan serve ..."
    }
}
```

**nixpacks.toml:**
```toml
[phases.setup]
nixPkgs = ['...', 'php84Extensions.intl', 'php84Extensions.pdo_pgsql']

[phases.install]
cmds = ['composer install --no-dev ...', 'npm ci']
```

### Waarom PostgreSQL en niet MySQL?

Dit is een belangrijk punt. Op Railway kun je geen MySQL gebruiken vanwege een technisch probleem:

- Railway gebruikt MySQL 8
- MySQL 8 gebruikt `caching_sha2_password` voor authenticatie
- De PHP-build op Railway gebruikt `libmariadb-client`
- `libmariadb-client` begrijpt `caching_sha2_password` niet
- Resultaat: verbinding mislukt

PostgreSQL heeft dit probleem niet. En Laravel maakt niet uit welke database je gebruikt — de code is hetzelfde.

### Environment Variables

Deze variables moet je instellen in Railway:

| Variabele | Waarde | Waarom |
|---|---|---|
| `APP_ENV` | `production` | Schakelt debug-modus uit |
| `APP_DEBUG` | `false` | Verbergt foutmeldingen voor gebruikers |
| `DB_CONNECTION` | `pgsql` | Gebruik PostgreSQL |
| `DB_URL` | `${{Postgres.DATABASE_URL}}` | Verbind met de database |
| `SESSION_DRIVER` | `database` | Sessies opslaan in database |
| `QUEUE_CONNECTION` | `sync` | Geen queue worker nodig |

### Veelgemaakte fouten bij deployment

| Fout | Oplossing |
|---|---|
| `403 Forbidden` | `FilamentUser` contract ontbreekt in User model |
| `500 Internal Server Error` | MySQL-specifieke code in queries |
| `could not find driver` | `pdo_pgsql` extensie ontbreekt |
| `No application encryption key` | `APP_KEY` niet gezet |
| `Connection refused` | Database niet bereikbaar |

Zie `DEPLOY.md` voor een uitgebreider overzicht.

---

## 13. Testen schrijven en uitvoeren

### Hoe voer je testen uit?

```bash
# Alle tests
composer test

# Of via artisan
php artisan test

# Specifieke test
php artisan test --filter=WorkbookTest

# Met verbose output
php artisan test -v
```

### Hoe schrijf je een test?

Tests staan in `tests/Feature/`. We gebruiken **Pest PHP**, een test-framework dat makkelijker is dan PHPUnit.

**Voorbeeld: een simpele test**

```php
it('stuurt gasten van de homepage door naar de login', function () {
    $this->get('/')->assertRedirect('/admin/login');
});
```

Dit zegt: "Test of een gast (niet-ingelogde gebruiker) wordt doorgestuurd naar de login als hij naar de homepage gaat."

**Voorbeeld: test met een gebruiker**

```php
it('laat een gewone gebruiker alleen zijn eigen uren zien', function () {
    // Maak twee gebruikers
    $user = User::factory()->create();
    $ander = User::factory()->create();

    // Maak uren voor beide
    $eigen = TimeEntry::factory()->for($user)->create(['description' => 'Mijn uur']);
    $vremd = TimeEntry::factory()->for($ander)->create(['description' => 'Andermans uur']);

    // Login als de eerste gebruiker
    actingAs($user)
        ->get('/admin/time-entries')
        ->assertOk()
        ->assertSee('Mijn uur')           // Ziet eigen uur
        ->assertDontSee('Andermans uur'); // Ziet NIET andermans uur
});
```

**Voorbeeld: test een error**

```php
it('gooit een fout bij ontbrekende kolommen', function () {
    $user = User::factory()->create();
    $pad = sys_get_temp_dir().'/test.xlsx';

    schrijfXlsx($pad, [
        ['Foo', 'Bar'],  // Verkeerde kolommen
        ['1', '2'],
    ]);

    app(TimeEntrySyncService::class)->syncFromFile($user, $pad);
})->throws(RuntimeException::class, 'kopregel');
```

### De test suites

#### AuthAndAccessTest (8 tests)
Test of authenticatie en toegangscontrole werken.

#### TimeEntrySyncTest (7 tests)
Test of Excel-import werkt met verschillende formaten.

#### WorkbookTest (6 tests)
Test of het Excel-workbook correct wordt aangemaakt en bijgewerkt.

### Hoe schrijf je een nieuwe test?

1. Maak een bestand in `tests/Feature/`
2. Gebruik `it()` of `test()` om een test te definiëren
3. Gebruik `User::factory()->create()` om test-gebruikers te maken
4. Gebruik `actingAs($user)` om in te loggen
5. Gebruik assertions zoals `assertOk()`, `assertSee()`, `assertRedirect()`

```php
it('doet iets goeds', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/some-page')
        ->assertOk();
});
```

---

## 14. Als er iets misgaat: debugging gids

### Stap 1: Bekijk de logs

```bash
# Laravel logs
cat storage/logs/laravel.log

# Of gebruik Pail (live logs)
php artisan pail

# Docker logs
docker-compose logs app
docker-compose logs mysql
```

### Stap 2: Gebruik Tinker

```bash
php artisan tinker

# Test een query
App\Models\User::where('email', 'test@test.nl')->first();

# Test een relatie
$user = App\Models\User::first();
$user->timeEntries->count();

# Test een method
$user->isAdmin();
```

### Stap 3: Controleer de database

```bash
# Bekijk de tabellen
php artisan migrate:status

# Reset de database (LET OP: verwijdert alles!)
php artisan migrate:fresh --seed
```

### Stap 4: Controleer de routes

```bash
# Bekijk alle routes
php artisan route:list

# Zoek een specifieke route
php artisan route:list --name=workbook
```

### Stap 5: Controleer de configuratie

```bash
# Bekijk een config waarde
php artisan tinker
config('database.default');  // 'sqlite' of 'mysql'

# Controleer de env
env('DB_CONNECTION');
```

### Veelgemaakte fouten en oplossingen

| Foutmelding | Waarschijnlijke oorzaak | Oplossing |
|---|---|---|
| `Target class does not exist` | Route verwijst naar niet-bestaande class | Controleer `routes/web.php` |
| `Column not found` | Database kolom bestaat niet | Voer migratie uit: `php artisan migrate` |
| `403 Forbidden` | Geen toegang | Controleer Policy |
| `404 Not Found` | Route bestaat niet | Controleer `php artisan route:list` |
| `SQLSTATE connection refused` | Database niet bereikbaar | Controleer `.env` |
| `View not found` | Blade view bestaat niet | Controleer `resources/views/` |
| `Class not found` | Autoloader niet ververst | Voer `composer dump-autoload` uit |

### Waar vind ik wat?

| Probleem | Waar kijken |
|---|---|
| Gebruiker kan niet inloggen | `app/Providers/Filament/AdminPanelProvider.php` |
| Gebruiker ziet andermans uren | `app/Filament/Admin/Resources/TimeEntries/TimeEntryResource.php` (getEloquentQuery) — hoort niet te gebeuren, iedereen ziet alleen z'n eigen uren |
| Excel import werkt niet | `app/Services/TimeEntrySyncService.php` |
| Excel download werkt niet | `routes/web.php` (ontbreekt de route?) |
| Button doet niks | Browser console (F12) |
| Fout bij deployment | Railway logs |

### Bekende bugs

#### 1. Ontbrekende workbook.download route
**Status:** Opgelost ✅
**Locatie:** `routes/web.php`
**Fix:** Route toegevoegd:
```php
Route::get('/workbook/download', [WorkbookController::class, 'download'])
    ->name('workbook.download')
    ->middleware('auth');
```

---

## 15. Verantwoordelijkheden per rol

### Admin (1 gebruiker — jij!)

**Account beheer:**
- Andere gebruikers aanmaken
- Gebruikers bewerken (naam, email, rol)
- Gebruikers verwijderen (niet jezelf!)

**Eigen uren:**
- Aanmaken, bewerken, verwijderen
- Alleen z'n eigen uren zien (net als iedereen)

**Export & Sync:**
- Eigen uren exporteren (XLSX)
- Eigen uren synchroniseren (Excel)

**Systeem:**
- Enige die het "Gebruikers" menu ziet

### Gebruiker

**Eigen uren:**
- Aanmaken, bewerken, verwijderen

**Dashboard:**
- Weekoverzicht zien
- Voortgangsbalk zien

**Export & Sync:**
- Eigen uren exporteren (XLSX)
- Eigen uren synchroniseren (Excel)

**Workbook:**
- Persoonlijk Excel-bestand koppelen
- Bestand downloaden
- Koppeling verwijderen

**Instellingen:**
- Eigen account aanpassen
- Thema kiezen
- Accentkleur kiezen
- Stage-uren doel instellen

### Student
Zelfde als Gebruiker. Deze rol is voor de toekomst.

---

## 16. Alle bestanden op een rij

### Models
| Bestand | Wat het doet |
|---|---|
| `app/Models/User.php` | Gebruiker gegevens en methods |
| `app/Models/TimeEntry.php` | Uren registratie |

### Enums
| Bestand | Wat het doet |
|---|---|
| `app/Enums/Role.php` | Rollen: admin, gebruiker, student |

### Helpers
| Bestand | Wat het doet |
|---|---|
| `app/Helpers/DurationHelper.php` | Tijd formattering (minuten → HH:MM) |

### Services
| Bestand | Wat het doet |
|---|---|
| `app/Services/WorkbookService.php` | Excel werkblad beheer |
| `app/Services/TimeEntrySyncService.php` | Excel/CSV import |
| `app/Services/SyncResult.php` | Resultaat van een sync |
| `app/Services/ExportService.php` | Excel/CSV export |

### Filament Resources
| Bestand | Wat het doet |
|---|---|
| `app/Filament/Admin/Resources/TimeEntries/TimeEntryResource.php` | Uren CRUD |
| `app/Filament/Admin/Resources/TimeEntries/Schemas/TimeEntryForm.php` | Uren formulier |
| `app/Filament/Admin/Resources/TimeEntries/Tables/TimeEntriesTable.php` | Uren tabel |
| `app/Filament/Admin/Resources/TimeEntries/Pages/CreateTimeEntry.php` | Uur aanmaken |
| `app/Filament/Admin/Resources/TimeEntries/Pages/EditTimeEntry.php` | Uur bewerken |
| `app/Filament/Admin/Resources/TimeEntries/Pages/ListTimeEntries.php` | Uren lijst |
| `app/Filament/Admin/Resources/Users/UserResource.php` | Gebruikers CRUD |
| `app/Filament/Admin/Resources/Users/Schemas/UserForm.php` | Gebruikers formulier |
| `app/Filament/Admin/Resources/Users/Tables/UsersTable.php` | Gebruikers tabel |
| `app/Filament/Admin/Resources/Users/Pages/CreateUser.php` | Gebruiker aanmaken |
| `app/Filament/Admin/Resources/Users/Pages/EditUser.php` | Gebruiker bewerken |
| `app/Filament/Admin/Resources/Users/Pages/ListUsers.php` | Gebruikers lijst |

### Filament Exports
| Bestand | Wat het doet |
|---|---|
| `app/Filament/Exports/TimeEntryExporter.php` | Filament export voor uren |

### Filament Pages
| Bestand | Wat het doet |
|---|---|
| `app/Filament/Admin/Pages/Dashboard.php` | Hoofddashboard |
| `app/Filament/Admin/Pages/Settings.php` | Instellingen |
| `app/Filament/Admin/Pages/EditProfile.php` | Profiel bewerken |

### Actions
| Bestand | Wat het doet |
|---|---|
| `app/Filament/Admin/Actions/WorkbookActions.php` | Excel werkblad acties |
| `app/Filament/Admin/Actions/SyncTimeEntriesAction.php` | Excel sync actie |

### Policies
| Bestand | Wat het doet |
|---|---|
| `app/Policies/TimeEntryPolicy.php` | Toegang tot uren |
| `app/Policies/UserPolicy.php` | Toegang tot gebruikers |

### Providers
| Bestand | Wat het doet |
|---|---|
| `app/Providers/AppServiceProvider.php` | Model event listeners |
| `app/Providers/Filament/AdminPanelProvider.php` | Filament panel config |

### Controllers
| Bestand | Wat het doet |
|---|---|
| `app/Http/Controllers/WorkbookController.php` | Download endpoint |

### Database
| Bestand | Wat het doet |
|---|---|
| `database/migrations/*_create_users_table.php` | Users tabel |
| `database/migrations/*_create_time_entries_table.php` | Time entries tabel |
| `database/factories/UserFactory.php` | Test data voor users |
| `database/factories/TimeEntryFactory.php` | Test data voor uren |
| `database/seeders/UsersSeeder.php` | Standaard gebruikers |
| `database/seeders/DatabaseSeeder.php` | Hoofd seeder |

### Tests
| Bestand | Wat het doet |
|---|---|
| `tests/Feature/AuthAndAccessTest.php` | Authenticatie tests |
| `tests/Feature/TimeEntrySyncTest.php` | Excel sync tests |
| `tests/Feature/WorkbookTest.php` | Workbook tests |
| `tests/Pest.php` | Pest configuratie |

### Views (Blade templates)
| Bestand | Wat het doet |
|---|---|
| `resources/views/filament/widgets/progress-bar.blade.php` | Voortgangsbalk |
| `resources/views/filament/theme-sync.blade.php` | Thema sync script |
| `resources/views/filament/pages/settings.blade.php` | Settings page wrapper |

### Configuratie
| Bestand | Wat het doet |
|---|---|
| `config/database.php` | Database connecties |
| `config/filament-palette.php` | Kleuren palettes |
| `.env.example` | Environment variabelen voorbeeld |

### Deployment
| Bestand | Wat het doet |
|---|---|
| `railway.json` | Railway configuratie |
| `nixpacks.toml` | Build configuratie |
| `docker-local/docker-compose.yml` | Docker lokaal |
| `DEPLOY.md` | Deploy instructies |

---

## Appendix: Database Migratie Beleid

**Gouden regel:** altijd additief. Nieuwe kolommen met `default()` of nullable. Nieuwe tabellen. Nooit kolommen dropen of data herschrijven in een migratie.

Waarom? Omdat `migrate:fresh` op productie elke keer opnieuw draait. Als je een kolom dropt, verdwijnt de data.

---


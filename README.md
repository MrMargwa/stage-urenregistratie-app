# Stage Urenregistratie

Een Laravel 13 + Filament 5 applicatie om stage-uren bij te houden: datum, begin- en eindtijd,
pauze en beschrijving per registratie; duur wordt automatisch berekend.

## Functionaliteit

### Urenregistratie
- Registraties aanmaken, bewerken en verwijderen (`/dashboard/time-entries`)
- Automatische duurberekening (werkt ook over middernacht heen)
- Overlap-validatie: overlappende registraties op dezelfde dag worden geweigerd
- **Weekstaat-filter**: selecteer een week in de lijst om alleen die week te bekijken
- **Exporteer naar `.xlsx`** (knop "Exporteren (.xlsx)"): exporteert je uren zoals je ze ziet —
  selecteer eerst een weekstaat in de filter voor een weekexport, of laat de filter leeg voor alle uren.
  Kolommen: Week, Datum, Begintijd, Eindtijd, Pauze, Beschrijving, Duur.

### Dashboard
- Overzicht van de huidige (of gekozen) week met per dag de registraties
- Weeknavigatie (vorige / huidige / volgende week)
- Totaal aantal uren + stage-voortgangsbalk op basis van je doel (instelbaar in Instellingen)

### Rollen & beveiliging
- Rollen `admin`, `user` of `student` op elk account (alleen admins kunnen rollen toewijzen)
- **Gebruikersbeheer** (`Beheer → Gebruikers`) is alleen zichtbaar én toegankelijk voor admins.
  Een admin kan gebruikers aanmaken/bewerken/verwijderen; bij het verwijderen van een gebruiker
  worden diens stage-uren ook verwijderd.
- **Privacy:** elke gebruiker — óók de admin — ziet alleen zijn eigen uren. Niemand kan andermans
  registraties bekijken of bewerken.
- Niet-ingelogd? Dan kom je altijd op de login terecht (ook bij onbekende URL's / 404's);
  ingelogd word je bij een onbekende URL naar het dashboard gestuurd

### Instellingen
Via `Instellingen` in de navigatie kan elke gebruiker:
- Naam, e-mailadres en wachtwoord aanpassen
- Thema kiezen: donker, licht of systeem
- Totaal te lopen stage-uren instellen (voor de voortgangsbalk)

## Lokaal ontwikkelen

De lokale omgeving draait via Docker (`docker-local/docker-compose.yml`, MySQL):

```bash
docker compose -f docker-local/docker-compose.yml up -d   # MySQL (poort 3307) + app
composer install
npm install && npm run build
php artisan migrate --seed
```

Zonder Docker kun je ook gewoon `php artisan serve` gebruiken zolang `DB_*` in `.env` klopt.

De seeder maakt bij een nieuwe database precies één admin-account aan: `admin@admin.com` /
`Admin1!23` (wijzig het wachtwoord direct na de eerste login via Instellingen).

### Tests

```bash
php artisan test        # Pest, draait op sqlite :memory: (instellingen in phpunit.xml)
vendor/bin/pint         # code style
```

## Productie

Zie [DEPLOY.md](DEPLOY.md) voor het live zetten.

> **Belangrijk:** alle migraties zijn **additief** (nieuwe kolommen met default/nullable, nooit
> drop of destructieve wijziging). Daardoor kan er veilig gedeployed worden zonder de
> productiedata te verliezen. Het verwijderen van de stage-uren bij een verwijderde gebruiker
> gebeurt op applicatieniveau (User::deleting event) en vereist geen DB-migratie.

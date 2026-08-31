# Stage Urenregistratie

Een Laravel 13 + Filament 5 applicatie om stage-uren bij te houden: datum, begin- en eindtijd,
pauze en beschrijving per registratie; duur wordt automatisch berekend.

## Functionaliteit

### Urenregistratie
- Registraties aanmaken, bewerken en verwijderen (`/admin/time-entries`)
- Automatische duurberekening (werkt ook over middernacht heen)
- Overlap-validatie: overlappende registraties op dezelfde dag worden geweigerd
- **Weekstaat-filter**: selecteer een week in de lijst om alleen die week te bekijken
- **Exporteer naar `.xlsx`** (knop "Exporteren (.xlsx)"): exporteert de uren zoals je ze ziet —
  selecteer eerst een weekstaat in de filter voor een weekexport, of laat de filter leeg voor alle uren.
  Kolommen: Week, Datum, Begintijd, Eindtijd, Pauze, Beschrijving, Duur.
- **Excel synchroniseren** (knop "Excel synchroniseren"): upload een `.xlsx`/`.csv`-bestand en de app
  synchroniseert je registraties ermee. Herkende kopregels (NL én EN): datum/date, begintijd/start,
  eindtijd/end/einde, pauze/break en beschrijving/omschrijving/description.
  - Bestaande regels worden herkend op **datum + begintijd** en bijgewerkt
  - Nieuwe regels worden aangemaakt
  - Optioneel: regels die niet in het bestand staan worden verwijderd
  - Na afloop krijg je een rapport met aangemaakt/bijgewerkt/verwijderd/overgeslagen + eventuele fouten per rij
- **Persoonlijk Excel-werkblad** (knop "Excel koppelen"): koppel één keer je eigen stage-urenwerkblad.
  Daarna wordt het bestand **automatisch bijgewerkt** zodra je een uur toevoegt, aanpast of verwijdert
  (ook na een Excel-sync). Download het actuele werkblad op elk moment via "Mijn Excel-werkblad".

### Rollen & beveiliging
- Rol `admin`, `user` of `student` op elk account (alleen admins kunnen rollen toewijzen)
- **Gebruikersbeheer** (`Beheer → Gebruikers`) is alleen zichtbaar én toegankelijk voor admins;
  registratie is bewust niet mogelijk — accounts worden door een admin aangemaakt
- **Privacy:** elke gebruiker — óók de admin — ziet alleen zijn eigen uren. Niemand kan andermans
  registraties bekijken of bewerken.
- Niet-ingelogd? Dan kom je altijd op de login terecht (ook bij onbekende URL's / 404's);
  ingelogd word je bij een onbekende URL naar het dashboard gestuurd

### Instellingen
Via `Instellingen` in de navigatie kan elke gebruiker:
- Naam, e-mailadres en wachtwoord aanpassen
- Thema kiezen: donker (standaard), licht of systeem
- Accentkleur kiezen (rood, geel, groen, paars, blauw, roze, …) — direct toegepast op de hele app

## Lokaal ontwikkelen

De lokale omgeving draait via Docker (`docker-local/docker-compose.yml`, MySQL):

```bash
docker compose -f docker-local/docker-compose.yml up -d   # MySQL (poort 3307) + app
composer install
npm install && npm run build
php artisan migrate --seed
```

Zonder Docker kun je ook gewoon `php artisan serve` gebruiken zolang `DB_*` in `.env` klopt.

### Tests

```bash
php artisan test        # Pest, draait op sqlite :memory: (instellingen in phpunit.xml)
vendor/bin/pint         # code style
```

## Online zetten (Railway)

Zie [DEPLOY.md](DEPLOY.md). Push naar `main` = automatische deploy.

> **Belangrijk:** alle migraties zijn **additief** (nieuwe kolommen met default/nullable, nooit
> drop of destructive change). Daardoor kan er veilig gedeployd worden zonder de productiedata
> te verliezen.

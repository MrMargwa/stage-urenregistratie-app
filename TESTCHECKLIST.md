# ✅ Handmatige Test Checklist — Stage-Urenregistratie App

> Werk de checklist door om te controleren dat alle functionaliteit werkt.
> **Per regel:** de test uitvoeren in de browser, resultaat checken, dan aanvinken ✔.

---

## 🔐 Hoe start je de app lokaal

```bash
# Optie A — Docker (aanbevolen)
cd docker-local && docker compose up -d        # daarna: http://localhost:8000

# Optie B — direct
composer install && npm install && npm run build
php artisan migrate --seed
php artisan serve                               # daarna: http://localhost:8000
```

**Standaard testaccounts** (uit `UsersSeeder`):

| Rol  | E-mail | Wachtwoord |
|------|--------|------------|
| Admin | `admin@admin.com` | `Welkom1!23` |
| Gebruiker | `testaccount01@example.com` | `Welkom1!23` |

> Tip: maak voor de rol-tests zelf ook een account met rol **Student** aan
> via Beheer → Gebruikers (zie blok 5).

---

## 1️⃣ Login & Toegang

- [ v ] **1.1** Gast opent `/` → wordt doorgestuurd naar de **loginpagina**
- [ v ] **1.2** Onbestaande URL (bv. `/xyz`) als gast → ook naar loginpagina
- [ v ] **1.3** Inloggen met `admin@admin.com` / `Welkom1!23` lukt → dashboard opent
- [ v ] **1.4** Inloggen met fout wachtwoord → foutmelding, geen toegang
- [ v ] **1.5** Uitloggen (menu rechtsboven → "Afmelden") werkt
- [ v ] **1.6** Na uitloggen kan je beveiligde pagina's niet meer openen (wordt naar login gestuurd)

### Rollen (gelijkwaardigheid student & gebruiker)
- [ ] **1.7** Log in als **Student** én als **Gebruiker** → beide zien exact dezelfde menu-items en rechten (GEEN verschil)
- [ ] **1.8** **Admin** ziet extra menu-groep **"Beheer → Gebruikers"**; Student/Gebruiker zien die NIET

---

## 2️⃣ Dashboard

- [ ] **2.1** Dashboard toont de **lopende week** met weeklabel (bv. "24 aug – 30 aug 2026 (week 35)")
- [ ] **2.2** Knoppen **"Vorige week" / "Volgende week" / "Huidige week"** verplaatsen de week correct
- [ ] **2.3** **"Totaal deze week"** toont de som van de uren van die week (formaat `u:mm`)
- [ ] **2.4** De tabel onder het overzicht toont de registraties van de **geselecteerde week** (kolommen: Datum, Dag, Start, Eind, Pauze, Omschrijving, Duur)
- [ ] **2.5** Een week zonder uren → melding "Nog geen uren geregistreerd in deze week."
- [ ] **2.6** **Voortgangsbalk** (top) toont voortgang t.o.v. je stage-doeluren (zie Instellingen, blok 6)
- [ ] **2.7** Knop **"+ Tijdregistratie"** opent het aanmaakformulier
- [ ] **2.8** Alleen de **eigen** uren verschijnen op het dashboard (geen uren van anderen)

---

## 3️⃣ Tijdregistraties (CRUD)

### Aanmaken
- [ ] **3.1** Tijdregistraties → "+ Tijdregistratie" → vul datum, begintijd, eindtijd, pauze, beschrijving
- [ ] **3.2** Na opslaan verschijnt de registratie in de lijst + dashboard; **Duur is automatisch berekend** (eind - begin - pauze)
- [ ] **3.3** Pauze groter dan gewerkte tijd → duur wordt `00:00` (niet negatief)
- [ ] **3.4** Registratie **over middernacht** (bv. 22:00 → 06:00) → duur klopt (8 uur)

### Validatie
- [ ] **3.5** **Eindtijd vóór begintijd** → foutmelding "De eindtijd kan niet voor de begintijd liggen", niet opslaan
- [ ] **3.6** **Overlappende registratie** op dezelfde dag → foutmelding "Deze registratie overlapt…", niet opslaan
- [ ] **3.7** Twee registraties die **net naast elkaar** liggen (09:00–12:00 en 13:00–17:00) → gewoon toegestaan
- [ ] **3.8** Dezelfde tijden op een **andere dag**, of bij een andere gebruiker → toegestaan
- [ ] **3.9** Datum/velden leeglaten → verplicht-veldmeldingen

### Bewerken & verwijderen
- [ ] **3.10** Lijst → bewerk een registratie (tijd of beschrijving aanpassen) → wijziging opgeslagen
- [ ] **3.11** Bewerken die **niet** overlapt met zichzelf → lukt
- [ ] **3.12** Lijst → verwijder een registratie (niet-bulk) → verdwenen uit lijst + totalen bijgewerkt
- [ ] **3.13** Bulk-selectie + "Verwijderen" (bulk) → meerdere registraties tegelijk verwijderd

### Privacy (belangrijkste regel!)
- [ ] **3.14** Log in als **Admin** → zie je alleen je **eigen** tijdregistraties, NIET die van een andere account
- [ ] **3.15** Rechtstreeks de edit-URL van een **anders** zijn registratie openen → krijg je **404** (niet de data)
- [ ] **3.16** Er is **geen** "Gebruiker"-kolom zichtbaar in de lijst (iedereen ziet enkel eigen uren)

---

## 4️⃣ Weekfilter & Excel-Export

- [ ] **4.1** Lijstpagina heeft een **"Weekstaat"**-filter; de opties zijn de weken waarin je uren hebt (eigen uren)
- [ ] **4.2** Selecteer een week in de filter → lijst toont **alleen** die week
- [ ] **4.3** Filter leegmaken → toont weer alle uren
- [ ] **4.4** Knop **"Exporteren (.xlsx)"** → downloadt een Excel-bestand (`.xlsx`)
- [ ] **4.5** Het exportbestand heeft kolommen: **Week · Datum · Begintijd · Eindtijd · Pauze (minuten) · Beschrijving · Duur**
- [ ] **4.6** De **Duur**-kolom in Excel is leesbaar `u:mm` (bv. `07:30`)
- [ ] **4.7** Export **met** een actief weekfilter → alleen die week in het bestand
- [ ] **4.8** Export **zonder** filter → alle uren in het bestand
- [ ] **4.9** Geopende export bevat **alleen eigen uren** (geen andermans data)

---

## 5️⃣ Gebruikersbeheer (Alleen Admin)

- [ ] **5.1** Als **niet-admin** de URL `/dashboard/users` openen → **Verboden** (403), geen toegang
- [ ] **5.2** Als **admin**: Beheer → Gebruikers → lijst toont naam, e-mail, rol, aantal urenregistraties, aangemaakt-op
- [ ] **5.3** **Filter op rol** (Student/Gebruiker/Admin) werkt
- [ ] **5.4** **Zoeken** op naam of e-mail werkt
- [ ] **5.5** **Nieuw account aanmaken**: naam, e-mail, rol, wachtwoord (min. 8 tekens) + bevestiging
- [ ] **5.6** Gebruiker aanmaken met **dubbele e-mail** → foutmelding
- [ ] **5.7** **Bewerken**: wijzig naam/rol/e-mail van een gebruiker → opgeslagen
- [ ] **5.8** **Wachtwoord vergeten opslaan**: pas alleen opgeslagen als je een nieuw wachtwoord invult (anders blijft hetzelfde)
- [ ] **5.9** **Admin kan zichzelf NIET verwijderen** (verwijderknop ontbreekt/geweigerd)
- [ ] **5.10** **Laatste admin** kan niet worden verwijderd (altijd minstens 1 admin)
- [ ] **5.11** Verwijder een niet-admin gebruiker → account weg, kan niet meer inloggen
- [ ] **5.12** Het aantal "Urenregistraties" per gebruiker in de lijst klopt

> ⚠️ Belangrijk: het verwijderen van een gebruiker raakt NIET andermans uren —
> maar elke gebruiker ziet toch alleen zijn eigen uren (zie 3.14).

---

## 6️⃣ Instellingen / Profiel

- [ ] **6.1** Menu → "Instellingen" opent de pagina met secties **Account** en **Stage**
- [ ] **6.2** Naam en e-mail aanpassen → "Opslaan" → bevestiging 'Instellingen opgeslagen' + nieuwe waarden gebonden
- [ ] **6.3** E-mail instellen die al door een ander gebruikt wordt → foutmelding
- [ ] **6.4** **Wachtwoord wijzigen**: vul alleen "Nieuw wachtwoord" (min. 8 tekens) in → opgeslagen; daarna kan je ermee inloggen
- [ ] **6.5** Nieuw wachtwoord korter dan 8 tekens → foutmelding
- [ ] **6.6** Wachtwoordveld **leeg** laten → huidige wachtwoord blijft geldig
- [ ] **6.7** **Stage-uren doel** (`target_hours`) instellen (bv. 500) → opslaan
- [ ] **6.8** Voortgangsbalk op het dashboard reageert op het ingestelde doel (2.6)
- [ ] **6.9** Doel min. 1, max. 9999 → waarden erbuiten geblokkeerd
- [ ] **6.10** **Ctrl/Cmd+S** op de Instellingenpagina slaat op (sneltoets)

---

## 7️⃣ Thema & UI Switcher

- [ ] **7.1** Gebruikersmenu toont de kleurensswitcher (palette: amber/teal/slate enz.) met theme-switcher
- [ ] **7.2** Van **donker → licht** wisselen werkt en onthoudt de keuze (ook na herladen)
- [ ] **7.3** Kleurpalet wisselen past de accentkleur van de app/export aan
- [ ] **7.4** De keuze blijft per gebruiker bewaard (niet globaal)

---

## 8️⃣ Statussen & Meldingen

- [ ] **8.1** Na elke succesvolle actie (opslaan/verwijderen/export) verschijnt een bevestigingsmelding (toast)
- [ ] **8.2** Bij fouten (overlap, verplichte velden, fout wachtwoord) krijg je een duidelijke foutmelding
- [ ] **8.3** Healthcheck `/up` geeft een geldig antwoord (deploy-controle)

---

## ☑️ Overzicht / Resultaat

| Blok | Onderwerp | Aantal tests | ✅ Behaald | ✏️ Opmerkingen |
|------|-----------|--------------|------------|----------------|
| 1 | Login & Toegang | 8 | | |
| 2 | Dashboard | 8 | | |
| 3 | Tijdregistraties (CRUD) | 16 | | |
| 4 | Weekfilter & Export | 9 | | |
| 5 | Gebruikersbeheer | 12 | | |
| 6 | Instellingen / Profiel | 10 | | |
| 7 | Thema & UI Switcher | 4 | | |
| 8 | Statussen & Meldingen | 3 | | |
| | **Totaal** | **70** | | |

---

## 📝 Opmerkingen / Issues gevonden tijdens testen

| # | Blok | Omschrijving van het probleem | Ernst (Laag/Midden/Hoog) |
|---|------|-------------------------------|--------------------------|
|   |      |                               |                          |

# MD-totals

Webbapplikation för att visa statistik över rabatterade priser från butikens
scanner-data. Kunder loggar in och ser statistik för sin egen tenant.

## Arkitektur (översikt)

```
Scanner-data → C++ sammanställningsprogram → Databas
                                                  ↓
                                          REST-API (kollega)
                                                  ↓
                                    Denna app (Laravel/PHP)
                                                  ↓
                                    Frontend (Blade + jQuery/AJAX)
```

- Käll-databasen ägs och drivs av Rainer Barnekow.
- Denna app hämtar data via API:et (eller en lokal mirror/cache), filtrerar
  per tenant baserat på inloggad kund, och visar statistik med filter.

## Status / öppna frågor

> Uppdatera denna lista löpande.

- [ ] API:et är inte satt upp ännu (kollega bygger)
- [ ] Autentisering mot API:et ej bestämd
- [ ] Endpoints/filtrering (tenant, datumintervall) ej bestämda
- [ ] Tenant-till-kund-mappning är ny och ska designas i denna app
- [ ] Klarhet kring om produktionsservern kan kompilera JS (påverkar frontend-val)

## Tech stack

- PHP 8.5.9 / Laravel [version]
- MySQL/MariaDB (lokal utveckling)
- Blade-vyer, jQuery för AJAX, Chart.js för diagram (inget byggsteg krävs)

## Kom igång (lokal utveckling)

### Krav
- PHP 8.5.9
- Composer
- Node.js (endast om npm-paket för frontend-assets används, t.ex. Chart.js via npm istället för CDN)
- MySQL/MariaDB eller SQLite

### Installation

```bash
git clone <repo-url>
cd rabatt-statistik
composer install
cp .env.example .env
php artisan key:generate
```

Uppdatera `.env` med databasuppgifter, se `.env.example` för vilka värden som krävs.

```bash
php artisan migrate
php artisan db:seed   # om testdata finns
php artisan serve
```

Sidan nås sedan på `http://127.0.0.1:8000`.

## Mappstruktur (huvuddelar)

```
app/
  Http/Controllers/       # Hanterar requests, anropar Services
  Models/                 # Eloquent-modeller (Rabatt, Tenant, User m.fl.)
  Services/                # Affärslogik: hämtning/filtrering av data
database/
  migrations/              # Schema
  seeders/                  # Testdata
resources/views/           # Blade-vyer
routes/web.php             # Routes (inkl. AJAX-endpoints)
```

## Datakälla

Data kommer ursprungligen från butikens scanners via ett C++
sammanställningsprogram som lagrar i en databas. [Kollegans namn] ansvarar
för databasen och bygger ett REST-API som denna app anropar.

Se `app/Services/RabattDataService.php` för all logik kring hämtning,
filtrering och (ev.) cache av extern data — hålls samlat på ett ställe med
avsikt, så att API-anrop inte sprids ut i controllers/vyer.

## Kontakt / ägarskap

- Utvecklare: Fredrik Olsson
- API/databas: Rainer Barnekow
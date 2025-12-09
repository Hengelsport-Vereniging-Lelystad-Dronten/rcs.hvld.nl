Developer Notities

Kort overzicht voor ontwikkelaars die aan deze repository werken:

- Belangrijke mappen:
  - `app/` : Laravel backend (models, controllers, services). Gedocumenteerd met Nederlandse docblocks.
  - `resources/js/` : Vue 3 + Inertia frontend. Pages, Layouts en herbruikbare Components bevinden zich hier.
  - `resources/views/` : Blade templates (Inertia root layout en e-mails). Enkele bestanden bevatten extra commentaar in het Nederlands.
  - `database/migrations/`, `database/seeders/`, `database/factories/` : Database-structuur en startdata. Migraties en seeders zijn voorzien van korte toelichting.

- Recente wijzigingen:
  - Recidive-logica is geïmplementeerd in `app/Services/RecidiveCheckService.php` en wordt aangeroepen via `App\\Http\\Controllers\\Api\\RecidiveController`.
  - Frontend gebruikt Inertia + Vite. Run `npm run dev` tijdens ontwikkeling.

- Aanbevelingen:
  - Voer `php artisan migrate --seed` uit in een veilige (lokale) database voordat je de applicatie start.
  - Gebruik `npm run build` voor productie en `npm run dev` tijdens development.
  - Houd gevoelige credentials (wachtwoorden, API-keys) uit repo en zet ze in `.env`.

Als je wilt dat ik verdere documentatie toevoeg (bijv. volledige API-spec, flow diagrammen of extra comments in `resources/views`), zeg het gerust.

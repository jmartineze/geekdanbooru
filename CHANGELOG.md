# Changelog

All notable changes to this project are documented here, in reverse chronological order.

---

## [Unreleased] — 2026-06-25

### Added
- `README.md` with full project documentation: stack table, Docker setup, first-run commands, import command reference, tag sections/groups, i18n instructions, production deployment guide, project structure tree, and API reference.
- `CHANGELOG.md` (this file).

### Fixed
- Tag misclassification: `paizuri`, `breast_grab`, `breast_press`, `breast_squeeze`, `breast_hold`, `breast_lift`, `bouncing_breasts`, `presenting`, `pov_hands`, `breast_expansion` moved from `character/breasts` to `pose/sex_acts` — these are actions/interactions, not body features.
- Tag misclassification: `taut_clothes`, `popping_button`, `bursting_buttons`, `clothes_burst`, `seams_bursting`, `clothes_too_small`, `see-through`, `wet_clothes`, `wardrobe_malfunction` moved to `outfit/sexual_attire`.

### Changed
- Import map updated: added pattern searches `*_breasts`, `*_ass`, `ass_*`, `cum_*`, `*_job`, `breast_*`, `*_penetration`, `see-through_*`, `*_lift`, `*_pull`, `torn_*`, `ripped_*`, `popping_*`, `taut_*`, `bursting_*` for broader coverage of NSFW and clothing-stress tags.
- Total tags in DB after re-import and fixes: **2,496**.

---

## [0.3.0] — 2026-06-25

### Added
- Tag import from Danbooru public API: Artisan command `danbooru:import` (`app/Console/Commands/ImportDanbooruTags.php`).
  - Options: `--section`, `--group`, `--fresh`, `--min-count` (default 100).
  - Full section→subsection→patterns map covering all 4 sections and 37 subsections.
  - API wildcard patterns: `*_hair`, `*_eyes`, and NSFW patterns for body/outfit/sex tags.
  - Rate limiting: 400 ms sleep between API calls to respect Danbooru's public rate limit.
  - Upsert logic: existing tags are updated, new ones inserted; `--fresh` truncates the scope first.
- First successful import: **2,055 tags** inserted across all sections.
- Tag counts per section after first import:
  | Section | Tags |
  |---|---|
  | character | 685 |
  | pose | 190 |
  | outfit | 563 |
  | scene | 617 |

---

## [0.2.0] — 2026-06-25

### Added
- **Dashboard UI** (`resources/views/dashboard/index.blade.php`):
  - 4-tab left navigation (Character / Pose / Outfit / Scene) with per-section selection counters.
  - Sticky search bar with 250 ms debounce; clears with `✕` button.
  - Collapsible subsection accordions (open by default, chevron indicator).
  - Tag pills: show top 30 per group by post count; "Show X more / Show less" button for groups with more than 30 tags.
  - Post count tooltip on every tag pill (`title` attribute, formatted as "2.5M posts", "150K posts", etc.).
  - NSFW toggle button in header: reloads active section with/without adult tags.
  - Right-side prompt panel: live comma-separated prompt, copy-to-clipboard button (2 s feedback), removable tag badges.
  - Clear All button (visible only when tags are selected).
- **Alpine.js v3** reactive state (`promptBuilder()` function):
  - `loadSection()` — fetches `/api/tags?section=...&nsfw=...` on tab switch or NSFW toggle.
  - `toggleTag()` / `removeTag()` / `clearAll()` — manage selection state and per-section/subsection counters.
  - `copyPrompt()` — writes to clipboard using `navigator.clipboard`.
  - `tagPillClass()` — returns correct CSS class based on NSFW status and selection state.
  - `formatPostCount()` — formats raw numbers to human-readable strings.
  - `subsectionLabel()` — resolves key to display label from `SUBSECTION_LABELS` (passed from `lang/en/ui.php`).
- **Tailwind CSS v4** custom design tokens in `resources/css/app.css` (`@theme` block):
  - Brand palette: `--color-brand-{50,100,300,400,500,600,700}` (indigo/blue).
  - NSFW palette: `--color-nsfw-{500,600}` (rose/red).
  - Component classes: `.tag-pill`, `.tag-pill-default`, `.tag-pill-selected`, `.tag-pill-nsfw`, `.tag-pill-nsfw-selected`, `.selected-badge`, `.selected-badge-nsfw`, `.section-tab`, `.subsection-header`, `.prompt-panel`.
- **i18n files**:
  - `lang/en/ui.php` — all UI strings: app name, section labels, 37+ subsection labels, prompt panel labels, button text, placeholders.
  - `lang/es/ui.php` — full Spanish translation of all keys.
- **API endpoint** `GET /api/tags` (`app/Http/Controllers/Api/TagController.php`):
  - Parameters: `section` (required), `subsection`, `nsfw` (boolean), `q` (search string).
  - Returns up to 500 tags ordered by `post_count` DESC.
  - SFW filter applied by default; `nsfw=1` includes all tags.
- API route registered in `bootstrap/app.php` → `routes/api.php`.
- **DashboardController** (`app/Http/Controllers/DashboardController.php`): passes `$structure` (distinct subsections per section) and `$totalTags` to view.

### Fixed
- Blade layout resolution error: `Unable to locate a class or view for component [layouts.app]`. Anonymous Blade components must live under `resources/views/components/`. Moved layout file to `resources/views/components/layouts/app.blade.php`.
- Tailwind v4 build error: `Cannot apply unknown utility class text-brand-300`. Added `--color-brand-300` to `@theme` block.
- Alpine.js `x-collapse` directive not available (requires `@alpinejs/collapse` plugin, not installed). Replaced with native `x-show`.

---

## [0.1.0] — 2026-06-25

### Added
- **Docker infrastructure**:
  - `Dockerfile` — PHP 8.4-fpm with extensions: pdo, pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip, xdebug.
  - `docker-compose.yml` — services: `app` (PHP-FPM, port 9003), `web` (nginx:alpine, port 8086), `db` (mariadb:11.4, port 3312), `composer` (one-off). Network: `danbooru`. Volume: `danbooru_db_data`.
  - `nginx.conf` — standard Laravel nginx config (root → `public/`, fastcgi → `app:9000`).
- **Laravel 13 installation** via `composer create-project laravel/laravel` into a temp subdirectory (project root already contained Docker files), then merged back.
- **`.env` configuration**: `APP_NAME="Danbooru Prompt Builder"`, MariaDB connection settings (`DB_HOST=db`, `DB_DATABASE=danbooru`), `SESSION_DRIVER=file`, `CACHE_STORE=file`.
- **Database migration** `2026_06_25_000001_create_tags_table.php`:
  - Columns: `id`, `name` (varchar, unique), `section` (enum: character/pose/outfit/scene), `subsection` (varchar 100), `post_count` (int), `is_nsfw` (boolean), timestamps.
  - Indexes: `(section, subsection)`, `post_count`.
- **Tag model** `app/Models/Tag.php` with fillable and scopes: `bySection()`, `bySubsection()`, `sfw()`, `popular()`.
- **TagSeeder** (`database/seeders/TagSeeder.php`) — ~428 curated essential tags with approximate post counts, covering all 4 sections and 37 subsections. Used as offline fallback when API is unavailable.
- **Alpine.js v3** added to `package.json` dependencies and bootstrapped in `resources/js/app.js`.
- **Base layout** `resources/views/components/layouts/app.blade.php` with Inter font (Google Fonts), Vite asset tags, dark background.
- `bootstrap/app.php` updated to register `routes/api.php`.

### Fixed
- `docker-compose.yml` `version` attribute removed (obsolete in modern Compose, caused a warning).
- PHP version upgraded from 8.3 to **8.4** in `Dockerfile`: Laravel 13 requires `>= PHP 8.4.1`.
- `composer create-project` failed with "directory not empty" because Docker files were already present → used temp subdirectory install + file merge strategy.

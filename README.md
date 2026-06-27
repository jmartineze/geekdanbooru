# Danbooru Prompt Builder

A visual dashboard for building AI image generation prompts using Danbooru tags. Select character features, poses, outfits and scene elements through an organized UI and get a ready-to-use prompt string for platforms like ourdream.ai, DreamGen, Stable Diffusion, and similar tools.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 + PHP 8.4 |
| Database | MariaDB 11.4 |
| Frontend | Tailwind CSS v4 + Alpine.js v3 + Vite |
| Local dev | Docker (PHP-FPM + nginx + MariaDB) |
| Production | cPanel / CloudPanel + nginx + MySQL/MariaDB |

---

## Features

- **4 builder sections** following Danbooru's official tag group structure:
  - **Character** — hair, eyes, face, body, skin, ears, hands, feet + NSFW body tags
  - **Pose** — standing/sitting/lying, camera angles, expressions, sex acts, sexual positions, BDSM
  - **Outfit** — tops, bottoms, dresses, uniforms, swimwear, legwear, footwear, accessories, lingerie, sexual attire
  - **Scene** — nature, urban, indoor, fantasy, sci-fi, lighting, colors, art style, objects
- **NSFW toggle** — show or hide adult tags globally
- **Collapsible groups** — each tag group (subsection) is independently collapsible
- **Show more / Show less** — groups show top 30 tags by popularity; expand to see all
- **Post count tooltip** — hover any tag to see how many Danbooru posts use it (e.g. "2.5M posts")
- **Live prompt panel** — selected tags assemble into a comma-separated prompt in real time
- **Copy to clipboard** — one click to copy the finished prompt
- **Tag search** — filter tags across the active section instantly
- **Save prompts** — authenticated users can save prompts with a name, section, and optional result image
- **My Prompts** — manage saved prompts: edit name/image, toggle public/private, delete
- **Open in Builder** — load any saved prompt back into the builder to edit tags
- **Update / Save as New** — when editing a loaded prompt, choose to update the original or fork a new one
- **Community gallery** — public prompts shown on the landing page
- **i18n ready** — UI in English; Spanish locale file included, add more under `lang/`

---

## Local Development (Docker)

### Requirements

- Docker Desktop
- Docker Compose

### Ports

| Service | Host port |
|---|---|
| Web (nginx) | `8086` |
| PHP-FPM | `9003` |
| MariaDB | `3312` |

### First-time setup

```bash
# 1. Build and start containers
docker-compose up -d --build

# 2. Run database migrations
docker-compose exec app php artisan migrate

# 3. Seed with curated tags (offline, ~400 essential tags)
docker-compose exec app php artisan db:seed --class=TagSeeder

# 4. Import tags from the Danbooru API (~2500 tags, requires internet)
docker-compose exec app php artisan danbooru:import

# 5. Install JS dependencies and build assets
docker-compose exec app npm install
docker-compose exec app npm run build
```

Open `http://localhost:8086`

### Daily use

```bash
docker-compose up -d        # start
docker-compose down         # stop
docker-compose logs -f app  # view PHP logs
```

---

## Tag Import Commands

### `danbooru:import-csv` — Recommended ⚡

Imports tags from the community CSV export ([dbr-e621-lists-archive](https://github.com/DraconicDragon/dbr-e621-lists-archive)). Downloads ~39K general tags, applies regex pattern matching locally, and upserts in seconds. No API rate limiting.

```bash
# Import all sections from CSV (downloads automatically)
php artisan danbooru:import-csv

# Use a pre-downloaded local CSV file
php artisan danbooru:import-csv --file=/path/to/danbooru_2026-04-01_pt20-ia-dd.csv

# Only one section
php artisan danbooru:import-csv --section=character
php artisan danbooru:import-csv --section=outfit --group=sexual_attire

# Minimum post count threshold (default: 100)
php artisan danbooru:import-csv --min-count=500

# Wipe scope and reimport
php artisan danbooru:import-csv --fresh

# Use a specific CSV URL
php artisan danbooru:import-csv --url=https://raw.githubusercontent.com/.../danbooru_2026-07-01_pt20-ia-dd.csv
```

### `danbooru:import` — API fallback

Fetches tags directly from the Danbooru public API with wildcard pattern search. Slower (400 ms sleep between requests) but always up-to-date.

```bash
# Import all sections from the Danbooru API
php artisan danbooru:import

# Import only one section
php artisan danbooru:import --section=character
php artisan danbooru:import --section=pose
php artisan danbooru:import --section=outfit
php artisan danbooru:import --section=scene

# Import only one group within a section
php artisan danbooru:import --section=character --group=breasts

# Set minimum post count threshold (default: 100)
php artisan danbooru:import --min-count=500

# Delete existing tags in scope before importing
php artisan danbooru:import --section=outfit --group=sexual_attire --fresh
```


---

## Tag Sections & Groups

### Character
`hair_color` · `hair_style` · `hair_length` · `eyes` · `face` · `ears` · `body` · `skin_color` · `breasts` · `ass` · `pussy` · `hands_gestures` · `feet`

### Pose
`standing` · `sitting` · `lying` · `leg_position` · `camera_angle` · `sex_acts` · `sexual_positions` · `bdsm`

### Outfit
`top` · `bottom` · `dress` · `uniform` · `swimwear` · `headwear` · `handwear` · `legwear` · `footwear` · `accessories` · `eyewear` · `neckwear` · `sleeves` · `makeup` · `sexual_attire` · `fashion_style` · `embellishment`

### Scene
`outdoor_nature` · `outdoor_urban` · `indoor_home` · `indoor_public` · `fantasy` · `scifi` · `image_composition` · `lighting` · `colors` · `atmosphere` · `locations` · `objects`

---

## Adding a New Language

1. Copy `lang/en/ui.php` to `lang/{locale}/ui.php`
2. Translate the values (tag names stay in English)
3. Set `APP_LOCALE={locale}` in `.env`

---

## Production Deployment (cPanel / CloudPanel)

1. Upload files excluding `node_modules/`, `vendor/`, `.env`
2. Run `composer install --no-dev --optimize-autoloader`
3. Run `npm install && npm run build`
4. Copy `.env.example` to `.env` and configure DB credentials
5. Run `php artisan key:generate`
6. Run `php artisan migrate`
7. Run `php artisan danbooru:import` (or upload a pre-built DB dump)
8. Point the web root to the `public/` directory
9. Ensure `storage/` and `bootstrap/cache/` are writable

---

## Project Structure

```
app/
  Console/Commands/
    ImportDanbooruTags.php      # danbooru:import (API)
    ImportDanbooruTagsCsv.php   # danbooru:import-csv (recommended)
  Http/Controllers/
    Controller.php              # Base — includes AuthorizesRequests
    LandingController.php       # Landing page + public gallery
    DashboardController.php     # Builder view
    PromptController.php        # Save / update / delete prompts
    Api/TagController.php       # GET /api/tags + GET /api/tags/resolve
  Models/
    Tag.php                     # Eloquent model with scopes
    SavedPrompt.php             # User-owned prompts
  Policies/
    SavedPromptPolicy.php       # ownership checks
database/
  migrations/
    *_create_tags_table.php
    *_create_saved_prompts_table.php
  seeders/
    TagSeeder.php               # ~400 curated offline tags
lang/
  en/ui.php                     # English UI strings
  es/ui.php                     # Spanish UI strings
resources/
  css/app.css                   # Tailwind v4 + custom components
  js/app.js                     # Alpine.js bootstrap
  views/
    components/layouts/app.blade.php
    landing/index.blade.php     # Landing + community gallery
    dashboard/index.blade.php   # Builder (promptBuilder Alpine component)
    prompts/my-prompts.blade.php
routes/
  web.php                       # GET / + prompt CRUD
  api.php                       # GET /api/tags + /api/tags/resolve
```

---

## Changelog

### 2026-06-27
- **feat:** Edit prompt image from My Prompts modal (replace existing image with preview)
- **feat:** "Open in Builder" button loads saved prompt tags into the builder via `/api/tags/resolve`
- **feat:** Save modal now offers "Update existing" / "Save as new" when editing a loaded prompt
- **feat:** Update mode shows current image with suggestion to replace if prompt changed significantly
- **fix:** `AuthorizesRequests` trait added to base `Controller` (was missing in Laravel 11, caused 500 on `authorize()`)
- **fix:** `savePending` stuck-disabled bug — Alpine `:disabled` binding removed from submit button; reset handled in JS
- **fix:** `Storage::url()` replaced with relative `/storage/{path}` for cPanel compatibility

### 2026-06-25
- **feat:** Save Prompt feature — authenticated users can save prompts with name, section, optional image
- **feat:** My Prompts page with public/private toggle, delete, edit name
- **feat:** Community gallery on landing page shows public prompts ordered by likes
- **style:** Gallery and prompt card images use portrait `aspect-[3/4]` ratio
- **fix:** PATCH/DELETE method spoofing via FormData `_method` field for cPanel Apache compatibility
- **fix:** `Options +FollowSymLinks` in `.htaccess` to serve storage symlink images on cPanel
- **fix:** Image URLs use relative `/storage/` path instead of `Storage::url()` to avoid `APP_URL` dependency

---

## API

### `GET /api/tags`

Returns tags filtered by section and optional subsection.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `section` | string | ✅ | `character`, `pose`, `outfit`, or `scene` |
| `subsection` | string | — | Filter by specific group |
| `nsfw` | boolean | — | `1` to include NSFW tags (default: `0`) |
| `q` | string | — | Search by tag name |

**Example:**
```
GET /api/tags?section=character&subsection=hair_color&nsfw=0
```

```json
[
  { "id": 1, "name": "blonde_hair", "subsection": "hair_color", "post_count": 900000, "is_nsfw": false },
  { "id": 2, "name": "brown_hair",  "subsection": "hair_color", "post_count": 800000, "is_nsfw": false }
]
```

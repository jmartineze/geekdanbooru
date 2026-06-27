# CLAUDE.md — Geekguayaco Danbooru Prompt Builder

## Stack

- **Laravel 13** + PHP 8.4, Docker local (PHP-FPM + nginx + MariaDB), cPanel production
- **Tailwind CSS v4** — config via `@theme` in `resources/css/app.css`, no `tailwind.config.js`
- **Alpine.js v3** — `x-data="promptBuilder()"` in `resources/views/dashboard/index.blade.php`
- **Vite** — `npm run build` for production assets

## Key Architectural Decisions

### HTTP Method Spoofing (cPanel compatibility)
Apache on cPanel blocks PATCH/DELETE methods. All mutating requests use POST with `_method` field:
```js
const fd = new FormData();
fd.append('_method', 'PATCH');
fetch(url, { method: 'POST', body: fd });
```
Routes remain standard (`Route::patch`, `Route::delete`) — Laravel handles spoofing natively.
**Never** revert to non-standard route names like `/update` or `/delete`.

### Image URLs
Always use relative `/storage/{path}` — never `Storage::url()` which depends on `APP_URL` and breaks on cPanel where APP_URL might be `localhost`.

### AuthorizesRequests trait
Laravel 11+ ships with an empty base `Controller`. The `authorize()` method requires:
```php
// app/Http/Controllers/Controller.php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
abstract class Controller { use AuthorizesRequests; }
```

### Alpine `:disabled` binding gotcha
Using `:disabled="expression"` in Alpine v3 where the expression can evaluate to `''` (empty string) may set the `disabled` attribute even though `''` is falsy in JS. Prefer removing `:disabled` entirely and handling validation inside the click handler instead.

### `savePending` state
The builder's save modal tracks in-flight requests with `savePending`. Always reset it at the top of `submitSave()` before setting it to `true`, to prevent it getting stuck from a previous failed session:
```js
async submitSave() {
    this.savePending = false; // reset any stuck state
    // ... guards ...
    this.savePending = true;
    // ... fetch ...
    // finally: this.savePending = false;
}
```

## File Map

| File | Purpose |
|---|---|
| `app/Http/Controllers/Controller.php` | Base controller — must include `AuthorizesRequests` |
| `app/Http/Controllers/PromptController.php` | CRUD for saved prompts (store, update, destroy) |
| `app/Http/Controllers/Api/TagController.php` | `GET /api/tags` + `GET /api/tags/resolve?names=` |
| `app/Http/Controllers/LandingController.php` | Landing page with public prompts gallery |
| `app/Models/SavedPrompt.php` | User-owned prompts with image_path |
| `app/Policies/SavedPromptPolicy.php` | update/delete checks `user_id` ownership |
| `resources/views/dashboard/index.blade.php` | Builder — all Alpine state in `promptBuilder()` |
| `resources/views/prompts/my-prompts.blade.php` | My Prompts grid with edit/delete/toggle/open-in-builder |
| `resources/views/landing/index.blade.php` | Landing page with community gallery |
| `public/.htaccess` | Has `Options +FollowSymLinks` inside mod_rewrite for storage symlink |
| `routes/web.php` | Standard PATCH/DELETE routes (spoofing at JS level) |
| `routes/api.php` | `/api/tags` + `/api/tags/resolve` |

## Builder State (Alpine `promptBuilder()`)

Key state variables:
- `selected` — `{ tagName: { section, subsection, is_nsfw } }`
- `loadedPromptId` / `loadedPromptName` / `loadedImagePath` — set when coming from "Open in Builder"
- `saveMode` — `'update'` | `'new'`
- `savePending` / `saveSuccess` / `saveError` — modal submission state
- `saveImageName` — filename of newly selected image (empty = keep existing)

Key sessionStorage keys:
- `pendingSave` — `{ selected }` — saved when unauthenticated, restored after login
- `builderLoad` — `{ names, promptId, promptName, imagePath }` — set by "Open in Builder"

API endpoints used:
- `GET /api/tags?section=&nsfw=` — loads tags for active section
- `GET /api/tags/resolve?names=tag1,tag2` — resolves tag names → objects for pre-loading

## Development Workflow

```bash
# Start containers
docker-compose up -d

# Watch assets
docker-compose exec app npm run dev

# Run migrations
docker-compose exec app php artisan migrate

# Import tags
docker-compose exec app php artisan danbooru:import-csv
```

## Production Deploy (cPanel)

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan config:clear
php artisan view:clear
```

Storage symlink must exist: `php artisan storage:link`
`.htaccess` must have `Options +FollowSymLinks` for symlinked storage images to serve.

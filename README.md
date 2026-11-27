# FreshRoute

Mobile-first produce market coordination PWA: farmers and cooperatives, buyers, storage providers and transporters, source-labelled prices, food-loss recording. MVP: **Irish potato, Nyandarua-Nakuru corridor**. No payments, escrow or custody. **Not production ready; demo data is synthetic.**

Laravel 13 / PHP 8.4, Blade + vanilla JS (Vite, no CDN), SQLite dev (Postgres-portable), Pest, Pint, Larastan, Playwright.

## Run
```
composer install && npm ci && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed     # SYNTHETIC data
php artisan serve --port=4361
```
Demo accounts and details: docs/DEPLOYMENT.md. Scheduler/queue: `php artisan schedule:work`, `php artisan queue:work`.

## Test
```
./vendor/bin/pest                                    # 272 tests
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse
composer audit && npm audit
npx playwright test                                  # 29 e2e (starts its own server on 4362, own SQLite file)
```
## Docs
docs/ARCHITECTURE, DATA-SOURCES, SECURITY, API (+openapi.yaml), DEPLOYMENT, PILOT, USER-GUIDE (EN + draft SW), KNOWN-LIMITATIONS, DESIGN-FIDELITY, ACCEPTANCE-REPORT. Design package: design/.

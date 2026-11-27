# Deployment (staging only; not deployed, not production)

Nothing has been deployed. Docker and Postgres were not available on the dev machine, so `Dockerfile` and `docker-compose.yml` were **never built or run**; treat them as reviewed-by-reading drafts.

## Local (verified)
```
composer install && npm ci && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed      # seed = SYNTHETIC data
php artisan serve --port=4361            # http://localhost:4361
php artisan queue:work                   # or QUEUE_CONNECTION=sync
php artisan schedule:work                # reminders, matching, expiry, deletions
```
Demo logins (synthetic, local only): `wanjiru@freshroute.test` (farmer), `njeri@freshroute.test` (buyer), `njoro-cold@freshroute.test`, `kariuki@freshroute.test`, `hannah@freshroute.test` (coop admin, TOTP), `admin@freshroute.test` (platform admin, TOTP); password and demo TOTP secret are constants in `database/seeders/SyntheticDataSeeder.php`. Create a real admin with `php artisan fr:make-admin email "Name"` (TOTP forced at first sign-in).

## Staging (to be done by a human)
1. Postgres 16; set `DB_CONNECTION=pgsql` and credentials. Run `php artisan migrate --force` (migrations are portable, only exercised on SQLite so far: run the test suite against Postgres first).
2. `APP_ENV=staging`, `APP_DEBUG=false`, new `APP_KEY`, `APP_URL` https, `SESSION_SECURE_COOKIE=true`, `FR_SHOW_SYNTHETIC_BANNER=true` while data is synthetic. Do not run the synthetic seeder (it refuses in production but not in staging: use `FR_...` discipline).
3. TLS terminated at a reverse proxy (HSTS header is sent on HTTPS); trust proxies configured for the proxy.
4. Processes: web, `queue:work`, scheduler (`schedule:run` each minute). Persist `storage/app/private` (photos).
5. Mail: set a real mailer only after a provider decision; default is the log driver. SMS stays off.
6. Health: `GET /health` (JSON, database check) and `/up`.
7. Price data: no live feed; import only licence-confirmed files (DATA-SOURCES.md).

## Backup and restore
- Database: nightly `pg_dump -Fc freshroute > freshroute-$(date +%F).dump` (SQLite dev: copy `database/database.sqlite` while idle). Restore: `pg_restore -c -d freshroute file.dump`, then `php artisan migrate --force`.
- Photos: back up `storage/app/private/listing-photos` with the dump (they are referenced by `listing_photos.path`).
- Keep `APP_KEY` safe and separate: encrypted columns (TOTP secrets, recovery codes) cannot be read without it.
- Test a restore into a scratch database before relying on it. **Not rehearsed here.**
- Deletion requests must be re-applied after restoring an old backup (anonymised users would reappear).

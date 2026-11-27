# Architecture

Status: Phase B build, **not production ready**. Demo data is synthetic.

## Stack and why
Laravel 13 (PHP 8.4), Blade + vanilla JS bundled by Vite (no CDN, no framework), SQLite for dev/test with Postgres-portable migrations, Pest, Pint, Larastan (level 5), Playwright. This is the studio's recommended stack; no deviation. Reasons: one modular monolith is enough for a single commodity and corridor; server-rendered pages keep low-end phones fast; Laravel gives CSRF, validation, policies, queues, scheduler, notifications and rate limiting without extra services. Postgres is the target database; `docker-compose.yml` exists but was **not run** here (no Docker/Postgres on the dev machine).

## Shape
```
Browser (PWA: Blade pages, sw.js, IndexedDB queue)
   |  HTTPS, session cookie, CSRF
Laravel monolith
   Http/Controllers  -> thin: authorise (policies), validate, call Actions/Services
   Actions/          -> CreateListing, CreateEnquiry, CreateLossEvent, CreateHarvestLot, CreateBuyerNeed,
                        CreateUserPrice, CreateDeliveryRequest  (shared by web forms and offline sync)
   Services/         -> Transitions (only place states change), MatchScorer, LossReconciler, LossReport,
                        Price/{PriceImporter, PriceFeedAdapter, CsvPriceFeedAdapter, PriceStats},
                        SyncProcessor (idempotent), Notifier, ImageSanitiser, Totp, DataExporter, AccountDeletion
   Policies/         -> ownership and role rules
   Console/Commands  -> fr:harvest-reminders, fr:notify-matches, fr:expire-listings, fr:process-deletions,
                        fr:prices:import, fr:make-admin, fr:seed-synthetic
Database (SQLite dev, Postgres staging)  | queue: database driver | mail: log driver (no provider)
```

## Key decisions
- **Commodity-generic model, one-commodity scope.** `commodities` table, every record carries `commodity_id`; seed, UI strings, validation (county list, price bounds, 50 kg bag) and pricing are scoped to Irish potato in Nyandarua and Nakuru (`config/freshroute.php`).
- **Money is integer minor units** (`price_minor`, KES cents). No payments, escrow or custody exist anywhere; the UI says so on enquiry and delivery screens.
- **Origin on every record.** `origin` = `user_submitted | synthetic | officially_published`. Synthetic rows show a badge; a global banner reads "Synthetic demonstration data" (`FR_SHOW_SYNTHETIC_BANNER`).
- **Two price origins, two tables.** `official_prices` (only via the importer; requires source URL, collection date, licence status) and `user_prices`. `PriceStats` reads only the official table. Tests prove user prices never change official statistics.
- **State machines as enums** (`ListingStatus`, `EnquiryStatus`, `DeliveryStatus`) with an explicit transition table, changed only through `Transitions`, each change audited and notified. Tests check every from/to pair.
- **Loss accounting invariant.** harvested = sold + damaged + spoiled + rejected + unaccounted, all terms >= 0; unaccounted is the remainder; a loss that would break it is refused. Rates use only lot-linked losses (known denominator) and are labelled farmer-reported.
- **Offline.** `public/sw.js` caches the shell and visited pages (network-first) and never caches API, photos, exports, MFA or admin. Writes made offline go to IndexedDB with a client UUID. `POST /api/v1/sync` replays them: UUID is the idempotency key (table `idempotency_keys` plus unique `(user, client_uuid)` on each entity). Dates are validated against the capture day. Rejected ops stay visible for the user. Cached pages are cleared at sign-out. Warm-up requests carry `X-FR-Warm` and cannot write the session (`WarmSafeSessionHandler`), found when an end-to-end test lost a one-time flash to a concurrent warm-up request.
- **Matching.** `MatchScorer` scores listing vs need out of 100 (quantity 30, price 25, location 20, quality 15, timing 10) and returns each line with its reason; the UI prints them.
- **Notifications.** One `Notifier`: in-app (database) and email per user preference; SMS is an interface with a sandbox gateway, off by default, UI shows it disabled. Text is stored as type + params and rendered per reader's language.
- **Scheduler** (`routes/console.php`): harvest reminders 06:00, match notifications 07:00, listing expiry 00:10, deletions 02:00, queue worker each minute. Staging compose runs a worker and a scheduler container.

## Data model (main tables)
users(role, coop_id, origin, consent_at, totp_*, notification_prefs) - cooperatives - commodities - farms - crop_profiles - harvest_calendar_entries - listings(+listing_photos) - enquiries - saved_suppliers - buyer_needs - storage_facilities - storage_requests - transporter_profiles - delivery_requests - price_sources - price_import_batches - official_prices - user_prices - harvest_lots - loss_events - audit_logs - idempotency_keys - deletion_requests - notifications - sms_outbox (sandbox) - queue/cache/session tables.

## Roles and scoping
farmer, coop_admin, buyer, storage_provider, transporter, platform_admin. Route groups use `role:` middleware; object access uses policies. Coop admin sees only farmers who joined that cooperative (aggregate losses, listings, member list; never messages). Enquiry content is visible only to its buyer and the listing's farmer, not to admins.

## Not built (see KNOWN-LIMITATIONS)
Payments, GPS tracking, SMS/USSD/WhatsApp, live official price feed, maps, multi-commodity UI, native-reviewed Swahili.

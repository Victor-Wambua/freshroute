# Acceptance report

Scoring: met / partially / not met. Evidence = automated test or file. Not production ready.
Executed gates (this machine, final state): Pest **272 passed (3519 assertions)**; Playwright **29 passed** (3 consecutive full runs after stabilisation; offline file allows 1 retry, see limitations); Pint pass; Larastan level 5 no errors; `composer audit` no advisories; `npm audit` 0 vulnerabilities. **Coverage not measured** (no PCOV/Xdebug; no system extension installed). Postgres, Docker, CI not run here.

## Product acceptance list
| Item | Score | Evidence |
|---|---|---|
| Farmers register (farm, crop profile, capacity, calendar, contacts) | Met | AuthTest, e2e register; farms and calendar pages |
| Cooperative accounts | Met | register coop_admin, MFA forced, coop pages; verification is manual |
| Listings create/update (crop, qty, date, location, grade, price, storage need, photos, availability window) | Met | ListingTest, UploadTest, e2e journey |
| Buyers discover (search, filter location/quantity/grade, compare, price vs official) | Met | ListingTest, e2e |
| Enquiries submit and track; saved suppliers | Met | EnquiryTest, e2e |
| Storage providers register and list (capacity, free, temperature with verification label, price) | Met | LogisticsTest, e2e register |
| Logistics providers register; delivery requests with status tracking | Met (manual status, no GPS) | LogisticsTest, e2e |
| Prices carry source + date (+ licence, origin) | Met | PriceTest |
| Official vs user prices never mixed | Met | PriceTest |
| Real official price data | **Not met** | no licensed feed; synthetic only (DATA-SOURCES) |
| Food loss recorded; reconciliation; reports by commodity and stage; CSV | Met | LossTest, e2e |
| Notifications: enquiries, matching demand, harvest reminders, storage availability, delivery updates, price changes (in-app + email) | Met | NotificationTest; email uses log driver; SMS sandbox only (not met as live channel) |
| Users export their records (JSON + CSV) | Met | PrivacyTest, e2e |
| Offline capture with service worker + queue, idempotent sync | Partially | SyncTest, e2e offline; desktop Chromium only; photos not offline; occasional e2e flake |
| Matching demand to listings with explainable score | Met | MatchTest |
| One commodity, one region, no payments | Met | config, UI wording |
| Roles + RBAC, ownership scoping, MFA for admins, audit log, deletion workflow | Met | AuthzMatrixTest, MfaTest, PrivacyTest |
| Swahili-ready | Partially | EN/SW strings complete but draft, needs native review |

## Universal list
| Criterion | Score | Evidence / note |
|---|---|---|
| Critical journeys work | Met | Playwright journey: list (online and offline) -> enquire -> reply -> delivery -> loss -> report export |
| Data persists | Met | DB-backed tests and e2e |
| Business logic tested | Met | state machines (every pair), loss reconciliation, match scoring, importer rules, idempotency |
| Authn + authz work | Met | role x endpoint matrix, isolation, MFA |
| Critical data protected | Partially | hashing, encrypted secrets, private photos, CSP; no pen test, no at-rest DB encryption, no email verification |
| Error handling | Met | friendly 403/404/419/429/500 pages, validation messages, sync rejections surfaced |
| Reports from actual data | Met | loss report/CSV, coop dashboard computed from records |
| APIs documented | Met | docs/openapi.yaml checked against routes by test |
| Tests executed | Met | numbers above |
| No unresolved critical security issues | Partially | none known; limited review (see SECURITY gaps) |
| Deployable to staging | Partially | Dockerfile/compose/health/runbook exist but were never built or run |
| Docs complete | Met | docs/ list in README |
| Known limitations disclosed | Met | KNOWN-LIMITATIONS |
| Pilot plan exists | Met | PILOT.md |
| Business + impact metrics defined | Met | PILOT.md (no results claimed) |

## Issues found and fixed during QA (real defects)
Missing `User` relation on calendar entries (reminders would have crashed); need status unset after create (matching skipped); price import duplicate-key on re-import (date stored as datetime); unlicensed batch left rows_imported null; service-worker warm-up requests could erase one-time session flash (new `WarmSafeSessionHandler`); rate limiter counted page reads as writes; SQLite lock under concurrent requests (WAL + busy timeout); small tap targets and unstyled untyped inputs.

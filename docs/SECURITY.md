# Security

No compliance claim is made (not Kenya Data Protection Act 2019, not any standard). This lists what was built and what was checked.

## Controls built
- **Auth:** email + password (min 10 chars, letters and numbers, bcrypt), session regeneration on login, login rate limit 5/min per email+IP, generic errors, no account enumeration on reset, suspended/anonymised accounts blocked mid-session.
- **MFA:** TOTP (RFC 6238, implemented in `Totp`, verified against the RFC vectors) is mandatory for platform admin and coop admin: enrolment forced before any page, challenge per session, one-time use of codes (replay refused), 8 hashed recovery codes, 5/min limit. Secrets are encrypted at rest. No QR image (key entered by hand).
- **Authorisation:** role middleware plus policies; ownership checks on every object route; coop scope limited to own members; enquiry content hidden from admins. Tested as a role x endpoint matrix and by isolation tests.
- **CSRF** on all web forms; the JSON sync endpoint needs the CSRF header (fresh token from `/api/v1/session`). Verified: a POST without token returns 419 (Playwright).
- **Input validation** server-side everywhere; Eloquent/parameterised queries; LIKE wildcards escaped; enum-validated filters; Blade escapes output (tested with script/img/svg payloads); CSV exports neutralise formula injection.
- **Uploads:** magic-byte type check (JPEG/PNG/WebP only), 4 MB cap, dimension cap (decompression-bomb guard), GD re-encode to JPEG (drops EXIF/GPS and appended payloads, tested), random names, private disk, authorised serving route with `nosniff`, 4 photos per listing, 30 uploads/hour.
- **Rate limits:** login, register, MFA, writes (60/min), sync (30/min), uploads, exports, public reads.
- **Headers:** strict CSP (self only, no inline script, frame-ancestors none), nosniff, X-Frame-Options, Referrer-Policy, HSTS on HTTPS. No third-party origins (tested).
- **Audit log:** append-only table for auth, MFA, listing/enquiry/delivery transitions, uploads, exports, deletion, admin actions. Never stores secrets; failed-login entries store only a hash fragment.
- **Privacy:** consent at sign-up; approximate location only; phone numbers released after the farmer replies; own-data export (JSON, CSV per dataset); deletion request with 7-day cancellable grace then anonymisation (identifiers and photos removed, loss/price records kept without identity); coop sharing is opt-in by joining.
- **Secrets:** `.env` only; `.env.example` has no secrets; demo TOTP secret and password exist only in the synthetic seeder, which refuses to run in production.
- **Service worker** never caches API, photos, exports, MFA, admin or auth pages; cache cleared at sign-out. Residual risk: on a shared phone, previously visited pages stay in the cache until sign-out.

## Checks executed (results in ACCEPTANCE-REPORT)
`composer audit`, `npm audit`, Larastan level 5, Pint, Pest security tests, Playwright (CSRF 419, MFA flow).

## Known gaps
No external penetration test; no WAF; email verification not enforced; password reset mails go to the log driver; sessions in the database (no device list); `unsafe-inline` allowed for styles (inline style attributes for chart bars); no encryption at rest beyond secrets (rely on disk/DB encryption in staging); retention periods for audit logs and legal retention advice outstanding; TLS termination is a deployment concern (see DEPLOYMENT.md); no virus scanner on uploads (re-encoding mitigates).

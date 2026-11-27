# API

A small same-origin JSON API exists (offline sync and a public official-price feed). The contract is `docs/openapi.yaml`; a Pest test fails if a route under `/api/v1` is missing from it or if it documents a route that does not exist.

| Method | Path | Auth | Purpose |
|---|---|---|---|
| GET | /api/v1/prices | none | Official/published (or labelled synthetic) prices; never user prices |
| GET | /api/v1/session | session | Auth state and fresh CSRF token |
| POST | /api/v1/sync | session + CSRF | Idempotent replay of records captured offline (max 50) |
| GET | /api/v1/listings | session | Open listings |
| GET | /health | none | Liveness and database check |

No tokens are issued; third-party API access is not offered in this version.

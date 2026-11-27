# Data sources: what is live, what is synthetic

| Data | Status | Licence / origin | Notes |
|---|---|---|---|
| Official market prices | **No live feed.** Importer and adapter interface are built and tested. | Real feeds (KAMIS, WFP/HDX) are **not accessed**: licence unconfirmed. | KAMIS: certificate rejected on fetch in Phase A. HDX "Kenya - Food Prices": HTTP 403 on fetch in Phase A and again in Phase B, so its licence field was never read. Do not import until a human reads the licence on the source page and records it. |
| Seeded "official" rows | **Synthetic** | `licence_status = synthetic`, `origin = synthetic`, source "Synthetic sample bulletin (NOT real data)", URL `example.invalid` | Dates are generated relative to today, imported through the real importer. UI badge reads "Synthetic". |
| User-submitted prices | Live (once users exist) | `origin = user_submitted` | Separate table and tab. Never averaged into official figures. |
| Users, listings, enquiries, storage, transporters, losses, harvest lots | Seed = synthetic; real records = user-submitted | `origin` field | Names, phones and quantities in the seed are invented. |
| WRI food-loss figure (KES 72 bn/yr, 30-40%) | Cited in design brief only | Read from the WRI page in Phase A | Not used for any number in the app. No per-stage percentage is shown that was not entered by a user. |
| County production data | Unverified | - | Nyandarua/Nakuru as major potato counties was not verified; confirm with MoALD/county statistics before a pilot. |
| Bag size (50 kg) | Assumption | - | Confirm with the cooperative and weights-and-measures rules. |

## Importer rules (enforced in `PriceImporter`, tested)
1. The price source must carry `open_licence`, `permission_granted` or `synthetic`; `unconfirmed` and `restricted` sources are rejected whole.
2. Each row needs commodity, market, price type, unit, price, collection date (not in the future), source URL (http/https) and licence status.
3. `synthetic` rows may only come from a synthetic source and vice versa.
4. Re-importing the same market/date/source updates, never duplicates.
5. A change of 10% or more against the previous collection notifies farmers and buyers, prefixed "Official" or "Synthetic".

CSV header: `commodity,market,county,price_type,unit,price_kes,collected_on,source_url,licence_status`. Command: `php artisan fr:prices:import file.csv --source=<price_sources.id>`.
To add a live source: confirm licence in writing, create the `price_sources` row, write an adapter implementing `PriceFeedAdapter`, add tests with a recorded fixture (no network in tests).

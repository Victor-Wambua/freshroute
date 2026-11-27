# Design review against requirements

| Spec item | Covered by | Status |
|---|---|---|
| A Registration (farmer, coop, farms, crops, capacity, calendar, contacts) | login, farm profile wireframe, calendar, members | Partly: registration forms are wireframed (01), not clickable beyond role choice |
| B Listings (crop, qty, date, location, grade, price, storage need, photos) | sell wizard, listings | Yes; storage requirement field shown on detail but not entered in wizard (gap) |
| C Buyer marketplace (search, filter, compare, contact, enquiries, saved, track) | market, listing, saved, enquiries | Yes; "location" filter is county/place text, no map |
| D Storage directory | storage | Yes (temperature verification label) |
| E Logistics + delivery requests + status | transport, requests | Yes; provider registration forms not designed |
| F Prices with source/date, never mixed | prices | Yes (synthetic data; real feed outstanding) |
| G Food-loss recording + reports by commodity and stage | losses, reports | Yes for one commodity; multi-commodity report is a build concern |
| H Notifications | alerts, spec | In-app/email designed; SMS outstanding |
| I Offline-friendly | offline toggle + queue | Designed and simulated, not a real service worker |
| Export own records | mydata | Designed (simulated) |
| 60-second listing | sell | 9 taps verified; real timing needs user testing |

## Honest gaps
Real price source and licence unconfirmed; county choice not verified against production data; Swahili strings are machine-drafted-style and need native review; no usability testing with farmers; no screen-reader testing; registration, provider onboarding and matching-demand ("buyer posts a need") flows not prototyped; quality photos are placeholders; hi-fi gallery uses screenshots of the prototype rather than separately drawn art.
## Open questions
1. Will the first pilot cooperative confirm bag size and typical prices? 2. Is a coop admin also a farmer user in the same account? 3. Which email provider (cost)? 4. Who verifies storage temperature claims? (Not critical to Phase A.)

# UX review

**Method.** Ran the app locally on synthetic seed data, signed in as farmer, buyer, storage provider and transporter, and captured every main screen at 375x812 (light) and the market at 1280x800, using the cached Chromium through Playwright. Automated checks per page: horizontal overflow, single h1, touch targets under 44 px. Manual review of hierarchy, forms, offline messaging, photo upload, price labelling. Dark mode and desktop of every screen were covered by the existing e2e screenshot suite (docs/screenshots) but not re-reviewed by eye.

| # | Sev | Screen | Problem | Fix | Status |
|---|-----|--------|---------|-----|--------|
| 1 | High | Offline sync | After reconnecting, one failed sync attempt waited for the 30 s poll; likely cause of the flaky offline e2e (20 s wait) | Retry with backoff (1.5 s doubling, max 20 s) | Fixed (flake not reproduced locally in 4 full runs; retry kept) |
| 2 | High | Sell: photo | `capture` forced the camera, no gallery choice; phone photos over 4 MB were refused on slow links | Removed `capture`; photos over 900 KB are shrunk in the browser to 1600 px JPEG before upload, with a "Photo ready (KB)" status | Fixed (canvas path verified only by code, not with a real large photo) |
| 3 | Medium | Market (phone) | Five stacked filters pushed results below the fold | County, bags, sort, grade collapse behind a Filter button (open if any filter applied; always visible without JS and on wide screens) | Fixed, e2e added |
| 4 | Medium | Prices (phone) | Source column wrapped badly and pushed licence status off-screen | Source column hidden under 600 px; the card above and licence status still show source and "synthetic" labels | Fixed, e2e added |
| 5 | Low | Prices tabs | Pill badge nested inside a button looked clipped | Plain coloured label | Fixed |
| 6 | Low | Compare / checkboxes | 24 px inputs (rows are 48 px) | None needed: the label row is the target | Not changed |
| 7 | Low | Sell wizard | Progress bar has no "Step n of 5" text | - | Not fixed |
| 8 | Low | Prices | Source URL shown as raw example.invalid text (synthetic) | Kept: honesty label | Not changed |

**Not reviewed / not fixed.** Real low-end device and throttled-network timing; screen reader testing; Swahili copy; print styles; admin and coop screens by eye; the design/prototype was not changed (no drift: only the phone market filter and prices table differ).

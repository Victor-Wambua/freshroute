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

## Round 2

**Scope.** Closed the round-1 "not fixed / not reviewed" list and added automated accessibility, keyboard, print, performance and Swahili checks. All new checks are Playwright e2e tests (`tests/e2e/a11y|keyboard|print|perf|photo|i18n.spec.js`, shared screen list in `tests/e2e/pages.js`) and run in CI with the rest of the suite. `axe-core` was added as a dev-only dependency; the tests bypass CSP in the test browser only, the app CSP is unchanged.

### 1. Automated accessibility (axe-core 4, WCAG 2.0/2.1/2.2 A and AA plus best-practice rules)
69 screens across 7 roles (public, farmer, buyer, storage provider, transporter, coop admin, platform admin), each at 375 and 1280 px, light and dark (276 page views).

| | serious | critical | moderate | minor |
|---|---|---|---|---|
| Before | 424 | 8 | 4 | 8 |
| After | 0 | 0 | 0 | 0 |

Counts are rule violations per page and view. Causes fixed: the header brand link had no accessible name on phones (text was `display:none`); the language button's accessible name did not contain its visible text ("SW"/"EN"); the harvest calendar used grid roles without rows; scrollable table regions were not keyboard-focusable (now `tabindex=0`, `role=region`, labelled, with a visible "Swipe sideways" hint on phones); empty table headers; a skipped heading level on the welcome page; two identically named regions on admin prices.

### 2. Keyboard-only walkthroughs (e2e, `keyboard.spec.js`)
Tab / Shift+Tab / Enter / Space only. Every farmer and buyer page is tabbed through to the end: no trap, no positive tabindex, no invisible focus stop, a 3 px focus ring on every stop (the native calendar button inside date inputs is browser-drawn and excluded). Also covered: skip link (first stop, moves focus to main), sign-in, the complete 5-step sell wizard including Back, the phone Filter disclosure and the compare checkboxes. The app has no modal dialogs or sheets, so focus trapping and restoring is not applicable.

### 3. Print styles (`print.spec.js`)
Found: reports printed on a tinted background with filter forms and buttons, and the synthetic-data notice was hidden in print. Now: forced white/black tokens (also from dark mode), app chrome, forms, buttons and pagination hidden, the synthetic notice kept, a print footer with date and the "not production ready / synthetic" statement, tables repeat headers and do not split rows, bars print in grey. Checked on loss report, coop dashboard/members/reports, admin audit/users, compare, listing, prices.

### 4. Remaining roles and screens
Reviewed admin and coop screens at 375 (light) and 1280 (dark). Fixed: Swahili admin tab bar and long emails widened the phone viewport (515 px); the audit table header "User" was hard-coded English; listing page file input overflowed 29 px. Suspending a user now asks for confirmation (`data-confirm`, external JS, no inline handler); the action is reversible and audited as before.

### 5. Contrast
Axe measured every rendered text node in both themes: 0 failures. Elements axe cannot resolve (SVG chart text, text over gradients) were checked with `tools/contrast.mjs` token pairs, now including ink and ink-2 on the green and amber tints: 0 failures in light and dark (`design/contrast-results.md` regenerated).

### 6. Round-1 items
- "Step n of 5" shown above the wizard progress bar (live region, English and Swahili).
- Compare checkbox rows are 48 px high and the whole label is the hit area (e2e checks size and a corner click).
- Photo shrink verified with a real generated 2000x1500 PNG of noise, over 4 MB: shrunk in the browser to JPEG under 4 MB and accepted by the server (`photo.spec.js`). It is not proof for every phone camera format (HEIC is not accepted by the app at all).

### 7. Prototype sync
`design/prototype/`: visible "Step n of 5", 48 px compare rows, print rules, and a persistent "Simulated - design prototype" banner. Still self-contained, no network.

### 8. Performance (`perf.spec.js`, Chromium at 375 px, service worker blocked, no compression on the dev server)
Every page of the farmer, buyer, coop and admin roles: largest 46 KB uncompressed (admin verify, my-data, coop), 5 requests (document, CSS 20 KB, JS 10 KB, two icons), CLS 0.0000 everywhere. No cheap wins left; budgets of 400 KB, 25 requests, CLS 0.1 are asserted as regression guards. Listings with photos were not in the seed so photo weight is not included.

### 9. Swahili (`i18n.spec.js`)
Every screen rendered in both languages at 375 px: no raw translation keys, no horizontal overflow (after the fixes above), key parity test already in Pest. Remaining English in Swahili mode: the unit "50 kg bag" (config), the commodity name "Irish potato", and framework validation messages (e.g. "The selected sort is invalid."), plus user data. Shortened the Swahili admin tab "Kumbukumbu ya ukaguzi" to "Ukaguzi" so five tabs fit. **The Swahili text is a machine-assisted draft and has NOT been reviewed by a native speaker; that review is still required.**

### Tests
Pest 272 passed; Pint and Larastan clean; `composer audit` and `npm audit` clean; Playwright e2e 61 tests (29 existing plus new axe x7, keyboard x6, print x4, perf x4, photo, Swahili x7, admin/compare). Final full local run: 61 passed, 0 failed. A first CI run failed 4 tests (Swahili overflow of the delivery step labels on phones, and TOTP codes already spent by another spec); fixed with wrapping step labels and a shared `submitTotp` helper that never reuses a spent code. Screenshots: `docs/screenshots/ux-review/round2-*.png`.

### Still open (cannot be done here)
Real screen-reader testing (VoiceOver, NVDA, TalkBack); real low-end devices; throttled real-network timing (no emulated throttling was run); Swahili validation messages and unit/commodity names; native Swahili review; photos in performance numbers.

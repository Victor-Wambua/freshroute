# Prototype verification (headless Chromium, Playwright)

Run: `cd tools && node serve.mjs ../design 4360 &` then `node clickthrough.mjs`.

```
PASS  login screen renders 4 roles
PASS  Swahili toggle labels nav
PASS  English toggle back
PASS  quality required validation
PASS  listing published and appears in My produce - 9 taps, 4.746s incl. 450ms waits
PASS  new listing taps <= 10 - 9
PASS  bags=0 shows error
PASS  offline banner
PASS  offline create queued
PASS  sync clears queue
PASS  loss validation
PASS  loss recorded
PASS  official tab: no user-submitted rows
PASS  user tab: no official rows
PASS  error state shown
PASS  retry recovers
PASS  search filters
PASS  empty state
PASS  listings restored after clear (6 seeded + 2 created earlier this session)
PASS  min-bags filter
PASS  saved supplier appears
PASS  compare dialog
PASS  enquiry validation
PASS  enquiry tracked
PASS  storage filter hides full
PASS  delivery request created
PASS  status advances
PASS  coop dashboard
PASS  member search
PASS  SMS toggle disabled (outstanding)
PASS  no horizontal overflow at 390px (54 route/theme combos)
PASS  rendered-text contrast (1558 text nodes, light+dark; gradient backgrounds skipped)
PASS  touch targets >= 44px (interactive elements measured)
PASS  no console errors/warnings/page errors

34 passed, 0 failed
```

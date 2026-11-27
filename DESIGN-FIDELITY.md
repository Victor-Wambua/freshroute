# Design fidelity

Reference: approved design in `design/` (tokens, `screen-specifications.md`, screenshots in `design/screenshots/`). Real-app screenshots captured by `tests/e2e/screens.spec.js` into `docs/screenshots/` (60 PNGs: mobile 390x844@2x and desktop 1440x900, light and dark, 15 screens) from the running app with synthetic data. I opened the farmer home pair (design vs build, mobile light) and compared; the other pairs were generated but compared only through the automated checks below, not eyeballed one by one.

## What matches
- Tokens: `design-tokens.css` and the prototype `styles.css` are the base of `resources/css/app.css` (colours, radius, touch sizes, type stack, light/dark). Official and user-submitted colours are used only for those badges/banners.
- Layout: sticky top bar (brand, language, theme, alerts), raised clay "Sell" tab, 5-item bottom bar on phones, 260 px side nav from 900 px, cream paper background, serif display type, big 48-72 px controls.
- Components reused from the prototype: buttons, chips, big-choice quality/stage buttons, steppers, progress dots, cards, badges, banners, step trackers (enquiry 3 steps, delivery 4 steps), bar rows for loss by stage, calendar grid, tables inside their own scroll container.
- Sell wizard: 5 steps as specified (bags, date, quality, price with official reference, review) with chips and steppers.
- Official vs user-submitted prices are separate tabs with distinct badges; stale warning after 7 days; sparkline replaced by an accessible inline SVG line chart with a text alternative.
- Automated checks (Playwright, 390 px): no horizontal overflow, interactive targets >= 44 px (design target 48), every input labelled, one h1 per page, tab bar visible and side nav hidden on phones, side nav at >= 900 px, EN/SW toggle, dark theme persists.

## Deviations (honest list)
1. Farmer Home KPI cards are a 2x2 grid with icons; the design shows a single column of text cards with a "Sell - new listing in 4 taps" label and the farmer's first name. Official reference shows KES per 50 kg bag (design prototype showed KES per kg).
2. Notification dot on the bell is drawn only when unread alerts exist (design always shows it); the offline pill appears only when offline or items wait (design had a permanent wifi button).
3. Navigation is multi-page Blade with real URLs, not hash routes; the offline toggle in the prototype is replaced by real connectivity detection.
4. Compare is a separate page (not a dialog); record-loss and reply forms are inline (not dialogs).
5. Gap screens designed here following the system, not in the original prototype: registration per role, provider onboarding (storage facility, vehicle and routes), buyer "post a need" with match score explanations, MFA setup and challenge, My data, admin pages (users, verification, audit, deletions, price sources), farm profile.
6. Logo uses the design SVG symbol; PNG app icons are generated from it.
7. Swahili strings are a new draft (the prototype's drafts were not carried over verbatim) and need native review.
8. No audio, voice or USSD screens (design noted them as ideas).
Not verified: pixel comparison, 320 px, tablet, landscape, screen readers.

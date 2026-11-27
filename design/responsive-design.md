# Responsive design

Mobile is designed first (390 px reference), not shrunk from desktop.
| Breakpoint | Layout |
|---|---|
| < 760 | single column; bottom tab bar (64 px, 5 items); sticky top bar with offline, language, theme, alerts; brand text hidden < 480 to fit |
| 760-899 | two-column card grids, tab bar retained |
| >= 900 | fixed 260 px side nav, tab bar hidden, content up to 1120 px, 2-4 column grids, toolbars in one row |
Choices: tab bar over hamburger (one-thumb reach); hero action raised; tables (coop members, compare) scroll horizontally inside their own container, never the page; dialogs max 560 px; 18 px base text on mobile. Verified: no horizontal overflow at 390 px for 54 route/theme combinations; screenshots at 1440x900 and 390x844 (2x), light and dark.
Not yet tested: 320 px width, landscape phone, tablet 768, real low-end devices, 200% text zoom (open items).

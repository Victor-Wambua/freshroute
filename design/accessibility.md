# Accessibility

Target: WCAG 2.2 AA for the design. Not an audit or compliance claim; no screen-reader testing was done.

## Computed
- Token pairs: `contrast-results.md` (light and dark, 0 failures; text 4.5:1, borders/focus 3:1).
- Rendered DOM: click-through computes colour vs nearest opaque background for every visible text node on 27 routes in light and dark (about 2,200 nodes at last run; gradient-background text skipped, which is only the white-on-brown listing icons, no text). Result in `verification-results.md`.
- Touch targets: all measured interactive elements >= 44 px (design: 48-72 px).

## Design measures
Semantic landmarks (header, nav, main, aside), skip link, visible 3 px focus ring, native `<dialog>` with focus handling, `aria-live` on result counts and toasts, `role="alert"` on form errors with `aria-invalid` and `aria-describedby`, labels on all inputs, status never by colour alone (text + icon + badge label), `aria-pressed`/`aria-selected` on chips and tabs, `aria-current` on nav, table headers with scope, reduced-motion respected, page zoom allowed, `lang` switches to `sw`.
## Low-literacy and low-bandwidth
Icons plus words, numerals large, quick-pick chips instead of typing, plain sentences, no jargon, system fonts, no images except user photos (compressed in build), whole prototype ~100 KB with no network calls.
## Known gaps
No screen-reader pass (VoiceOver/TalkBack), Swahili strings unreviewed, dialog focus-return not tested, voice input and audio prompts are ideas not designs, colour-blind simulation not run (labels carry meaning).

# Prototype

Self-contained vanilla HTML/CSS/JS in `prototype/` (no CDN, no network calls, ~100 KB). Everything is simulated: banner "Synthetic demonstration data. Simulated - design prototype."

Open: `cd tools && npm install && node serve.mjs ../design 4360`, then http://localhost:4360/prototype/ (any static server works; file:// also works).
Start screen picks language and role. Top bar: offline toggle (wifi icon), EN/SW, dark theme, alerts.

Try: Farmer > Sell (9 taps) / toggle offline then Sell then Send now / Losses > Record. Buyer > search "Njoro", compare two, View > Send enquiry (try 99999 bags). Farmer > More > Prices (two tabs). Provider > Transport > Request delivery > Deliveries > Update status. Coop > Members search; More > Demo error state.
Hash routes: `#/farmer/home`, `#/buyer/market`, `#/provider/storage`, `#/coop/dashboard`.
Files: `data.js` (synthetic fixtures + EN/SW strings), `app.js` (state, routes, actions), `styles.css`, `tokens.css` (copy of tokens).
Simulated: sign-in/code, photo upload, exports, SMS/email, sync, carrier updates.
Verification: `tools/clickthrough.mjs` (34 checks) results in `verification-results.md`.

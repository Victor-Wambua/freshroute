# Screen specifications

Route = `#/<role>/<route>` in the prototype. Data is synthetic. "Validation" lists rules that Phase B must enforce server-side too.

| Screen | Role | Purpose / content | Interactions | States / validation |
|---|---|---|---|---|
| login | all | Language chips, phone, role cards | choose language, role | phone simulated; real: code or email+password+TOTP |
| home | farmer | Sell hero, 4 KPI cards (next harvest, enquiries, official price, losses), my listings | cards link to screens | empty listings state |
| sell | farmer | 5 steps: bags, date, quality, price (+ official reference, photo), review | steppers, chips, back/continue | bags 1-5000; date not past; quality required; price 500-20000; offline -> queue; photo: type/size/magic-byte check + re-encode in build |
| listings | farmer | own listings with Live/Queued badge | add | - |
| calendar | farmer | month grid, upcoming list, add date | add | colour + text for harvest days |
| losses | farmer | record (stage, bags, reason), history, total | dialog | bags >= 1; offline -> saved on phone |
| enquiries | farmer, buyer | cards with status steps | reply, mark agreed (farmer) | no money handled note |
| prices | all | tabs Official / User-submitted; per row market, unit, price, collected date, source, age warning >7 days, trend | tab switch, submit price | user rows never in Official; source and date mandatory |
| mydata | farmer, buyer, coop | export CSV/JSON, deletion request, privacy note | export (simulated) | - |
| more | all | secondary nav, demo error, sign out | - | error state demo |
| market | buyer | search, location, min bags, sort, grade chips, result count, cards | save, compare (max 3), view | empty state; result count aria-live |
| listing | buyer | detail, storage need, approximate location, enquiry button | enquiry dialog | bags 1..available |
| saved | buyer | saved suppliers | - | empty state |
| storage | provider/farmer | directory: capacity, free, temperature + verification label, price | free-only filter, ask for space | "Verified" reserved for visited sites; Full state |
| transport | provider/farmer | vehicle, capacity, routes, price, availability | request delivery dialog | bags > 0 |
| requests | provider/farmer | delivery cards with 4-step status | carrier updates status | no GPS in MVP |
| dashboard | coop | KPIs, supply by week, loss by stage | - | - |
| members | coop | searchable table (name, place, bags, listings, loss %, last active) | search, invite | scope: own coop only |
| reports | coop | loss report by stage, exports | CSV/PDF | labelled self-reported |
| alerts | all | notification list, channel switches | in-app, email on; SMS disabled | SMS needs provider |

## Notification design
Types: new enquiry, enquiry reply, harvest reminder, matching demand (buyer posts need), storage availability, delivery update, price change (official only, text prefixed "Official"). Channels in MVP: in-app + email. SMS, USSD and WhatsApp: outstanding (provider/cost/approval needed). Bell badge for unread; each item: icon, one sentence (EN/SW), time.

## Offline queue design
Offline toggle in top bar (prototype stand-in for navigator.onLine). Queue holds listing, enquiry, loss. Banner shows count. On reconnect: banner with "Send now" plus auto-sync in build. Build notes: service worker caches shell + fixtures; IndexedDB queue with client-generated IDs (idempotent); last-write-wins for own records, server wins for status fields.

## USSD sketch (later channel, not designed in detail)
`*xyz#` -> 1 List produce (crop fixed, bags, harvest in days, quality, price) / 2 Price (official, market, date) / 3 Record loss (stage, bags) / 4 My enquiries. Needs a provider; outstanding.

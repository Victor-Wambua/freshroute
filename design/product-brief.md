# FreshRoute - product brief

Founder: Victor Wambua, Nairobi. Product 4 of 10. Phase A (design) only; no backend exists.

## 1. One-line product
A mobile-first market-coordination PWA that connects Irish-potato farmers and cooperatives in the Nyandarua-Nakuru corridor with buyers, storage and transporters, shows source-labelled prices, and records food loss by value-chain stage. FreshRoute never holds, escrows or moves money.

## 2. Research confirmation (what was actually read)

Method: pages fetched with a web-fetch tool and web searches on 27 Nov 2026. Search results are summaries, not primary reads; anything marked "search summary" is weaker evidence and must be re-confirmed from the primary page before Phase B relies on it.

### 2.1 WRI Kenya food-loss study (fetched)
Source: WRI research page "Food Loss and Waste in the Maize, Potato, Fresh Fruits and Fish Value Chains in Kenya" (URL in the spec). Read in full by fetch.
- Confirmed: estimated economic loss of **KES 72 billion per year** (about USD 578 million); publication date 2 September 2025; crops covered: maize, potato, fresh fruits, fish; overall loss/waste of **30-40%** between production and consumption.
- Caveats on the page: limited capacity of farmers, businesses and government to measure and report losses; no robust monitoring or standard measurement methods; evidence gaps on the size of losses and the critical loss points; weak policy coordination and financing.
- **Mismatches with the brief:** (a) The page gave no per-commodity or per-stage loss percentages, so none are used anywhere in this design; the loss-report screens use clearly labelled synthetic numbers. (b) The page names no counties, so the county choice below does not come from WRI. (c) "Varies by crop/stage" in the spec is plausible but the fetched page did not show the breakdown (it may be in the full PDF, which was not fetched).
- Design consequence: the measurement gap WRI describes is exactly what the loss-recording feature addresses. Positioning must say "farmer-reported", never "measured".

### 2.2 MVP commodity and region (decision)
**Irish potato, Nyandarua + Nakuru counties (Ol Kalou, Kinangop, Ol Joro Orok, Molo, Njoro, Rongai), reference market Nakuru.**
Evidence for:
1. Potato is one of the four value chains in the WRI study, so a documented loss problem exists (fetched).
2. Potato price reporting exists by region including Nakuru: Food Security Portal monthly Kenya reports (search summary) cite a Daily Market Survey and gave a national average of 52 KES/kg (March 2026 report) and a Nakuru wholesale figure of 47 KES/kg (Dec 2025 report). Not confirmed that this survey equals KAMIS.
3. KAMIS (search summary of KALRO text) claims five markets in each of 47 counties and 150+ products across wholesale, retail and farm-gate prices, so a nationally structured official price source plausibly exists.
4. Potato is perishable and short-cycle, so listing-to-harvest coordination, cool/ventilated storage and transport timing matter more than for storable maize.
Evidence not obtained: county production shares. Nyandarua and Nakuru being major potato counties is general knowledge that was **not verified in this session**; confirm with official county/MoALD production statistics before pilot. Tomatoes and the other options were considered; WRI does not cover tomatoes and no tomato evidence was fetched, so they were not chosen. Maize is storable and has a different (NCPB-influenced) market; deferred.
Unverified design assumption: unit = 50 kg bag. Real bag sizes and any weights-and-measures rules must be checked with the cooperative and the regulator before pilot.

### 2.3 Price sources and licences
| Source | What was verified | Licence / access status |
|---|---|---|
| KAMIS, kamis.kilimo.go.ke (MoALD/KALRO) | Fetch failed (certificate rejected). A search surfaced its About page and KALRO text describing coverage (see 2.2) | **Unknown.** No terms found. A third-party note says rows can omit grade, volume or farm-gate value |
| WFP VAM / HDX "Kenya - Food Prices" | HDX dataset exists (search summary): monthly update expectation, last modified Aug 2024 on the cached copy; fetch of HDX and VAM pages returned 403 | **Unconfirmed.** Check the licence field on the HDX page by hand before importing |
| Food Security Portal Kenya monthly reports | Search summary: reports cite a "Daily Market Survey" for price tables | Re-use terms not checked |
| FEWS NET | Not researched | Unknown |
| opendata.go.ke | Not fetched | Unknown |
Decision for the prototype and MVP build: **no real price is shown as data**. The Official tab uses synthetic rows labelled "Sample bulletin (synthetic)". An adapter interface (commodity, market, unit, price, date, source, source URL, licence, retrieval time) is specified, with real import only after licence confirmation. User-submitted prices live in a separate table and a separate tab and are never averaged with official ones.

### 2.4 Competitor scan (only what search results supported)
- **Twiga Foods (Kenya):** B2B marketplace aggregating informal retail demand; per a GSMA programme page (older) it used collection centres and M-Pesa payments to farmers; later reports mention moving into farming and a logistics-marketplace pivot (the TechCrunch item's date was not checked). Capital-heavy aggregator model; FreshRoute is coordination-only and does not buy, hold or pay.
- **iProcure:** described by a start-up directory as an agricultural supply-chain platform (directory self-description, not verified). Input-focused rather than produce coordination.
- **Tulaa:** Nairobi mobile-first marketplace for smallholders; seed round reported at USD 627,000 in 2018. Later status contested in sources (one item alleges collapse, CB Insights says it ceased operations); **no primary source seen, do not state either**.
- **M-Farm:** SMS price service (shortcode given inconsistently as 3555 and 3535 by different pages); status not confirmed.
- **Apollo Agriculture, Farmcrowdy and similar:** not researched here. Not verified.
Differentiation hypotheses (to test, not claims): produce-level coordination in one corridor; loss recording as a first-class feature; strict official-vs-user price separation; offline-first PWA; no payments (lower regulatory surface).

### 2.5 WhatsApp / USSD realities (reasoning, not fetched evidence)
No source on farmer channel usage was fetched. Working assumptions to validate in field interviews: many farmers use feature phones or low-end Android, intermittent data, mixed literacy, Swahili plus local languages. Design response: PWA with icon-led UI, Swahili/English toggle, large targets, offline queue, no typing for the common flows. USSD and WhatsApp Business notifications both need a provider or Meta onboarding and costs; both are listed as **outstanding dependencies** and shown disabled in the UI. A USSD menu mirror of "list produce / check price / record loss" is sketched in screen-specifications but not designed in detail.

### 2.6 Legal and regulatory notes (no compliance claims)
- No escrow, custody, payment collection or money transfer. Enquiries are not orders; copy says payment is agreed directly. This is a product boundary chosen to avoid payment-service regulation; legal advice needed to confirm.
- Kenya Data Protection Act 2019: FreshRoute processes names, phone numbers, farm locations. A search summary says the ODPC adopted a Guidance Note on registration of data controllers and processors on 30 Nov 2023; the registration process, fees and thresholds were not fetched. Design measures: consent at sign-up, phone shown only after reply, own-data export, deletion request, approximate location in public listings. Nothing here is a compliance claim.
- Produce quality: grades A/B/C are the seller's own description with on-screen disclaimer; no certification or standards (e.g. KEBS) claims.
- Storage temperature: shown as self-declared until a site visit verifies it; the label "Verified" is reserved.

## 3. Problem, users, scope
Users: smallholder farmer (primary), cooperative admin, buyer (trader, hotel, processor, market agent), storage provider, transporter. Jobs: sell before spoilage; find a fair reference price; line up storage/transport; know where losses happen.
In scope (MVP): registration, listings, buyer search/compare/enquiry tracking, storage directory, transporter directory + delivery requests with manual status, source-labelled prices, loss recording and reports, in-app + email notifications, own-records export, offline queue.
Out of scope: payments, escrow, credit, GPS tracking, nationwide launch, other commodities, SMS/USSD/WhatsApp (outstanding).

## 4. Commercial (from spec)
Buyer subscriptions, cooperative subscriptions, storage listings, logistics subscriptions, enterprise supply-chain intelligence, NGO-supported farmer deployments. No pricing validated.

## 5. Success measures (pilot, to be defined with partners)
Listing created in under 60 s (design target: 9 taps; timing to be user-tested); share of listings receiving an enquiry; loss records per farmer per season; official-price page views; cooperative weekly active admins.

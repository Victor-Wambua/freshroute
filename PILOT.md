# Pilot plan (nothing here is claimed as achieved: no pilot has taken place)

## 1. Pilot guide
Scope: one cooperative in Nyandarua or Nakuru, Irish potato, 30-60 farmers, 3-5 buyers, 1-2 storage sites, 2-3 transporters, 12 weeks (one harvest cycle). Staging deployment only; start only after: licence-confirmed price source or an honest "no official price" mode, native Swahili review, bag size confirmed, data-protection review by counsel, email provider chosen, remove the synthetic seed and set `FR_SHOW_SYNTHETIC_BANNER=false`.

## 2. Cooperative onboarding
1. Agree scope, roles, data sharing (members opt in by joining) and a named cooperative champion.
2. Coop admin registers, sets up TOTP, is verified by platform staff (documents checked off-platform).
3. Champion registers 5 farmers in person on their phones (30 min session): account, farm, crop profile, join coop.
4. Practice session: list produce online, then in airplane mode; record a loss; read a price with its source.
5. Buyers, storage and transport providers are invited by the coop and onboarded the same way; storage temperature claims stay "self-declared" until a site visit.
6. Weekly 30-minute call; WhatsApp group for support (outside the product).

## 3. Support workflow
Tier 1 champion (how-to, password reset request); tier 2 FreshRoute support via email (account, data, bugs); tier 3 engineering (audit log, logs). Severity: S1 data exposure or wrong money-related claim (same day), S2 core flow down (1 business day), S3 other (5 days). Account deletion and export requests follow the in-app workflow; staff use Admin > Users, Deletions, Audit log. Every support action that touches data is written to the audit log.

## 4. Success metrics (definitions; targets to set with the cooperative)
Activation: farmers with >=1 listing in week 2. Listing speed: median time from tapping Sell to publish (instrumented later; design target 60 s, unvalidated). Match: share of listings with >=1 enquiry; enquiry reply rate and median reply time; enquiries reaching Agreed. Logistics: delivery requests accepted/delivered. Prices: weekly users viewing prices; user price reports per week. Reliability: offline items synced without rejection; sync rejection rate. Coop: weekly active admins.
**Food-loss measurement method.** Loss is farmer-reported, not measured. Unit: 50 kg bags. Each harvest lot records harvested and sold bags; each loss event records stage (harvest, handling, storage, transport, market), kind (damaged, spoiled, rejected) and reason; unaccounted = harvested - sold - recorded losses (never negative). Loss rate = lot-linked loss bags / harvested bags in lots (unlinked events are excluded from the rate because the denominator is unknown). Validation: spot-check 10% of lots by cooperative staff with a scale on one day; compare reported vs weighed; report the gap. Bias risks: under-reporting, rounding, bag size variation, recall. Baseline vs end-of-pilot comparison is only meaningful with the same farmers and enough lots; no result is claimed.

## 5. Feedback collection
In-person weekly check-in notes, a short end-of-week questionnaire (5 questions, Swahili/English), support-ticket tags, and session observation of 5 farmers using the sell and loss flows. Findings feed a prioritised backlog reviewed fortnightly.

## 6. Risk register
| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Low smartphone/data access; mixed literacy | High | High | Coop champion, icon-led UI, offline queue, Swahili review, consider USSD later |
| No licensed official price source | High | Medium | Honest "no official price" mode, user-reported tab clearly separate, seek KAMIS/WFP permission |
| Users read synthetic or user prices as official | Medium | High | Origin badges, separate tabs, banner, tests |
| Disputes after enquiry (no payment handled) | Medium | Medium | Clear no-money wording; status trail in audit log |
| Data protection (phones, locations) | Medium | High | Consent, minimal data, export/delete, counsel review, no compliance claim |
| Storage temperature claims untrue | Medium | Medium | "Self-declared" label, staff verification before "Verified" |
| Loss data gaming or poor quality | Medium | Medium | Spot checks, lot reconciliation, "farmer-reported" label |
| Buyer/seller disintermediation | High | Medium | Value from prices, matching, loss reports, logistics |
| Single developer / support capacity | High | Medium | Narrow scope, runbooks, champion-led support |
| SQLite-to-Postgres untested | Medium | Medium | Run suite on Postgres in staging before pilot |

## 7. Business model (hypotheses from the spec, no pricing validated)
Revenue lines: buyer subscriptions; cooperative subscriptions; storage-provider listing fees; logistics subscriptions; enterprise supply-chain intelligence (aggregated, anonymised loss and supply data, only with consent); NGO-funded farmer deployments. Farmers free. No payment handling, so no take-rate. Costs to estimate with real quotes: hosting, email/SMS, support time, field onboarding. Test willingness to pay with the coop and buyers during the pilot; do not publish prices before that.

## 8. Impact metrics (to be measured, not claimed)
Reported loss rate by stage vs baseline (same farmers); share of harvest sold within 7 days of harvest date; bags moved through storage listed; price-information use; farmer-reported income change (survey, with caveats); cooperative reporting time saved. Context only: WRI (Sept 2025) estimates KES 72 bn annual loss across maize, potato, fruit and fish in Kenya; it names no stage percentages and FreshRoute makes no loss-reduction claim.

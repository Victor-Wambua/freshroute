# User journeys

All journeys are implemented as clickable flows in `prototype/`. Content is synthetic.

## Personas (synthetic)
- **Wanjiru Kamau**, 41, farmer, Ol Kalou. Android phone, patchy data, reads Swahili more comfortably than English. Goal: sell 120 bags before rain damage.
- **Hannah Wafula**, coop secretary, Kinangop. Laptop at the coop office. Goal: see supply and losses across members and export reports to the board.
- **Mama Njeri**, buyer, Nakuru greens trader. Goal: find 100+ bags this week and compare price and harvest date.
- **Njoro Cold Store** / **Kariuki Haulage**: providers wanting visible capacity and routes.

## J1 Farmer creates a listing (target under 60 s, 9 taps)
1. Open app (language remembered) - Home. 2. Tap big "Sell". 3. Bags: tap chip (50). Continue. 4. Harvest date chip. 5. Quality A/B/C. 6. Price stepper (official reference shown). Continue. 7. Check, Publish.
Errors: bags outside 1-5000, past date, no quality, price outside KES 500-20,000. Offline: "Save to queue", listing shows Queued.
Verified in the Playwright click-through: 9 taps from Home.

## J2 Farmer offline then back online
Toggle offline (banner with count). Create listing or loss record: saved on phone, badge "Queued". Back online: banner "n waiting" with "Send now"; success toast; queue cleared.

## J3 Farmer handles an enquiry
Bell or Home card "1 new" - Enquiries - Reply (dialog) - status Replied - Mark agreed. Footnote: no money handled.

## J4 Farmer records a loss
Losses - "Record a loss" - stage big buttons - bags - reason - Save. Appears in history; total updates. Validation on bags.

## J5 Buyer discovers and enquires
Market - search/filter (place, min bags, grade, sort) - save supplier (heart) - tick Compare on up to 3 - Compare dialog - View listing - Send enquiry (validated against bags available) - Enquiries tracker (Sent, Replied, Agreed).
Empty state: no matches offers "Clear filters". Saved with none: empty state with link to Market.

## J6 Anyone checks prices
Prices - Official tab (market, unit, price, collected date, source, age warning after 7 days, 8-week trend) or User-submitted tab (warning banner). Submitting a price adds only to the user tab.

## J7 Storage and transport
Storage: filter "free space", ask for space. Transport: Request delivery (bags) - Deliveries with four-step status (Requested, Accepted, Picked up, Delivered) updated by the carrier.

## J8 Coop admin
Overview KPIs and charts - Members table with search - Reports (loss by stage, exports CSV/PDF) - Alerts and channel settings.

## J9 Errors and recovery
Simulated load error with Try again (More menu in prototype). Loading skeleton on every navigation. Form errors are inline with role="alert" and focus returns to the field.

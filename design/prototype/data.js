/* SYNTHETIC DEMONSTRATION DATA. Every name, quantity and price below is invented for the design prototype. */
window.FR = {
 today: '2026-11-27',
 crop: { en: 'Irish potato', sw: 'Viazi mviringo' },
 unit: { en: '50 kg bag', sw: 'gunia la kg 50' },
 me: { farmer: { name: 'Wanjiru Kamau (synthetic)', place: 'Ol Kalou, Nyandarua', coop: 'Kinangop Potato Growers (synthetic)' },
       buyer: { name: 'Mama Njeri Greens Ltd (synthetic)', place: 'Nakuru town' },
       provider: { name: 'Njoro Cold Store (synthetic)', place: 'Njoro, Nakuru' },
       coop: { name: 'Hannah Wafula (synthetic)', place: 'Kinangop Potato Growers (synthetic)' } },
 listings: [
  { id: 'L-101', farmer: 'Wanjiru Kamau', place: 'Ol Kalou, Nyandarua', coop: 'Kinangop PG', bags: 120, harvest: '2026-12-03', grade: 'A', price: 2600, storage: 'Cool, dry, shaded', status: 'active', mine: true, variety: 'Shangi' },
  { id: 'L-102', farmer: 'Peter Mwangi', place: 'Kinangop, Nyandarua', coop: 'Kinangop PG', bags: 300, harvest: '2026-12-01', grade: 'A', price: 2750, storage: 'Needs shade within 24h', status: 'active', variety: 'Shangi' },
  { id: 'L-103', farmer: 'Joyce Chebet', place: 'Molo, Nakuru', coop: 'Molo Growers', bags: 80, harvest: '2026-12-10', grade: 'B', price: 2300, storage: 'Dry store', status: 'active', variety: 'Dutch Robijn' },
  { id: 'L-104', farmer: 'Samuel Kiprono', place: 'Njoro, Nakuru', coop: 'Njoro Farmers', bags: 450, harvest: '2026-12-05', grade: 'A', price: 2680, storage: 'Cold store preferred', status: 'active', variety: 'Shangi' },
  { id: 'L-105', farmer: 'Agnes Wairimu', place: 'Ol Joro Orok, Nyandarua', coop: 'Kinangop PG', bags: 60, harvest: '2026-12-20', grade: 'B', price: 2200, storage: 'Dry store', status: 'active', variety: 'Unica' },
  { id: 'L-106', farmer: 'David Otieno', place: 'Rongai, Nakuru', coop: 'Rongai Growers', bags: 200, harvest: '2026-12-08', grade: 'A', price: 2900, storage: 'Cool, dry, shaded', status: 'active', variety: 'Shangi' }
 ],
 storage: [
  { id: 'S-1', name: 'Njoro Cold Store', place: 'Njoro, Nakuru', cap: 4000, free: 1200, temp: '4-8 C', verified: 'Self-declared, site visit pending', price: 40, unit: 'bag / week' },
  { id: 'S-2', name: 'Kinangop Ventilated Shed', place: 'Kinangop, Nyandarua', cap: 2500, free: 900, temp: 'Ambient, ventilated', verified: 'Not applicable', price: 15, unit: 'bag / week' },
  { id: 'S-3', name: 'Ol Kalou Dry Store', place: 'Ol Kalou, Nyandarua', cap: 1800, free: 0, temp: 'Ambient', verified: 'Not applicable', price: 12, unit: 'bag / week' }
 ],
 transporters: [
  { id: 'T-1', name: 'Kariuki Haulage', vehicle: 'Canter 3 t', bags: 60, routes: 'Ol Kalou - Nakuru; Kinangop - Nakuru', price: 14000, avail: 'Available Mon-Sat' },
  { id: 'T-2', name: 'Rift Valley Lorries', vehicle: 'Lorry 10 t', bags: 200, routes: 'Nyandarua - Nakuru - Nairobi', price: 38000, avail: 'Next free: 2 Dec' },
  { id: 'T-3', name: 'Mwende Pickup Hire', vehicle: 'Pickup 1 t', bags: 20, routes: 'Molo - Njoro - Nakuru', price: 5500, avail: 'Available today' }
 ],
 enquiries: [
  { id: 'E-31', listing: 'L-101', buyer: 'Mama Njeri Greens Ltd', bags: 60, status: 'new', when: 'Today 08:40', note: 'Can collect Friday.' },
  { id: 'E-30', listing: 'L-101', buyer: 'Nakuru Fries Kitchen', bags: 30, status: 'replied', when: 'Yesterday', note: 'Is grade A washed?' },
  { id: 'E-27', listing: 'L-102', buyer: 'Mama Njeri Greens Ltd', bags: 100, status: 'agreed', when: '24 Nov', note: 'Delivery to Nakuru market.' }
 ],
 harvest: [
  { id: 'H-1', date: '2026-12-03', label: 'Plot A harvest (Shangi)', bags: 120 },
  { id: 'H-2', date: '2026-12-18', label: 'Plot B harvest (Unica)', bags: 70 },
  { id: 'H-3', date: '2026-12-07', label: 'Reminder: book transport', bags: 0 }
 ],
 officialPrices: [
  { market: 'Nakuru (wholesale)', unit: 'KES / kg', price: 54, date: '2026-11-24', source: 'Sample bulletin (synthetic)', prev: 52 },
  { market: 'Nyahururu (wholesale)', unit: 'KES / kg', price: 49, date: '2026-11-24', source: 'Sample bulletin (synthetic)', prev: 50 },
  { market: 'Nairobi Wakulima (wholesale)', unit: 'KES / kg', price: 61, date: '2026-11-23', source: 'Sample bulletin (synthetic)', prev: 58 },
  { market: 'Nakuru (retail)', unit: 'KES / kg', price: 70, date: '2026-11-24', source: 'Sample bulletin (synthetic)', prev: 70 }
 ],
 trend: [48, 50, 51, 49, 52, 53, 52, 54],
 userPrices: [
  { who: 'Farmer, Kinangop', price: 2600, unit: 'KES / 50 kg bag', date: '2026-11-26' },
  { who: 'Farmer, Molo', price: 2350, unit: 'KES / 50 kg bag', date: '2026-11-25' },
  { who: 'Buyer, Nakuru', price: 2700, unit: 'KES / 50 kg bag', date: '2026-11-26' }
 ],
 losses: [
  { stage: 'field', label: 'In field / harvest', pct: 6 }, { stage: 'handling', label: 'Handling & sorting', pct: 4 },
  { stage: 'transport', label: 'Transport', pct: 3 }, { stage: 'storage', label: 'Storage', pct: 5 }, { stage: 'market', label: 'At market', pct: 2 }
 ],
 members: [
  { name: 'Wanjiru Kamau', place: 'Ol Kalou', bags: 120, listings: 1, loss: 12, last: 'Today' },
  { name: 'Peter Mwangi', place: 'Kinangop', bags: 300, listings: 2, loss: 9, last: 'Yesterday' },
  { name: 'Agnes Wairimu', place: 'Ol Joro Orok', bags: 60, listings: 1, loss: 15, last: '3 days ago' },
  { name: 'Grace Njoki', place: 'Kinangop', bags: 0, listings: 0, loss: 0, last: '12 days ago' },
  { name: 'John Maina', place: 'Ndaragwa', bags: 90, listings: 1, loss: 7, last: '2 days ago' }
 ],
 notifs: [
  { id: 'N1', icon: 'msg', en: 'New enquiry: Mama Njeri Greens wants 60 bags', sw: 'Ombi jipya: Mama Njeri Greens anataka magunia 60', when: '08:40', unread: true },
  { id: 'N2', icon: 'cal', en: 'Harvest reminder: Plot A in 6 days', sw: 'Ukumbusho wa mavuno: Shamba A baada ya siku 6', when: 'Yesterday', unread: true },
  { id: 'N3', icon: 'tag', en: 'Official Nakuru wholesale price rose to KES 54/kg', sw: 'Bei rasmi ya jumla Nakuru imepanda hadi KES 54/kg', when: '24 Nov', unread: false },
  { id: 'N4', icon: 'box', en: 'Njoro Cold Store has 1,200 bag spaces free', sw: 'Njoro Cold Store ina nafasi ya magunia 1,200', when: '23 Nov', unread: false }
 ]
};
window.I18N = {
 en: { appname:'FreshRoute', home:'Home',requests:'Deliveries',mydata:'My data', listings:'My produce', sell:'Sell', calendar:'Harvest', losses:'Losses', enquiries:'Enquiries', prices:'Prices', more:'More', market:'Market', saved:'Saved', storage:'Storage', transport:'Transport', members:'Members', reports:'Reports', dashboard:'Overview', alerts:'Alerts', settings:'My data', offline:'Offline', online:'Online', queue:'waiting to send', sync:'Send now', synthetic:'Synthetic demonstration data. Simulated - design prototype.', save:'Save', cancel:'Cancel', send:'Send enquiry', quick:'New listing in 4 taps', bags:'Bags', harvestDate:'Harvest date', grade:'Quality', price:'Price per bag', place:'Where', photo:'Add photo', official:'Official', user:'User-submitted', search:'Search', sort:'Sort', filter:'Filter', compare:'Compare', empty:'Nothing here yet', retry:'Try again', loading:'Loading', welcome:'Welcome to FreshRoute', choose:'Choose your language', role:'I am a...', continue:'Continue', phone:'Phone number', code:'6-digit code', export:'Download my records' },
 sw: { appname:'FreshRoute', home:'Mwanzo',requests:'Usafirishaji',mydata:'Data yangu', listings:'Mazao yangu', sell:'Uza', calendar:'Mavuno', losses:'Hasara', enquiries:'Maombi', prices:'Bei', more:'Zaidi', market:'Soko', saved:'Zilizohifadhiwa', storage:'Ghala', transport:'Usafiri', members:'Wanachama', reports:'Ripoti', dashboard:'Muhtasari', alerts:'Arifa', settings:'Data yangu', offline:'Nje ya mtandao', online:'Mtandaoni', queue:'zinasubiri kutumwa', sync:'Tuma sasa', synthetic:'Data ya majaribio ya kubuni. Imeigwa - mfano wa muundo.', save:'Hifadhi', cancel:'Ghairi', send:'Tuma ombi', quick:'Tangazo jipya kwa hatua 4', bags:'Magunia', harvestDate:'Tarehe ya mavuno', grade:'Ubora', price:'Bei kwa gunia', place:'Mahali', photo:'Ongeza picha', official:'Rasmi', user:'Imetolewa na mtumiaji', search:'Tafuta', sort:'Panga', filter:'Chuja', compare:'Linganisha', empty:'Hakuna bado', retry:'Jaribu tena', loading:'Inapakia', welcome:'Karibu FreshRoute', choose:'Chagua lugha', role:'Mimi ni...', continue:'Endelea', phone:'Nambari ya simu', code:'Msimbo wa tarakimu 6', export:'Pakua rekodi zangu' }
};

/* FreshRoute design prototype. Everything is simulated; no network calls. */
(function(){
'use strict';
const D=window.FR, $=(s,r=document)=>r.querySelector(s);
const S={lang:'en',offline:false,queue:[],saved:new Set(),compare:new Set(),theme:null,
  listings:D.listings.slice(),enquiries:D.enquiries.slice(),losses:[{id:'X1',stage:'storage',bags:4,reason:'Rot',when:'20 Nov'}],
  deliveries:[{id:'D-9',who:'Kariuki Haulage',from:'Ol Kalou',to:'Nakuru',bags:60,step:2}],wizard:null,error:false,prefs:{inapp:true,email:true}};
const NAV={
 farmer:{tabs:['home','sell','calendar','losses','more'],more:['listings','enquiries','prices','storage','transport','requests','mydata','alerts']},
 buyer:{tabs:['market','saved','enquiries','prices','more'],more:['alerts','mydata']},
 provider:{tabs:['storage','transport','requests','alerts'],more:[]},
 coop:{tabs:['dashboard','members','reports','prices','more'],more:['alerts','mydata']}};
const ICON={home:'home',sell:'plus',calendar:'cal',losses:'loss',more:'menu',listings:'box',enquiries:'msg',prices:'tag',mydata:'download',alerts:'bell',market:'search',saved:'heart',storage:'store',transport:'truck',requests:'truck',members:'users',reports:'chart',dashboard:'chart'};
const ROLES={farmer:{en:'Farmer',sw:'Mkulima',i:'user'},buyer:{en:'Buyer',sw:'Mnunuzi',i:'search'},provider:{en:'Storage / transport',sw:'Ghala / usafiri',i:'truck'},coop:{en:'Cooperative admin',sw:'Msimamizi wa ushirika',i:'users'}};
const t=k=>(window.I18N[S.lang][k]||window.I18N.en[k]||k);
const e=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const ic=(n,c='')=>`<svg class="ico ${c}" aria-hidden="true"><use href="#i-${n}"/></svg>`;
const kes=n=>'KES '+Number(n).toLocaleString('en-KE');
const day=d=>new Date(d+'T00:00:00').toLocaleDateString('en-GB',{day:'numeric',month:'short'});
const daysBetween=(a,b)=>Math.round((new Date(b)-new Date(a))/864e5);
const ST={new:['b-warn','New','Mpya'],replied:['b-official','Replied','Imejibiwa'],agreed:['b-ok','Agreed','Imekubaliwa'],queued:['b-warn','Queued','Imesubiri']};
const stBadge=s=>{const x=ST[s]||ST.new;return `<span class="badge ${x[0]}">${S.lang==='sw'?x[2]:x[1]}</span>`};
function role(){const m=location.hash.match(/^#\/(farmer|buyer|provider|coop)\//);return m?m[1]:null}
function route(){const m=location.hash.match(/^#\/(?:farmer|buyer|provider|coop)\/([\w-]+)(?:\/([\w-]+))?/);return m?{r:m[1],id:m[2]}:{r:'login'}}
function go(r,rl){location.hash=`#/${rl||role()}/${r}`}
function toast(m){const x=$('#toast');x.textContent=m;x.classList.add('on');clearTimeout(toast.t);toast.t=setTimeout(()=>x.classList.remove('on'),2800)}
function dlg(html){const d=$('#dlg');d.innerHTML=html;if(!d.open)d.showModal()}
function closeDlg(){const d=$('#dlg');if(d.open)d.close()}
const syn=()=>`<div class="banner syn">${ic('warn','sm')}<span>${t('synthetic')}</span></div>`;
/* ---------- shell ---------- */
function shell(rl,r,body){
 const nav=NAV[rl];const link=(k,cls)=>`<a class="${cls}" href="#/${rl}/${k}" ${r===k?'aria-current="page"':''}>${ic(ICON[k])}<span>${t(k)}</span></a>`;
 const moreActive=r==='more'||nav.more.includes(r);
 const tabs=nav.tabs.map(k=>k==='more'?`<a class="tab" href="#/${rl}/more" ${moreActive?'aria-current="page"':''}>${ic('menu')}<span>${t('more')}</span></a>`:`<a class="tab ${k==='sell'?'cta':''}" href="#/${rl}/${k}" ${r===k?'aria-current="page"':''}>${ic(ICON[k])}<span>${t(k)}</span></a>`).join('');
 const side=nav.tabs.filter(k=>k!=='more').concat(nav.more).map(k=>link(k,'navlink')).join('');
 const unread=D.notifs.filter(n=>n.unread).length;
 return `<div class="app"><aside class="side" aria-label="Main"><a class="brand" href="#/login">${ic('logo')}FreshRoute</a><div class="small muted" style="margin:0 14px 10px">${e(ROLES[rl][S.lang])}</div>${side}</aside>
 <header class="top"><a class="brand" href="#/login" aria-label="FreshRoute">${ic('logo')}<span>FreshRoute</span></a>
  <div class="bar-actions">
   <button class="iconbtn" data-act="offline" aria-pressed="${S.offline}" aria-label="${S.offline?t('offline'):t('online')}">${ic(S.offline?'wifioff':'wifi')}</button>
   <button class="iconbtn langbtn" data-act="lang" aria-label="Language: ${S.lang==='en'?'English':'Kiswahili'}">${ic('globe','sm')}${S.lang.toUpperCase()}</button>
   <button class="iconbtn" data-act="theme" aria-label="Dark / light theme">${ic('moon')}</button>
   <a class="iconbtn" href="#/${rl}/alerts" aria-label="${t('alerts')}">${ic('bell')}${unread?'<span class="dot"></span>':''}</a>
  </div></header>
 <main id="view" tabindex="-1">${S.offline?`<div class="banner off">${ic('wifioff')}<span>${t('offline')}. ${S.queue.length} ${t('queue')}.</span></div>`:''}${!S.offline&&S.queue.length?`<div class="banner off"><span class="grow">${S.queue.length} ${t('queue')}</span><button class="btn sm" data-act="sync">${t('sync')}</button></div>`:''}${syn()}${body}</main>
 <nav class="tabbar" aria-label="Main">${tabs}</nav></div>`}
/* ---------- screens ---------- */
const listingCard=(l,opts={})=>`<article class="card listing" aria-label="${e(l.farmer)}">
 <div class="thumb">${ic('box','lg')}</div>
 <div><div class="row between wrap"><strong>${e(l.farmer)}</strong><span class="badge b-ok">${t('grade')} ${l.grade}</span></div>
 <div class="muted small row">${ic('pin','sm')}${e(l.place)} - ${e(l.coop)}</div>
 <div class="row between wrap" style="margin-top:6px"><span class="price">${kes(l.price)}<span class="small muted"> / bag</span></span><span><strong>${l.bags}</strong> ${t('bags').toLowerCase()}</span></div>
 <div class="small muted">${t('harvestDate')}: ${day(l.harvest)} - ${e(l.variety)}</div>
 ${opts.buyer?`<div class="row wrap" style="margin-top:10px"><a class="btn sm" href="#/buyer/listing/${l.id}">View</a><button class="btn sm secondary" data-act="save" data-id="${l.id}" aria-pressed="${S.saved.has(l.id)}">${ic('heart','sm')}${S.saved.has(l.id)?'Saved':t('save')}</button><label class="row small" style="margin:0"><input type="checkbox" data-act="cmp" data-id="${l.id}" ${S.compare.has(l.id)?'checked':''} style="width:24px;height:24px"> ${t('compare')}</label></div>`:''}
 ${opts.mine?`<div class="row" style="margin-top:8px">${S.queue.some(q=>q.id===l.id)?'<span class="badge b-warn">Queued - not sent yet</span>':'<span class="badge b-ok">Live</span>'}</div>`:''}
 </div></article>`;
const SCREENS={
login(){return `<div class="login"><div class="row" style="margin-bottom:16px"><svg class="ico lg" style="width:56px;height:56px"><use href="#i-logo"/></svg><h1 style="margin:0">${t('welcome')}</h1></div>
 ${syn()}
 <p class="lbl">${t('choose')}</p><div class="chips" style="margin-bottom:20px"><button class="chip" data-act="setlang" data-v="sw" aria-pressed="${S.lang==='sw'}">Kiswahili</button><button class="chip" data-act="setlang" data-v="en" aria-pressed="${S.lang==='en'}">English</button></div>
 <form data-form="login" novalidate><div class="field"><label for="ph">${t('phone')}</label><input id="ph" inputmode="tel" type="tel" placeholder="07xx xxx xxx" autocomplete="off"><div class="hint">Simulated: no code is sent. Try 0712 345 678.</div></div>
 <p class="lbl">${t('role')}</p>
 ${Object.keys(ROLES).map(k=>`<button type="button" class="rolecard" data-act="enter" data-v="${k}">${ic(ROLES[k].i,'lg')}<span>${ROLES[k][S.lang]}</span></button>`).join('')}</form>
 <p class="small muted">Accounts: phone + 6-digit code for farmers (no password); email + password + TOTP for cooperative admins and providers (build phase).</p></div>`},
home(){const f=S.listings.filter(l=>l.mine);const nextH=D.harvest.filter(h=>h.bags).sort((a,b)=>a.date<b.date?-1:1)[0];const inDays=daysBetween(D.today,nextH.date);
 return `<h1>Jambo, Wanjiru</h1>
 <a class="btn clay block" style="min-height:72px;font-size:1.2rem" href="#/farmer/sell">${ic('plus')}${t('sell')} - ${t('quick')}</a>
 <div class="grid g2" style="margin-top:14px">
  <div class="card kpi"><div class="l">${t('calendar')}</div><div class="n">${inDays} days</div><div class="l">${e(nextH.label)} - ${day(nextH.date)}</div></div>
  <a class="card kpi" href="#/farmer/enquiries" style="text-decoration:none;color:inherit"><div class="l">${t('enquiries')}</div><div class="n">${S.enquiries.filter(x=>x.status==='new').length} new</div><div class="l">Mama Njeri Greens - 60 ${t('bags').toLowerCase()}</div></a>
  <a class="card kpi" href="#/farmer/prices" style="text-decoration:none;color:inherit"><div class="l">${t('prices')} <span class="badge b-official">${t('official')}</span></div><div class="n">KES 54/kg</div><div class="l">Nakuru wholesale - 24 Nov</div></a>
  <a class="card kpi" href="#/farmer/losses" style="text-decoration:none;color:inherit"><div class="l">${t('losses')}</div><div class="n">${S.losses.reduce((a,b)=>a+b.bags,0)} ${t('bags').toLowerCase()}</div><div class="l">this season (recorded by you)</div></a></div>
 <h2 style="margin-top:20px">${t('listings')}</h2><div class="stack">${f.map(l=>listingCard(l,{mine:true})).join('')||`<div class="empty">${ic('box')}<p>${t('empty')}</p></div>`}</div>`},
sell(){const w=S.wizard||(S.wizard={step:1,bags:20,date:D.today,grade:'',price:2700,photo:false,err:{}});
 const st=w.step,bar=`<div class="progress" role="img" aria-label="Step ${st} of 5">${[1,2,3,4,5].map(i=>`<i class="${i<=st?'on':''}"></i>`).join('')}</div>`;
 let b='';
 if(st===1)b=`<h2>${t('bags')}?</h2><p class="muted">${D.crop[S.lang]} - ${D.unit[S.lang]}</p><div class="stepper"><button data-act="wstep" data-k="bags" data-d="-5" aria-label="Minus 5">-</button><input id="w-bags" type="number" inputmode="numeric" value="${w.bags}" aria-label="${t('bags')}" ${w.err.bags?'aria-invalid="true" aria-describedby="e-bags"':''}><button data-act="wstep" data-k="bags" data-d="5" aria-label="Plus 5">+</button></div>${w.err.bags?`<div class="err" id="e-bags" role="alert">${ic('warn','sm')}${w.err.bags}</div>`:''}<div class="chips" style="margin-top:12px">${[10,20,50,100,200].map(n=>`<button class="chip" data-act="wset" data-k="bags" data-v="${n}">${n}</button>`).join('')}</div>`;
 if(st===2){const opts=[['Today',0],['+3 days',3],['+1 week',7],['+2 weeks',14]];b=`<h2>${t('harvestDate')}?</h2><div class="chips">${opts.map(([l,d])=>{const v=new Date(new Date(D.today).getTime()+d*864e5).toISOString().slice(0,10);return `<button class="chip" data-act="wset" data-k="date" data-v="${v}" aria-pressed="${w.date===v}">${l}</button>`}).join('')}</div><div class="field" style="margin-top:14px"><label for="w-date">Or pick a date</label><input id="w-date" type="date" value="${w.date}" ${w.err.date?'aria-invalid="true" aria-describedby="e-date"':''}></div>${w.err.date?`<div class="err" id="e-date" role="alert">${ic('warn','sm')}${w.err.date}</div>`:''}`}
 if(st===3)b=`<h2>${t('grade')}?</h2><div class="bigchoice" role="group" aria-label="${t('grade')}">${[['A','Even size, no green, no damage'],['B','Some small damage'],['C','Mixed sizes / damage']].map(([g,d])=>`<button data-act="wset" data-k="grade" data-v="${g}" aria-pressed="${w.grade===g}"><span class="gl">${g}</span><span class="small">${d}</span></button>`).join('')}</div>${w.err.grade?`<div class="err" role="alert">${ic('warn','sm')}${w.err.grade}</div>`:''}<p class="hint">Your own description. FreshRoute does not certify quality.</p>`;
 if(st===4){const ref=Math.round(54*50);b=`<h2>${t('price')}?</h2><div class="stepper"><button data-act="wstep" data-k="price" data-d="-50" aria-label="Minus 50">-</button><input id="w-price" type="number" inputmode="numeric" value="${w.price}" aria-label="${t('price')}" ${w.err.price?'aria-invalid="true" aria-describedby="e-price"':''}><button data-act="wstep" data-k="price" data-d="50" aria-label="Plus 50">+</button></div>${w.err.price?`<div class="err" id="e-price" role="alert">${ic('warn','sm')}${w.err.price}</div>`:''}<div class="card tight" style="margin-top:12px"><span class="badge b-official">${t('official')}</span> Nakuru wholesale KES 54/kg = about ${kes(ref)} per 50 kg bag (24 Nov, sample)<div class="hint">Reference only. Your price is your own.</div></div>
  <button class="btn ghost block" style="margin-top:12px" data-act="wphoto">${ic('camera')}${t('photo')}${w.photo?' - added':''}</button>`}
 if(st===5)b=`<h2>Check and publish</h2><div class="card stack"><div class="row between"><span>${t('bags')}</span><strong>${w.bags}</strong></div><div class="row between"><span>${t('harvestDate')}</span><strong>${day(w.date)}</strong></div><div class="row between"><span>${t('grade')}</span><strong>${w.grade}</strong></div><div class="row between"><span>${t('price')}</span><strong>${kes(w.price)}</strong></div><div class="row between"><span>${t('place')}</span><strong>Ol Kalou, Nyandarua</strong></div></div>${S.offline?`<div class="banner off" style="margin-top:12px">${ic('wifioff')}You are offline. This will wait in your queue and send when you are back online.</div>`:''}`;
 return `<h1>${t('sell')}</h1>${bar}<div class="card">${b}</div><div class="row" style="margin-top:14px">${st>1?`<button class="btn ghost" data-act="wback">${t('cancel')==='x'?'':'Back'}</button>`:''}<button class="btn block clay" data-act="wnext" style="flex:1">${st<5?t('continue'):(S.offline?'Save to queue':'Publish')}</button></div>`},
listings(){return `<h1>${t('listings')}</h1><div class="stack">${S.listings.filter(l=>l.mine).map(l=>listingCard(l,{mine:true})).join('')}</div><a class="btn clay block" style="margin-top:14px" href="#/farmer/sell">${ic('plus')}${t('sell')}</a>`},
calendar(){const evs=D.harvest;const first=new Date('2026-12-01T00:00:00');const lead=(first.getDay()+6)%7;let cells=['Mo','Tu','We','Th','Fr','Sa','Su'].map(x=>`<div class="hd">${x}</div>`).join('')+'<div class="mt"></div>'.repeat(lead);
 for(let d=1;d<=31;d++){const ds=`2026-12-${String(d).padStart(2,'0')}`;const ev=evs.find(x=>x.date===ds);cells+=`<div class="${ev?'ev':''}" ${ev?`aria-label="${d} December: ${e(ev.label)}"`:''}>${d}</div>`}
 return `<h1>${t('calendar')} - December 2026</h1><div class="card"><div class="cal">${cells}</div></div><h2 style="margin-top:18px">Coming up</h2><div class="stack">${evs.slice().sort((a,b)=>a.date<b.date?-1:1).map(h=>`<div class="card tight row"><span class="badge b-warn">${day(h.date)}</span><div class="grow"><strong>${e(h.label)}</strong>${h.bags?`<div class="small muted">${h.bags} ${t('bags').toLowerCase()} expected</div>`:''}</div></div>`).join('')}</div><button class="btn secondary block" style="margin-top:14px" data-act="addharvest">${ic('plus')}Add harvest date</button><p class="hint">Reminders arrive in-app and by email. SMS reminders: planned, needs an SMS provider (outstanding).</p>`},
losses(){const total=S.losses.reduce((a,b)=>a+b.bags,0);
 return `<h1>${t('losses')}</h1><button class="btn clay block" style="min-height:72px;font-size:1.15rem" data-act="newloss">${ic('plus')}Record a loss</button>
 <div class="card kpi" style="margin-top:14px"><div class="l">Total you recorded</div><div class="n">${total} ${t('bags').toLowerCase()}</div></div>
 <h2 style="margin-top:18px">History</h2><div class="stack">${S.losses.map(x=>`<div class="card tight row"><span class="badge b-danger">${x.bags} ${t('bags').toLowerCase()}</span><div class="grow"><strong>${e(D.losses.find(s=>s.stage===x.stage).label)}</strong><div class="small muted">${e(x.reason)} - ${e(x.when)}${x.queued?' - queued':''}</div></div></div>`).join('')}</div>`},
enquiries(){const rl=role();return `<h1>${t('enquiries')}</h1><div class="stack">${S.enquiries.filter(x=>rl==='farmer'?true:x.buyer.startsWith('Mama')).map(x=>{const l=S.listings.find(l=>l.id===x.listing)||D.listings[0];const step=x.status==='new'?1:x.status==='replied'?2:3;
 return `<article class="card"><div class="row between wrap"><strong>${rl==='farmer'?e(x.buyer):e(l.farmer)}</strong>${stBadge(x.status)}</div><div class="muted small">${x.bags} ${t('bags').toLowerCase()} - ${e(x.when)}</div><p>${e(x.note)}</p>
 <ol class="steps" aria-label="Progress"><li class="${step>=1?'done':''}">Sent</li><li class="${step>=2?'done':''}">Replied</li><li class="${step>=3?'done':''}">Agreed</li></ol>
 ${rl==='farmer'&&x.status==='new'?`<div class="row"><button class="btn sm" data-act="reply" data-id="${x.id}">Reply</button><button class="btn sm secondary" data-act="agree" data-id="${x.id}">Mark agreed</button></div>`:''}
 <div class="hint">FreshRoute does not hold or move money. Agree price and payment directly with the other party.</div></article>`}).join('')}</div>`},
prices(){const tab=S.pt||'official';
 const trendSvg=(()=>{const v=D.trend,mx=Math.max(...v),mn=Math.min(...v);const pts=v.map((y,i)=>`${i*40+10},${70-((y-mn)/(mx-mn))*55}`).join(' ');return `<svg viewBox="0 0 300 80" role="img" aria-label="Nakuru wholesale price over last 8 weeks, rising from 48 to 54 KES per kg, sample data" style="width:100%;height:80px"><polyline points="${pts}" fill="none" stroke="var(--fr-official)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>`})();
 const off=`<div class="banner" style="background:var(--fr-official-bg);color:var(--fr-official);border:2px solid var(--fr-official)">${ic('check')}Official bulletin sample (synthetic). Real feed: outstanding, licence to be confirmed.</div>
 <div class="stack">${D.officialPrices.map(p=>{const age=daysBetween(p.date,D.today);const ch=p.price-p.prev;return `<article class="card"><div class="row between wrap"><strong>${e(p.market)}</strong><span class="badge b-official">${t('official')}</span></div><div class="row between wrap"><span class="price">${p.price} <span class="small muted">${p.unit}</span></span><span class="${ch>0?'':'muted'}">${ch>0?'+':''}${ch} vs prev</span></div><div class="small muted">Collected ${day(p.date)} (${age} days ago) - Source: ${e(p.source)}</div>${age>7?`<div class="badge b-warn">Older than 7 days</div>`:''}</article>`}).join('')}</div>
 <div class="card" style="margin-top:12px"><strong>Nakuru wholesale, last 8 weeks</strong>${trendSvg}<div class="small muted">Sample data - shape only</div></div>`;
 const usr=`<div class="banner" style="background:var(--fr-user-bg);color:var(--fr-user);border:2px solid var(--fr-user)">${ic('warn')}User-submitted. Not verified. Not an official average and not combined with official prices.</div>
 <div class="stack">${D.userPrices.map(p=>`<article class="card"><div class="row between wrap"><strong>${e(p.who)}</strong><span class="badge b-user">${t('user')}</span></div><span class="price">${kes(p.price)} <span class="small muted">/ 50 kg bag</span></span><div class="small muted">Submitted ${day(p.date)}</div></article>`).join('')}</div><button class="btn secondary block" style="margin-top:12px" data-act="submitprice">${ic('plus')}Submit a price you saw</button>`;
 return `<h1>${t('prices')} - ${D.crop[S.lang]}</h1><div class="tabs" role="tablist"><button role="tab" aria-selected="${tab==='official'}" data-act="ptab" data-v="official">${t('official')}</button><button role="tab" aria-selected="${tab==='user'}" data-act="ptab" data-v="user">${t('user')}</button></div>${tab==='official'?off:usr}`},
mydata(){return `<h1>${t('settings')}</h1><div class="card stack"><p>You own your records. Download everything you entered: profile, listings, enquiries, losses.</p><button class="btn block" data-act="export">${ic('download')}${t('export')} (CSV)</button><button class="btn ghost block" data-act="export">${ic('download')}JSON</button><hr style="border:0;border-top:1px solid var(--fr-line);width:100%"><button class="btn ghost block" data-act="delreq">Request account deletion</button></div><div class="card" style="margin-top:12px"><h3>Privacy</h3><p class="small">Your phone number is shown to a buyer only after you reply to their enquiry. (Design intent; Data Protection Act 2019 considerations in the brief; no compliance claim.)</p></div>`},
more(){const rl=role();return `<h1>${t('more')}</h1><div class="stack">${NAV[rl].more.map(k=>`<a class="card row" style="text-decoration:none;color:inherit;min-height:64px" href="#/${rl}/${k}">${ic(ICON[k],'lg')}<strong class="grow">${t(k)}</strong></a>`).join('')}<button class="card row" style="min-height:64px;width:100%;text-align:left;cursor:pointer;border:1px solid var(--fr-line)" data-act="simerr">${ic('warn','lg')}<strong class="grow">Demo: show an error state</strong></button><a class="card row" style="text-decoration:none;color:inherit;min-height:64px" href="#/login">${ic('user','lg')}<strong class="grow">Switch role / sign out</strong></a></div>`},
market(){const q=(S.mq||'').toLowerCase(),loc=S.ml||'',min=+S.mm||0,gr=S.mg||'',so=S.ms||'price';
 let r=S.listings.filter(l=>(!q||(l.farmer+l.place+l.coop+l.variety).toLowerCase().includes(q))&&(!loc||l.place.includes(loc))&&l.bags>=min&&(!gr||l.grade===gr));
 r.sort((a,b)=>so==='price'?a.price-b.price:so==='bags'?b.bags-a.bags:a.harvest<b.harvest?-1:1);
 return `<h1>${t('market')} - ${D.crop[S.lang]}</h1><div class="toolbar"><div><label class="sr" for="mq">${t('search')}</label><input id="mq" type="search" placeholder="${t('search')}: name, place, variety" value="${e(S.mq||'')}"></div>
 <div><label class="sr" for="ml">Location</label><select id="ml"><option value="">All places</option>${['Nyandarua','Nakuru','Ol Kalou','Kinangop','Molo','Njoro'].map(x=>`<option ${loc===x?'selected':''}>${x}</option>`).join('')}</select></div>
 <div><label class="sr" for="mm">Min bags</label><select id="mm"><option value="0">Any quantity</option>${[50,100,200,400].map(x=>`<option value="${x}" ${min===x?'selected':''}>${x}+ bags</option>`).join('')}</select></div>
 <div><label class="sr" for="ms">${t('sort')}</label><select id="ms"><option value="price" ${so==='price'?'selected':''}>Lowest price</option><option value="bags" ${so==='bags'?'selected':''}>Most bags</option><option value="date" ${so==='date'?'selected':''}>Earliest harvest</option></select></div></div>
 <div class="chips" style="margin-bottom:12px" role="group" aria-label="${t('grade')}">${['','A','B'].map(g=>`<button class="chip" data-act="mgrade" data-v="${g}" aria-pressed="${gr===g}">${g?t('grade')+' '+g:'All'}</button>`).join('')}</div>
 <p class="muted" aria-live="polite">${r.length} listings</p>
 ${S.compare.size>=2?`<button class="btn clay block" data-act="docompare" style="margin-bottom:12px">${t('compare')} ${S.compare.size}</button>`:''}
 <div class="grid g2">${r.map(l=>listingCard(l,{buyer:true})).join('')||`<div class="empty card">${ic('search')}<p>No listings match. Try fewer filters.</p><button class="btn secondary" data-act="clearf">Clear filters</button></div>`}</div>`},
listing(id){const l=S.listings.find(x=>x.id===id.id)||D.listings[0];return `<a href="#/buyer/market" class="btn ghost sm">Back</a><h1 style="margin-top:12px">${e(l.farmer)}</h1>${listingCard(l)}
 <div class="card" style="margin-top:12px"><h3>Details</h3><p>Storage need: ${e(l.storage)}</p><p>Farm location: ${e(l.place)} (approximate area shown; exact location shared after reply)</p><p class="small muted">Quality grade is the farmer's own description, not a certification.</p></div>
 <div class="card" style="margin-top:12px"><h3>Related</h3><p class="small"><a href="#/buyer/prices">Official price reference</a> - <a href="#/buyer/market">Storage and transport are on the farmer's side too</a></p></div>
 <button class="btn clay block" style="margin-top:14px;min-height:64px" data-act="enq" data-id="${l.id}">${ic('msg')}Send enquiry</button>`},
saved(){const r=S.listings.filter(l=>S.saved.has(l.id));return `<h1>${t('saved')}</h1>${r.length?`<div class="stack">${r.map(l=>listingCard(l,{buyer:true})).join('')}</div>`:`<div class="empty card">${ic('heart')}<p>${t('empty')}. Tap the heart on a listing to save a supplier.</p><a class="btn" href="#/buyer/market">${t('market')}</a></div>`}`},
storage(){return `<h1>${t('storage')}</h1><div class="chips" style="margin-bottom:12px"><button class="chip" data-act="stfree" aria-pressed="${!!S.sf}">Only with free space</button></div><div class="grid g2">${D.storage.filter(s=>!S.sf||s.free>0).map(s=>`<article class="card"><div class="row between wrap"><strong>${e(s.name)}</strong>${s.free?`<span class="badge b-ok">${s.free} bags free</span>`:'<span class="badge b-danger">Full</span>'}</div><div class="muted small row">${ic('pin','sm')}${e(s.place)}</div><p>Capacity ${s.cap.toLocaleString()} bags - ${e(s.temp)}</p><p class="small">Temperature: <span class="badge ${s.verified.startsWith('Self')?'b-warn':'b-user'}">${e(s.verified)}</span></p><div class="row between"><span class="price">${kes(s.price)}<span class="small muted"> ${e(s.unit)}</span></span><button class="btn sm" data-act="contact" data-id="${s.id}">Ask for space</button></div></article>`).join('')}</div>`},
transport(){return `<h1>${t('transport')}</h1><div class="grid g2">${D.transporters.map(x=>`<article class="card"><div class="row between wrap"><strong>${e(x.name)}</strong><span class="badge b-ok">${e(x.vehicle)}</span></div><p class="small muted">Up to ${x.bags} bags - ${e(x.avail)}</p><p class="small">${ic('pin','sm')}${e(x.routes)}</p><div class="row between"><span class="price">from ${kes(x.price)}</span><button class="btn sm" data-act="delreq" data-id="${x.id}">Request delivery</button></div></article>`).join('')}</div>`},
requests(){return `<h1>Delivery requests</h1><div class="stack">${S.deliveries.map(d=>`<article class="card"><div class="row between wrap"><strong>${d.from} to ${d.to}</strong><span class="badge b-ok">${d.bags} bags</span></div><div class="muted small">Carrier: ${e(d.who)}</div><ol class="steps" aria-label="Delivery status">${['Requested','Accepted','Picked up','Delivered'].map((s,i)=>`<li class="${d.step>i?'done':''}">${s}</li>`).join('')}</ol>${d.step<4?`<button class="btn sm" data-act="advance" data-id="${d.id}">Update status</button>`:''}<div class="hint">Status is entered by the carrier. No GPS tracking and no payment handling in the MVP.</div></article>`).join('')}</div>`},
dashboard(){return `<h1>${e(D.me.coop.place)}</h1><div class="grid g4"><div class="card kpi"><div class="l">Members</div><div class="n">${D.members.length}</div></div><div class="card kpi"><div class="l">Bags listed</div><div class="n">${D.members.reduce((a,b)=>a+b.bags,0)}</div></div><div class="card kpi"><div class="l">Open enquiries</div><div class="n">${S.enquiries.filter(x=>x.status!=='agreed').length}</div></div><div class="card kpi"><div class="l">Reported loss</div><div class="n">${D.losses.reduce((a,b)=>a+b.pct,0)}%</div></div></div>
 <div class="grid g2" style="margin-top:14px"><div class="card"><h3>Supply by week (bags)</h3>${[['Wk 49',420],['Wk 50',210],['Wk 51',60]].map(([l,v])=>`<div class="bars"><div class="bar"><span>${l}</span><div class="track"><div class="fill" style="width:${v/4.2}%;background:var(--fr-primary)"></div></div><strong>${v}</strong></div></div>`).join('')}</div><div class="card"><h3>Loss by stage (% of harvest, sample)</h3><div class="bars">${D.losses.map(x=>`<div class="bar"><span>${x.label}</span><div class="track"><div class="fill" style="width:${x.pct*10}%"></div></div><strong>${x.pct}%</strong></div>`).join('')}</div></div></div>`},
members(){const q=(S.mbq||'').toLowerCase();return `<div class="row between wrap"><h1>${t('members')}</h1><button class="btn secondary" data-act="invite">${ic('plus')}Invite member</button></div><div class="field"><label class="sr" for="mbq">${t('search')}</label><input id="mbq" type="search" placeholder="${t('search')}" value="${e(S.mbq||'')}"></div><div class="tablewrap"><table><thead><tr><th scope="col">Name</th><th scope="col">Place</th><th scope="col">Bags</th><th scope="col">Listings</th><th scope="col">Loss %</th><th scope="col">Last active</th></tr></thead><tbody>${D.members.filter(m=>!q||(m.name+m.place).toLowerCase().includes(q)).map(m=>`<tr><th scope="row">${e(m.name)}</th><td>${e(m.place)}</td><td>${m.bags}</td><td>${m.listings}</td><td>${m.loss}</td><td>${e(m.last)}</td></tr>`).join('')||'<tr><td colspan="6">No members match.</td></tr>'}</tbody></table></div>`},
reports(){return `<h1>${t('reports')}</h1><div class="grid g2"><div class="card"><h3>Food-loss report</h3><p class="muted small">By stage, ${D.crop.en}, Nov 2026. Sample data (synthetic). Based on farmer self-reports, not measurements.</p><div class="bars">${D.losses.map(x=>`<div class="bar"><span>${x.label}</span><div class="track"><div class="fill" style="width:${x.pct*10}%"></div></div><strong>${x.pct}%</strong></div>`).join('')}</div><div class="hint">Values are synthetic and are not WRI or national figures.</div></div>
 <div class="card stack"><h3>Export</h3><button class="btn block" data-act="export">${ic('download')}Loss report (CSV)</button><button class="btn secondary block" data-act="export">${ic('download')}Member supply (CSV)</button><button class="btn ghost block" data-act="export">${ic('download')}Print / save as PDF</button></div></div>`},
alerts(){return `<h1>${t('alerts')}</h1><div class="stack">${D.notifs.map(n=>`<div class="card tight row">${ic(n.icon,'lg')}<div class="grow"><strong>${e(n[S.lang])}</strong><div class="small muted">${n.when} - in-app + email</div></div>${n.unread?'<span class="badge b-warn">New</span>':''}</div>`).join('')}</div>
 <div class="card" style="margin-top:16px"><h3>How you get alerts</h3><div class="stack"><label class="switch"><input type="checkbox" data-act="pref" data-k="inapp" ${S.prefs.inapp?'checked':''} style="width:28px;height:28px"> In-app</label><label class="switch"><input type="checkbox" data-act="pref" data-k="email" ${S.prefs.email?'checked':''} style="width:28px;height:28px"> Email</label><label class="switch"><input type="checkbox" disabled style="width:28px;height:28px"> SMS <span class="badge b-warn">Coming later - needs an SMS provider</span></label></div></div>`}
};
/* ---------- render ---------- */
let loadTimer;
function render(){const rr=route();const rl=role();document.documentElement.lang=S.lang==='sw'?'sw':'en';
 if(S.theme)document.documentElement.dataset.theme=S.theme;
 const app=$('#app');
 if(rr.r==='login'||!rl){app.innerHTML=SCREENS.login();document.title='FreshRoute - design prototype';return}
 let fn=SCREENS[rr.r];if(!fn||!(NAV[rl].tabs.includes(rr.r)||NAV[rl].more.includes(rr.r)||rr.r==='more'||rr.r==='listing')){go(NAV[rl].tabs[0],rl);return}
 if(S.error){app.innerHTML=shell(rl,rr.r,`<div class="banner state-error">${ic('warn')}<span class="grow">Could not load. Check your connection.</span></div><button class="btn block" data-act="retry">${t('retry')}</button>`);return}
 if(S.loading){app.innerHTML=shell(rl,rr.r,`<div aria-busy="true" aria-label="${t('loading')}"><div class="skel"></div><div class="skel"></div><div class="skel"></div></div>`);return}
 app.innerHTML=shell(rl,rr.r,fn(rr));document.title=`${t(rr.r)||'FreshRoute'} - FreshRoute prototype`;
 const f=S.focus;S.focus=null;if(f){const x=$('#'+f);if(x){x.focus();const v=x.value;try{x.setSelectionRange(v.length,v.length)}catch(_){}}}}
function navTo(){S.loading=true;render();clearTimeout(loadTimer);loadTimer=setTimeout(()=>{S.loading=false;render();const m=$('#view');if(m)m.focus({preventScroll:true})},S.fast?0:350)}
window.addEventListener('hashchange',()=>{if(route().r==='login'){render();return}if(route().r!=='sell')S.wizard=null;navTo()});
/* ---------- actions ---------- */
function submitListing(w){const l={id:'L-'+(107+S.listings.length-6),farmer:'Wanjiru Kamau',place:'Ol Kalou, Nyandarua',coop:'Kinangop PG',bags:w.bags,harvest:w.date,grade:w.grade,price:w.price,storage:'Cool, dry, shaded',status:'active',mine:true,variety:'Shangi'};S.listings.push(l);if(S.offline){S.queue.push({id:l.id,type:'listing'});toast('Saved. Will send when you are online.')}else toast('Listing is live. Buyers can see it.');S.wizard=null;go('listings')}
function validateStep(w){w.err={};if(w.step===1&&!(w.bags>0&&w.bags<=5000))w.err.bags='Enter a number of bags from 1 to 5,000.';
 if(w.step===2&&(!w.date||w.date<D.today))w.err.date='Harvest date cannot be in the past.';
 if(w.step===3&&!w.grade)w.err.grade='Choose a quality: A, B or C.';
 if(w.step===4&&!(w.price>=500&&w.price<=20000))w.err.price='Enter a price between KES 500 and 20,000 per bag.';
 return !Object.keys(w.err).length}
document.addEventListener('click',ev=>{const a=ev.target.closest('[data-act]');if(!a)return;const act=a.dataset.act,w=S.wizard;
 switch(act){
 case'lang':S.lang=S.lang==='en'?'sw':'en';render();break;
 case'setlang':S.lang=a.dataset.v;render();break;
 case'theme':{const dark=getComputedStyle(document.documentElement).getPropertyValue('--fr-bg').trim().toLowerCase()==='#1a130d';S.theme=dark?'light':'dark';render();break}
 case'offline':S.offline=!S.offline;if(!S.offline&&S.queue.length)toast(S.queue.length+' waiting. Tap "Send now".');render();break;
 case'sync':{const n=S.queue.length;S.queue=[];S.losses.forEach(x=>x.queued=false);toast(n+' sent');render();break}
 case'enter':{const v=a.dataset.v;S.fast=false;location.hash=`#/${v}/${NAV[v].tabs[0]}`;break}
 case'wset':w[a.dataset.k]=a.dataset.k==='bags'?+a.dataset.v:a.dataset.v;w.err={};render();break;
 case'wstep':{const k=a.dataset.k;const el=$(k==='bags'?'#w-bags':'#w-price');w[k]=Math.max(0,(+el.value||0)+(+a.dataset.d));w.err={};render();break}
 case'wphoto':w.photo=!w.photo;render();break;
 case'wback':w.step--;render();break;
 case'wnext':{const eb=$('#w-bags'),ep=$('#w-price'),ed=$('#w-date');if(eb)w.bags=+eb.value;if(ep)w.price=+ep.value;if(ed)w.date=ed.value;
  if(!validateStep(w)){S.focus=w.step===1?'w-bags':w.step===2?'w-date':w.step===4?'w-price':null;render();break}
  if(w.step<5){w.step++;render()}else submitListing(w);break}
 case'save':{const id=a.dataset.id;S.saved.has(id)?S.saved.delete(id):S.saved.add(id);render();break}
 case'cmp':{const id=a.dataset.id;if(a.checked){if(S.compare.size>=3){a.checked=false;toast('Compare up to 3 listings');break}S.compare.add(id)}else S.compare.delete(id);render();break}
 case'docompare':{const r=S.listings.filter(l=>S.compare.has(l.id));dlg(`<h2>${t('compare')}</h2><div class="tablewrap"><table><thead><tr><th scope="col"></th>${r.map(l=>`<th scope="col">${e(l.farmer)}</th>`).join('')}</tr></thead><tbody>${[['Price / bag',l=>kes(l.price)],['Bags',l=>l.bags],['Quality',l=>l.grade],['Harvest',l=>day(l.harvest)],['Place',l=>l.place],['Variety',l=>l.variety]].map(([n,f])=>`<tr><th scope="row">${n}</th>${r.map(l=>`<td>${e(f(l))}</td>`).join('')}</tr>`).join('')}</tbody></table></div><div class="row" style="margin-top:14px"><button class="btn block" data-act="close">Close</button></div>`);break}
 case'close':closeDlg();break;
 case'mgrade':S.mg=a.dataset.v;render();break;
 case'clearf':S.mq=S.ml=S.mm=S.mg='';render();break;
 case'enq':{const id=a.dataset.id;const l=S.listings.find(x=>x.id===id);dlg(`<h2>Enquiry to ${e(l.farmer)}</h2><form data-form="enq" data-id="${id}" novalidate><div class="field"><label for="eq-bags">How many bags? (up to ${l.bags})</label><input id="eq-bags" type="number" inputmode="numeric" value="20"></div><div class="field"><label for="eq-note">Message (optional)</label><input id="eq-note" type="text" value="I can collect this week."></div><div id="eq-err"></div><div class="hint">This is an enquiry, not an order. FreshRoute does not take payment.</div><div class="row" style="margin-top:14px"><button type="button" class="btn ghost" data-act="close">${t('cancel')}</button><button class="btn block" style="flex:1">${t('send')}</button></div></form>`);setTimeout(()=>$('#eq-bags')&&$('#eq-bags').focus(),30);break}
 case'reply':dlg(`<h2>Reply</h2><form data-form="reply" data-id="${a.dataset.id}"><div class="field"><label for="rp">Message</label><input id="rp" type="text" value="Yes, available. Price as listed."></div><div class="row"><button type="button" class="btn ghost" data-act="close">${t('cancel')}</button><button class="btn block" style="flex:1">Send reply</button></div></form>`);break;
 case'agree':{const x=S.enquiries.find(q=>q.id===a.dataset.id);x.status='agreed';toast('Marked as agreed');render();break}
 case'newloss':dlg(`<h2>Record a loss</h2><form data-form="loss" novalidate><p class="lbl">Where did it happen?</p><div class="bigchoice" id="lstage" role="group" aria-label="Stage" style="grid-template-columns:repeat(2,1fr)">${D.losses.map((x,i)=>`<button type="button" data-act="lstage" data-v="${x.stage}" aria-pressed="${i===3}" style="min-height:64px">${x.label}</button>`).join('')}</div><div class="field" style="margin-top:14px"><label for="lb">Bags lost</label><input id="lb" type="number" inputmode="numeric" value="2"></div><div class="field"><label for="lr">Reason</label><select id="lr"><option>Rot</option><option>Rain damage</option><option>Pests</option><option>Bruising</option><option>Rejected by buyer</option><option>Greening (light)</option></select></div><div id="l-err"></div><div class="row"><button type="button" class="btn ghost" data-act="close">${t('cancel')}</button><button class="btn block" style="flex:1">${t('save')}</button></div></form>`);break;
 case'lstage':a.parentElement.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',b===a));break;
 case'ptab':S.pt=a.dataset.v;render();break;
 case'submitprice':dlg(`<h2>Submit a price</h2><p class="small muted">Shown as User-submitted. Never mixed into official prices.</p><form data-form="price" novalidate><div class="field"><label for="sp">KES per 50 kg bag</label><input id="sp" type="number" inputmode="numeric" value="2600"></div><div class="row"><button type="button" class="btn ghost" data-act="close">${t('cancel')}</button><button class="btn block" style="flex:1">${t('save')}</button></div></form>`);break;
 case'export':toast('Simulated: your file would download now');break;
 case'delreq':if(a.dataset.id){const x=D.transporters.find(q=>q.id===a.dataset.id);dlg(`<h2>Delivery request</h2><p>${e(x.name)} - ${e(x.vehicle)}</p><form data-form="del" data-id="${x.id}"><div class="field"><label for="dr">Bags to move</label><input id="dr" type="number" value="40"></div><div class="row"><button type="button" class="btn ghost" data-act="close">${t('cancel')}</button><button class="btn block" style="flex:1">Send request</button></div></form>`)}else toast('Simulated: deletion request recorded');break;
 case'advance':{const d=S.deliveries.find(q=>q.id===a.dataset.id);d.step++;toast('Status updated');render();break}
 case'contact':toast('Simulated: request sent to the storage provider');break;
 case'stfree':S.sf=!S.sf;render();break;
 case'invite':toast('Simulated: invitation by SMS/email would be sent');break;
 case'addharvest':toast('Simulated: choose crop plot and date');break;
 case'simerr':S.error=true;render();break;
 case'retry':S.error=false;toast('Loaded');render();break;
 case'pref':S.prefs[a.dataset.k]=a.checked;toast('Saved');break}});
document.addEventListener('input',ev=>{const i=ev.target;if(i.id==='mq'){S.mq=i.value;S.focus='mq';render()}if(i.id==='mbq'){S.mbq=i.value;S.focus='mbq';render()}});
document.addEventListener('change',ev=>{const i=ev.target;if(i.id==='ml'){S.ml=i.value;render()}if(i.id==='mm'){S.mm=i.value;render()}if(i.id==='ms'){S.ms=i.value;render()}});
document.addEventListener('submit',ev=>{const f=ev.target.closest('form');if(!f)return;ev.preventDefault();const k=f.dataset.form;
 if(k==='enq'){const l=S.listings.find(x=>x.id===f.dataset.id),n=+$('#eq-bags').value;if(!(n>0&&n<=l.bags)){$('#eq-err').innerHTML=`<div class="err" role="alert">${ic('warn','sm')}Enter 1 to ${l.bags} bags.</div>`;$('#eq-bags').setAttribute('aria-invalid','true');return}
  S.enquiries.unshift({id:'E-'+(40+S.enquiries.length),listing:l.id,buyer:'Mama Njeri Greens Ltd',bags:n,status:S.offline?'queued':'new',when:'Just now',note:$('#eq-note').value});if(S.offline){S.queue.push({id:'E',type:'enquiry'});toast('Offline: enquiry queued')}else toast('Enquiry sent. Track it under Enquiries.');closeDlg();go('enquiries')}
 if(k==='reply'){const x=S.enquiries.find(q=>q.id===f.dataset.id);x.status='replied';toast('Reply sent');closeDlg();render()}
 if(k==='loss'){const n=+$('#lb').value;if(!(n>0)){$('#l-err').innerHTML=`<div class="err" role="alert">${ic('warn','sm')}Enter bags lost (1 or more).</div>`;return}
  const st=$('#lstage [aria-pressed=true]').dataset.v;S.losses.unshift({id:'X'+S.losses.length,stage:st,bags:n,reason:$('#lr').value,when:'Today',queued:S.offline});if(S.offline){S.queue.push({id:'X',type:'loss'});toast('Offline: saved on this phone')}else toast('Loss recorded');closeDlg();render()}
 if(k==='price'){const n=+$('#sp').value;if(!(n>0))return;D.userPrices.unshift({who:'You',price:n,unit:'KES / 50 kg bag',date:D.today});closeDlg();toast('Added as User-submitted');render()}
 if(k==='del'){S.deliveries.unshift({id:'D-'+(10+S.deliveries.length),who:D.transporters.find(q=>q.id===f.dataset.id).name,from:'Ol Kalou',to:'Nakuru',bags:+$('#dr').value,step:1});closeDlg();toast('Request sent');go('requests',role())}
 if(k==='login'){toast('Choose a role below')}});
$('#dlg').addEventListener('click',ev=>{if(ev.target.id==='dlg')closeDlg()});
window.FRS=S;render();
})();

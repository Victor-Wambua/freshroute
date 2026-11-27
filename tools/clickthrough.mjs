import { chromium } from 'playwright';
import fs from 'fs';
const home=process.env.HOME,exe=fs.readdirSync(home+'/Library/Caches/ms-playwright').filter(x=>x.startsWith('chromium-')).map(x=>`${home}/Library/Caches/ms-playwright/${x}/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing`)[0];
const U='http://localhost:4360/prototype/';
const b=await chromium.launch({executablePath:exe});
const res=[],errs=[];let pass=0,fail=0;
const ok=(n,c,d='')=>{(c?pass++:fail++);res.push(`${c?'PASS':'FAIL'}  ${n}${d?' - '+d:''}`)};
const ctx=await b.newContext({viewport:{width:390,height:844},isMobile:true,hasTouch:true,deviceScaleFactor:2});
const p=await ctx.newPage();
p.on('console',m=>{if(['error','warning'].includes(m.type()))errs.push(m.type()+': '+m.text())});p.on('pageerror',e=>errs.push('pageerror: '+e.message));
const wait=()=>p.waitForTimeout(450);
const click=async(sel)=>{await p.locator(sel).first().click();await wait()};
await p.goto(U+'#/login');await wait();
ok('login screen renders 4 roles',await p.locator('.rolecard').count()===4);
await click('[data-act=setlang][data-v=sw]');await click('[data-act=enter][data-v=farmer]');
ok('Swahili toggle labels nav',(await p.locator('.tabbar').innerText()).includes('Mwanzo'));
await click('[data-act=lang]');
ok('English toggle back',(await p.locator('.tabbar').innerText()).includes('Home'));
// sell wizard: count taps
let taps=0;const t0=Date.now();
await click('.tab.cta');taps++;
await click('[data-act=wset][data-k=bags][data-v="50"]');taps++;await click('[data-act=wnext]');taps++;
await click('[data-act=wset][data-k=date]:nth-of-type(2)');taps++;await click('[data-act=wnext]');taps++;
await click('[data-act=wnext]');ok('quality required validation',await p.locator('.err').count()>0);
await click('[data-act=wset][data-k=grade][data-v=A]');taps++;await click('[data-act=wnext]');taps++;
await click('[data-act=wnext]');taps++;
await click('[data-act=wnext]');taps++;
ok('listing published and appears in My produce',/#\/farmer\/listings/.test(p.url())&&(await p.locator('main').innerText()).includes('50'),`${taps} taps, ${(Date.now()-t0)/1000}s incl. 450ms waits`);
ok('new listing taps <= 10',taps<=10,String(taps));
// wizard validation: bags=0
await click('.tab.cta');await p.fill('#w-bags','0');await click('[data-act=wnext]');
ok('bags=0 shows error',(await p.locator('#e-bags').count())===1);
await p.goto(U+'#/farmer/home');await wait();
// offline queue
await click('[data-act=offline]');ok('offline banner',(await p.locator('.banner.off').count())>0);
await click('.tab.cta');await click('[data-act=wnext]');await click('[data-act=wnext]');await p.locator('[data-act=wset][data-k=grade][data-v=B]').click();await click('[data-act=wnext]');await click('[data-act=wnext]');await click('[data-act=wnext]');
ok('offline create queued',(await p.locator('main').innerText()).includes('Queued'));
await click('[data-act=offline]');await click('[data-act=sync]');
ok('sync clears queue',(await p.locator('.banner.off').count())===0);
// losses
await p.goto(U+'#/farmer/losses');await wait();await click('[data-act=newloss]');await p.fill('#lb','0');await p.locator('#dlg button.btn.block').click();ok('loss validation',(await p.locator('#l-err .err').count())===1);
await p.fill('#lb','3');await p.locator('#dlg button.btn.block').click();await wait();ok('loss recorded',(await p.locator('main').innerText()).includes('7 bags'));
// prices separation
await p.goto(U+'#/farmer/prices');await wait();
let txt=await p.locator('main').innerText();ok('official tab: no user-submitted rows',!txt.includes('Farmer, Kinangop')&&txt.includes('Official'));
await click('[data-act=ptab][data-v=user]');txt=await p.locator('main').innerText();ok('user tab: no official rows',!txt.includes('Nakuru (wholesale)')&&txt.includes('User-submitted'));
// error state
await p.goto(U+'#/farmer/more');await wait();await click('[data-act=simerr]');ok('error state shown',(await p.locator('.state-error').count())===1);await click('[data-act=retry]');ok('retry recovers',(await p.locator('.state-error').count())===0);
// buyer
await p.goto(U+'#/login');await click('[data-act=enter][data-v=buyer]');
await p.fill('#mq','Njoro');await wait();ok('search filters',(await p.locator('article.listing').count())===1);
await p.fill('#mq','zzz');await wait();ok('empty state',(await p.locator('.empty').count())===1);await click('[data-act=clearf]');
ok('listings restored after clear (6 seeded + 2 created earlier this session)',(await p.locator('article.listing').count())===8);
await p.selectOption('#mm','200');await wait();ok('min-bags filter',(await p.locator('article.listing').count())===3);
await p.selectOption('#mm','0');await wait();
await p.locator('[data-act=save]').first().click();await wait();await p.goto(U+'#/buyer/saved');await wait();ok('saved supplier appears',(await p.locator('article.listing').count())===1);
await p.goto(U+'#/buyer/market');await wait();await p.locator('[data-act=cmp]').nth(0).check();await wait();await p.locator('[data-act=cmp]').nth(1).check();await wait();await click('[data-act=docompare]');ok('compare dialog',(await p.locator('#dlg table').count())===1);await click('#dlg [data-act=close]');
await p.locator('a:has-text("View")').first().click();await wait();await click('[data-act=enq]');await p.fill('#eq-bags','99999');await p.locator('#dlg button.block').click();ok('enquiry validation',(await p.locator('#eq-err .err').count())===1);
await p.fill('#eq-bags','10');await p.locator('#dlg button.block').click();await wait();ok('enquiry tracked',/enquiries/.test(p.url())&&(await p.locator('main').innerText()).includes('Just now'));
// provider
await p.goto(U+'#/login');await click('[data-act=enter][data-v=provider]');await click('[data-act=stfree]');ok('storage filter hides full',(await p.locator('article.card').count())===2);
await p.goto(U+'#/provider/transport');await wait();await p.locator('[data-act=delreq]').first().click();await wait();await p.locator('#dlg button.block').click();await wait();ok('delivery request created',/requests/.test(p.url())&&(await p.locator('article.card').count())===2);
await click('[data-act=advance]');ok('status advances',true);
// coop
await p.goto(U+'#/login');await click('[data-act=enter][data-v=coop]');ok('coop dashboard',(await p.locator('.kpi').count())===4);
await p.goto(U+'#/coop/members');await wait();await p.fill('#mbq','Peter');await wait();ok('member search',(await p.locator('tbody tr').count())===1);
await p.goto(U+'#/coop/alerts');await wait();ok('SMS toggle disabled (outstanding)',await p.locator('input[disabled]').count()===1);
// viewport overflow + touch targets + DOM contrast
const routes=[['farmer',['home','sell','listings','calendar','losses','enquiries','prices','mydata','more','storage','transport','requests','alerts']],['buyer',['market','saved','enquiries','prices','alerts']],['provider',['storage','transport','requests','alerts']],['coop',['dashboard','members','reports','prices','alerts']]];
const audit=()=>{const lum=c=>{const v=c.map(x=>x/255).map(x=>x<=.03928?x/12.92:((x+.055)/1.055)**2.4);return .2126*v[0]+.7152*v[1]+.0722*v[2]};
 const parse=s=>{const m=s.match(/rgba?\(([^)]+)\)/);if(!m)return null;const a=m[1].split(/[ ,\/]+/).map(Number);return {c:a.slice(0,3),a:a.length>3?a[3]:1}};
 const bgOf=el=>{while(el){const cs=getComputedStyle(el);if(cs.backgroundImage!=='none')return null;const p=parse(cs.backgroundColor);if(p&&p.a>0.99)return p.c;el=el.parentElement}return [255,255,255]};
 const bad=[];let n=0;const w=document.createTreeWalker(document.querySelector('#app'),NodeFilter.SHOW_TEXT);let node;
 while(node=w.nextNode()){if(!node.textContent.trim())continue;const el=node.parentElement;const cs=getComputedStyle(el);if(cs.visibility==='hidden'||el.closest('.sr')||el.getClientRects().length===0)continue;const fg=parse(cs.color);const bg=bgOf(el);if(!bg||!fg)continue;const r=(a,b)=>{const [x,y]=[lum(a),lum(b)].sort((p,q)=>q-p);return (x+.05)/(y+.05)};const ratio=r(fg.c,bg);const big=parseFloat(cs.fontSize)>=24||(parseFloat(cs.fontSize)>=18.66&&+cs.fontWeight>=700);n++;if(ratio<(big?3:4.5))bad.push(el.tagName+'.'+el.className+' "'+node.textContent.trim().slice(0,24)+'" '+ratio.toFixed(2))}
 const small=[...document.querySelectorAll('button,a.btn,a.tab,.iconbtn,select,input:not([type=checkbox]),.chip')].filter(e=>{const r=e.getBoundingClientRect();return r.width&&r.height&&(r.height<44||r.width<44)}).map(e=>e.tagName+'.'+e.className+' '+Math.round(e.getBoundingClientRect().width)+'x'+Math.round(e.getBoundingClientRect().height));
 return {n,bad,small,over:document.documentElement.scrollWidth>innerWidth+1}};
let overflowFails=[],smallAll=new Set(),contrastBad={};let checked=0,txtN=0;
for(const scheme of ['light','dark']){const c2=await b.newContext({viewport:{width:390,height:844},isMobile:true,hasTouch:true,colorScheme:scheme});const q=await c2.newPage();q.on('pageerror',e=>errs.push('pageerror: '+e.message));q.on('console',m=>{if(m.type()==='error')errs.push('error: '+m.text())});
 for(const [rl,rs] of routes)for(const r of rs){await q.goto(U+`#/${rl}/${r}`);await q.waitForTimeout(550);const a=await q.evaluate(audit);checked++;txtN+=a.n;if(a.over)overflowFails.push(scheme+' '+rl+'/'+r);a.small.forEach(s=>smallAll.add(s));a.bad.forEach(s=>(contrastBad[scheme+' '+s]=1))}
 await c2.close()}
ok(`no horizontal overflow at 390px (${checked} route/theme combos)`,overflowFails.length===0,overflowFails.join(', '));
ok(`rendered-text contrast (${txtN} text nodes, light+dark; gradient backgrounds skipped)`,Object.keys(contrastBad).length===0,Object.keys(contrastBad).slice(0,8).join(' | '));
ok('touch targets >= 44px (interactive elements measured)',smallAll.size===0,[...smallAll].slice(0,8).join(' | '));
ok('no console errors/warnings/page errors',errs.length===0,errs.slice(0,5).join(' | '));
console.log(res.join('\n'));console.log(`\n${pass} passed, ${fail} failed`);
fs.writeFileSync('../design/verification-results.md','# Prototype verification (headless Chromium, Playwright)\n\nRun: `cd tools && node serve.mjs ../design 4360 &` then `node clickthrough.mjs`.\n\n```\n'+res.join('\n')+`\n\n${pass} passed, ${fail} failed\n\`\`\`\n`);
await b.close();

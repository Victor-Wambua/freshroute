import { chromium } from 'playwright';import fs from 'fs';
const home=process.env.HOME,exe=fs.readdirSync(home+'/Library/Caches/ms-playwright').filter(x=>x.startsWith('chromium-')).map(x=>`${home}/Library/Caches/ms-playwright/${x}/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing`)[0];
const b=await chromium.launch({executablePath:exe});const c=await b.newContext({colorScheme:'dark',viewport:{width:390,height:844}});const p=await c.newPage();
await p.goto('http://localhost:4360/prototype/#/farmer/home');await p.waitForTimeout(700);
console.log(await p.evaluate(()=>{const el=[...document.querySelectorAll('.tab span')][0];const out=[];let x=el;while(x){const cs=getComputedStyle(x);out.push(x.tagName+' '+cs.backgroundColor+' pos='+cs.position);x=x.parentElement}return out.join(' | ')}));console.log(await p.evaluate(()=>{const s=document.querySelector('.tab span');let o=[];let e=s;while(e){const cs=getComputedStyle(e);o.push(e.tagName+'.'+e.className+' bg='+cs.backgroundColor+' img='+cs.backgroundImage.slice(0,20)+' c='+cs.color);e=e.parentElement}return o.join('\n')}));
await b.close();

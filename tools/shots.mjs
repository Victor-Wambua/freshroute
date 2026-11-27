import { chromium } from 'playwright';
import fs from 'fs';
const out='../design/screenshots';fs.mkdirSync(out,{recursive:true});
const exe=fs.readdirSync(process.env.HOME+'/Library/Caches/ms-playwright').filter(x=>x.startsWith('chromium-')).map(x=>`${process.env.HOME}/Library/Caches/ms-playwright/${x}/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing`)[0];
const b=await chromium.launch({executablePath:exe});
const shots=[['farmer','home'],['farmer','sell'],['farmer','calendar'],['farmer','losses'],['farmer','enquiries'],['farmer','prices'],['buyer','market'],['buyer','saved'],['provider','storage'],['provider','transport'],['provider','requests'],['coop','dashboard'],['coop','members'],['coop','reports'],['coop','alerts'],['login']];
for(const [vn,w,h,mob] of [['desktop',1440,900,false],['mobile',390,844,true]]) for(const scheme of ['light','dark']){
 const ctx=await b.newContext({viewport:{width:w,height:h},deviceScaleFactor:mob?2:1,colorScheme:scheme,isMobile:mob,hasTouch:mob});const p=await ctx.newPage();
 for(const [r,s] of shots){await p.goto('http://localhost:4360/prototype/#/'+(s?r+'/'+s:r));await p.waitForTimeout(600);await p.screenshot({path:`${out}/${vn}-${scheme}-${r}${s?'-'+s:''}.png`});}
 await ctx.close();}
await b.close();console.log('shots done');

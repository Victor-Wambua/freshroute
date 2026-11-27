import fs from 'fs';
const css=fs.readFileSync('../design/design-tokens.css','utf8');
const grab=(re)=>{const m=css.match(re);const o={};if(m)for(const x of m[1].matchAll(/--fr-([\w-]+):\s*([^;]+);/g))o[x[1]]=x[2].trim();return o};
const out={light:grab(/:root\{([\s\S]*?)\n\}/),dark:grab(/:root\[data-theme="dark"\]\{([\s\S]*?)\n\}/)};
fs.writeFileSync('../design/design-tokens.json',JSON.stringify(out,null,2));console.log(Object.keys(out.light).length,Object.keys(out.dark).length);

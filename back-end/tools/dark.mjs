import puppeteer from 'puppeteer';
const BASE='http://localhost:8080';
const b=await puppeteer.launch({headless:'new',args:['--no-sandbox']});
const ctx=await b.createBrowserContext(); const p=await ctx.newPage();
await p.setViewport({width:Number(process.env.W||1440),height:900});
await p.goto(BASE+'/login',{waitUntil:'networkidle2'});
await p.evaluate(()=>localStorage.setItem('etpb-theme','dark'));
await p.reload({waitUntil:'networkidle2'});
await p.type('#email',process.env.U); await p.type('#password',process.env.PW||'Etpb@2026#Change');
await Promise.all([p.waitForNavigation({waitUntil:'networkidle2'}),p.click('button[type=submit]')]);
await p.goto(BASE+process.env.P,{waitUntil:'networkidle2'});
const t=await p.evaluate(()=>document.documentElement.getAttribute('data-theme'));
// Anything whose text colour is too close to its own background is unreadable.
const bad=await p.evaluate(()=>{
  const lum=c=>{const m=c.match(/[\d.]+/g);if(!m)return null;const[r,g,bl]=m.map(Number);
    return (0.2126*r+0.7152*g+0.0722*bl)/255;};
  const out=[];
  for(const el of document.querySelectorAll('body *')){
    if(el.childElementCount||!(el.textContent||'').trim())continue;
    const cs=getComputedStyle(el); if(cs.visibility==='hidden'||!el.getBoundingClientRect().height)continue;
    let bg='rgba(0, 0, 0, 0)', n=el;
    while(n&&(bg==='rgba(0, 0, 0, 0)'||bg==='transparent')){bg=getComputedStyle(n).backgroundColor;n=n.parentElement;}
    const a=lum(cs.color), c=lum(bg); if(a==null||c==null)continue;
    const ratio=(Math.max(a,c)+0.05)/(Math.min(a,c)+0.05);
    if(ratio<2.2) out.push({txt:(el.textContent||'').trim().slice(0,40),color:cs.color,bg,ratio:ratio.toFixed(2)});
  }
  const seen=new Set();
  return out.filter(o=>!seen.has(o.txt)&&seen.add(o.txt)).slice(0,10);
});
console.log('theme =',t,'| low-contrast items:',bad.length);
for(const x of bad) console.log('   ',x.ratio,JSON.stringify(x.txt),x.color,'on',x.bg);
await p.screenshot({path:process.env.OUT});
await b.close();

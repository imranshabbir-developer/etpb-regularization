import puppeteer from 'puppeteer';
const BASE='http://localhost:8080';
const b=await puppeteer.launch({headless:'new',args:['--no-sandbox']});
const ctx=await b.createBrowserContext(); const p=await ctx.newPage();
await p.setViewport({width:390,height:844,isMobile:true,hasTouch:true});
await p.goto(BASE+'/login',{waitUntil:'networkidle2'});
await p.type('#email','admin.lhr@etpb.gov.pk'); await p.type('#password','Etpb@2026#Change');
await Promise.all([p.waitForNavigation({waitUntil:'networkidle2'}),p.click('button[type=submit]')]);
await p.goto(BASE+process.env.P,{waitUntil:'networkidle2'});
const out=await p.evaluate(()=>{
  const vw=document.documentElement.clientWidth; const res=[];
  for(const el of document.querySelectorAll('body *')){
    const r=el.getBoundingClientRect(); if(!r.width||!r.height) continue;
    const cs=getComputedStyle(el); if(cs.visibility==='hidden'||cs.position==='fixed') continue;
    if(r.right<=vw+2) continue;
    // only leaves or elements whose children are all inside -> the true culprits
    const kids=[...el.children].filter(k=>{const kr=k.getBoundingClientRect();return kr.width&&kr.right>vw+2;});
    if(kids.length) continue;
    res.push({tag:el.tagName.toLowerCase(),cls:(el.className||'').toString().slice(0,70),
      right:Math.round(r.right),w:Math.round(r.width),ws:cs.whiteSpace,ovx:cs.overflowX,
      txt:(el.textContent||'').trim().slice(0,45)});
  }
  return {vw,scrollW:document.documentElement.scrollWidth,res};
});
console.log('viewport',out.vw,'scrollWidth',out.scrollW);
for(const r of out.res) console.log(' ',r.tag,'.'+r.cls,'| right',r.right,'w',r.w,'| ws',r.ws,'ovx',r.ovx,'|',JSON.stringify(r.txt));
await b.close();

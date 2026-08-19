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
  const res=[];
  for(const el of document.querySelectorAll('html,body,body *')){
    if(el.scrollWidth > el.clientWidth + 1 && el.clientWidth>0){
      const cs=getComputedStyle(el);
      res.push({tag:el.tagName.toLowerCase(),cls:(el.className||'').toString().slice(0,50),
        cw:el.clientWidth, sw:el.scrollWidth, ovx:cs.overflowX, disp:cs.display,
        minw:cs.minWidth, w:cs.width});
    }
  }
  return res;
});
for(const r of out) console.log(` ${r.tag}.${r.cls} client=${r.cw} scroll=${r.sw} ovx=${r.ovx} disp=${r.disp} minW=${r.minw} w=${r.w}`);
await b.close();

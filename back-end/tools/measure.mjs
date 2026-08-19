import puppeteer from 'puppeteer';
const BASE='http://localhost:8080';
const b=await puppeteer.launch({headless:'new',args:['--no-sandbox']});
const ctx=await b.createBrowserContext(); const p=await ctx.newPage();
await p.setViewport({width:Number(process.env.W||1440),height:900});
await p.goto(BASE+'/login',{waitUntil:'networkidle2'});
await p.type('#email',process.env.U); await p.type('#password','Etpb@2026#Change');
await Promise.all([p.waitForNavigation({waitUntil:'networkidle2'}),p.click('button[type=submit]')]);
await p.goto(BASE+process.env.P,{waitUntil:'networkidle2'});
console.log(await p.evaluate(()=>[...document.querySelectorAll('.table-wrap')].map(w=>({
  client:w.clientWidth, scroll:w.scrollWidth, short:w.scrollWidth-w.clientWidth,
  cols:[...w.querySelectorAll('thead th')].map(th=>th.textContent.trim()+':'+Math.round(th.getBoundingClientRect().width))
}))));
await b.close();

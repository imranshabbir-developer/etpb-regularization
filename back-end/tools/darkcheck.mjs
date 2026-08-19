/**
 * Contrast sweep in dark mode.
 *
 * The palette switches by theme but a number of rules name a light-mode ink
 * directly, so text can end up dark green on a dark green ground. This visits
 * every screen in dark mode and reports any text whose contrast against its own
 * background falls below a readable ratio, naming the CSS class responsible.
 */
import puppeteer from 'puppeteer';
import { readFileSync } from 'fs';

const BASE = 'http://localhost:8080';
const src = readFileSync(new URL('./uicheck.mjs', import.meta.url), 'utf8');
const ROLES = JSON.parse(process.env.ROLES || 'null') || null;
const THEME = process.env.THEME || 'dark';

const probe = () => {
    const lum = c => {
        const m = c.match(/[\d.]+/g);
        if (!m) return null;
        const f = v => { v /= 255; return v <= .03928 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; };
        return .2126 * f(+m[0]) + .7152 * f(+m[1]) + .0722 * f(+m[2]);
    };
    const out = [];
    for (const el of document.querySelectorAll('body *')) {
        if (el.childElementCount || !(el.textContent || '').trim()) continue;
        const cs = getComputedStyle(el);
        if (cs.visibility === 'hidden' || !el.getBoundingClientRect().height) continue;
        // Walk up for the ground this text is actually drawn on. An ancestor
        // painted with a gradient or image has no resolvable colour, so stop
        // there and skip rather than report the page behind it.
        let bg = cs.backgroundColor, n = el, painted = false;
        while (n && (bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent')) {
            if (getComputedStyle(n).backgroundImage !== 'none') { painted = true; break; }
            n = n.parentElement;
            if (!n) break;
            bg = getComputedStyle(n).backgroundColor;
        }
        if (painted) continue;
        const a = lum(cs.color), b = lum(bg);
        if (a == null || b == null) continue;
        const ratio = (Math.max(a, b) + .05) / (Math.min(a, b) + .05);
        if (ratio < 4.5) {
            out.push({
                ratio: +ratio.toFixed(2),
                sel: el.tagName.toLowerCase() + (el.className ? '.' + el.className.toString().trim().split(/\s+/).join('.') : ''),
                parent: el.parentElement ? (el.parentElement.className || '').toString().slice(0, 40) : '',
                fg: cs.color, bg,
                txt: (el.textContent || '').trim().slice(0, 30),
            });
        }
    }
    return out;
};

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
const worst = new Map();

const CFG = (await import('./roles.mjs')).ROLES;
for (const [role, cfg] of Object.entries(CFG)) {
    if (ROLES && !ROLES.includes(role)) continue;
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
    await page.evaluate(t => localStorage.setItem('etpb-theme', t), THEME);
    await page.reload({ waitUntil: 'networkidle2' });
    await page.type('#email', cfg.email);
    await page.type('#password', cfg.password || 'Etpb@2026#Change');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('button[type=submit]')]);

    for (const path of cfg.pages) {
        const res = await page.goto(BASE + path, { waitUntil: 'networkidle2' }).catch(() => null);
        if (!res || res.status() >= 400) continue;
        for (const f of await page.evaluate(probe)) {
            const key = f.sel + '|' + f.fg + '|' + f.bg;
            if (!worst.has(key) || worst.get(key).ratio > f.ratio) worst.set(key, { ...f, where: `${role} ${path}` });
        }
    }
    await page.close(); await ctx.close();
    process.stdout.write(`  swept ${role}\n`);
}
await browser.close();

const rows = [...worst.values()].sort((a, b) => a.ratio - b.ratio);
console.log(`\n=== ${rows.length} distinct low-contrast pairings in dark mode ===\n`);
for (const r of rows) {
    console.log(`  ${String(r.ratio).padStart(5)}  ${r.sel}`);
    console.log(`         ${r.fg} on ${r.bg}  "${r.txt}"  [${r.where}]`);
}

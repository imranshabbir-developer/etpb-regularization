/**
 * Visual and responsive check across roles and screen sizes.
 *
 * Drives a real browser against the running portal, signs in as each role,
 * visits every screen at phone, tablet and desktop widths, and reports:
 *
 *   - horizontal overflow (the single most common responsive defect)
 *   - elements spilling outside the viewport
 *   - text too small to read on a phone
 *   - tap targets below the guideline
 *   - console errors
 *
 * Screenshots are written so the pages can be inspected by eye as well.
 *
 *   node tools/uicheck.mjs [--shots] [--only=role]
 */
import puppeteer from 'puppeteer';
import { mkdirSync, writeFileSync } from 'fs';
import { join } from 'path';
import { ROLES } from './roles.mjs';

const BASE = process.env.ETPB_BASE || 'http://localhost:8080';
const PASSWORD = 'Etpb@2026#Change';
const OUT = process.env.ETPB_SHOTS || join(process.cwd(), 'storage', 'uicheck');

const VIEWPORTS = [
    { name: 'phone',   width: 390,  height: 844,  mobile: true },
    { name: 'tablet',  width: 820,  height: 1180, mobile: true },
    { name: 'desktop', width: 1440, height: 900,  mobile: false },
];

const shots = process.argv.includes('--shots');
const only = (process.argv.find(a => a.startsWith('--only=')) || '').split('=')[1];

mkdirSync(OUT, { recursive: true });

const findings = [];
const note = (sev, role, vp, page, msg) => findings.push({ sev, role, vp, page, msg });

async function signIn(page, email, password) {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });

    // The login route is rate limited to ten attempts a minute, which is the
    // behaviour a public portal should have. When a burst trips it the form is
    // not on the page at all, so wait the window out rather than reporting a
    // fault in the application.
    if (!(await page.$('#email'))) {
        await new Promise(r => setTimeout(r, 62000));
        await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
        if (!(await page.$('#email'))) throw new Error('login form unavailable (rate limited?)');
    }

    await page.type('#email', email);
    await page.type('#password', password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }),
        page.click('button[type=submit]'),
    ]);

    if (page.url().includes('/login')) {
        const err = await page.$eval('.alert-danger', el => el.textContent.trim().slice(0, 90))
            .catch(() => 'credentials rejected');
        throw new Error(err);
    }
}

/** Everything measured inside the page, where the real layout is. */
const audit = () => {
    const vw = document.documentElement.clientWidth;
    const out = { vw, scrollW: document.documentElement.scrollWidth, wide: [], small: [], tiny: [] };

    for (const el of document.querySelectorAll('body *')) {
        const r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) continue;

        const cs = getComputedStyle(el);
        if (cs.visibility === 'hidden' || cs.display === 'none') continue;
        if (cs.position === 'fixed') continue;         // drawers and widgets sit off-canvas by design

        // Overflowing the viewport horizontally.
        if (r.right > vw + 2 || r.left < -2) {
            let scrollable = false;
            for (let p = el.parentElement; p; p = p.parentElement) {
                const po = getComputedStyle(p).overflowX;
                if (po === 'auto' || po === 'scroll') { scrollable = true; break; }
            }
            if (!scrollable) {
                out.wide.push({
                    tag: el.tagName.toLowerCase(),
                    cls: (el.className || '').toString().slice(0, 60),
                    right: Math.round(r.right), left: Math.round(r.left),
                });
            }
        }

        // Body text that is hard to read on a phone.
        const fs = parseFloat(cs.fontSize);
        if (fs && fs < 11 && el.childElementCount === 0 && (el.textContent || '').trim().length > 12) {
            out.small.push({ tag: el.tagName.toLowerCase(), fs: fs.toFixed(1),
                             text: (el.textContent || '').trim().slice(0, 40) });
        }

        // Tap targets.
        if (['A', 'BUTTON'].includes(el.tagName) || el.getAttribute('role') === 'button') {
            if (r.height > 0 && r.height < 32 && (el.textContent || '').trim()) {
                out.tiny.push({ tag: el.tagName.toLowerCase(), h: Math.round(r.height),
                                text: (el.textContent || '').trim().slice(0, 30) });
            }
        }
    }

    const dedupe = (arr, key) => {
        const seen = new Set();
        return arr.filter(o => { const k = key(o); if (seen.has(k)) return false; seen.add(k); return true; });
    };

    out.wide  = dedupe(out.wide,  o => o.tag + o.cls).slice(0, 6);
    out.small = dedupe(out.small, o => o.tag + o.fs).slice(0, 5);
    out.tiny  = dedupe(out.tiny,  o => o.text).slice(0, 5);
    return out;
};

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });

for (const [role, cfg] of Object.entries(ROLES)) {
    if (only && only !== role) continue;

    // One session per role, resized between viewports. Signing in afresh for
    // every viewport would be twenty-one sign-ins in a burst, and the portal
    // rate limits that — which is what we want it to do, so the harness adapts.
    const context = await browser.createBrowserContext();
    const page = await context.newPage();
    await page.setViewport({ width: 1440, height: 900 });

    const consoleErrors = [];
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 120)); });
    page.on('pageerror', e => consoleErrors.push('pageerror: ' + String(e).slice(0, 120)));

    try {
        await signIn(page, cfg.email, cfg.password || PASSWORD);
    } catch (e) {
        note('FAIL', role, 'all', '/login', 'could not sign in: ' + String(e.message || e).slice(0, 90));
        await page.close();
        await context.close();
        continue;
    }

    for (const vp of VIEWPORTS) {
        await page.setViewport({ width: vp.width, height: vp.height, isMobile: vp.mobile,
                                 hasTouch: vp.mobile, deviceScaleFactor: 1 });

        for (const path of cfg.pages) {
            let status = 0;
            try {
                const res = await page.goto(BASE + path, { waitUntil: 'networkidle2', timeout: 30000 });
                status = res ? res.status() : 0;
            } catch (e) {
                note('FAIL', role, vp.name, path, 'navigation failed: ' + String(e).slice(0, 80));
                continue;
            }

            if (status >= 400) {
                note(status === 403 ? 'INFO' : 'FAIL', role, vp.name, path, `HTTP ${status}`);
                continue;
            }

            const a = await page.evaluate(audit);

            if (a.scrollW > a.vw + 2) {
                note('FAIL', role, vp.name, path,
                     `page scrolls sideways (${a.scrollW}px content in ${a.vw}px viewport)`);
                for (const w of a.wide) {
                    note('FAIL', role, vp.name, path,
                         `  overflows: <${w.tag} class="${w.cls}"> right edge at ${w.right}px`);
                }
            }
            if (vp.mobile && a.small.length) {
                for (const s of a.small) {
                    note('WARN', role, vp.name, path, `text at ${s.fs}px: "${s.text}"`);
                }
            }
            if (vp.name === 'phone' && a.tiny.length) {
                for (const t of a.tiny) {
                    note('WARN', role, vp.name, path, `tap target ${t.h}px tall: "${t.text}"`);
                }
            }

            if (shots) {
                const name = `${role}-${vp.name}-${path.replace(/[^a-z0-9]+/gi, '_')}.png`;
                await page.screenshot({ path: join(OUT, name), fullPage: vp.name === 'desktop' });
            }
        }
    }

    if (consoleErrors.length) {
        for (const e of [...new Set(consoleErrors)].slice(0, 3)) {
            note('WARN', role, 'all', '(any)', 'console: ' + e);
        }
    }

    await page.close();
    await context.close();              // otherwise every role leaves a browser behind
    console.log(`  checked ${role}`);
}

await browser.close();

const fails = findings.filter(f => f.sev === 'FAIL');
const warns = findings.filter(f => f.sev === 'WARN');

console.log('');
console.log(`=== ${fails.length} failures, ${warns.length} warnings ===`);
console.log('');
for (const f of [...fails, ...warns]) {
    console.log(`  [${f.sev}] ${f.role}/${f.vp} ${f.page}`);
    console.log(`         ${f.msg}`);
}
writeFileSync(join(OUT, 'findings.json'), JSON.stringify(findings, null, 2));
console.log(`report: ${join(OUT, 'findings.json')}`);

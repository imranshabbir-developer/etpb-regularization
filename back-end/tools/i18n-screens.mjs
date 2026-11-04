/*
 | Screenshots of the key screens in Urdu and English for visual review.
 |
 |   node tools/i18n-screens.mjs [baseUrl] [ur|en] [mobile]
 |
 | Images go to storage/app/i18n-qa/screens/.
 */
import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer';

const BASE = (process.argv.find((a) => a.startsWith('http')) || 'http://localhost:8080').replace(/\/$/, '');
const LOCALES = process.argv.includes('en') && ! process.argv.includes('ur') ? ['en']
    : (process.argv.includes('ur') && ! process.argv.includes('en') ? ['ur'] : ['ur', 'en']);
const MOBILE = process.argv.includes('mobile');
const OUT = path.resolve('storage/app/i18n-qa/screens');
fs.mkdirSync(OUT, { recursive: true });

const PW = 'Etpb@2026#Change';
const PLAN = [
    [null, null, ['/login', '/register', '/no-such-page']],
    ['chairman@etpb.gov.pk', PW, ['/dashboard', '/reports/executive', '/reports/glimpse']],
    ['do.lhr@etpb.gov.pk', PW, ['/dashboard', '/applications', 'CASE', 'CASE/assessment', 'CASE/due-process', 'CASE/arrears']],
    ['da.lhr@etpb.gov.pk', PW, ['/queues/scrutiny']],
    ['admin@etpb.gov.pk', PW, ['/admin/users', '/admin/settings']],
    ['demo.applicant@example.com', 'Demo#Portal2026', ['/dashboard', '/apply/about-you', '/apply/property', '/apply/possession']],
];

const exe = [
    process.env.PUPPETEER_EXECUTABLE_PATH,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
].find((p) => p && fs.existsSync(p));
const browser = await puppeteer.launch({ headless: true, ...(exe ? { executablePath: exe } : {}) });

async function settle(page) {
    await page.evaluate(() => document.fonts && document.fonts.ready);
    await page.evaluate(() => document.getElementById('etpbPromoModal')?.remove());
    await new Promise((r) => setTimeout(r, 900));
}

try {
    for (const locale of LOCALES) {
        const ctx = await browser.createBrowserContext();
        const page = await ctx.newPage();
        await page.setViewport(MOBILE ? { width: 390, height: 844, isMobile: true, deviceScaleFactor: 2 } : { width: 1440, height: 900 });
        await page.goto(`${BASE}/language/${locale}`, { waitUntil: 'networkidle0' });

        for (const [email, pw, urls] of PLAN) {
            let caseUrl = null;
            if (email) {
                await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
                await page.evaluate((e, p) => {
                    document.getElementById('email').value = e;
                    document.getElementById('password').value = p;
                }, email, pw);
                await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate(() => document.getElementById('loginForm').submit())]);
            }
            for (let u of urls) {
                if (u.startsWith('CASE')) {
                    if (! caseUrl) {
                        await page.goto(`${BASE}/applications`, { waitUntil: 'networkidle0' });
                        caseUrl = await page.evaluate(() => Array.from(document.querySelectorAll('table a[href*="/applications/"]'))
                            .map((a) => a.href).find((h) => /\/applications\/\d+$/.test(h)));
                    }
                    u = u.replace('CASE', caseUrl.replace(BASE, ''));
                }
                await page.goto(`${BASE}${u}`, { waitUntil: 'networkidle0' });
                await settle(page);
                const who = email ? email.split('@')[0].replace(/\./g, '-') : 'guest';
                const name = `${locale}${MOBILE ? '-m' : ''}-${who}-${u.replace(/\/applications\/\d+/, '/case').replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '')}.png`;
                await page.screenshot({ path: path.join(OUT, name), fullPage: false });
            }
            if (email) {
                await page.goto(`${BASE}/dashboard`, { waitUntil: 'networkidle0' });
                await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate(() => document.querySelector('form[action$="/logout"]').submit())]);
            }
        }
        await ctx.close();
    }
} finally {
    await browser.close();
}
console.log(`Screens written to ${OUT}`);

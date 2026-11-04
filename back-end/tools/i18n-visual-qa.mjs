/*
 | End-to-end language QA: signs in as every role, in Urdu and in English,
 | opens every page reachable from the sidebar plus the screens of a case,
 | and reports HTTP errors, console errors, horizontal overflow and any text
 | left in the wrong language. Screenshots go to storage/app/i18n-qa/.
 |
 |   node tools/i18n-visual-qa.mjs [baseUrl] [--shots]
 */
import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer';

const BASE = (process.argv.find((a) => a.startsWith('http')) || 'http://localhost:8080').replace(/\/$/, '');
const SHOTS = process.argv.includes('--shots');
const OUT = path.resolve('storage/app/i18n-qa');
fs.mkdirSync(OUT, { recursive: true });

const OFFICER_PW = 'Etpb@2026#Change';
const ACCOUNTS = [
    ['admin', 'admin@etpb.gov.pk', OFFICER_PW],
    ['chairman', 'chairman@etpb.gov.pk', OFFICER_PW],
    ['secretary', 'secretary@etpb.gov.pk', OFFICER_PW],
    ['administrator', 'admin.lhr@etpb.gov.pk', OFFICER_PW],
    ['district-officer', 'do.lhr@etpb.gov.pk', OFFICER_PW],
    ['dealing-assistant', 'da.lhr@etpb.gov.pk', OFFICER_PW],
    ['accounts', 'accounts.lhr@etpb.gov.pk', OFFICER_PW],
    ['legal', 'legal.lhr@etpb.gov.pk', OFFICER_PW],
    ['auditor', 'audit@etpb.gov.pk', OFFICER_PW],
    ['applicant', 'demo.applicant@example.com', 'Demo#Portal2026'],
];

const report = { pages: [], problems: [], latin: {}, arabicInEn: {} };

function slug(url) {
    return url.replace(BASE, '').replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '') || 'root';
}

async function collect(page, locale, role, url, label) {
    const consoleErrors = [];
    const onConsole = (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text()); };
    const onPageError = (err) => consoleErrors.push(String(err));
    page.on('console', onConsole);
    page.on('pageerror', onPageError);

    let status = 0;
    try {
        const res = await page.goto(url, { waitUntil: 'networkidle0', timeout: 45000 });
        status = res ? res.status() : 0;
        await page.evaluate(() => document.fonts && document.fonts.ready);
    } catch (e) {
        report.problems.push({ locale, role, url, issue: 'navigation', detail: String(e) });
    }

    const info = await page.evaluate(() => {
        const dir = document.documentElement.getAttribute('dir');
        const lang = document.documentElement.getAttribute('lang');
        const overflow = document.documentElement.scrollWidth - window.innerWidth;
        const texts = [];
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
            const el = node.parentElement;
            if (! el || el.closest('script, style, noscript, template, [hidden], .lang-switch')) continue;
            const cs = getComputedStyle(el);
            if (cs.display === 'none' || cs.visibility === 'hidden') continue;
            const t = node.textContent.replace(/\s+/g, ' ').trim();
            if (t) texts.push(t);
        }
        document.querySelectorAll('[placeholder], [title], [aria-label]').forEach((el) => {
            if (el.closest('.lang-switch')) return;
            ['placeholder', 'title', 'aria-label'].forEach((a) => {
                const v = el.getAttribute(a);
                if (v) texts.push(`@${a}: ${v.trim()}`);
            });
        });
        const heading = document.querySelector('.topbar-title, h1')?.textContent?.trim() || '';
        return { dir, lang, overflow, texts, heading, title: document.title };
    });

    page.off('console', onConsole);
    page.off('pageerror', onPageError);

    const entry = { locale, role, label, url, status, heading: info.heading, dir: info.dir, overflow: info.overflow };
    report.pages.push(entry);

    if (status >= 400 || status === 0) report.problems.push({ locale, role, url, issue: `HTTP ${status}` });
    if (consoleErrors.length) report.problems.push({ locale, role, url, issue: 'console', detail: consoleErrors.slice(0, 5) });
    if (info.overflow > 2) report.problems.push({ locale, role, url, issue: `horizontal overflow ${info.overflow}px` });
    const expectDir = locale === 'ur' ? 'rtl' : 'ltr';
    if (info.dir !== expectDir) report.problems.push({ locale, role, url, issue: `dir=${info.dir} lang=${info.lang}` });

    const bucket = locale === 'ur' ? report.latin : report.arabicInEn;
    const re = locale === 'ur' ? /[A-Za-z]{2,}/ : /[\u0600-\u06FF]/;
    for (const t of info.texts) {
        if (! re.test(t.replace(/^@(placeholder|title|aria-label): /, ''))) continue;
        const key = t.length > 160 ? t.slice(0, 160) + '…' : t;
        bucket[key] ??= { count: 0, where: new Set() };
        bucket[key].count++;
        if (bucket[key].where.size < 4) bucket[key].where.add(`${role} ${url.replace(BASE, '')}`);
    }

    if (SHOTS) {
        const file = path.join(OUT, `${locale}-${role}-${slug(url)}.png`);
        await page.screenshot({ path: file, fullPage: false });
    }
}

async function setLocale(page, locale) {
    await page.goto(`${BASE}/language/${locale}`, { waitUntil: 'networkidle0' });
}

async function signIn(page, email, password) {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
    await page.evaluate((e, p) => {
        document.getElementById('etpbPromoModal')?.remove();
        document.getElementById('email').value = e;
        document.getElementById('password').value = p;
    }, email, password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 60000 }),
        page.evaluate(() => document.getElementById('loginForm').submit()),
    ]);
    if (page.url().includes('/login')) {
        const err = await page.evaluate(() => document.querySelector('.alert-danger')?.innerText || '');
        throw new Error(`Sign-in failed for ${email}: ${err}`);
    }
    await page.evaluate(() => {
        const m = document.getElementById('etpbPromoModal');
        if (m) m.remove();
    });
}

async function signOut(page) {
    await page.goto(`${BASE}/dashboard`, { waitUntil: 'networkidle0' });
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.evaluate(() => document.querySelector('form[action$="/logout"]').submit()),
    ]);
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
    if (! (await page.$('#email'))) throw new Error(`Sign-out did not complete (at ${page.url()})`);
}

async function linksIn(page, selector) {
    return page.evaluate((sel) => Array.from(document.querySelectorAll(sel))
        .map((a) => a.href)
        .filter((h) => h && h.startsWith(location.origin) && ! h.includes('#') && ! /logout|language|format=|download/.test(h)), selector);
}

// Prefer an installed Chrome / Edge so the script works without a Puppeteer download.
const executablePath = [
    process.env.PUPPETEER_EXECUTABLE_PATH,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
].find((p) => p && fs.existsSync(p));

const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'], ...(executablePath ? { executablePath } : {}) });
try {
    for (const locale of ['ur', 'en']) {
        const ctx = await browser.createBrowserContext();
        const page = await ctx.newPage();
        await page.setViewport({ width: 1440, height: 900 });
        await setLocale(page, locale);
        await collect(page, locale, 'guest', `${BASE}/login`, 'login');
        await collect(page, locale, 'guest', `${BASE}/register`, 'register');

        const caseLinksSeen = new Set();
        for (const [role, email, pw] of ACCOUNTS) {
            await signIn(page, email, pw);
            await collect(page, locale, role, `${BASE}/dashboard`, 'dashboard');
            const nav = [...new Set(await linksIn(page, '#sidebar a[href], .sidebar a[href]'))];
            for (const url of nav) {
                if (url.endsWith('/dashboard')) continue;
                await collect(page, locale, role, url, 'nav');
            }

            // One case and every screen hanging off it.
            await page.goto(`${BASE}/applications`, { waitUntil: 'networkidle0' });
            const firstCase = (await linksIn(page, 'table a[href*="/applications/"]'))
                .find((h) => /\/applications\/\d+$/.test(h));
            if (firstCase) {
                await collect(page, locale, role, firstCase, 'case');
                const sub = [...new Set(await linksIn(page, '.main a[href*="/applications/"]'))]
                    .filter((h) => h.startsWith(firstCase) && h !== firstCase);
                for (const url of sub) {
                    const key = `${role}|${url.replace(/\/applications\/\d+/, '/applications/N')}`;
                    if (caseLinksSeen.has(key)) continue;
                    caseLinksSeen.add(key);
                    await collect(page, locale, role, url, 'case-sub');
                }
            }

            if (role === 'applicant') {
                for (const p of ['/apply', '/apply/about-you', '/apply/property', '/apply/possession', '/my-applications']) {
                    await collect(page, locale, role, `${BASE}${p}`, 'apply');
                }
            }
            await signOut(page);
        }

        // Phone width: sign-in page plus the dashboard of an officer.
        await page.setViewport({ width: 390, height: 844, isMobile: true });
        await collect(page, locale, 'mobile', `${BASE}/login`, 'login-mobile');
        await signIn(page, 'do.lhr@etpb.gov.pk', OFFICER_PW);
        await collect(page, locale, 'mobile', `${BASE}/dashboard`, 'dashboard-mobile');
        await collect(page, locale, 'mobile', `${BASE}/applications`, 'applications-mobile');
        await signOut(page);
        await ctx.close();
    }
} finally {
    await browser.close();
}

const sortBucket = (b) => Object.entries(b)
    .sort((a, z) => z[1].count - a[1].count)
    .map(([text, v]) => ({ text, count: v.count, where: [...v.where] }));

const summary = {
    pagesVisited: report.pages.length,
    problems: report.problems,
    latinInUrdu: sortBucket(report.latin),
    urduInEnglish: sortBucket(report.arabicInEn),
    pages: report.pages,
};
fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(summary, null, 2));
console.log(`Visited ${summary.pagesVisited} pages. Problems: ${summary.problems.length}. ` +
    `Latin strings on Urdu pages: ${summary.latinInUrdu.length}. Urdu strings on English pages: ${summary.urduInEnglish.length}.`);
console.log(`Report: ${path.join(OUT, 'report.json')}`);

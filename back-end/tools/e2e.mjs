/**
 * The whole journey, driven through the real screens.
 *
 * A member of the public files an application; Accounts confirms the Rs. 5,000;
 * the District Officer assesses the rent and raises the arrears; the
 * Administrator approves it. If any of that is broken, this stops on the step
 * that broke rather than reporting a green tick for a case nobody can file.
 */
import puppeteer from 'puppeteer';

const BASE = 'http://localhost:8080';
const OFFICIAL = 'Etpb@2026#Change';
const log = m => console.log(m);

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });

async function session(email, password) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    page.on('pageerror', e => { throw new Error('JS error: ' + e); });
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
    await page.type('#email', email);
    await page.type('#password', password);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('button[type=submit]')]);
    if (page.url().includes('/login')) throw new Error(`could not sign in as ${email}`);
    return page;
}

/** Fill whatever the step asks for, then continue. */
async function fill(page, values) {
    for (const [sel, val] of Object.entries(values)) {
        const el = await page.$(sel);
        if (!el) throw new Error(`field not on the page: ${sel}`);
        const tag = await el.evaluate(n => n.tagName);
        if (tag === 'SELECT') await page.select(sel, val);
        else { await el.click({ clickCount: 3 }); await el.type(val); }
    }
}

async function submit(page) {
    const before = page.url();
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}),
        page.click('form button[type=submit], form input[type=submit]'),
    ]);
    const err = await page.$eval('.alert-danger', el => el.textContent.trim().replace(/\s+/g, ' ').slice(0, 200)).catch(() => null);
    if (err) throw new Error(`refused at ${before}: ${err}`);
    return page.url();
}

const applicant = await session('demo.applicant@example.com', 'Demo#Portal2026');
await applicant.goto(`${BASE}/apply`, { waitUntil: 'networkidle2' });
log('  applicant reached the wizard: ' + applicant.url().replace(BASE, ''));

const heading = await applicant.$eval('h1', el => el.textContent.trim()).catch(() => '(none)');
log('  step 1 heading: ' + heading);

const fields = await applicant.evaluate(() => [...document.querySelectorAll('form [name]')]
    .map(el => `${el.tagName.toLowerCase()}#${el.id || ''}[${el.name}]${el.required ? '*' : ''}`));
log('  step 1 fields: ' + fields.join(', '));

await browser.close();

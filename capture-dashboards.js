import puppeteer from 'puppeteer-core';
import path from 'path';

const CHROME_PATH = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT_DIR = '/Users/user/.gemini/antigravity/brain/a047eadc-1ba4-484b-9ce9-227185c70d73';

async function capture() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const roles = [
        { name: 'admin', email: 'admin@cargotrace.test' },
        { name: 'customer', email: 'customer@cargotrace.test' }
    ];

    for (const role of roles) {
        console.log(`Logging in as ${role.name}...`);
        const context = await browser.createBrowserContext();
        const page = await context.newPage();

        await page.goto('http://cargotrace.test/login', { waitUntil: 'networkidle2' });
        await page.type('#email', role.email);
        await page.type('#password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]')
        ]);

        console.log(`Current URL for ${role.name}: ${page.url()}`);

        // Wait for WebGL globe / canvas and charts to settle
        await new Promise(r => setTimeout(r, 3000));

        // 1440px desktop
        await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 1 });
        await new Promise(r => setTimeout(r, 1000));
        const desktopPath = path.join(OUT_DIR, `dashboard-${role.name}-1440.png`);
        await page.screenshot({ path: desktopPath, fullPage: true });
        console.log(`Saved ${desktopPath}`);

        // 390px mobile
        await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
        await new Promise(r => setTimeout(r, 1000));
        const mobilePath = path.join(OUT_DIR, `dashboard-${role.name}-390.png`);
        await page.screenshot({ path: mobilePath, fullPage: true });
        console.log(`Saved ${mobilePath}`);

        await context.close();
    }

    // Also capture welcome landing page
    const welcomePage = await browser.newPage();
    await welcomePage.setViewport({ width: 1440, height: 900, deviceScaleFactor: 1 });
    await welcomePage.goto('http://cargotrace.test/', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 2000));
    const welcomeDesktopPath = path.join(OUT_DIR, `landing-desktop-1440.png`);
    await welcomePage.screenshot({ path: welcomeDesktopPath, fullPage: true });
    console.log(`Saved ${welcomeDesktopPath}`);

    await browser.close();
    console.log('Capture finished successfully!');
}

capture().catch(err => {
    console.error('Capture failed:', err);
    process.exit(1);
});


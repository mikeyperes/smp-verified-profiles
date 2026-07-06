const fs = require('fs');
const path = require('path');
const puppeteer = require('/root/codex-browser/node_modules/puppeteer-core');

const WP_USER = process.env.WP_USER;
const WP_PASS = process.env.WP_PASS;
const CHROME = process.env.CHROME || '/root/.cache/ms-playwright/chromium-1217/chrome-linux64/chrome';
const ADMIN_URL = 'https://mashviral.com/hexa-admin/';
const EDIT_URL = 'https://mashviral.com/wp-admin/post.php?post=560671&action=edit';
const ARTIFACT_DIR = '/root/codex-browser/artifacts/mashviral-verified-profiles';

if (!WP_USER || !WP_PASS) {
  console.error('WP_USER and WP_PASS are required.');
  process.exit(2);
}

function visibleTextIncludes(text, needle) {
  return text.toLowerCase().includes(String(needle).toLowerCase());
}

(async () => {
  fs.mkdirSync(ARTIFACT_DIR, { recursive: true });

  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: 'new',
    userDataDir: `/root/codex-browser/.tmp-mashviral-founder-${Date.now()}`,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1400 });

  await page.goto(ADMIN_URL, { waitUntil: 'networkidle2', timeout: 60000 });

  const loginField = await page.$('#user_login');
  if (loginField) {
    await page.type('#user_login', WP_USER);
    await page.type('#user_pass', WP_PASS);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }),
      page.click('#wp-submit'),
    ]);
  }

  await page.goto(EDIT_URL, { waitUntil: 'networkidle2', timeout: 60000 });
  await page.waitForSelector('body', { timeout: 60000 });

  const screenshot = path.join(ARTIFACT_DIR, `she-sells-founder-backend-${Date.now()}.png`);
  await page.screenshot({ path: screenshot, fullPage: true });

  const proof = await page.evaluate(() => {
    const text = document.body.innerText || '';
    const fieldLabels = Array.from(document.querySelectorAll('.acf-label label, label, .acf-field .acf-label'))
      .map((node) => node.textContent.replace(/\s+/g, ' ').trim())
      .filter(Boolean);
    const selects = Array.from(document.querySelectorAll('select, .select2-selection__rendered, .acf-input input'))
      .map((node) => node.textContent || node.value || '')
      .map((value) => value.replace(/\s+/g, ' ').trim())
      .filter(Boolean);

    return {
      url: window.location.href,
      title: document.title,
      textSample: text.slice(0, 2000),
      fieldLabels,
      selects,
    };
  });

  const text = proof.textSample + '\n' + proof.fieldLabels.join('\n') + '\n' + proof.selects.join('\n');
  const checks = {
    toolkit_admin_url_reached: proof.url.includes('/wp-admin/post.php') || proof.url.includes('/hexa-admin/post.php'),
    profile_type_visible: visibleTextIncludes(text, 'Profile Type'),
    company_details_visible: visibleTextIncludes(text, 'Company Details'),
    founded_by_visible: visibleTextIncludes(text, 'Founded By'),
    brooke_selected_or_visible: visibleTextIncludes(text, 'Brooke Triplett'),
    old_founded_by_name_hidden: !visibleTextIncludes(text, 'Founded By Name'),
    old_founder_url_hidden: !visibleTextIncludes(text, 'Founder URL'),
    old_founder_schema_hidden: !visibleTextIncludes(text, 'Founder Schema ID'),
  };

  console.log(JSON.stringify({ ...proof, checks, screenshot }, null, 2));

  await browser.close();

  if (!Object.values(checks).every(Boolean)) {
    process.exit(1);
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});

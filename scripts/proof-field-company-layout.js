const fs = require('fs');
const path = require('path');
const puppeteer = require('/root/codex-browser/node_modules/puppeteer-core');

const WP_USER = process.env.WP_USER;
const WP_PASS = process.env.WP_PASS;
const CHROME = process.env.CHROME || '/root/.cache/ms-playwright/chromium-1217/chrome-linux64/chrome';
const ADMIN_URL = 'https://mashviral.com/hexa-admin/';
const EDIT_URL = process.env.EDIT_URL || 'https://mashviral.com/wp-admin/post.php?post=560686&action=edit';
const ARTIFACT_DIR = '/root/codex-browser/artifacts/mashviral-verified-profiles';

if (!WP_USER || !WP_PASS) {
  console.error('WP_USER and WP_PASS are required.');
  process.exit(2);
}

(async () => {
  fs.mkdirSync(ARTIFACT_DIR, { recursive: true });

  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: 'new',
    userDataDir: `/root/codex-browser/.tmp-mashviral-acf-layout-${Date.now()}`,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1600, height: 1600 });

  await page.goto(ADMIN_URL, { waitUntil: 'networkidle2', timeout: 60000 });
  if (await page.$('#user_login')) {
    await page.type('#user_login', WP_USER);
    await page.type('#user_pass', WP_PASS);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }),
      page.click('#wp-submit'),
    ]);
  }

  await page.goto(EDIT_URL, { waitUntil: 'networkidle2', timeout: 60000 });
  await page.waitForSelector('body', { timeout: 60000 });

  const screenshot = path.join(ARTIFACT_DIR, `acf-company-layout-${Date.now()}.png`);
  await page.screenshot({ path: screenshot, fullPage: true });

  const proof = await page.evaluate(() => {
    const bodyText = document.body.innerText || '';
    const fields = Array.from(document.querySelectorAll('.acf-field')).map((field) => {
      const labelNode = field.querySelector(':scope > .acf-label label') || field.querySelector(':scope > .acf-label');
      const label = labelNode?.textContent?.replace(/\s+/g, ' ').trim() || '';
      const text = field.textContent.replace(/\s+/g, ' ').trim();
      return {
        label,
        text,
        dataName: field.getAttribute('data-name') || '',
        dataKey: field.getAttribute('data-key') || '',
        dataWidth: field.getAttribute('data-width') || field.style.width || '',
      };
    });

    const indexOfText = (needle) => bodyText.indexOf(needle);
    const findField = (name) => fields.find((field) => field.dataName === name || field.label === name || field.text.startsWith(name));
    const fieldIndex = (name) => fields.findIndex((field) => field.dataName === name || field.label === name);
    const companyNames = ['organization_name', 'legal_name', 'founder_profile', 'founding_date', 'number_of_employees'];
    const companyFields = Object.fromEntries(companyNames.map((name) => [name, findField(name)]));

    return {
      url: window.location.href,
      title: document.title,
      selectedCompanyText: bodyText.includes('PersonCompany') && bodyText.includes('Company'),
      profileTypeIndex: indexOfText('Profile Type'),
      featuredIndex: indexOfText('Featured'),
      profileTypeFieldIndex: fieldIndex('profile_type'),
      featuredFieldIndex: fieldIndex('featured'),
      companyDetailsVisible: bodyText.includes('Company Details'),
      personalEducationVisible: bodyText.includes('Personal Education'),
      shortcodeInstructions: {
        profileType: bodyText.includes('[verified_profile field="profile_type"]'),
        companyDetails: bodyText.includes('[verified_profile field="company_details"]'),
        companyName: bodyText.includes('[verified_profile field="company_details.organization_name"]'),
        founder: bodyText.includes('[verified_profile field="company_details.founder_profile" output="link"]'),
      },
      oldFounderFieldsHidden: !bodyText.includes('Founded By Name') && !bodyText.includes('Founder URL') && !bodyText.includes('Founder Schema ID'),
      companyFields,
    };
  });

  const companyWidthOk = Object.values(proof.companyFields).every((field) => field && String(field.dataWidth) === '100');
  const checks = {
    toolkit_admin_url_reached: proof.url.includes('/wp-admin/post.php') || proof.url.includes('/hexa-admin/post.php'),
    profile_type_before_featured: proof.profileTypeFieldIndex >= 0 && proof.featuredFieldIndex >= 0 && proof.profileTypeFieldIndex < proof.featuredFieldIndex,
    company_details_visible: proof.companyDetailsVisible,
    personal_education_hidden_for_company: !proof.personalEducationVisible,
    company_fields_one_per_row: companyWidthOk,
    shortcode_instructions_visible: Object.values(proof.shortcodeInstructions).every(Boolean),
    old_founder_fields_hidden: proof.oldFounderFieldsHidden,
  };

  console.log(JSON.stringify({ ...proof, companyWidthOk, checks }, null, 2));

  await browser.close();

  if (!Object.values(checks).every(Boolean)) {
    process.exit(1);
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});

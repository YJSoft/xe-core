/* Run against a disposable installed XE site. Requires Puppeteer Core + Chromium. */
const assert = require('node:assert/strict');
const http = require('node:http');
const https = require('node:https');
const fs = require('node:fs');
const puppeteer = require('puppeteer-core');
const base = (process.env.XE_TEST_URL || 'http://127.0.0.1:8080/').replace(/\/?$/, '/');
let checks = 0;
function check(value, message) { assert.ok(value, message); checks++; }
function request(url, headers = {}, body = null) {
  return new Promise((resolve, reject) => {
    const transport = new URL(url).protocol === 'https:' ? https : http;
    const ca = process.env.XE_TEST_CA ? fs.readFileSync(process.env.XE_TEST_CA) : undefined;
    const req = transport.request(url, {method: body === null ? 'GET' : 'POST', headers, ca}, res => {
      let data = '';
      res.setEncoding('utf8');
      res.on('data', chunk => { data += chunk; });
      res.on('end', () => resolve({status: res.statusCode, headers: res.headers, body: data}));
    });
    req.setTimeout(15000, () => req.destroy(new Error('HTTP timeout')));
    req.on('error', reject);
    req.end(body);
  });
}
(async () => {
  const attacker = http.createServer((req, res) => {
    const url = new URL(req.url, 'http://localhost');
    const action = url.searchParams.get('action') || 'dispMemberLogout';
    const target = base + '?module=' + (action === 'procAdminLogout' ? 'admin' : 'member') + '&act=' + action;
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.setHeader('Referrer-Policy', 'no-referrer');
    if (url.pathname === '/image') res.end('<img src="' + target + '">');
    else if (url.pathname === '/post') res.end('<form action="' + target + '" method="post"><button id="attack">submit</button></form>');
    else res.end('<a id="attack" href="' + target + '">logout</a>');
  });
  await new Promise(resolve => attacker.listen(0, '127.0.0.1', resolve));
  const attackBase = 'http://127.0.0.1:' + attacker.address().port;
  let browser;
  try {
    browser = await puppeteer.launch({executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium', headless: true, acceptInsecureCerts: !!process.env.XE_TEST_CA, args: ['--disable-dev-shm-usage']});
    const page = await browser.newPage();
    const dialogs = [];
    page.on('dialog', async dialog => { dialogs.push(dialog.message()); await dialog.dismiss(); });
    for (const target of ['javascript:alert("XE_SECURITY_REGRESSION")', attackBase + '/redirect-target']) {
      await page.goto(base + '?act=dispMemberLoginForm&xeVirtualRequestMethod=xml&xeRequestURI=' + encodeURIComponent(target), {waitUntil: 'networkidle0'});
      check(new URL(page.url()).origin === new URL(base).origin, 'VirtualXML must stay within site');
      check(dialogs.length === 0, 'VirtualXML must not execute injected JavaScript');
    }
    const referer = await request(base + '?act=dispMemberLoginForm', {Referer: base + '?x="><script>alert(1)</script>'});
    check(!referer.body.includes('<script>alert(1)</script>'), 'Referer must not inject markup into login form');
    async function login() {
      await page.goto(base + '?act=dispMemberLoginForm', {waitUntil: 'networkidle0'});
      await page.type('#fo_member_login [name="user_id"]', process.env.XE_TEST_USER || 'admin@admin.net');
      await page.type('#fo_member_login [name="password"]', process.env.XE_TEST_PASSWORD || 'admin1@3');
      await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('#fo_member_login input[type="submit"], #fo_member_login button')]);
    }
    async function isAdmin() {
      await page.goto(base + '?module=admin', {waitUntil: 'networkidle0'});
      return !!(await page.$('#gnbNav'));
    }
    await login();
    check(await isAdmin(), 'Normal login and admin access');
    async function api(act, values) {
      return page.evaluate(async (act, values) => {
        const params = new URLSearchParams(Object.assign({module: 'module', act}, values));
        const res = await fetch(window.location.origin + '/index.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: params.toString()});
        return res.json();
      }, act, values);
    }
    const langName = 'security_browser_' + Date.now();
    const langValue = 'A&B <img src=x onerror=alert(1)> &lt;literal&gt;';
    try {
      const saved = await api('procModuleAdminInsertLang', {lang_name: langName, ko: langValue, en: '0'});
      check(saved.error === 0 && saved.name === langName, 'Real DB language save');
      const loaded = await api('getModuleAdminLangCode', {name: langName});
      check(loaded.langs.ko === langValue, 'Language editing returns original plain text');
      await api('procModuleAdminInsertLang', {lang_name: langName, ko: loaded.langs.ko, en: '0'});
      const reloaded = await api('getModuleAdminLangCode', {name: langName});
      check(reloaded.langs.ko === langValue, 'Language edit/save round trip does not double-encode');
      const list = await api('getModuleAdminLangListHtml', {name: langName, lang_code: 'ko'});
      const preview = await page.evaluate(html => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const span = doc.querySelector('.set span');
        return {text: span && span.textContent, executableImage: !!doc.querySelector('.set span img')};
      }, list.html);
      check(preview.text === langValue && !preview.executableImage, 'Language search preview renders text without executable markup');
    } finally {
      await api('procModuleAdminDeleteLang', {name: langName});
    }
    for (const action of ['dispMemberLogout', 'procAdminLogout']) {
      await page.goto(attackBase + '/image?action=' + action, {waitUntil: 'networkidle0'});
      check(await isAdmin(), 'Foreign-port image cannot log out: ' + action);
      await page.goto(attackBase + '/link?action=' + action, {waitUntil: 'networkidle0'});
      await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('#attack')]);
      check(!!(await page.$('input[name="logout_token"]')), 'Foreign navigation asks for confirmation: ' + action);
      check(await isAdmin(), 'Foreign navigation preserves session: ' + action);
    }
    await page.goto(attackBase + '/post?action=procMemberLogout', {waitUntil: 'networkidle0'});
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('#attack')]);
    check(await isAdmin(), 'Foreign POST cannot call member logout directly');
    const cookie = (await page.cookies(base)).map(item => item.name + '=' + item.value).join('; ');
    const confirmation = await request(base + '?act=dispMemberLogout', {Cookie: cookie});
    const match = confirmation.body.match(/name="logout_token" value="([a-f0-9]{64})"/);
    check(!!match, 'Headerless legacy client receives confirmation token');
    check(confirmation.headers['x-frame-options'] === 'SAMEORIGIN', 'Confirmation resists framing');
    check(await isAdmin(), 'Headerless GET preserves session');
    const body = new URLSearchParams({module: 'member', act: 'dispMemberLogout', logout_token: match[1]}).toString();
    await request(base + '?act=dispMemberLogout', {Cookie: cookie, 'Content-Type': 'application/x-www-form-urlencoded', 'Content-Length': Buffer.byteLength(body)}, body);
    check(!(await isAdmin()), 'Confirmed legacy POST logs out');
    await login();
    await page.goto(base + '?act=dispMemberLogout', {waitUntil: 'networkidle0'});
    check(!(await isAdmin()), 'Normal direct logout navigation still works');
    if (process.env.XE_TEST_HTTP_URL) {
      check(new URL(base).protocol === 'https:', 'Mixed SSL test requires HTTPS target');
      await page.goto(process.env.XE_TEST_HTTP_URL.replace(/\/?$/, '/') + '?act=dispMemberLoginForm', {waitUntil: 'networkidle0'});
      await page.type('#fo_member_login [name="user_id"]', process.env.XE_TEST_USER || 'admin@admin.net');
      await page.type('#fo_member_login [name="password"]', process.env.XE_TEST_PASSWORD || 'admin1@3');
      await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.$eval('#fo_member_login', (form, target) => { form.action = target; form.submit(); }, base + '?act=procMemberLogin')]);
      check(await isAdmin(), 'Actual HTTP-to-HTTPS form login succeeds');
      await page.goto(base + '?act=dispMemberLogout', {waitUntil: 'networkidle0'});
    }
    console.log('OK: ' + checks + ' browser/HTTP security checks');
  } finally {
    if (browser) await browser.close();
    await new Promise(resolve => attacker.close(resolve));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });

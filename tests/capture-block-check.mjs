/**
 * Guest email capture on the block checkout.
 *
 * capture.js used to look for the email field once at load and hang its
 * listeners and the consent box on that element. The block checkout renders
 * later and re-renders at will, and the box sat inside the field wrapper where
 * the floating label is drawn over it. This loads the real script in a browser
 * against a page that renders the block markup late, then re-renders it, and
 * asserts: no send without consent, the box survives a re-render with its
 * state and sits outside the wrapper, and the send carries email, nonce and
 * consent.
 *
 * Run: node tests/capture-block-check.mjs
 * Needs Playwright: set PLAYWRIGHT_MODULE to its path if it is not resolvable,
 * and BROWSER_PATH to a Chromium-based browser (default: Brave).
 */
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const script = readFileSync(new URL('../assets/js/capture.js', import.meta.url), 'utf8');

const field = '<div class="wc-block-components-text-input"><input type="email" id="email" autocomplete="email"><label for="email">Email address</label></div>';

const browser = await chromium.launch({
	executablePath: process.env.BROWSER_PATH || '/Applications/Brave Browser.app/Contents/MacOS/Brave Browser',
	headless: true,
});

try {
	const page = await browser.newPage();
	await page.setContent('<div class="wp-block-woocommerce-checkout"><div id="contact"></div><input id="other"></div>');
	await page.evaluate(() => {
		window.sent = [];
		window.fetch = (url, init) => {
			window.sent.push(init.body);
			return Promise.resolve();
		};
		window.RecoverCapture = { ajaxUrl: '/ajax', action: 'recover_capture_email', nonce: 'n0nce', requireConsent: true, consentLabel: 'Email me a link' };
	});
	await page.addScriptTag({ content: script });

	// The block checkout renders after the script has run.
	await page.evaluate((html) => { document.getElementById('contact').innerHTML = html; }, field);
	await page.waitForSelector('.recover-consent');
	assert.equal(
		await page.evaluate(() => document.querySelector('.recover-consent').previousElementSibling.className),
		'wc-block-components-text-input',
		'consent box must sit after the field wrapper, not inside it',
	);

	await page.fill('#email', 'guest@example.com');
	await page.click('#other');
	assert.deepEqual(await page.evaluate(() => window.sent), [], 'sent without consent');

	await page.check('.recover-consent-input');

	// A re-render replaces the contact step; the box must come back ticked.
	await page.evaluate((html) => { document.getElementById('contact').innerHTML = html; }, field);
	await page.waitForFunction(() => document.querySelector('.recover-consent')?.previousElementSibling?.classList.contains('wc-block-components-text-input'));
	assert.equal(await page.isChecked('.recover-consent-input'), true, 'consent state lost on re-render');
	assert.equal(await page.locator('.recover-consent').count(), 1, 'consent box duplicated');

	await page.fill('#email', 'later@example.com');
	await page.click('#other');
	const sent = await page.evaluate(() => window.sent);
	const last = new URLSearchParams(sent[sent.length - 1] || '');
	assert.equal(last.get('email'), 'later@example.com', 'email typed into the re-rendered field was not sent');
	assert.equal(last.get('nonce'), 'n0nce');
	assert.equal(last.get('consent'), '1');
	assert.equal(last.get('action'), 'recover_capture_email');

	// Another plugin's checkbox as the consent: no box of Recover's own, sends only while it is ticked.
	const ext = await browser.newPage();
	await ext.setContent('<div class="wp-block-woocommerce-checkout"><div id="contact"></div><input id="other"></div>');
	await ext.evaluate(() => {
		window.sent = [];
		window.fetch = (url, init) => {
			window.sent.push(init.body);
			return Promise.resolve();
		};
		window.RecoverCapture = { ajaxUrl: '/ajax', action: 'recover_capture_email', nonce: 'n0nce', requireConsent: true, consentLabel: 'x', consentSelector: '#marketing' };
	});
	await ext.addScriptTag({ content: script });
	await ext.evaluate((html) => { document.getElementById('contact').innerHTML = html + '<input type="checkbox" id="marketing">'; }, field);
	await ext.fill('#email', 'guest@example.com');
	await ext.click('#other');
	assert.equal(await ext.locator('.recover-consent').count(), 0, 'own box drawn although another checkbox is the consent');
	assert.deepEqual(await ext.evaluate(() => window.sent), [], 'sent before the other checkbox was ticked');
	await ext.check('#marketing');
	const extSent = await ext.evaluate(() => window.sent);
	assert.equal(new URLSearchParams(extSent[extSent.length - 1] || '').get('email'), 'guest@example.com', 'ticking the other checkbox did not send the email');

	const bad = await browser.newPage();
	await bad.setContent('<div class="wp-block-woocommerce-checkout"><div id="contact"></div><input id="other"></div>');
	await bad.evaluate(() => {
		window.sent = [];
		window.fetch = (url, init) => { window.sent.push(init.body); return Promise.resolve(); };
		window.RecoverCapture = { ajaxUrl: '/ajax', action: 'recover_capture_email', nonce: 'n0nce', requireConsent: true, consentLabel: 'x', consentSelector: '##broken[' };
	});
	const errors = [];
	bad.on('pageerror', (e) => errors.push(e.message));
	await bad.addScriptTag({ content: script });
	await bad.evaluate((html) => { document.getElementById('contact').innerHTML = html; }, field);
	await bad.fill('#email', 'guest@example.com');
	await bad.click('#other');
	assert.deepEqual(errors, [], 'a mistyped selector threw');
	assert.deepEqual(await bad.evaluate(() => window.sent), [], 'a mistyped selector counted as consent');
} finally {
	await browser.close();
}

console.log('OK: block checkout capture survives late render and re-render, consent required, external consent checkbox honoured');

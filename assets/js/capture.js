/**
 * Recover — early checkout email capture.
 *
 * Listens to the WooCommerce checkout billing-email field and, once a valid
 * address is entered (and consent given, if required), posts it to the server so
 * an abandoned cart can be tied to a contactable address. No jQuery; vanilla
 * fetch, loaded `defer` in the footer. Fails silently — never blocks checkout.
 */
(function () {
	'use strict';

	if (typeof window.RecoverCapture === 'undefined') {
		return;
	}

	var cfg = window.RecoverCapture;
	var lastSent = '';
	var consentEl = null;

	function getEmailField() {
		return (
			document.getElementById('billing_email') ||
			document.querySelector('input[name="billing_email"]') ||
			document.querySelector('#email, input[type="email"]')
		);
	}

	function isValidEmail(value) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
	}

	function injectConsent(emailField) {
		if (!cfg.requireConsent || consentEl || !emailField || !emailField.parentNode) {
			return;
		}
		var wrap = document.createElement('p');
		wrap.className = 'recover-consent';
		wrap.style.margin = '8px 0 0';

		var label = document.createElement('label');
		label.style.display = 'flex';
		label.style.gap = '8px';
		label.style.alignItems = 'flex-start';
		label.style.fontSize = '13px';

		consentEl = document.createElement('input');
		consentEl.type = 'checkbox';
		consentEl.className = 'recover-consent-input';

		var span = document.createElement('span');
		span.textContent = cfg.consentLabel;

		label.appendChild(consentEl);
		label.appendChild(span);
		wrap.appendChild(label);
		emailField.parentNode.appendChild(wrap);
	}

	function maybeSend(emailField) {
		var email = (emailField.value || '').trim();
		if (!isValidEmail(email) || email === lastSent) {
			return;
		}
		if (cfg.requireConsent && (!consentEl || !consentEl.checked)) {
			return;
		}
		lastSent = email;

		var body = new URLSearchParams();
		body.append('action', cfg.action);
		body.append('nonce', cfg.nonce);
		body.append('email', email);
		body.append('consent', cfg.requireConsent ? (consentEl && consentEl.checked ? '1' : '0') : '1');

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		}).catch(function () {
			lastSent = '';
		});
	}

	function init() {
		var emailField = getEmailField();
		if (!emailField) {
			return;
		}
		injectConsent(emailField);

		emailField.addEventListener('blur', function () {
			maybeSend(emailField);
		});
		if (consentEl) {
			consentEl.addEventListener('change', function () {
				lastSent = '';
				maybeSend(emailField);
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

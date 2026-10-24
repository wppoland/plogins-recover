/**
 * Recover, early checkout email capture.
 *
 * Listens to the checkout email field (classic billing_email or the block
 * checkout's #email) and, once a valid address is entered and consent given if
 * required, posts it to the server so an abandoned cart can be tied to a
 * contactable address. No jQuery; vanilla fetch, loaded `defer` in the footer.
 * Fails silently, never blocks checkout.
 *
 * The block checkout can render after this script runs and re-renders its
 * fields at will, so nothing here holds on to a field: the listeners are
 * delegated from the document, and the consent box is put back (with its
 * state) whenever a re-render drops it.
 */
(function () {
	'use strict';

	if (typeof window.RecoverCapture === 'undefined') {
		return;
	}

	var cfg = window.RecoverCapture;
	var EMAIL_SELECTOR = '#billing_email, input[name="billing_email"], .wp-block-woocommerce-checkout input#email';
	var lastSent = '';
	var consentGiven = false;
	var consentWrap = null;
	var scheduled = false;

	function getEmailField() {
		return document.querySelector(EMAIL_SELECTOR);
	}

	function isValidEmail(value) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
	}

	// Outside the field's own wrapper: the block checkout floats its label over
	// that wrapper, so a box inside it was drawn on top of the label.
	function anchorFor(emailField) {
		return emailField.closest('.wc-block-components-text-input, .form-row') || emailField;
	}

	function buildConsent() {
		var wrap = document.createElement('p');
		wrap.className = 'recover-consent';
		wrap.style.margin = '8px 0 0';

		var label = document.createElement('label');
		label.style.display = 'flex';
		label.style.gap = '8px';
		label.style.alignItems = 'flex-start';
		label.style.fontSize = '13px';

		var input = document.createElement('input');
		input.type = 'checkbox';
		input.className = 'recover-consent-input';

		var span = document.createElement('span');
		span.textContent = cfg.consentLabel;

		label.appendChild(input);
		label.appendChild(span);
		wrap.appendChild(label);

		return wrap;
	}

	function ensureConsent() {
		scheduled = false;
		var emailField = getEmailField();
		if (!emailField) {
			return;
		}
		var anchor = anchorFor(emailField);
		if (consentWrap && consentWrap.isConnected && consentWrap.previousElementSibling === anchor) {
			return;
		}
		if (!consentWrap) {
			consentWrap = buildConsent();
		}
		consentWrap.querySelector('input').checked = consentGiven;
		anchor.insertAdjacentElement('afterend', consentWrap);
	}

	function scheduleEnsure() {
		if (!scheduled) {
			scheduled = true;
			window.requestAnimationFrame(ensureConsent);
		}
	}

	function maybeSend() {
		var emailField = getEmailField();
		var email = emailField ? (emailField.value || '').trim() : '';
		if (!isValidEmail(email) || email === lastSent) {
			return;
		}
		if (cfg.requireConsent && !consentGiven) {
			return;
		}
		lastSent = email;

		var body = new URLSearchParams();
		body.append('action', cfg.action);
		body.append('nonce', cfg.nonce);
		body.append('email', email);
		body.append('consent', cfg.requireConsent ? (consentGiven ? '1' : '0') : '1');

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		}).catch(function () {
			lastSent = '';
		});
	}

	function onFieldEvent(event) {
		var target = event.target;
		if (!target || !target.matches) {
			return;
		}
		if (event.type === 'change' && target.matches('.recover-consent-input')) {
			consentGiven = target.checked;
			lastSent = '';
			maybeSend();
			return;
		}
		if (target.matches(EMAIL_SELECTOR)) {
			maybeSend();
		}
	}

	document.addEventListener('focusout', onFieldEvent);
	document.addEventListener('change', onFieldEvent);

	if (cfg.requireConsent) {
		new window.MutationObserver(scheduleEnsure).observe(document.documentElement, {
			childList: true,
			subtree: true,
		});
		scheduleEnsure();
	}
})();

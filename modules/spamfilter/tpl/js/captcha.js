// Adapted 2026-11-27 for XE from Rhymix spamfilter's recaptcha.js and turnstile.js.
// Original work by Kijin Sung and Lastorder. SPDX-License-Identifier: GPL-2.0-or-later
(function ($) {
	'use strict';
	var scripts = {}, widgets = [];
	var providers = {
		recaptcha: ['grecaptcha', 'https://www.google.com/recaptcha/api.js'],
		turnstile: ['turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js']
	};

	function load(provider) {
		if (!scripts[provider]) {
			var ready = scripts[provider] = $.Deferred();
			var api = providers[provider][0], callback = 'xeCaptchaReady_' + provider;
			if (window[api] && window[api].render) {
				ready.resolve(window[api]);
			} else {
				window[callback] = function () { ready.resolve(window[api]); };
				var script = document.createElement('script');
				script.src = providers[provider][1] + '?render=explicit&onload=' + callback;
				script.async = true;
				script.onerror = function () { delete scripts[provider]; ready.reject(); };
				document.head.appendChild(script);
			}
		}
		return scripts[provider].promise();
	}

	function render(form, position, provider, key) {
		return load(provider).then(function (api) {
			if (!$.contains(form, position)) return;
			var id = api.render(position, {
				sitekey: key,
				'expired-callback': function () { api.reset(id); }
			});
			widgets.push({api: api, id: id, element: position});
		});
	}

	function actionOf(form) {
		var act = $(form).find('[name=act]').val();
		if (act) return act;
		var filter = form.getAttribute('onsubmit') || '';
		var match = /procFilter\s*\(\s*this\s*,\s*(?:window\.)?(insert|insert_document|insert_comment|insert_issue|insert_history)\s*\)/i.exec(filter);
		if (!match) return '';
		return {insert: 'procBoardInsertDocument', insert_document: 'procBoardInsertDocument',
			insert_comment: 'procBoardInsertComment', insert_issue: 'procIssuetrackerInsertIssue',
			insert_history: 'procIssuetrackerInsertHistory'}[match[1].toLowerCase()];
	}

	$(function () {
		var config = document.getElementById('xe-captcha-config');
		if (config) {
			var targets = config.getAttribute('data-actions').toLowerCase().split(',');
			function scan() {
				$('form').each(function () {
					if (this.getAttribute('data-xe-captcha-loaded')) return;
					var place = this.querySelector('[data-xe-captcha-action]');
					var act = place ? place.getAttribute('data-xe-captcha-action') : actionOf(this);
					if ($.inArray(act.toLowerCase(), targets) < 0) return;
					if (!place) {
						place = document.createElement('div');
						var button = this.querySelector('[type=submit]');
						if (button) button.parentNode.insertBefore(place, button);
						else this.appendChild(place);
					}
					this.setAttribute('data-xe-captcha-loaded', '1');
					// Keep provider tokens out of URLs on older account recovery skins.
					if (this.method.toLowerCase() === 'get') this.method = 'post';
					render(this, place, config.getAttribute('data-provider'), config.getAttribute('data-sitekey')).fail(function () {
						place.setAttribute('role', 'alert');
						place.textContent = config.getAttribute('data-failed');
					});
				});
			}
			scan();
			if (window.MutationObserver) {
				new MutationObserver(scan).observe(document.body, {subtree: true, childList: true});
			}
		}

		// XE's legacy XML filter serializes the form before ajaxSend. A new attempt needs a fresh token.
		$(document).ajaxSend(function (event, xhr, options) {
			if (typeof options.data !== 'string') return;
			$.each(widgets, function (_, widget) {
				if (!document.documentElement.contains(widget.element)) return;
				var token = widget.api.getResponse(widget.id);
				if (token && (options.data.indexOf(token) >= 0 || options.data.indexOf(encodeURIComponent(token)) >= 0)) {
					widget.api.reset(widget.id);
				}
			});
		});
	});
})(jQuery);

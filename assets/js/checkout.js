/**
 * Subscribe — checkout opt-in.
 *
 * Presentation only: reflects the checkbox state onto the row so the postmark
 * can land when the shopper franks their consent. No behaviour, no submission
 * logic — WooCommerce owns the form. Degrades cleanly with JS off (the box and
 * the inked checkmark still work; only the postmark flourish is skipped).
 */
(function () {
	'use strict';

	function sync(row, input) {
		row.classList.toggle('is-subscribed', input.checked);
	}

	function init() {
		var rows = document.querySelectorAll('.subscribe-optin');

		Array.prototype.forEach.call(rows, function (row) {
			var input = row.querySelector('.subscribe-optin__input');

			if (!input) {
				return;
			}

			// Reflect the initial (possibly pre-checked) state without animating.
			if (input.checked) {
				row.classList.add('is-subscribed');
			}

			input.addEventListener('change', function () {
				sync(row, input);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

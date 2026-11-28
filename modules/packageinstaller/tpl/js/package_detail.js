/* Easy Install button interaction adapted from Rhymix. See COPYRIGHT. */
jQuery(function($) {
	var detail = $('.autoinstall_package_detail');
	var panel = detail.find('.pkg-state');
	var feedback = detail.find('.pkg-feedback');
	var recheck = detail.find('.pkg-recheck');
	var busy = false;

	function start(message) {
		busy = true;
		panel.attr('aria-busy', 'true').find(':submit').prop('disabled', true);
		recheck.prop('hidden', true);
		feedback.removeClass('error').text(message);
	}

	function finish(html, message, failed) {
		busy = false;
		panel.removeAttr('aria-busy');
		if (html) {
			panel.html(html);
		} else {
			// A lost response may follow a successful write. Only retry the read.
			message = (message ? message + ' ' : '') + feedback.attr('data-uncertain');
			recheck.prop('hidden', false);
		}
		feedback.toggleClass('error', failed || !html).text(message);
	}

	detail.on('submit', '.pkg-action', function(event) {
		event.preventDefault();
		if (busy || !recheck.prop('hidden')) return;
		var form = $(this);
		var data = form.serialize();
		start(feedback.attr('data-working'));
		$.ajax({
			url: window.request_uri || form.attr('action'),
			type: 'POST',
			contentType: 'application/json',
			dataType: 'json',
			data: data
		}).done(function(result) {
			if (result && typeof result === 'object') {
				finish(result.panel, result.message, Number(result.error) !== 0);
			} else {
				finish(null, '', true);
			}
		}).fail(function() {
			finish(null, '', true);
		});
	});

	recheck.on('click', function() {
		if (busy) return;
		start(feedback.attr('data-checking'));
		$.ajax({ url: window.location.href, dataType: 'html', cache: false }).done(function(html) {
			var state = $('<div>').append($.parseHTML(html, document, false)).find('.pkg-state');
			finish(state.length ? state.html() : null, '', false);
		}).fail(function() {
			finish(null, '', true);
		});
	});
});

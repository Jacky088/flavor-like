(function ($) {
	'use strict';

	if (typeof flavorLikePulse === 'undefined') {
		return;
	}

	var pollTimer = null;
	var browserActive = false;

	function post(action, extra) {
		return $.post(flavorLikePulse.ajaxUrl, $.extend({
			action: 'flavor_like_pulse_sync_action',
			nonce: flavorLikePulse.nonce,
			pulse_action: action
		}, extra || {}));
	}

	function fetchStatus() {
		return $.post(flavorLikePulse.ajaxUrl, {
			action: 'flavor_like_pulse_sync_status',
			nonce: flavorLikePulse.nonce
		});
	}

	function log(msg) {
		$('#flavor-like-pulse-log').text(msg || '');
	}

	function redirectAfterAction(res) {
		var url = (res && res.data && res.data.redirect) ? res.data.redirect : flavorLikePulse.redirectUrl;
		if (url) {
			window.location.href = url;
			return;
		}
		window.location.reload();
	}

	function syncComplete(data) {
		return !!(data && (data.sync_complete || data.migration_status === 'done' || (data.progress && data.progress.complete)));
	}

	function formatProgressText(progress, complete) {
		var imported = parseInt(progress.total_imported, 10) || 0;
		var skipped = parseInt(progress.total_skipped, 10) || 0;
		var percent = parseFloat(progress.percent_estimate);
		var strings = flavorLikePulse.strings || {};
		var text;

		if (complete) {
			if (skipped > 0) {
				text = (strings.progressCompleteSkipped || '%1$s rows copied (%2$s skipped) · complete')
					.replace('%1$s', imported.toLocaleString())
					.replace('%2$s', skipped.toLocaleString());
			} else {
				text = (strings.progressComplete || '%1$s rows copied · complete')
					.replace('%1$s', imported.toLocaleString());
			}
			return { text: text, percent: 100 };
		}

		if (skipped > 0) {
			text = (strings.progressCopiedSkipped || '%1$s rows copied (%2$s skipped)')
				.replace('%1$s', imported.toLocaleString())
				.replace('%2$s', skipped.toLocaleString());
		} else if (imported > 0) {
			text = (strings.progressCopied || '%1$s rows copied')
				.replace('%1$s', imported.toLocaleString());
		} else {
			text = strings.progressWaiting || 'Waiting to start…';
		}

		if (!isNaN(percent) && percent > 0 && percent < 100) {
			text += (strings.progressEstimated || ' · ~%s%% estimated')
				.replace('%s', percent.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 1 }));
		}

		return {
			text: text,
			percent: complete ? 100 : (isNaN(percent) ? 0 : Math.min(100, percent))
		};
	}

	function updateUi(data) {
		if (!data || !data.progress) {
			return;
		}

		var progress = data.progress;
		var complete = syncComplete(data);
		var running = data.migration_status === 'running' && !complete;
		var statusLabel = data.status_label || data.migration_status || 'idle';
		var display = data.progress_label
			? { text: data.progress_label, percent: complete ? 100 : (parseFloat(progress.percent_estimate) || 0) }
			: formatProgressText(progress, complete);

		$('#flavor-like-pulse-sync-status').text(statusLabel);
		$('#flavor-like-pulse-progress-text').text(display.text);
		$('#flavor-like-pulse-progress-bar').css('width', Math.min(100, display.percent) + '%');

		$('#flavor-like-pulse-start').prop('disabled', running || complete);
		$('#flavor-like-pulse-pause').prop('disabled', !running);

		if (complete && !data.is_pulse) {
			$('#flavor-like-pulse-start').hide();
			$('#flavor-like-pulse-pause').hide();
			$('#flavor-like-pulse-enable').prop('disabled', false).addClass('button-primary');
			$('#flavor-like-pulse-next-step').show();
			browserActive = false;
			stopPolling();
		}
	}

	function pollStatus() {
		return fetchStatus().done(function (res) {
			if (res.success && res.data) {
				updateUi(res.data);

				if (syncComplete(res.data) && !res.data.is_pulse) {
					log(flavorLikePulse.strings.syncComplete);
				}
			}
		});
	}

	function startPolling() {
		stopPolling();
		pollTimer = setInterval(pollStatus, 5000);
		return pollStatus();
	}

	function stopPolling() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	function runBatch() {
		if (!browserActive) {
			return;
		}

		post('batch').done(function (res) {
			if (!res.success || !res.data) {
				browserActive = false;
				return;
			}

			updateUi({
				migration_status: res.data.migration_status || 'running',
				sync_complete: !!res.data.done,
				status_label: res.data.done ? 'Complete' : 'Moving records…',
				progress: res.data.progress || {}
			});

			if (res.data.done) {
				browserActive = false;
				log(flavorLikePulse.strings.syncComplete);
				pollStatus();
				return;
			}

			setTimeout(runBatch, 800);
		});
	}

	function showActionError(msg) {
		log(msg || flavorLikePulse.strings.actionFailed || 'Request failed.');
	}

	$('#flavor-like-pulse-start').on('click', function () {
		post('start').done(function (res) {
			if (!res || !res.success) {
				showActionError();
				return;
			}

			browserActive = true;
			log(flavorLikePulse.strings.started);
			$('#flavor-like-pulse-start').prop('disabled', true);
			$('#flavor-like-pulse-pause').prop('disabled', false).show();
			startPolling();
			runBatch();
		}).fail(function () {
			showActionError();
		});
	});

	$('#flavor-like-pulse-pause').on('click', function () {
		browserActive = false;
		post('pause').done(function (res) {
			if (!res || !res.success) {
				showActionError();
				return;
			}

			stopPolling();
			pollStatus();
			log('');
		}).fail(function () {
			showActionError();
		});
	});

	$('#flavor-like-pulse-enable').on('click', function () {
		if (!window.confirm(flavorLikePulse.confirmEnable)) {
			return;
		}
		post('enable').done(function (res) {
			if (!res || !res.success) {
				var reason = res && res.data ? res.data.reason : '';
				if (reason === 'verify_failed') {
					log(flavorLikePulse.strings.enableVerifyFailed || flavorLikePulse.strings.enableFailed);
				} else if (reason === 'sync_incomplete') {
					log(flavorLikePulse.strings.enableSyncIncomplete || flavorLikePulse.strings.enableFailed);
				} else {
					log(flavorLikePulse.strings.enableFailed);
				}
				return;
			}
			window.location.reload();
		}).fail(function () {
			log(flavorLikePulse.strings.enableFailed);
		});
	});

	$('#flavor-like-pulse-dismiss').on('click', function () {
		post('dismiss').done(function (res) {
			log(flavorLikePulse.strings.dismissed);
			redirectAfterAction(res);
		}).fail(function () {
			showActionError();
		});
	});

	$('#flavor-like-pulse-drop-legacy').on('click', function () {
		if (!window.confirm(flavorLikePulse.confirmDrop)) {
			return;
		}
		post('drop_legacy').done(function (res) {
			if (!res || !res.success) {
				log(flavorLikePulse.strings.dropFailed);
				return;
			}
			log(flavorLikePulse.strings.dropped);
			redirectAfterAction(res);
		}).fail(function () {
			log(flavorLikePulse.strings.dropFailed);
		});
	});

	if (!flavorLikePulse.isPulse && flavorLikePulse.syncComplete) {
		log(flavorLikePulse.strings.syncComplete);
	} else if (flavorLikePulse.isRunning) {
		browserActive = true;
		startPolling();
		runBatch();
	}
})(jQuery);

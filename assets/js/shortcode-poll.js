(function () {
	'use strict';

	function formatDate(createdAt) {
		if (!createdAt) return '';
		try {
			var d = new Date(createdAt.replace(/\s/, 'T'));
			return isNaN(d.getTime()) ? createdAt : d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
		} catch (e) { return createdAt; }
	}

	function getItemIconClass(source) {
		if (source === 'woo') return 'dashicons-cart';
		if (source === 'admin') return 'dashicons-megaphone';
		return 'dashicons-bell';
	}

	function renderItem(item) {
		var title = (item.payload && item.payload.title) ? item.payload.title : '';
		var message = (item.payload && item.payload.message) ? item.payload.message : '';
		var date = formatDate(item.created_at);
		var url = (item.payload && item.payload.url) ? item.payload.url : '';
		var source = item.source || '';
		var isRead = item.is_read === 1 || item.is_read === '1';
		var isReadClass = isRead ? ' is-read' : '';
		var toggleLabel = isRead ? 'Mark as unread' : 'Mark as read';
		var toggleIcon = isRead ? 'dashicons-hidden' : 'dashicons-visibility';
		var toggleReadClass = isRead ? ' 4wp-notifications__toggle--read' : '';
		var linkHtml = url ? '<a class="4wp-notifications__link" href="' + escapeHtml(url) + '" aria-label="Go to page"><span class="4wp-notifications__link-icon dashicons dashicons-external" aria-hidden="true"></span></a>' : '';
		var toggleHtml = '<button type="button" class="4wp-notifications__toggle' + toggleReadClass + ' forwp-js-toggle" data-id="' + item.id + '" data-is-read="' + (isRead ? '1' : '0') + '" aria-label="' + escapeHtml(toggleLabel) + '"><span class="dashicons ' + toggleIcon + '" aria-hidden="true"></span></button>';
		var iconClass = getItemIconClass(source);
		return (
			'<li class="4wp-notifications__item' + isReadClass + '" data-id="' + item.id + '">' +
			'<span class="4wp-notifications__item-icon" aria-hidden="true"><span class="dashicons ' + iconClass + '"></span></span>' +
			'<div class="4wp-notifications__content">' +
			(title ? '<span class="4wp-notifications__title">' + escapeHtml(title) + '</span>' : '') +
			(message ? '<p class="4wp-notifications__message">' + escapeHtml(message) + '</p>' : '') +
			'<span class="4wp-notifications__date">' + escapeHtml(date) + '</span>' +
			linkHtml +
			'</div>' + toggleHtml + '</li>'
		);
	}

	function escapeHtml(str) {
		if (!str) return '';
		var div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	function renderList(items, emptyText) {
		if (!items || !items.length) {
			var text = (emptyText && String(emptyText).trim()) || 'No notifications.';
			return '<p class="4wp-notifications__empty">' + escapeHtml(text) + '</p>';
		}
		var html = '<ul class="4wp-notifications__list">';
		for (var i = 0; i < items.length; i++) {
			html += renderItem(items[i]);
		}
		html += '</ul>';
		return html;
	}

	function poll(container) {
		var restUrl = container.getAttribute('data-forwp-rest-url');
		var nonce = container.getAttribute('data-forwp-nonce');
		if (!restUrl || !nonce) return;

		var wrapper = container;

		function doFetch() {
			fetch(restUrl + '/notifications?per_page=20&page=1', {
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': nonce },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data.items && Array.isArray(data.items)) {
						var emptyText = container.getAttribute('data-forwp-empty-text') || '';
						wrapper.innerHTML = renderList(data.items, emptyText);
						bindToggle(container, restUrl, nonce);
					}
				})
				.catch(function () {});
		}

		var interval = parseInt(container.getAttribute('data-forwp-poll-interval'), 10) || 30000;
		bindToggle(container, restUrl, nonce);
		doFetch();
		setInterval(doFetch, interval);
	}

	function bindToggle(container, restUrl, nonce) {
		container.querySelectorAll('.forwp-js-toggle').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var id = this.getAttribute('data-id');
				var isRead = this.getAttribute('data-is-read') === '1';
				if (!id) return;
				var nextRead = !isRead;
				var self = this;
				var li = self.closest('.4wp-notifications__item');
				fetch(restUrl + '/notifications/' + id, {
					method: 'PATCH',
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
					body: JSON.stringify({ is_read: nextRead })
				}).then(function (res) {
					if (res.ok && li) {
						if (nextRead) {
							li.classList.add('is-read');
							self.setAttribute('data-is-read', '1');
							self.setAttribute('aria-label', 'Mark as unread');
							self.classList.add('4wp-notifications__toggle--read');
							var icon = self.querySelector('.dashicons');
							if (icon) icon.className = 'dashicons dashicons-hidden';
						} else {
							li.classList.remove('is-read');
							self.setAttribute('data-is-read', '0');
							self.setAttribute('aria-label', 'Mark as read');
							self.classList.remove('4wp-notifications__toggle--read');
							var icon = self.querySelector('.dashicons');
							if (icon) icon.className = 'dashicons dashicons-visibility';
						}
						document.dispatchEvent(new CustomEvent('forwp-notifications-updated'));
					}
				});
			});
		});
	}

	function refetchPage(container) {
		var restUrl = container.getAttribute('data-forwp-rest-url');
		var nonce = container.getAttribute('data-forwp-nonce');
		if (!restUrl || !nonce) return;
		fetch(restUrl + '/notifications?per_page=20&page=1', {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce },
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.items && Array.isArray(data.items)) {
					var emptyText = container.getAttribute('data-forwp-empty-text') || '';
					container.innerHTML = renderList(data.items, emptyText);
					bindToggle(container, restUrl, nonce);
				}
			})
			.catch(function () {});
	}

	function init() {
		document.querySelectorAll('.4wp-notifications[data-forwp-poll="1"]').forEach(poll);
	}

	document.addEventListener('forwp-notifications-updated', function () {
		document.querySelectorAll('.4wp-notifications[data-forwp-poll="1"]').forEach(refetchPage);
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

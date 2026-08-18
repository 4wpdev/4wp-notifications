(function () {
	'use strict';

	var DELETE_ALL_SEL 	= '.forwp-notifications-bell__delete-all',
		MARK_READ_SEL 	= '.forwp-notifications-bell__mark-all',
		TOGGLE_SEL		= '.forwp-js-toggle',
		DELETE_SEL		= '.forwp-js-delete';

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
		var deleteLabel = 'Delete';
		var deleteIcon = 'dashicons-trash';
		var toggleReadClass = isRead ? ' forwp-notifications__toggle--read' : '';
		var linkHtml = url ? '<a class="forwp-notifications__link" href="' + escapeHtml(url) + '" aria-label="Go to page"><span class="forwp-notifications__link-icon dashicons dashicons-external" aria-hidden="true"></span></a>' : '';
		var toggleHtml = '<button type="button" class="forwp-notifications__toggle' + toggleReadClass + ' forwp-js-toggle" data-id="' + item.id + '" data-is-read="' + (isRead ? '1' : '0') + '" aria-label="' + escapeHtml(toggleLabel) + '"><span class="dashicons ' + toggleIcon + '" aria-hidden="true"></span></button>';
		var iconClass = getItemIconClass(source);
		var deleteHtml = '<button type="button" class="forwp-notifications__delete forwp-js-delete" data-id="' + item.id + '" aria-label="' + escapeHtml(deleteLabel) + '"><span class="dashicons ' + deleteIcon + '" aria-hidden="true"></span></button>';
		return (
			'<li class="forwp-notifications__item' + isReadClass + '" data-id="' + item.id + '">' +
			'<span class="forwp-notifications__item-icon" aria-hidden="true"><span class="dashicons ' + iconClass + '"></span></span>' +
			'<div class="forwp-notifications__content">' +
			(title ? '<span class="forwp-notifications__title">' + escapeHtml(title) + '</span>' : '') +
			(message ? '<p class="forwp-notifications__message">' + escapeHtml(message) + '</p>' : '') +
			'<span class="forwp-notifications__date">' + escapeHtml(date) + '</span>' +
			linkHtml +
			'</div>' + toggleHtml + deleteHtml + '</li>'
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
			return '<p class="forwp-notifications__empty">' + escapeHtml(text) + '</p>';
		}
		var html = `<div class="forwp-button-group">
						<button type="button" class="forwp-notifications-bell__mark-all forwp-button-full">Mark all as read</button>
						<button type="button" class="forwp-notifications-bell__delete-all forwp-button-full">Delete All</button>
					</div>`;
		html += '<ul class="forwp-notifications__list">';
		for (var i = 0; i < items.length; i++) {
			html += renderItem(items[i]);
		}
		html += '</ul>';
		return html;
	}

	function bindEvents(container, restUrl, nonce) {
		if (container.hasAttribute('data-forwp-bound')) return;
		container.setAttribute('data-forwp-bound', '1');

		container.addEventListener('click', function (e) {
			var toggleBtn = e.target.closest(TOGGLE_SEL);
			if (toggleBtn) {
				handleToggle(toggleBtn, restUrl, nonce, e);
				return;
			}

			var deleteBtn = e.target.closest(DELETE_SEL);
			if (deleteBtn) {
				handleDelete(deleteBtn, restUrl, nonce, e);
				return;
			}

			var markAllBtn = e.target.closest(MARK_READ_SEL);
			if (markAllBtn) {
				handleMarkAll(restUrl, nonce, e);
				return;
			}

			var deleteAllBtn = e.target.closest(DELETE_ALL_SEL);
			if (deleteAllBtn) {
				handleDeleteAll(container, restUrl, nonce, e);
				return;
			}
		});
	}

	function handleToggle(btn, restUrl, nonce, e) {
		e.preventDefault();
		var id = btn.getAttribute('data-id');
		var isRead = btn.getAttribute('data-is-read') === '1';
		if (!id) return;
		var nextRead = !isRead;
		var li = btn.closest('.forwp-notifications__item');
		fetch(restUrl + '/notifications/' + id, {
			method: 'PATCH',
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
			body: JSON.stringify({ is_read: nextRead })
		}).then(function (res) {
			if (res.ok && li) {
				if (nextRead) {
					li.classList.add('is-read');
					btn.setAttribute('data-is-read', '1');
					btn.setAttribute('aria-label', 'Mark as unread');
					btn.classList.add('forwp-notifications__toggle--read');
					var icon = btn.querySelector('.dashicons');
					if (icon) icon.className = 'dashicons dashicons-hidden';
				} else {
					li.classList.remove('is-read');
					btn.setAttribute('data-is-read', '0');
					btn.setAttribute('aria-label', 'Mark as read');
					btn.classList.remove('forwp-notifications__toggle--read');
					var icon = btn.querySelector('.dashicons');
					if (icon) icon.className = 'dashicons dashicons-visibility';
				}
				document.dispatchEvent(new CustomEvent('forwp-notifications-updated'));
			}
		});
	}

	function handleDelete(btn, restUrl, nonce, e) {
		e.preventDefault();
		var id = btn.getAttribute('data-id');
		if (!id) return;
		var li = btn.closest('.forwp-notifications__item');
		fetch(restUrl + '/notifications/' + id, {
			method: 'DELETE',
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce },
		}).then(function (res) {
			if (res.ok && li) {
				li.parentNode.removeChild(li);
				document.dispatchEvent(new CustomEvent('forwp-notifications-updated'));
			}
		});
	}

	function handleMarkAll(restUrl, nonce, e) {
		e.preventDefault();
		e.stopPropagation();
		fetch(restUrl + '/notifications/mark-all-read', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }
		}).then(function (res) {
			if (res.ok) {
				document.dispatchEvent(new CustomEvent('forwp-notifications-updated'));
			}
		});
	}

	function handleDeleteAll(container, restUrl, nonce, e) {
		e.preventDefault();
		e.stopPropagation();

		var list = container.querySelector('.forwp-notifications__list');
		if (list) list.classList.add('disabled');

		fetch(restUrl + '/notifications/delete', {
			method: 'DELETE',
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce }
		}).then(function (res) {
			if (res.ok) {
				document.dispatchEvent(new CustomEvent('forwp-notifications-updated'));
			}
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
				}
			})
			.catch(function () {});
	}

	function poll(container) {
		var restUrl = container.getAttribute('data-forwp-rest-url');
		var nonce = container.getAttribute('data-forwp-nonce');
		if (!restUrl || !nonce) return;

		bindEvents(container, restUrl, nonce);

		var interval = parseInt(container.getAttribute('data-forwp-poll-interval'), 10) || 30000;
		refetchPage(container);
		setInterval(function () { refetchPage(container); }, interval);
	}

	function init() {
		document.querySelectorAll('.forwp-notifications[data-forwp-poll="1"]').forEach(poll);
	}

	document.addEventListener('forwp-notifications-updated', function () {
		document.querySelectorAll('.forwp-notifications[data-forwp-poll="1"]').forEach(refetchPage);
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

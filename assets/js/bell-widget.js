(function () {
	'use strict';

	var globalI18n = window.forwpNotificationsBellI18n || {};
	function getI18n(wrapper) {
		if (!wrapper) return globalI18n;
		var raw = wrapper.getAttribute('data-forwp-i18n');
		if (!raw) return globalI18n;
		try {
			return Object.assign({}, globalI18n, JSON.parse(raw));
		} catch (e) { return globalI18n; }
	}
	var LIST_SEL = '.forwp-notifications-bell__list';
	var LIST_EMPTY_SEL = '.forwp-notifications-bell__list-empty';
	var ITEM_SEL = '.forwp-notifications-bell__item';
	var ITEM_UNREAD_CLASS = 'forwp-notifications-bell__item--unread';
	var ITEM_CLASS = 'forwp-notifications-bell__item';
	var BADGE_SEL = '.forwp-notifications-bell__badge';
	var MARK_ALL_SEL = '.forwp-notifications-bell__mark-all';
	var DROPDOWN_ACTIVE_CLASS = 'forwp-notifications-bell__dropdown--active';
	var POLL_INTERVAL_MS = 30000;

	function bindDropdownToggle(wrapper) {
		var btn = wrapper.querySelector('.forwp-notifications-bell__button');
		var dropdown = wrapper.querySelector('.forwp-notifications-bell__dropdown');
		if (!btn || !dropdown) return;

		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var isOpen = dropdown.classList.contains(DROPDOWN_ACTIVE_CLASS);
			if (isOpen) {
				dropdown.classList.remove(DROPDOWN_ACTIVE_CLASS);
				btn.setAttribute('aria-expanded', 'false');
			} else {
				dropdown.classList.add(DROPDOWN_ACTIVE_CLASS);
				btn.setAttribute('aria-expanded', 'true');
			}
		});

		dropdown.addEventListener('click', function (e) {
			e.stopPropagation();
		});

		document.addEventListener('click', function (e) {
			if (!wrapper.contains(e.target)) {
				dropdown.classList.remove(DROPDOWN_ACTIVE_CLASS);
				btn.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function escapeHtml(str) {
		if (!str) return '';
		var div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	function formatTimeAgo(createdAt, i18n) {
		i18n = i18n || globalI18n;
		if (!createdAt) return '';
		try {
			var d = new Date(createdAt.replace(/\s/, 'T'));
			if (isNaN(d.getTime())) return createdAt;
			var now = new Date();
			var s = Math.floor((now - d) / 1000);
			if (s < 60) return i18n.justNow || 'just now';
			if (s < 3600) return Math.floor(s / 60) + ' ' + (i18n.minAgo || 'min ago');
			if (s < 86400) return Math.floor(s / 3600) + ' ' + (i18n.hrAgo || 'hr ago');
			return Math.floor(s / 86400) + ' ' + (i18n.dAgo || 'd ago');
		} catch (e) { return createdAt; }
	}

	var TOGGLE_SEL = '.forwp-notifications-bell__item-toggle';

	function getItemIconClass(source) {
		if (source === 'woo') return 'dashicons-cart';
		if (source === 'admin') return 'dashicons-megaphone';
		return 'dashicons-bell';
	}

	function renderItem(item, i18n) {
		i18n = i18n || globalI18n;
		var payload = item.payload || {};
		var source = item.source || '';
		var defaultTitle = i18n.notification || 'Notification';
		var title = payload.title || defaultTitle;
		var message = payload.message || '';
		var url = payload.url || '#';
		var isRead = item.is_read === 1 || item.is_read === '1';
		var timeAgo = formatTimeAgo(item.created_at, i18n);
		var unreadClass = isRead ? '' : ' ' + ITEM_UNREAD_CLASS;
		var itemClass = ITEM_CLASS + unreadClass;
		var msgHtml = message ? '<p class="forwp-notifications-bell__item-text">' + escapeHtml(message) + '</p>' : '';
		var timeHtml = timeAgo ? '<time class="forwp-notifications-bell__item-time">' + escapeHtml(timeAgo) + '</time>' : '';
		var toggleLabel = isRead ? (i18n.markUnread || 'Mark as unread') : (i18n.markRead || 'Mark as read');
		var toggleIcon = isRead ? 'dashicons-hidden' : 'dashicons-visibility';
		var toggleReadClass = isRead ? ' forwp-notifications-bell__item-toggle--read' : '';
		var toggleBtn = '<button type="button" class="forwp-notifications-bell__item-toggle' + toggleReadClass + '" data-id="' + item.id + '" data-is-read="' + (isRead ? '1' : '0') + '" aria-label="' + escapeHtml(toggleLabel) + '"><span class="dashicons ' + toggleIcon + '" aria-hidden="true"></span></button>';
		var iconClass = getItemIconClass(source);
		var hasLink = url && url !== '#';
		var goToPageLabel = i18n.goToPage || 'Go to page';
		var linkIconHtml = hasLink ? '<span class="forwp-notifications-bell__item-link-icon" aria-label="' + escapeHtml(goToPageLabel) + '"><span class="dashicons dashicons-external"></span></span>' : '';
		return (
			'<a href="' + escapeHtml(url) + '" class="' + itemClass + '" data-id="' + item.id + '">' +
			'<span class="forwp-notifications-bell__item-icon"><span class="dashicons ' + iconClass + '" style="font-size:20px;width:20px;height:20px;" aria-hidden="true"></span></span>' +
			'<div class="forwp-notifications-bell__item-content">' +
			'<h4 class="forwp-notifications-bell__item-title">' + escapeHtml(title) + '</h4>' + msgHtml + timeHtml + linkIconHtml +
			'</div>' + toggleBtn + '</a>'
		);
	}

	function renderList(items, i18n) {
		if (!items || !items.length) return '';
		var html = '';
		for (var i = 0; i < items.length; i++) {
			html += renderItem(items[i], i18n);
		}
		return html;
	}

	function setBadge(wrapper, count) {
		var btn = wrapper.querySelector('.forwp-notifications-bell__button');
		if (!btn) return;
		var badge = btn.querySelector(BADGE_SEL);
		if (!badge) return;
		if (count > 0) {
			badge.textContent = count > 99 ? '99+' : count;
			badge.style.display = 'flex';
		} else {
			badge.style.display = 'none';
		}
	}

	function getEmptyHtml(wrapper, i18n) {
		i18n = i18n || (wrapper ? getI18n(wrapper) : globalI18n);
		var text = i18n.empty || 'No new notifications';
		return '<div class="forwp-notifications-bell__list-empty"><p>' + escapeHtml(text) + '</p></div>';
	}

	function setListAndEmpty(wrapper, items) {
		var listEl = wrapper.querySelector(LIST_SEL);
		if (!listEl) return;
		var i18n = getI18n(wrapper);
		var listHtml = renderList(items, i18n);
		var emptyHtml = getEmptyHtml(wrapper, i18n);
		if (listHtml) {
			listEl.innerHTML = listHtml + emptyHtml;
			var emptyEl = listEl.querySelector(LIST_EMPTY_SEL);
			if (emptyEl) emptyEl.style.display = 'none';
		} else {
			listEl.innerHTML = emptyHtml;
		}
	}

	function fetchNotifications(wrapper, restUrl, nonce, then) {
		fetch(restUrl + '/notifications?per_page=20&page=1', {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce }
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.items && Array.isArray(data.items)) {
					setListAndEmpty(wrapper, data.items);
					setBadge(wrapper, data.unread_count != null ? data.unread_count : 0);
					if (then) then();
				}
			})
			.catch(function () {});
	}

	function bindMarkAllRead(wrapper, restUrl, nonce) {
		var dropdown = wrapper.querySelector('.forwp-notifications-bell__dropdown');
		if (!dropdown) return;
		var btn = dropdown.querySelector(MARK_ALL_SEL);
		if (!btn) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			fetch(restUrl + '/notifications/mark-all-read', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }
			}).then(function (res) {
				if (res.ok) {
					fetchNotifications(wrapper, restUrl, nonce, function () {
						bindItemToggles(wrapper, restUrl, nonce);
					});
					document.dispatchEvent(new CustomEvent('forwp-notifications-updated'));
				}
			});
		});
	}

	function bindItemToggles(wrapper, restUrl, nonce) {
		var listEl = wrapper.querySelector(LIST_SEL);
		if (!listEl) return;
		var i18n = getI18n(wrapper);
		listEl.querySelectorAll(TOGGLE_SEL).forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var id = this.getAttribute('data-id');
				var isRead = this.getAttribute('data-is-read') === '1';
				if (!id) return;
				var nextRead = !isRead;
				var self = this;
				var itemRow = this.closest(ITEM_SEL);
				fetch(restUrl + '/notifications/' + id, {
					method: 'PATCH',
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
					body: JSON.stringify({ is_read: nextRead })
				}).then(function (res) {
					if (res.ok && itemRow) {
						if (nextRead) {
							itemRow.classList.remove(ITEM_UNREAD_CLASS);
							self.setAttribute('data-is-read', '1');
							self.setAttribute('aria-label', i18n.markUnread || 'Mark as unread');
							self.classList.add('forwp-notifications-bell__item-toggle--read');
							var icon = self.querySelector('.dashicons');
							if (icon) { icon.className = 'dashicons dashicons-hidden'; }
							var n = parseInt(wrapper.querySelector(BADGE_SEL).textContent, 10) || 0;
							setBadge(wrapper, Math.max(0, n - 1));
						} else {
							itemRow.classList.add(ITEM_UNREAD_CLASS);
							self.setAttribute('data-is-read', '0');
							self.setAttribute('aria-label', i18n.markRead || 'Mark as read');
							self.classList.remove('forwp-notifications-bell__item-toggle--read');
							var icon = self.querySelector('.dashicons');
							if (icon) { icon.className = 'dashicons dashicons-visibility'; }
							var badge = wrapper.querySelector(BADGE_SEL);
							var n = badge ? (parseInt(badge.textContent, 10) || 0) : 0;
							setBadge(wrapper, n + 1);
						}
						document.dispatchEvent(new CustomEvent('forwp-notifications-updated'));
					}
				});
			});
		});
	}

	function initWidget(wrapper) {
		if (wrapper.getAttribute('data-forwp-bell-inited') === '1') return;
		wrapper.setAttribute('data-forwp-bell-inited', '1');
		var restUrl = wrapper.getAttribute('data-forwp-rest-url');
		var nonce = wrapper.getAttribute('data-forwp-nonce');
		if (!restUrl || !nonce) return;
		bindDropdownToggle(wrapper);
		bindMarkAllRead(wrapper, restUrl, nonce);
		bindItemToggles(wrapper, restUrl, nonce);
		fetchNotifications(wrapper, restUrl, nonce, function () {
			bindItemToggles(wrapper, restUrl, nonce);
		});
		setInterval(function () {
			fetch(restUrl + '/notifications/unread-count', {
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': nonce }
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data.unread_count != null) setBadge(wrapper, data.unread_count);
				})
				.catch(function () {});
		}, POLL_INTERVAL_MS);
	}

	function init() {
		document.querySelectorAll('.forwp-notifications-bell[data-forwp-bell="1"]').forEach(initWidget);
	}

	document.addEventListener('forwp-notifications-updated', function () {
		document.querySelectorAll('.forwp-notifications-bell[data-forwp-bell="1"]').forEach(function (wrapper) {
			var restUrl = wrapper.getAttribute('data-forwp-rest-url');
			var nonce = wrapper.getAttribute('data-forwp-nonce');
			if (restUrl && nonce) {
				fetchNotifications(wrapper, restUrl, nonce, function () {
					bindItemToggles(wrapper, restUrl, nonce);
				});
			}
		});
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
	window.addEventListener('load', init);
})();

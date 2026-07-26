(function () {
    'use strict';

    var app = document.querySelector('[data-proxy-app]');
    if (!app) {
        return;
    }

    var page = app.getAttribute('data-proxy-page');
    var endpoint = app.getAttribute('data-endpoint');
    var userToken = app.getAttribute('data-token') || '';
    var configured = app.getAttribute('data-configured') === '1';
    var state = {
        metadata: null,
        records: [],
        selected: new Set(),
        renewType: '',
        quote: null,
        quoteTimer: null
    };

    var $ = function (selector, root) {
        return (root || app).querySelector(selector);
    };
    var $$ = function (selector, root) {
        return Array.prototype.slice.call((root || app).querySelectorAll(selector));
    };

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setStatus(message, type) {
        var status = $('[data-proxy-status]');
        if (!status) {
            return;
        }
        status.hidden = !message;
        status.setAttribute('data-type', type || 'info');
        status.innerHTML = message ? '<i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>' + escapeHtml(message) + '</span>' : '';
    }

    function setButtonLoading(button, loading, loadingText) {
        if (!button) {
            return;
        }
        if (loading) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i> ' + escapeHtml(loadingText || 'Đang xử lý');
        } else if (button.dataset.originalText) {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }

    async function request(action, payload) {
        var body = Object.assign({}, payload || {}, { action: action, token: userToken });
        var response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        var data = await response.json().catch(function () { return { success: false, message: 'Phản hồi máy chủ không hợp lệ.' }; });
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Không thể hoàn tất yêu cầu.');
        }
        return data;
    }

    function formPayload(form) {
        var payload = {};
        new FormData(form).forEach(function (value, key) {
            payload[key] = value;
        });
        payload.auto_extend = form.querySelector('[name="auto_extend"]') && form.querySelector('[name="auto_extend"]').checked ? 1 : 0;
        return payload;
    }

    function moneyLabel(value) {
        return value === null || value === undefined || value === '' ? '--' : String(value);
    }

    function setSelectOptions(select, options, placeholder) {
        if (!select) {
            return;
        }
        var html = '<option value="">' + escapeHtml(placeholder || 'Chọn một tùy chọn') + '</option>';
        (options || []).forEach(function (option) {
            html += '<option value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label) + '</option>';
        });
        select.innerHTML = html;
        select.disabled = !(options && options.length);
    }

    function selectedType() {
        var input = $('[data-proxy-type-input]');
        return input ? input.value : '';
    }

    function setSelectedType(type) {
        var input = $('[data-proxy-type-input]');
        if (input) {
            input.value = type;
        }
        $$('[data-proxy-type]').forEach(function (card) {
            card.classList.toggle('is-selected', card.getAttribute('data-proxy-type') === type);
        });
        if (state.metadata && state.metadata.types && state.metadata.types[type]) {
            var details = state.metadata.types[type];
            setSelectOptions($('[data-country-select]'), details.countries, 'Chọn quốc gia');
            setSelectOptions($('[data-rent-select]'), details.rent_periods, 'Chọn thời hạn');
        }
        var isMobile = type === 'MOBILE';
        $$('[data-mobile-field]').forEach(function (field) {
            field.hidden = !isMobile;
            $$('select', field).forEach(function (select) { select.disabled = !isMobile; });
        });
        var isIpv6 = type === 'IPV6';
        $$('[data-protocol-field]').forEach(function (field) {
            field.hidden = !isIpv6;
            $$('select', field).forEach(function (select) { select.disabled = !isIpv6; });
        });
        scheduleBuyQuote();
    }

    async function loadMetadata() {
        if (!configured) {
            return;
        }
        try {
            var response = await request('metadata');
            state.metadata = response.data;
            var types = state.metadata.types || {};
            var available = Object.keys(types);
            $$('[data-proxy-type]').forEach(function (card) {
                var type = card.getAttribute('data-proxy-type');
                card.disabled = available.indexOf(type) === -1;
                card.hidden = available.indexOf(type) === -1;
            });
            if (!available.length) {
                throw new Error('Dịch vụ proxy chưa trả về loại proxy khả dụng.');
            }
            var current = available.indexOf(selectedType()) !== -1 ? selectedType() : available[0];
            setSelectedType(current);
            var mobileSelect = $('[data-mobile-select]');
            setSelectOptions(mobileSelect, state.metadata.mobile_operators || [], 'Tùy chọn nhà mạng');
            if (mobileSelect) {
                mobileSelect.disabled = current !== 'MOBILE';
            }
            var submit = $('[data-buy-submit]');
            if (submit) {
                submit.disabled = false;
                submit.innerHTML = '<i class="fa-solid fa-lock-open" aria-hidden="true"></i> Mua proxy';
            }
        } catch (error) {
            setStatus(error.message, 'error');
        }
    }

    function updateBuySummary(price, form) {
        var total = $('[data-wallet-total]');
        if (!price) {
            if (total) total.textContent = '--';
            return;
        }
        if (total) total.textContent = moneyLabel(price.wallet_label);
    }

    function scheduleBuyQuote() {
        if (page !== 'buy' || !configured) {
            return;
        }
        window.clearTimeout(state.quoteTimer);
        state.quoteTimer = window.setTimeout(loadBuyQuote, 400);
    }

    async function loadBuyQuote() {
        var form = $('[data-buy-form]');
        if (!form || !configured) {
            return;
        }
        var payload = formPayload(form);
        if (!payload.country || !payload.rent_period_days || !payload.goal) {
            updateBuySummary(null);
            return;
        }
        try {
            var response = await request('quote', payload);
            state.quote = response.data;
            updateBuySummary(state.quote, form);
            setStatus('', 'info');
        } catch (error) {
            state.quote = null;
            updateBuySummary(null);
        }
    }

    function initBuy() {
        var form = $('[data-buy-form]');
        if (!form) {
            return;
        }
        $$('[data-proxy-type]').forEach(function (card) {
            card.addEventListener('click', function () {
                if (!card.disabled) {
                    setSelectedType(card.getAttribute('data-proxy-type'));
                }
            });
        });
        form.addEventListener('change', function (event) {
            if (event.target.name === 'auth_type') {
                var ipField = $('[data-ip-field]');
                var isIp = event.target.value === 'IP';
                if (ipField) ipField.hidden = !isIp;
            }
            scheduleBuyQuote();
        });
        form.addEventListener('input', scheduleBuyQuote);
        var quantity = $('[data-quantity-input]');
        var setQuantity = function (delta) {
            if (!quantity) return;
            var value = Math.max(1, Math.min(100, Number(quantity.value || 1) + delta));
            quantity.value = value;
            scheduleBuyQuote();
        };
        var minus = $('[data-quantity-minus]');
        var plus = $('[data-quantity-plus]');
        if (minus) minus.addEventListener('click', function () { setQuantity(-1); });
        if (plus) plus.addEventListener('click', function () { setQuantity(1); });
        var submit = $('[data-buy-submit]');
        if (submit) {
            submit.addEventListener('click', async function () {
                if (!form.reportValidity() || !state.quote) {
                    setStatus('Vui lòng chọn đủ cấu hình để hệ thống tính giá trước khi mua.', 'error');
                    return;
                }
                setButtonLoading(submit, true, 'Đang tạo đơn');
                try {
                    var response = await request('buy', formPayload(form));
                    setStatus(response.message || 'Mua proxy thành công.', 'success');
                    window.setTimeout(function () { window.location.href = (window.baseUrl || '/') + 'client/proxy-list'; }, 900);
                } catch (error) {
                    setStatus(error.message, 'error');
                    setButtonLoading(submit, false);
                }
            });
        }
        loadMetadata().then(scheduleBuyQuote);
    }

    function dateEnd(record) {
        return record.date_end ? new Date(String(record.date_end).replace(/-/g, '/')) : null;
    }

    function isExpiring(record) {
        var end = dateEnd(record);
        return end && end.getTime() <= Date.now() + (7 * 86400000);
    }

    function recordProxyType(record) {
        return String(record.proxy_type || '').toUpperCase() || 'IPV4';
    }

    function recordConnection(record) {
        var port = record.https_port || record.socks5_port || '';
        var auth = record.login ? ':' + record.login + ':' + record.password : '';
        return record.ip + (port ? ':' + port : '') + auth;
    }

    function recordStatus(record) {
        var end = dateEnd(record);
        if (!end || Number.isNaN(end.getTime())) return { label: 'Đang hoạt động', soon: false };
        if (end.getTime() < Date.now()) return { label: 'Đã hết hạn', soon: true };
        return { label: isExpiring(record) ? 'Sắp hết hạn' : 'Đang hoạt động', soon: isExpiring(record) };
    }

    function renderList(data) {
        state.records = data.records || [];
        var tableWrap = $('[data-proxy-table]');
        if (!tableWrap) return;
        var stats = data.stats || {};
        var total = $('[data-stat-total]');
        var active = $('[data-stat-active]');
        var expiring = $('[data-stat-expiring]');
        var systemStatus = $('[data-stat-system-status]');
        if (total) total.textContent = stats.total || 0;
        if (active) active.textContent = stats.active || 0;
        if (expiring) expiring.textContent = stats.expiring || 0;
        if (systemStatus) systemStatus.textContent = stats.status_label || 'Sẵn sàng';
        var caption = $('[data-list-caption]');
        if (caption) caption.textContent = state.records.length ? state.records.length + ' proxy đang được quản lý.' : 'Chưa có proxy nào trong tài khoản.';
        if (!state.records.length) {
            tableWrap.innerHTML = '<div class="proxy-empty-state"><i class="fa-solid fa-server" aria-hidden="true"></i><strong>Chưa có proxy để hiển thị</strong><small>Hãy mua proxy mới để bắt đầu quản lý tại đây.</small><a class="proxy-secondary-button" href="' + escapeHtml((window.baseUrl || '/') + 'client/proxy-buy') + '"><i class="fa-solid fa-plus" aria-hidden="true"></i> Mua proxy</a></div>';
            return;
        }
        var rows = state.records.map(function (record) {
            var status = recordStatus(record);
            var id = escapeHtml(record.id);
            var connection = recordConnection(record);
            var type = recordProxyType(record);
            return '<tr data-record-id="' + id + '">' +
                '<td data-label="Chọn"><input type="checkbox" class="proxy-record-check" data-record-check="' + id + '"></td>' +
                '<td data-label="Địa chỉ"><div class="proxy-record-meta"><code>' + escapeHtml(record.ip || '--') + '</code><small>' + escapeHtml(type) + ' / ' + escapeHtml(record.country || '--') + '</small></div></td>' +
                '<td data-label="Cổng"><span>HTTPS ' + escapeHtml(record.https_port || '--') + '</span><small>SOCKS5 ' + escapeHtml(record.socks5_port || '--') + '</small></td>' +
                '<td data-label="Đăng nhập"><span>' + escapeHtml(record.login || '--') + '</span><small>' + escapeHtml(record.password || '--') + '</small></td>' +
                '<td data-label="Hạn dùng"><span>' + escapeHtml(record.date_end || '--') + '</span><small class="proxy-badge ' + (status.soon ? 'proxy-badge--soon' : '') + '">' + escapeHtml(status.label) + '</small></td>' +
                '<td data-label="Thao tác"><div class="proxy-record-actions"><button type="button" class="proxy-copy-button" data-copy-connection="' + escapeHtml(connection) + '" aria-label="Sao chép thông tin kết nối" title="Sao chép thông tin kết nối"><i class="fa-regular fa-copy" aria-hidden="true"></i></button></div></td>' +
                '</tr>';
        }).join('');
        tableWrap.innerHTML = '<table class="proxy-table"><thead><tr><th>Chọn</th><th>Địa chỉ</th><th>Cổng</th><th>Đăng nhập</th><th>Hạn dùng</th><th>Thao tác</th></tr></thead><tbody>' + rows + '</tbody></table>';
        $$('[data-record-check]', tableWrap).forEach(function (checkbox) {
            checkbox.checked = state.selected.has(checkbox.getAttribute('data-record-check'));
            checkbox.addEventListener('change', function () {
                var id = checkbox.getAttribute('data-record-check');
                if (checkbox.checked) state.selected.add(id); else state.selected.delete(id);
                updateSelectionButton();
            });
        });
        $$('[data-copy-connection]', tableWrap).forEach(function (button) {
            button.addEventListener('click', function () {
                copyText(button.getAttribute('data-copy-connection'), button);
            });
        });
        updateSelectionButton();
    }

    function updateSelectionButton() {
        var button = $('[data-go-renew]');
        if (button) button.disabled = state.selected.size === 0;
        var selectedCount = $('[data-renew-selected]');
        if (selectedCount) selectedCount.textContent = state.selected.size + ' proxy được chọn';
    }

    function copyText(value, button) {
        var done = function () {
            var original = button.innerHTML;
            button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
            window.setTimeout(function () { button.innerHTML = original; }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(done).catch(function () { fallbackCopy(value, done); });
        } else {
            fallbackCopy(value, done);
        }
    }

    function fallbackCopy(value, done) {
        var input = document.createElement('textarea');
        input.value = value;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
        done();
    }

    async function loadList(type) {
        if (!configured) return;
        try {
            var response = await request('list', type ? { proxy_type: type } : {});
            renderList(response.data);
            if (page === 'renew') renderRenewList();
        } catch (error) {
            setStatus(error.message, 'error');
            var table = $('[data-proxy-table]');
            var renewList = $('[data-renew-list]');
            if (table) table.innerHTML = '<div class="proxy-empty-state"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><strong>Không tải được danh sách</strong><small>' + escapeHtml(error.message) + '</small></div>';
            if (renewList) renewList.innerHTML = '<div class="proxy-empty-state"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><strong>Không tải được danh sách</strong><small>' + escapeHtml(error.message) + '</small></div>';
        }
    }

    function initList() {
        var filter = $('[data-list-type]');
        if (filter) filter.addEventListener('change', function () { state.selected.clear(); loadList(filter.value); });
        $$('[data-refresh-list]').forEach(function (button) { button.addEventListener('click', function () { loadList(filter ? filter.value : ''); }); });
        var renewButton = $('[data-go-renew]');
        if (renewButton) renewButton.addEventListener('click', function () {
            sessionStorage.setItem('proxyRenewSelection', JSON.stringify(Array.from(state.selected)));
            window.location.href = (window.baseUrl || '/') + 'client/proxy-renew';
        });
        loadList(filter ? filter.value : '');
    }

    function selectedRecords() {
        return state.records.filter(function (record) { return state.selected.has(String(record.id)); });
    }

    function renderRenewList() {
        var container = $('[data-renew-list]');
        if (!container) return;
        var filter = $('[data-renew-type]');
        var type = filter ? filter.value : '';
        var records = state.records.filter(function (record) { return !type || recordProxyType(record) === type; });
        if (!records.length) {
            container.innerHTML = '<div class="proxy-empty-state"><i class="fa-solid fa-server" aria-hidden="true"></i><strong>Chưa có proxy để gia hạn</strong><small>Mua proxy trước rồi quay lại trang này.</small></div>';
            return;
        }
        container.innerHTML = records.map(function (record) {
            var id = String(record.id);
            var status = recordStatus(record);
            return '<label class="proxy-renew-item ' + (state.selected.has(id) ? 'is-selected' : '') + '"><input type="checkbox" data-renew-check="' + escapeHtml(id) + '" ' + (state.selected.has(id) ? 'checked' : '') + '><span class="proxy-renew-item-content"><span><strong>' + escapeHtml(record.ip || '--') + '</strong><small>' + escapeHtml(recordProxyType(record) + ' / ' + (record.country || '--')) + '</small></span><span class="proxy-renew-item-date"><span class="proxy-badge ' + (status.soon ? 'proxy-badge--soon' : '') + '">' + escapeHtml(record.date_end || '--') + '</span></span></span></label>';
        }).join('');
        $$('[data-renew-check]', container).forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var id = checkbox.getAttribute('data-renew-check');
                var record = state.records.find(function (item) { return String(item.id) === id; });
                var first = selectedRecords()[0];
                if (checkbox.checked && first && recordProxyType(first) !== recordProxyType(record)) {
                    checkbox.checked = false;
                    setStatus('Một lần gia hạn chỉ áp dụng cho cùng một loại proxy.', 'error');
                    return;
                }
                if (checkbox.checked) state.selected.add(id); else state.selected.delete(id);
                checkbox.closest('.proxy-renew-item').classList.toggle('is-selected', checkbox.checked);
                updateRenewState();
            });
        });
        updateRenewState();
    }

    function setRenewType(type) {
        state.renewType = type;
        var filter = $('[data-renew-type]');
        if (filter) filter.value = type;
        state.selected.forEach(function (id) {
            var record = state.records.find(function (item) { return String(item.id) === id; });
            if (record && recordProxyType(record) !== type) state.selected.delete(id);
        });
        renderRenewList();
        populateRenewRent(type);
    }

    function populateRenewRent(type) {
        var select = $('[data-renew-rent]');
        if (!select || !state.metadata || !state.metadata.types || !state.metadata.types[type]) return;
        setSelectOptions(select, state.metadata.types[type].rent_periods, 'Chọn thời hạn');
    }

    function updateRenewState() {
        var selected = selectedRecords();
        var count = $('[data-renew-selected]');
        var quote = $('[data-renew-quote]');
        var submit = $('[data-renew-submit]');
        var rent = $('[data-renew-rent]');
        if (count) count.textContent = selected.length + ' proxy được chọn';
        var sameType = selected.length && selected.every(function (record) { return recordProxyType(record) === recordProxyType(selected[0]); });
        if (sameType && !state.renewType) setRenewType(recordProxyType(selected[0]));
        if (quote) quote.disabled = !sameType || !rent || !rent.value;
        if (submit) submit.disabled = !state.quote || !sameType;
    }

    async function renewQuote() {
        var selected = selectedRecords();
        var rent = $('[data-renew-rent]');
        if (!selected.length || !rent || !rent.value) {
            setStatus('Chọn ít nhất một proxy và thời hạn gia hạn.', 'error');
            return;
        }
        var button = $('[data-renew-quote]');
        setButtonLoading(button, true, 'Đang tính giá');
        try {
            var response = await request('renew_quote', { proxy_type: recordProxyType(selected[0]), ip_address_ids: selected.map(function (record) { return record.id; }), rent_period_days: rent.value });
            state.quote = response.data;
            var total = $('[data-wallet-total]');
            if (total) total.textContent = moneyLabel(state.quote.wallet_label);
            setStatus('', 'info');
            if ($('[data-renew-submit]')) $('[data-renew-submit]').disabled = false;
        } catch (error) {
            state.quote = null;
            setStatus(error.message, 'error');
        }
        setButtonLoading(button, false);
        updateRenewState();
    }

    async function initRenew() {
        if (!configured) return;
        var filter = $('[data-renew-type]');
        if (filter) filter.addEventListener('change', function () { setRenewType(filter.value); });
        var selectExpiring = $('[data-select-expiring]');
        if (selectExpiring) selectExpiring.addEventListener('click', function () {
            var first = state.records.find(function (record) { return isExpiring(record); });
            if (!first) return;
            setRenewType(recordProxyType(first));
            state.records.filter(function (record) { return recordProxyType(record) === recordProxyType(first) && isExpiring(record); }).forEach(function (record) { state.selected.add(String(record.id)); });
            renderRenewList();
        });
        var quoteButton = $('[data-renew-quote]');
        if (quoteButton) quoteButton.addEventListener('click', renewQuote);
        var submit = $('[data-renew-submit]');
        if (submit) submit.addEventListener('click', async function () {
            if (!state.quote) { await renewQuote(); if (!state.quote) return; }
            var selected = selectedRecords();
            var rent = $('[data-renew-rent]');
            setButtonLoading(submit, true, 'Đang gia hạn');
            try {
                var response = await request('renew', { proxy_type: recordProxyType(selected[0]), ip_address_ids: selected.map(function (record) { return record.id; }), rent_period_days: rent.value });
                var auto = $('[data-renew-auto]');
                var message = response.message || 'Gia hạn proxy thành công.';
                if (auto && auto.checked) {
                    await request('auto_extend', { proxy_type: recordProxyType(selected[0]), ip_address_ids: selected.map(function (record) { return record.id; }), rent_period_days: rent.value, auto_extend: 1 });
                    message += ' Đã bật tự động gia hạn.';
                }
                setStatus(message, 'success');
                state.quote = null;
                state.selected.clear();
                loadList('');
            } catch (error) {
                setStatus(error.message, 'error');
            }
            setButtonLoading(submit, false);
            updateRenewState();
        });
        await loadMetadata();
        var saved = [];
        try { saved = JSON.parse(sessionStorage.getItem('proxyRenewSelection') || '[]'); sessionStorage.removeItem('proxyRenewSelection'); } catch (e) { saved = []; }
        state.selected = new Set(saved.map(String));
        await loadList('');
        var selected = selectedRecords()[0];
        if (selected) setRenewType(recordProxyType(selected));
        updateRenewState();
    }

    if (!configured) {
        setStatus('Dịch vụ proxy chưa sẵn sàng. Vui lòng thử lại sau hoặc liên hệ hỗ trợ.', 'info');
    }
    if (page === 'buy') initBuy();
    if (page === 'list') initList();
    if (page === 'renew') initRenew();
}());

(function () {
    'use strict';

    var app = document.querySelector('[data-proxy-app]');
    if (!app) {
        return;
    }

    var page = app.getAttribute('data-proxy-page');
    var endpoint = app.getAttribute('data-endpoint');
    var userToken = app.getAttribute('data-token') || '';
    var loginUrl = app.getAttribute('data-login-url') || ((window.baseUrl || '/') + 'client/login');
    var authenticated = app.getAttribute('data-authenticated') === '1' || !!userToken;
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

    function redirectToLogin() {
        window.location.href = loginUrl;
    }

    function authRequiredMarkup(message) {
        return '<div class="proxy-empty-state"><i class="fa-solid fa-lock" aria-hidden="true"></i><strong>Vui lòng đăng nhập</strong><small>'
            + escapeHtml(message || 'Đăng nhập để xem dữ liệu tài khoản và tiếp tục thao tác.')
            + '</small><a class="proxy-secondary-button" href="' + escapeHtml(loginUrl) + '"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Đăng nhập</a></div>';
    }

    function renderAuthRequired(message) {
        setStatus('Đăng nhập để sử dụng đầy đủ chức năng proxy.', 'info');
        var table = $('[data-proxy-table]');
        var renewList = $('[data-renew-list]');
        if (table) table.innerHTML = authRequiredMarkup(message);
        if (renewList) renewList.innerHTML = authRequiredMarkup(message);
        $$('[data-download-proxies], [data-go-renew], [data-renew-quote], [data-renew-submit]').forEach(function (button) {
            button.disabled = true;
        });
        var caption = $('[data-list-caption]') || $('[data-renew-caption]');
        if (caption) caption.textContent = 'Cần đăng nhập để tải dữ liệu tài khoản.';
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var alpha3ToAlpha2 = {
        AFG: 'AF', ALB: 'AL', DZA: 'DZ', AND: 'AD', AGO: 'AO', ARG: 'AR', ARM: 'AM', AUS: 'AU', AUT: 'AT', AZE: 'AZ',
        BHR: 'BH', BGD: 'BD', BLR: 'BY', BEL: 'BE', BLZ: 'BZ', BEN: 'BJ', BTN: 'BT', BOL: 'BO', BIH: 'BA', BWA: 'BW', BRA: 'BR', BRN: 'BN',
        BGR: 'BG', BFA: 'BF', BDI: 'BI', KHM: 'KH', CMR: 'CM', CAN: 'CA', CHL: 'CL', CHN: 'CN', COL: 'CO', CRI: 'CR', HRV: 'HR', CYP: 'CY', CZE: 'CZ',
        DNK: 'DK', DJI: 'DJ', DOM: 'DO', ECU: 'EC', EGY: 'EG', SLV: 'SV', EST: 'EE', ETH: 'ET', FIN: 'FI', FRA: 'FR', GEO: 'GE', DEU: 'DE', GHA: 'GH',
        GRC: 'GR', GTM: 'GT', HND: 'HN', HKG: 'HK', HUN: 'HU', ISL: 'IS', IND: 'IN', IDN: 'ID', IRL: 'IE', ISR: 'IL', ITA: 'IT', JAM: 'JM', JPN: 'JP',
        JOR: 'JO', KAZ: 'KZ', KEN: 'KE', KWT: 'KW', LVA: 'LV', LBN: 'LB', LTU: 'LT', LUX: 'LU', MAC: 'MO', MYS: 'MY', MDV: 'MV', MLT: 'MT', MEX: 'MX',
        MDA: 'MD', MNG: 'MN', MNE: 'ME', MAR: 'MA', MOZ: 'MZ', MMR: 'MM', NAM: 'NA', NPL: 'NP', NLD: 'NL', NZL: 'NZ', NIC: 'NI', NGA: 'NG', NOR: 'NO',
        OMN: 'OM', PAK: 'PK', PAN: 'PA', PNG: 'PG', PRY: 'PY', PER: 'PE', PHL: 'PH', POL: 'PL', PRT: 'PT', QAT: 'QA', ROU: 'RO', RUS: 'RU', SAU: 'SA',
        SRB: 'RS', SGP: 'SG', SVK: 'SK', SVN: 'SI', ZAF: 'ZA', KOR: 'KR', ESP: 'ES', LKA: 'LK', SWE: 'SE', CHE: 'CH', TWN: 'TW', THA: 'TH', TUN: 'TN',
        TUR: 'TR', UKR: 'UA', ARE: 'AE', GBR: 'GB', USA: 'US', URY: 'UY', UZB: 'UZ', VEN: 'VE', VNM: 'VN', ZMB: 'ZM', ZWE: 'ZW'
    };

    function countryAlpha2(value) {
        var code = String(value || '').trim().toUpperCase();
        return /^[A-Z]{2}$/.test(code) ? code : (alpha3ToAlpha2[code] || '');
    }

    function countryFlag(value) {
        var region = countryAlpha2(value);
        if (region.length !== 2) {
            return '<i class="fa-solid fa-globe" aria-hidden="true"></i>';
        }
        var source = 'https://flagcdn.com/w40/' + region.toLowerCase() + '.png';
        return '<img data-country-flag-image src="' + escapeHtml(source) + '" alt="" loading="lazy" decoding="async">'
            + '<i class="fa-solid fa-globe" aria-hidden="true" hidden></i>';
    }

    function bindCountryFlagFallbacks(root) {
        $$('[data-country-flag-image]', root).forEach(function (image) {
            image.addEventListener('error', function () {
                image.hidden = true;
                var fallback = image.parentElement.querySelector('i');
                if (fallback) fallback.hidden = false;
            });
        });
    }

    function syncCountrySelection(select) {
        var value = select ? select.value : '';
        $$('[data-country-option]').forEach(function (button) {
            var selected = button.getAttribute('data-value') === value;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
    }

    function renderCountryOptions(select, options) {
        var picker = $('[data-country-options]');
        if (!picker) {
            return;
        }
        if (!options || !options.length) {
            picker.innerHTML = '<span class="proxy-country-empty">No countries available</span>';
            return;
        }
        picker.innerHTML = (options || []).map(function (option) {
            return '<button type="button" class="proxy-country-option" data-country-option data-value="'
                + escapeHtml(option.value) + '" role="option" aria-selected="false">'
                + '<span class="proxy-country-flag" aria-hidden="true">' + countryFlag(option.value) + '</span>'
                + '<span class="proxy-country-name">' + escapeHtml(option.label) + '</span>'
                + '<i class="fa-solid fa-check proxy-country-check" aria-hidden="true"></i></button>';
        }).join('');
        bindCountryFlagFallbacks(picker);
        $$('[data-country-option]', picker).forEach(function (button) {
            button.addEventListener('click', function () {
                select.value = button.getAttribute('data-value');
                syncCountrySelection(select);
                select.dispatchEvent(new Event('change', { bubbles: true }));
                scheduleBuyQuote();
            });
        });
        syncCountrySelection(select);
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
        if (select.matches('[data-country-select]')) {
            renderCountryOptions(select, options);
        }
    }

    function isUsableType(details) {
        return !!details && Array.isArray(details.countries) && details.countries.length > 0
            && Array.isArray(details.rent_periods) && details.rent_periods.length > 0;
    }

    function updateBuyButtonState() {
        var submit = $('[data-buy-submit]');
        if (!submit || submit.dataset.originalText) {
            return;
        }
        submit.disabled = !state.quote;
        submit.innerHTML = state.quote
            ? '<i class="fa-solid fa-lock-open" aria-hidden="true"></i> Mua proxy'
            : '<i class="fa-solid fa-lock" aria-hidden="true"></i> Chọn cấu hình';
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
        state.quote = null;
        updateBuySummary(null);
        updateBuyButtonState();
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
            var available = Object.keys(types).filter(function (type) {
                return isUsableType(types[type]);
            });
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
            updateBuyButtonState();
        } catch (error) {
            var submit = $('[data-buy-submit]');
            if (submit) {
                submit.disabled = true;
            }
            setStatus(error.message, 'error');
        }
    }

    function selectedOptionText(select) {
        if (!select || !select.value || !select.options[select.selectedIndex]) {
            return '';
        }
        return String(select.options[select.selectedIndex].textContent || '').trim();
    }

    function updateBuySummary(price, form) {
        var currentForm = form || $('[data-buy-form]');
        if (currentForm) {
            var country = $('[data-country-select]', currentForm);
            var countryName = $('[data-summary-country]');
            var countryFlagTarget = $('[data-summary-country-flag]');
            var summaryStatus = $('[data-summary-status]');
            var countryLabel = selectedOptionText(country);
            var rent = $('[data-rent-select]', currentForm);
            if (countryName) countryName.textContent = countryLabel || 'Chọn quốc gia';
            if (summaryStatus) summaryStatus.textContent = country && country.value && rent && rent.value ? 'Sẵn sàng cấp proxy' : 'Đang chờ cấu hình';
            if (countryFlagTarget) {
                countryFlagTarget.innerHTML = country && country.value
                    ? countryFlag(country.value)
                    : '<i class="fa-solid fa-globe" aria-hidden="true"></i>';
                bindCountryFlagFallbacks(countryFlagTarget);
            }

            var type = selectedType();
            var typeCard = $$('[data-proxy-type]').find(function (card) {
                return card.getAttribute('data-proxy-type') === type;
            });
            var typeLabel = typeCard && $('strong', typeCard) ? $('strong', typeCard).textContent.trim() : type;
            var rentLabel = selectedOptionText(rent);
            if (rentLabel && !/ngày|day/i.test(rentLabel)) rentLabel += ' ngày';
            var quantity = $('[data-quantity-input]', currentForm);
            var auth = $('[name="auth_type"]:checked', currentForm);
            var summaryType = $('[data-summary-type]');
            var summaryRent = $('[data-summary-rent]');
            var summaryQuantity = $('[data-summary-quantity]');
            var summaryAuth = $('[data-summary-auth]');
            if (summaryType) summaryType.textContent = typeLabel || '--';
            if (summaryRent) summaryRent.textContent = rentLabel || '--';
            if (summaryQuantity) summaryQuantity.textContent = quantity && quantity.value ? quantity.value : '1';
            if (summaryAuth) summaryAuth.textContent = auth && auth.value === 'IP' ? 'IP whitelist' : 'IP:PORT:USER:PASS';
        }
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
            state.quote = null;
            updateBuySummary(null);
            updateBuyButtonState();
            return;
        }
        try {
            var response = await request('quote', payload);
            state.quote = response.data;
            updateBuySummary(state.quote, form);
            updateBuyButtonState();
            setStatus('', 'info');
        } catch (error) {
            state.quote = null;
            updateBuySummary(null);
            updateBuyButtonState();
            setStatus(error.message, 'error');
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
            updateBuySummary(state.quote, form);
            if (event.target.name === 'auth_type') {
                var ipField = $('[data-ip-field]');
                var isIp = event.target.value === 'IP';
                if (ipField) ipField.hidden = !isIp;
            }
            scheduleBuyQuote();
        });
        form.addEventListener('input', function () {
            updateBuySummary(state.quote, form);
            scheduleBuyQuote();
        });
        var quantity = $('[data-quantity-input]');
        var setQuantity = function (delta) {
            if (!quantity) return;
            var value = Math.max(1, Math.min(100, Number(quantity.value || 1) + delta));
            quantity.value = value;
            updateBuySummary(state.quote, form);
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
                if (!userToken) {
                    redirectToLogin();
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
        if (!record || !record.date_end) {
            return null;
        }
        var date = new Date(String(record.date_end).trim());
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function isExpiring(record) {
        var end = dateEnd(record);
        return end && end.getTime() <= Date.now() + (7 * 86400000);
    }

    function recordProxyType(record) {
        return String(record.proxy_type || '').toUpperCase() || 'IPV4';
    }

    function primaryPort(record) {
        return String(record.https_port || record.socks5_port || '').trim();
    }

    function recordConnection(record) {
        var values = [record.ip, primaryPort(record), record.login, record.password];
        return values.every(function (value) { return String(value || '').trim() !== ''; }) ? values.join(':') : '';
    }

    function providerDateLabel(value) {
        var raw = String(value || '').trim();
        var match = raw.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/);
        if (match) {
            return match[4] + ':' + match[5] + ':' + match[6] + ' - ' + match[3] + '.' + match[2] + '.' + match[1];
        }
        var date = raw ? new Date(raw) : null;
        if (!date || Number.isNaN(date.getTime())) {
            return '--';
        }
        var pad = function (value) { return String(value).padStart(2, '0'); };
        return pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds()) + ' - ' + pad(date.getDate()) + '.' + pad(date.getMonth() + 1) + '.' + date.getFullYear();
    }

    function remainingDays(record) {
        var end = dateEnd(record);
        if (!end) {
            return null;
        }
        return Math.max(0, Math.ceil((end.getTime() - Date.now()) / 86400000));
    }

    function countdownClass(days) {
        if (days === null) {
            return '';
        }
        if (days <= 3) {
            return 'proxy-expiry-countdown--urgent';
        }
        if (days <= 7) {
            return 'proxy-expiry-countdown--soon';
        }
        return 'proxy-expiry-countdown--safe';
    }

    function fieldMarkup(label, value, note) {
        var raw = String(value || '').trim();
        var visible = raw || '--';
        var copyButton = raw
            ? '<button type="button" class="proxy-copy-button" data-copy-value="' + escapeHtml(raw) + '" aria-label="Sao chép ' + escapeHtml(label) + '" title="Sao chép ' + escapeHtml(label) + '"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>'
            : '<button type="button" class="proxy-copy-button" disabled aria-label="' + escapeHtml(label) + ' chưa có dữ liệu"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>';
        return '<div class="proxy-connection-field"><span class="proxy-detail-label">' + escapeHtml(label) + '</span><div class="proxy-detail-value"><code>' + escapeHtml(visible) + '</code>' + copyButton + '</div>' + (note ? '<small>' + escapeHtml(note) + '</small>' : '') + '</div>';
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
        var cards = state.records.map(function (record) {
            var status = recordStatus(record);
            var id = escapeHtml(record.id);
            var connection = recordConnection(record);
            var type = recordProxyType(record);
            var days = remainingDays(record);
            var autoExtend = Boolean(record.auto_extend);
            var portNote = 'HTTPS ' + (record.https_port || '--') + ' · SOCKS5 ' + (record.socks5_port || '--');
            var country = record.country || '--';
            var expiryNote = days === null ? 'Chưa xác định thời hạn' : (days > 0 ? 'Còn ' + days + ' ngày' : 'Đã hết hạn');
            var expiryClass = countdownClass(days);
            var formatButton = connection
                ? '<button type="button" class="proxy-secondary-button proxy-copy-format" data-copy-connection="' + escapeHtml(connection) + '"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copy định dạng</button>'
                : '<button type="button" class="proxy-secondary-button proxy-copy-format" disabled><i class="fa-regular fa-copy" aria-hidden="true"></i> Chưa đủ dữ liệu</button>';
            return '<article class="proxy-record-card" data-record-id="' + id + '">' +
                '<div class="proxy-record-card-header"><label class="proxy-record-select"><input type="checkbox" class="proxy-record-check" data-record-check="' + id + '"><span>Chọn</span></label><div class="proxy-record-identity"><span class="proxy-country-flag proxy-record-flag" aria-hidden="true">' + countryFlag(record.country) + '</span><div><strong>' + escapeHtml(record.ip || '--') + '</strong><small>' + escapeHtml(type + ' · ' + country) + '</small></div></div><span class="proxy-badge ' + (status.soon ? 'proxy-badge--soon' : '') + '">' + escapeHtml(status.label) + '</span></div>' +
                '<div class="proxy-connection-grid">' + fieldMarkup('IP', record.ip) + fieldMarkup('Port', primaryPort(record), portNote) + fieldMarkup('User', record.login) + fieldMarkup('Pass', record.password) + '</div>' +
                '<div class="proxy-format-row"><div><span class="proxy-detail-label">Định dạng kết nối</span><code>' + escapeHtml(connection || 'IP:Port:User:Pass') + '</code></div>' + formatButton + '</div>' +
                '<div class="proxy-record-card-footer"><div class="proxy-expiry"><span class="proxy-detail-label">Hạn dùng</span><strong>' + escapeHtml(providerDateLabel(record.date_end)) + '</strong><small class="proxy-expiry-countdown ' + expiryClass + '"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>' + escapeHtml(expiryNote) + '</small></div><div class="proxy-auto-status ' + (autoExtend ? 'is-enabled' : '') + '"><i class="fa-solid ' + (autoExtend ? 'fa-arrows-rotate' : 'fa-circle-minus') + '" aria-hidden="true"></i><span>' + (autoExtend ? 'Đã bật gia hạn' : 'Chưa bật gia hạn') + '</span></div></div>' +
                '</article>';
        }).join('');
        tableWrap.innerHTML = '<div class="proxy-record-grid">' + cards + '</div>';
        bindCountryFlagFallbacks(tableWrap);
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
        $$('[data-copy-value]', tableWrap).forEach(function (button) {
            button.addEventListener('click', function () {
                copyText(button.getAttribute('data-copy-value'), button);
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

    function downloadProxyFile() {
        var lines = state.records.map(recordConnection).filter(Boolean);
        if (!lines.length) {
            setStatus('Chưa có proxy đủ thông tin để tải xuống.', 'info');
            return;
        }
        var blob = new Blob([lines.join('\r\n') + '\r\n'], { type: 'text/plain;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'caffemmo-proxies-' + new Date().toISOString().slice(0, 10) + '.txt';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
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
        if (!authenticated) {
            renderAuthRequired('Đăng nhập để xem danh sách proxy đã mua, copy định dạng và tải file TXT.');
            return;
        }
        var filter = $('[data-list-type]');
        if (filter) filter.addEventListener('change', function () { state.selected.clear(); loadList(filter.value); });
        $$('[data-refresh-list]').forEach(function (button) { button.addEventListener('click', function () { loadList(filter ? filter.value : ''); }); });
        var renewButton = $('[data-go-renew]');
        if (renewButton) renewButton.addEventListener('click', function () {
            sessionStorage.setItem('proxyRenewSelection', JSON.stringify(Array.from(state.selected)));
            window.location.href = (window.baseUrl || '/') + 'client/proxy-renew';
        });
        var downloadButton = $('[data-download-proxies]');
        if (downloadButton) downloadButton.addEventListener('click', downloadProxyFile);
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
            var days = remainingDays(record);
            var autoExtend = Boolean(record.auto_extend);
            var remaining = days === null ? 'Chưa xác định thời hạn' : (days > 0 ? 'Còn ' + days + ' ngày' : 'Đã hết hạn');
            return '<label class="proxy-renew-item ' + (state.selected.has(id) ? 'is-selected' : '') + '"><input type="checkbox" data-renew-check="' + escapeHtml(id) + '" ' + (state.selected.has(id) ? 'checked' : '') + '><span class="proxy-renew-item-content"><span class="proxy-renew-item-identity"><span class="proxy-country-flag proxy-renew-flag" aria-hidden="true">' + countryFlag(record.country) + '</span><span><strong>' + escapeHtml(record.ip || '--') + '</strong><small>' + escapeHtml(recordProxyType(record) + ' · ' + (record.country || '--')) + '</small></span></span><span class="proxy-renew-item-date"><strong>' + escapeHtml(providerDateLabel(record.date_end)) + '</strong><span class="proxy-expiry-countdown ' + countdownClass(days) + '"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>' + escapeHtml(remaining) + '</span></span><span class="proxy-auto-status ' + (autoExtend ? 'is-enabled' : '') + '"><i class="fa-solid ' + (autoExtend ? 'fa-arrows-rotate' : 'fa-circle-minus') + '" aria-hidden="true"></i>' + (autoExtend ? 'Đã bật gia hạn' : 'Chưa bật gia hạn') + '</span></span></label>';
        }).join('');
        bindCountryFlagFallbacks(container);
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
        if (!authenticated) {
            renderAuthRequired('Đăng nhập để chọn proxy cần gia hạn và bật tự động gia hạn.');
            return;
        }
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

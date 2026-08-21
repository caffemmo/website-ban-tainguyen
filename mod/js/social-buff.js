(function () {
    'use strict';

    var app = document.querySelector('[data-social-buff]');
    if (!app) return;

    var endpoint = app.getAttribute('data-endpoint');
    var isAuthenticated = app.getAttribute('data-authenticated') === '1';
    var isConfigured = app.getAttribute('data-configured') === '1';
    var loginUrl = app.getAttribute('data-login-url');
    var servicesRoot = app.querySelector('[data-social-buff-services]');
    var historyRoot = app.querySelector('[data-social-buff-history]');
    var feedback = app.querySelector('[data-social-buff-feedback]');
    var form = app.querySelector('[data-social-buff-form]');
    var serviceIdInput = app.querySelector('[data-social-buff-service-id]');
    var targetUrlInput = app.querySelector('[name="target_url"]');
    var quantityInput = app.querySelector('[name="quantity"]');
    var rangeLabel = app.querySelector('[data-social-buff-range]');
    var total = app.querySelector('[data-social-buff-total]');
    var unit = app.querySelector('[data-social-buff-unit]');
    var selectedRoot = app.querySelector('[data-social-buff-selected]');
    var submit = app.querySelector('[data-social-buff-submit]');
    var search = app.querySelector('[data-social-buff-search]');
    var filters = app.querySelector('[data-social-buff-filters]');
    var historyRefresh = app.querySelector('[data-social-buff-history-refresh]');
    var services = [];
    var selectedService = null;
    var activeFilter = 'all';
    var requestKeyStorage = 'caffemmo-social-buff-request';

    function formatMoney(value) {
        var amount = Math.max(0, Math.ceil(Number(value) || 0));
        return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function platformIcon(platform) {
        var map = {
            'Facebook': 'fa-brands fa-facebook-f',
            'Instagram': 'fa-brands fa-instagram',
            'TikTok': 'fa-brands fa-tiktok',
            'YouTube': 'fa-brands fa-youtube',
            'Shopee': 'fa-solid fa-bag-shopping',
            'X / Twitter': 'fa-brands fa-x-twitter',
            'Google': 'fa-brands fa-google'
        };
        return map[platform] || 'fa-solid fa-bolt';
    }

    function showFeedback(message) {
        if (!feedback) return;
        feedback.textContent = message || '';
        feedback.hidden = !message;
    }

    function request(action, data) {
        var body = new URLSearchParams(data || {});
        body.set('action', action);
        return fetch(endpoint, {
            method: action === 'services' || action === 'history' ? 'POST' : 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (response) {
            return response.json().catch(function () {
                return { success: false, message: 'Máy chủ trả về phản hồi không hợp lệ.' };
            }).then(function (payload) {
                if (!response.ok && payload.success !== true) {
                    throw new Error(payload.message || 'Yêu cầu không thành công.');
                }
                return payload;
            });
        });
    }

    function visibleServices() {
        var keyword = search ? search.value.trim().toLowerCase() : '';
        return services.filter(function (service) {
            var matchesFilter = activeFilter === 'all'
                || (activeFilter === 'video' && service.is_video)
                || service.platform === activeFilter;
            var haystack = [service.name, service.category, service.platform, service.description].join(' ').toLowerCase();
            return matchesFilter && (!keyword || haystack.indexOf(keyword) !== -1);
        });
    }

    function renderServices() {
        if (!servicesRoot) return;
        var filtered = visibleServices();
        if (!filtered.length) {
            servicesRoot.innerHTML = '<div class="social-buff-history-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Không tìm thấy dịch vụ phù hợp.</span></div>';
            return;
        }

        servicesRoot.innerHTML = filtered.map(function (service) {
            var selected = selectedService && selectedService.id === service.id;
            var description = service.description || service.category || 'Dịch vụ tự động qua kết nối máy chủ.';
            return '<button type="button" class="social-buff-service-card' + (selected ? ' is-selected' : '') + '" data-social-service="' + escapeHtml(service.id) + '" aria-pressed="' + (selected ? 'true' : 'false') + '">' +
                '<span class="social-buff-service-icon"><i class="' + platformIcon(service.platform) + '" aria-hidden="true"></i></span>' +
                '<h3>' + escapeHtml(service.name) + '</h3>' +
                '<p>' + escapeHtml(description) + '</p>' +
                '<span class="social-buff-service-meta"><span class="social-buff-service-price"><strong>' + formatMoney(service.rate) + '</strong><small>/ 1.000 lượt</small></span><span class="social-buff-service-limit">' + escapeHtml(service.platform) + '<br>Từ ' + Number(service.min).toLocaleString('vi-VN') + ' - ' + Number(service.max).toLocaleString('vi-VN') + '</span></span>' +
                '</button>';
        }).join('');
    }

    function renderSelected() {
        if (!selectedRoot || !rangeLabel || !total || !unit || !submit) return;
        if (!selectedService) {
            selectedRoot.innerHTML = '<span class="social-buff-selected-icon"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span><div><strong>Chưa chọn dịch vụ</strong><small>Chọn một dịch vụ ở danh sách bên trái.</small></div>';
            rangeLabel.textContent = 'Chọn dịch vụ để xem giới hạn.';
            total.textContent = '0đ';
            unit.textContent = 'Giá được tính theo 1.000 lượt.';
            quantityInput.disabled = true;
            submit.disabled = true;
            return;
        }

        selectedRoot.innerHTML = '<span class="social-buff-selected-icon"><i class="' + platformIcon(selectedService.platform) + '" aria-hidden="true"></i></span><div><strong>' + escapeHtml(selectedService.name) + '</strong><small>' + escapeHtml(selectedService.platform) + ' · ' + formatMoney(selectedService.rate) + ' / 1.000 lượt</small></div>';
        rangeLabel.textContent = 'Từ ' + Number(selectedService.min).toLocaleString('vi-VN') + ' đến ' + Number(selectedService.max).toLocaleString('vi-VN') + '.';
        quantityInput.min = String(selectedService.min);
        quantityInput.max = String(selectedService.max);
        quantityInput.placeholder = String(selectedService.min);
        quantityInput.disabled = !isAuthenticated || !isConfigured;
        if (!quantityInput.value || Number(quantityInput.value) < selectedService.min || Number(quantityInput.value) > selectedService.max) {
            quantityInput.value = String(selectedService.min);
        }
        updateTotal();
    }

    function updateTotal() {
        if (!selectedService || !quantityInput || !total || !unit || !submit) return;
        var quantity = Number(quantityInput.value) || 0;
        var valid = Number.isInteger(quantity) && quantity >= selectedService.min && quantity <= selectedService.max;
        total.textContent = valid ? formatMoney(selectedService.rate * quantity / 1000) : '0đ';
        unit.textContent = valid ? 'Đơn giá ' + formatMoney(selectedService.rate) + ' / 1.000 lượt.' : 'Số lượng phải nằm trong giới hạn của dịch vụ.';
        submit.disabled = !valid || !isAuthenticated || !isConfigured;
    }

    function selectService(serviceId) {
        selectedService = services.find(function (service) { return service.id === serviceId; }) || null;
        if (serviceIdInput) serviceIdInput.value = selectedService ? selectedService.id : '';
        renderSelected();
        renderServices();
    }

    function renderHistory(orders) {
        if (!historyRoot) return;
        if (!orders || !orders.length) {
            historyRoot.innerHTML = '<div class="social-buff-history-empty"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Chưa có đơn dịch vụ nào.</span></div>';
            return;
        }

        historyRoot.innerHTML = orders.map(function (order) {
            var providerDetail = order.provider_order_id ? 'Mã NCC: ' + escapeHtml(order.provider_order_id) : 'Mã đơn: ' + escapeHtml(order.code);
            var progress = order.remains ? 'Còn lại: ' + escapeHtml(order.remains) : 'Số lượng: ' + Number(order.quantity).toLocaleString('vi-VN');
            return '<article class="social-buff-order-row" data-social-order="' + escapeHtml(order.code) + '">' +
                '<div class="social-buff-order-service"><strong>' + escapeHtml(order.service_name) + '</strong><small>' + providerDetail + '</small></div>' +
                '<div class="social-buff-order-metric"><span>' + formatMoney(order.charged_amount) + '</span><small>' + escapeHtml(order.platform) + '</small></div>' +
                '<div class="social-buff-order-metric"><span>' + progress + '</span><small>' + escapeHtml(order.created_at) + '</small></div>' +
                '<span class="social-buff-order-status ' + escapeHtml(order.status_class) + '">' + escapeHtml(order.status_label) + '</span>' +
                '<button type="button" class="social-buff-order-check" data-social-order-refresh="' + escapeHtml(order.code) + '" title="Cập nhật trạng thái" aria-label="Cập nhật trạng thái đơn ' + escapeHtml(order.code) + '"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i></button>' +
                '</article>';
        }).join('');
    }

    function loadHistory() {
        if (!isAuthenticated) return;
        if (historyRefresh) historyRefresh.classList.add('is-loading');
        request('history').then(function (payload) {
            renderHistory(payload.orders || []);
        }).catch(function (error) {
            if (historyRoot) historyRoot.innerHTML = '<div class="social-buff-history-empty"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>' + escapeHtml(error.message || 'Chưa thể tải lịch sử đơn.') + '</span></div>';
        }).finally(function () {
            if (historyRefresh) historyRefresh.classList.remove('is-loading');
        });
    }

    function loadServices() {
        if (!isAuthenticated || !isConfigured) {
            if (servicesRoot) servicesRoot.innerHTML = '<div class="social-buff-history-empty"><i class="fa-solid fa-bolt" aria-hidden="true"></i><span>' + (isAuthenticated ? 'Dịch vụ đang được cấu hình.' : 'Đăng nhập để xem danh sách dịch vụ.') + '</span></div>';
            return;
        }
        request('services').then(function (payload) {
            services = Array.isArray(payload.services) ? payload.services : [];
            if (!payload.configured) showFeedback(payload.message || 'Dịch vụ đang được cấu hình.');
            renderServices();
        }).catch(function (error) {
            services = [];
            showFeedback(error.message || 'Chưa thể tải danh sách dịch vụ.');
            renderServices();
        });
    }

    function newRequestKey() {
        var bytes = new Uint8Array(18);
        if (window.crypto && window.crypto.getRandomValues) {
            window.crypto.getRandomValues(bytes);
            return Array.prototype.map.call(bytes, function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
        }
        return Date.now().toString(36) + Math.random().toString(36).slice(2);
    }

    function orderFingerprint() {
        return [
            selectedService ? selectedService.id : '',
            targetUrlInput ? targetUrlInput.value.trim() : '',
            quantityInput ? quantityInput.value : ''
        ].join('|');
    }

    function requestKeyForCurrentOrder() {
        var fingerprint = orderFingerprint();
        try {
            var saved = JSON.parse(window.sessionStorage.getItem(requestKeyStorage) || '{}');
            if (saved.fingerprint === fingerprint && saved.key) return saved.key;
            var key = newRequestKey();
            window.sessionStorage.setItem(requestKeyStorage, JSON.stringify({ fingerprint: fingerprint, key: key }));
            return key;
        } catch (error) {
            return newRequestKey();
        }
    }

    function clearRequestKey() {
        try {
            window.sessionStorage.removeItem(requestKeyStorage);
        } catch (error) {}
    }

    if (filters) {
        filters.addEventListener('click', function (event) {
            var button = event.target.closest('[data-social-filter]');
            if (!button) return;
            activeFilter = button.getAttribute('data-social-filter') || 'all';
            filters.querySelectorAll('[data-social-filter]').forEach(function (filter) {
                filter.classList.toggle('is-active', filter === button);
            });
            renderServices();
        });
    }

    if (search) search.addEventListener('input', renderServices);
    if (quantityInput) quantityInput.addEventListener('input', updateTotal);

    if (servicesRoot) {
        servicesRoot.addEventListener('click', function (event) {
            var card = event.target.closest('[data-social-service]');
            if (card) selectService(card.getAttribute('data-social-service'));
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!selectedService || submit.disabled) return;
            if (!isAuthenticated) {
                window.location.assign(loginUrl);
                return;
            }
            var targetUrl = targetUrlInput.value.trim();
            if (!targetUrl) {
                targetUrlInput.focus();
                return;
            }
            showFeedback('');
            submit.disabled = true;
            submit.classList.add('is-loading');
            request('place_order', {
                service_id: selectedService.id,
                target_url: targetUrl,
                quantity: quantityInput.value,
                request_key: requestKeyForCurrentOrder()
            }).then(function (payload) {
                showFeedback(payload.message || 'Đã tiếp nhận đơn dịch vụ.');
                if (payload.order) {
                    targetUrlInput.value = '';
                    clearRequestKey();
                    loadHistory();
                }
            }).catch(function (error) {
                showFeedback(error.message || 'Không thể đặt dịch vụ.');
            }).finally(function () {
                submit.classList.remove('is-loading');
                updateTotal();
            });
        });
    }

    if (historyRefresh) historyRefresh.addEventListener('click', loadHistory);

    if (historyRoot) {
        historyRoot.addEventListener('click', function (event) {
            var button = event.target.closest('[data-social-order-refresh]');
            if (!button) return;
            var code = button.getAttribute('data-social-order-refresh');
            button.classList.add('is-loading');
            button.disabled = true;
            request('refresh', { order_code: code }).then(function (payload) {
                if (payload.order) loadHistory();
            }).catch(function (error) {
                showFeedback(error.message || 'Chưa thể cập nhật đơn.');
            }).finally(function () {
                button.classList.remove('is-loading');
                button.disabled = false;
            });
        });
    }

    loadServices();
    loadHistory();
}());

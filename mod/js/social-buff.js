(function () {
    'use strict';

    var app = document.querySelector('[data-social-buff]');
    if (!app) return;

    var endpoint = app.getAttribute('data-endpoint');
    var isAuthenticated = app.getAttribute('data-authenticated') === '1';
    var isConfigured = app.getAttribute('data-configured') === '1';
    var unavailableMessage = app.getAttribute('data-unavailable-message') || 'Dịch vụ đang được cập nhật.';
    var loginUrl = app.getAttribute('data-login-url');
    var servicesRoot = app.querySelector('[data-social-buff-services]');
    var serviceCount = app.querySelector('[data-social-buff-count]');
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
    var detailPlatform = app.querySelector('[data-social-buff-detail-platform]');
    var detailRate = app.querySelector('[data-social-buff-detail-rate]');
    var detailMin = app.querySelector('[data-social-buff-detail-min]');
    var detailMax = app.querySelector('[data-social-buff-detail-max]');
    var submit = app.querySelector('[data-social-buff-submit]');
    var search = app.querySelector('[data-social-buff-search]');
    var filters = app.querySelector('[data-social-buff-filters]');
    var typeFiltersRoot = app.querySelector('[data-social-buff-type-filters]');
    var typeFilterTitle = app.querySelector('[data-social-buff-type-title]');
    var typeFilterList = app.querySelector('[data-social-buff-type-filter-list]');
    var historyRefresh = app.querySelector('[data-social-buff-history-refresh]');
    var services = [];
    var selectedService = null;
    var activeFilter = 'all';
    var activeServiceType = 'all';
    var requestKeyStorage = 'caffemmo-social-buff-request';
    var serviceTypeRules = [
        { key: 'share', label: 'Chia sẻ bài viết', icon: 'fa-share-nodes', terms: ['share', 'chia se'] },
        { key: 'like', label: 'Like bài viết', icon: 'fa-thumbs-up', terms: ['like', 'luot thich', 'thich'] },
        { key: 'follow', label: 'Tăng theo dõi', icon: 'fa-user-plus', terms: ['follow', 'theo doi', 'follower', 'sub'] },
        { key: 'comment', label: 'Bình luận', icon: 'fa-comment-dots', terms: ['comment', 'binh luan'] },
        { key: 'reaction', label: 'Cảm xúc', icon: 'fa-face-smile', terms: ['reaction', 'cam xuc'] },
        { key: 'member', label: 'Thành viên', icon: 'fa-users', terms: ['member', 'thanh vien', 'tham gia', 'join'] },
        { key: 'livestream', label: 'Livestream', icon: 'fa-tower-broadcast', terms: ['livestream', 'live stream', 'live'] },
        { key: 'view', label: 'Lượt xem', icon: 'fa-eye', terms: ['view', 'luot xem', 'mat xem', 'watch', 'video', 'reel', 'story'] }
    ];

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

    function normalizedServiceText(value) {
        var text = String(value == null ? '' : value);
        if (typeof text.normalize === 'function') {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return text.toLowerCase();
    }

    function serviceTypeFor(service) {
        var text = normalizedServiceText([service.name, service.description, service.type].join(' '));
        var matched = serviceTypeRules.find(function (rule) {
            return rule.terms.some(function (term) { return text.indexOf(term) !== -1; });
        });
        return matched ? matched.key : 'other';
    }

    function serviceTypeMeta(type) {
        return serviceTypeRules.find(function (rule) { return rule.key === type; }) || {
            key: 'other',
            label: 'Dịch vụ khác',
            icon: 'fa-ellipsis'
        };
    }

    function matchesActivePlatform(service) {
        return activeFilter === 'all'
            || (activeFilter === 'video' && service.is_video)
            || service.platform === activeFilter;
    }

    function renderServiceTypeFilters() {
        if (!typeFiltersRoot || !typeFilterList) return;
        if (activeFilter === 'all') {
            activeServiceType = 'all';
            typeFiltersRoot.hidden = true;
            typeFilterList.innerHTML = '';
            return;
        }

        var types = [];
        services.forEach(function (service) {
            if (!matchesActivePlatform(service)) return;
            var type = serviceTypeFor(service);
            if (types.indexOf(type) === -1) types.push(type);
        });

        if (!types.length) {
            activeServiceType = 'all';
            typeFiltersRoot.hidden = true;
            typeFilterList.innerHTML = '';
            return;
        }

        if (types.indexOf(activeServiceType) === -1) activeServiceType = 'all';
        if (typeFilterTitle) typeFilterTitle.textContent = activeFilter === 'video' ? 'Loại video' : activeFilter + ' - loại dịch vụ';
        typeFilterList.innerHTML = '<button type="button" data-social-service-type="all"><i class="fa-solid fa-list" aria-hidden="true"></i><span>Tất cả loại</span></button>' + types.map(function (type) {
            var meta = serviceTypeMeta(type);
            return '<button type="button" data-social-service-type="' + escapeHtml(meta.key) + '"><i class="fa-solid ' + escapeHtml(meta.icon) + '" aria-hidden="true"></i><span>' + escapeHtml(meta.label) + '</span></button>';
        }).join('');
        typeFilterList.querySelectorAll('[data-social-service-type]').forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-social-service-type') === activeServiceType);
        });
        typeFiltersRoot.hidden = false;
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
            var matchesFilter = matchesActivePlatform(service);
            var matchesType = activeServiceType === 'all' || serviceTypeFor(service) === activeServiceType;
            var haystack = [service.name, service.platform].join(' ').toLowerCase();
            return matchesFilter && matchesType && (!keyword || haystack.indexOf(keyword) !== -1);
        });
    }

    function renderServices() {
        if (!servicesRoot) return;
        var filtered = visibleServices();
        if (serviceCount) serviceCount.textContent = filtered.length + ' dịch vụ';
        if (!filtered.length) {
            servicesRoot.innerHTML = '<div class="social-buff-history-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Không tìm thấy dịch vụ phù hợp.</span></div>';
            return;
        }

        servicesRoot.innerHTML = filtered.map(function (service) {
            var selected = selectedService && selectedService.id === service.id;
            return '<button type="button" class="social-buff-service-card' + (selected ? ' is-selected' : '') + '" data-social-service="' + escapeHtml(service.id) + '" aria-pressed="' + (selected ? 'true' : 'false') + '">' +
                '<span class="social-buff-service-card-top"><span class="social-buff-service-selector" aria-hidden="true"></span><span class="social-buff-service-icon"><i class="' + platformIcon(service.platform) + '" aria-hidden="true"></i></span><span class="social-buff-service-heading"><strong>' + escapeHtml(service.name) + '</strong><small>' + escapeHtml(service.platform) + '</small></span><span class="social-buff-service-price"><strong>' + formatMoney(service.rate) + '</strong><small>/ 1.000 lượt</small></span></span>' +
                '<span class="social-buff-service-card-detail"><span><i class="fa-solid fa-arrow-down-1-9" aria-hidden="true"></i> Tối thiểu ' + Number(service.min).toLocaleString('vi-VN') + '</span><span><i class="fa-solid fa-arrow-up-9-1" aria-hidden="true"></i> Tối đa ' + Number(service.max).toLocaleString('vi-VN') + '</span></span>' +
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
            if (detailPlatform) detailPlatform.textContent = '--';
            if (detailRate) detailRate.textContent = '--';
            if (detailMin) detailMin.textContent = '--';
            if (detailMax) detailMax.textContent = '--';
            return;
        }

        selectedRoot.innerHTML = '<span class="social-buff-selected-icon"><i class="' + platformIcon(selectedService.platform) + '" aria-hidden="true"></i></span><div><strong>' + escapeHtml(selectedService.name) + '</strong><small>' + escapeHtml(selectedService.platform) + ' · ' + formatMoney(selectedService.rate) + ' / 1.000 lượt</small></div><span class="social-buff-selection-badge">Đã chọn</span>';
        if (detailPlatform) detailPlatform.textContent = selectedService.platform;
        if (detailRate) detailRate.textContent = formatMoney(selectedService.rate) + ' / 1.000';
        if (detailMin) detailMin.textContent = Number(selectedService.min).toLocaleString('vi-VN');
        if (detailMax) detailMax.textContent = Number(selectedService.max).toLocaleString('vi-VN');
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
            var orderDetail = 'Mã đơn: ' + escapeHtml(order.code);
            var progress = order.remains ? 'Còn lại: ' + escapeHtml(order.remains) : 'Số lượng: ' + Number(order.quantity).toLocaleString('vi-VN');
            return '<article class="social-buff-order-row" data-social-order="' + escapeHtml(order.code) + '">' +
                '<div class="social-buff-order-service"><strong>' + escapeHtml(order.service_name) + '</strong><small>' + orderDetail + '</small></div>' +
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
            if (serviceCount) serviceCount.textContent = isAuthenticated ? 'Tạm thời không khả dụng' : 'Đăng nhập để xem';
            if (servicesRoot) servicesRoot.innerHTML = '<div class="social-buff-history-empty"><i class="fa-solid fa-bolt" aria-hidden="true"></i><span>' + (isAuthenticated ? escapeHtml(unavailableMessage) : 'Đăng nhập để xem danh sách dịch vụ.') + '</span></div>';
            return;
        }
        request('services').then(function (payload) {
            services = Array.isArray(payload.services) ? payload.services : [];
            if (!payload.configured) showFeedback(payload.message || 'Dịch vụ đang được cấu hình.');
            renderServiceTypeFilters();
            renderServices();
        }).catch(function (error) {
            services = [];
            showFeedback(error.message || 'Chưa thể tải danh sách dịch vụ.');
            renderServiceTypeFilters();
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
            activeServiceType = 'all';
            filters.querySelectorAll('[data-social-filter]').forEach(function (filter) {
                filter.classList.toggle('is-active', filter === button);
            });
            renderServiceTypeFilters();
            renderServices();
        });
    }

    if (typeFiltersRoot) {
        typeFiltersRoot.addEventListener('click', function (event) {
            var button = event.target.closest('[data-social-service-type]');
            if (!button) return;
            activeServiceType = button.getAttribute('data-social-service-type') || 'all';
            typeFilterList.querySelectorAll('[data-social-service-type]').forEach(function (filter) {
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

(function () {
    'use strict';

    var app = document.querySelector('[data-locket-app]');
    if (!app) return;

    var endpoint = app.getAttribute('data-endpoint') || '';
    var token = app.getAttribute('data-token') || '';
    var authenticated = app.getAttribute('data-authenticated') === '1';
    var enabled = app.getAttribute('data-enabled') === '1';
    var loginUrl = app.getAttribute('data-login-url') || '';
    var packageData = {};
    var packageButtons = Array.prototype.slice.call(app.querySelectorAll('[data-locket-package]'));
    var selectedLabel = app.querySelector('[data-locket-selected-label]');
    var selectedPrice = app.querySelector('[data-locket-selected-price]');
    var limitHelp = app.querySelector('[data-locket-limit-help]');
    var usernameInput = app.querySelector('[data-locket-usernames]');
    var submitButton = app.querySelector('[data-locket-submit]');
    var result = app.querySelector('[data-locket-result]');
    var resultTitle = app.querySelector('[data-locket-result-title]');
    var resultMeta = app.querySelector('[data-locket-result-meta]');
    var loginLink = app.querySelector('[data-locket-login]');
    var selectedKey = packageButtons.length ? packageButtons[0].getAttribute('data-locket-package') : '';

    try {
        packageData = JSON.parse(app.getAttribute('data-packages') || '{}');
    } catch (error) {
        packageData = {};
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Number(value || 0)) + 'đ';
    }

    function updateSelection(key) {
        var data = packageData[key];
        if (!data) return;
        selectedKey = key;
        packageButtons.forEach(function (button) {
            var isSelected = button.getAttribute('data-locket-package') === key;
            button.classList.toggle('is-selected', isSelected);
            button.setAttribute('aria-checked', isSelected ? 'true' : 'false');
        });
        if (selectedLabel) selectedLabel.textContent = data.label;
        if (selectedPrice) selectedPrice.textContent = formatCurrency(data.price);
        if (limitHelp) {
            limitHelp.textContent = 'Mỗi dòng một username, tối đa ' + data.max_accounts + ' tài khoản với gói đang chọn.';
        }
    }

    function showResult(title, message, isError) {
        if (!result) return;
        result.hidden = false;
        result.classList.toggle('is-error', Boolean(isError));
        result.classList.toggle('is-success', !isError);
        if (resultTitle) resultTitle.textContent = title;
        if (resultMeta) resultMeta.textContent = message;
    }

    packageButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            updateSelection(button.getAttribute('data-locket-package'));
        });
    });
    updateSelection(selectedKey);

    if (loginLink) {
        loginLink.href = loginUrl;
        loginLink.hidden = authenticated;
    }

    if (!submitButton || !usernameInput) return;
    submitButton.addEventListener('click', function () {
        if (!authenticated) {
            window.location.href = loginUrl;
            return;
        }
        if (!enabled) {
            showResult('Dịch vụ đang bảo trì', 'Vui lòng quay lại sau.', true);
            return;
        }

        var usernames = usernameInput.value.trim();
        if (!usernames) {
            showResult('Chưa có username', 'Vui lòng nhập username Locket trước khi gửi yêu cầu.', true);
            usernameInput.focus();
            return;
        }

        submitButton.disabled = true;
        submitButton.classList.add('is-loading');
        var originalHtml = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Đang gửi yêu cầu';

        var formData = new URLSearchParams();
        formData.set('action', 'create_order');
        formData.set('token', token);
        formData.set('package_key', selectedKey);
        formData.set('usernames', usernames);

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: formData.toString(),
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (payload) {
                return { ok: response.ok, payload: payload };
            });
        }).then(function (response) {
            var payload = response.payload || {};
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Không thể tạo đơn lúc này.');
            }
            usernameInput.value = '';
            showResult('Đã tiếp nhận yêu cầu', 'Mã đơn ' + payload.order_code + '. Đơn đang chờ Caffemmo xử lý thủ công.', false);
        }).catch(function (error) {
            showResult('Chưa thể gửi yêu cầu', error.message || 'Vui lòng thử lại sau.', true);
        }).finally(function () {
            submitButton.disabled = false;
            submitButton.classList.remove('is-loading');
            submitButton.innerHTML = originalHtml;
        });
    });
}());

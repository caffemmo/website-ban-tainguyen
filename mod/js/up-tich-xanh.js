(function () {
    'use strict';

    var app = document.querySelector('[data-up-app]');
    if (!app) return;

    var endpoint = app.getAttribute('data-endpoint') || '';
    var token = app.getAttribute('data-token') || '';
    var loginUrl = app.getAttribute('data-login-url') || ((window.baseUrl || '/') + 'client/login');
    var service = app.getAttribute('data-service') || '';
    var configured = app.getAttribute('data-configured') === '1';
    var historyUrl = app.getAttribute('data-history-url') || '';
    var form = app.querySelector('[data-up-form]');
    var submit = app.querySelector('[data-up-submit]');
    var notice = app.querySelector('[data-up-notice]');
    var result = app.querySelector('[data-up-result]');

    function redirectToLogin() {
        window.location.href = loginUrl;
    }

    function setMessage(element, message, type) {
        if (!element) return;
        element.hidden = !message;
        element.setAttribute('data-type', type || 'info');
        element.textContent = message || '';
    }

    function setLoading(loading) {
        if (!submit) return;
        if (loading) {
            submit.disabled = true;
            submit.dataset.originalText = submit.innerHTML;
            submit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i> Đang xử lý';
        } else {
            submit.disabled = false;
            if (submit.dataset.originalText) {
                submit.innerHTML = submit.dataset.originalText;
                delete submit.dataset.originalText;
            }
        }
    }

    function showResult(data) {
        if (!result) return;
        var html = '<strong>Yêu cầu đã hoàn tất</strong>'
            + '<span>Chi phí dịch vụ: ' + String(data.charged_label || '') + '</span>';
        if (data.link) {
            html += '<a href="' + String(data.link).replace(/"/g, '&quot;') + '" target="_blank" rel="noopener noreferrer">Mở link xác minh <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>';
        }
        if (historyUrl) {
            html += '<a href="' + historyUrl + '">Xem lịch sử yêu cầu <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>';
        }
        result.innerHTML = html;
        result.hidden = false;
    }

    async function submitForm() {
        if (!configured || !form || !submit) return;
        if (!token) {
            redirectToLogin();
            return;
        }
        var cookie = form.querySelector('[name="cookie"]');
        var image = form.querySelector('[name="image"]');
        if (!cookie || cookie.value.trim().length < 10) {
            setMessage(notice, 'Vui lòng nhập cookie đầy đủ trước khi gửi.', 'error');
            cookie && cookie.focus();
            return;
        }
        if ((service === 'up-fb' || service === 'up-ig') && (!image || !image.files.length)) {
            setMessage(notice, 'Vui lòng chọn ảnh giấy tờ xác minh.', 'error');
            return;
        }
        if (image && image.files.length && image.files[0].size > 10 * 1024 * 1024) {
            setMessage(notice, 'Ảnh không được vượt quá 10MB.', 'error');
            return;
        }
        var body = new FormData(form);
        body.append('action', 'submit');
        body.append('service', service);
        body.append('token', token);
        setLoading(true);
        setMessage(notice, 'Đang tiếp nhận yêu cầu...', 'info');
        if (result) result.hidden = true;

        try {
            var response = await fetch(endpoint, { method: 'POST', credentials: 'same-origin', body: body, headers: { 'Accept': 'application/json' } });
            var data = await response.json().catch(function () { return { success: false, message: 'Phản hồi máy chủ không hợp lệ.' }; });
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Không thể hoàn tất yêu cầu.');
            }
            setMessage(notice, data.message || 'Yêu cầu đã được xử lý thành công.', 'success');
            showResult(data.data || {});
            form.reset();
        } catch (error) {
            setMessage(notice, error.message || 'Không thể hoàn tất yêu cầu.', 'error');
        } finally {
            setLoading(false);
        }
    }

    if (!configured) {
        if (submit) submit.disabled = true;
        return;
    }
    if (notice) notice.hidden = false;
    if (form) form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitForm();
    });
}());

(function () {
    'use strict';

    const app = document.querySelector('[data-netflix-app]');
    if (!app) return;

    const button = app.querySelector('[data-netflix-submit]');
    const result = app.querySelector('[data-netflix-result]');
    const resultTitle = app.querySelector('[data-netflix-result-title]');
    const resultMeta = app.querySelector('[data-netflix-result-meta]');
    const pcLink = app.querySelector('[data-netflix-pc]');
    const mobileLink = app.querySelector('[data-netflix-mobile]');
    const refreshButton = app.querySelector('[data-netflix-refresh]');
    if (!button || !result || !resultTitle || !resultMeta) return;
    let logId = '';

    const formatRemaining = (seconds) => {
        const total = Math.max(0, Number(seconds) || 0);
        const minutes = Math.floor(total / 60);
        if (minutes >= 60) {
            const hours = Math.floor(minutes / 60);
            const restMinutes = minutes % 60;
            return `${hours} giờ${restMinutes ? ` ${restMinutes} phút` : ''}`;
        }
        return `${Math.max(1, minutes)} phút`;
    };

    const showResult = (title, meta, isError) => {
        result.hidden = false;
        result.dataset.state = isError ? 'error' : 'success';
        resultTitle.textContent = title;
        resultMeta.textContent = meta;
        if (pcLink) pcLink.hidden = true;
        if (mobileLink) mobileLink.hidden = true;
        if (refreshButton) refreshButton.hidden = true;
    };

    const requestLink = async (action, requestedLogId, trigger) => {
        if (trigger.disabled) return;
        const originalHtml = trigger.innerHTML;
        trigger.disabled = true;
        trigger.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Đang xử lý...';
        result.hidden = true;

        try {
            const body = new URLSearchParams({
                action,
                token: app.dataset.token || '',
                log_id: requestedLogId || ''
            });
            const response = await fetch(app.dataset.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Không thể tạo link Netflix.');
            }

            const data = payload.data || {};
            if (!data.pc_link && !data.mobile_link) {
                throw new Error('API không trả về link xem hợp lệ.');
            }

            const chargeNote = data.charged_label ? ` Đã trừ ${data.charged_label}.` : '';
            showResult('Đã tạo link xem Netflix', `Link còn hiệu lực khoảng ${formatRemaining(data.time_remaining)}.${chargeNote}`, false);
            logId = String(data.log_id || logId || '');
            if (pcLink && data.pc_link) {
                pcLink.href = data.pc_link;
                pcLink.hidden = false;
            }
            if (mobileLink && data.mobile_link) {
                mobileLink.href = data.mobile_link;
                mobileLink.hidden = false;
            }
            if (refreshButton && logId) refreshButton.hidden = false;
        } catch (error) {
            showResult('Chưa tạo được link', error.message || 'Vui lòng thử lại sau.', true);
        } finally {
            trigger.disabled = false;
            trigger.innerHTML = originalHtml;
        }
    };

    button.addEventListener('click', () => requestLink('get_cookie', '', button));
    if (refreshButton) {
        refreshButton.addEventListener('click', () => requestLink('regenerate_link', logId, refreshButton));
    }
})();

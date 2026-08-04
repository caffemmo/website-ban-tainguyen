(function () {
    'use strict';

    const app = document.querySelector('[data-netflix-history]');
    if (!app) return;

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

    const requestLink = async (trigger) => {
        if (trigger.disabled) return;

        const row = trigger.closest('tr');
        const result = row ? row.querySelector('[data-netflix-history-result]') : null;
        const message = row ? row.querySelector('[data-netflix-history-message]') : null;
        const pcLink = row ? row.querySelector('[data-netflix-history-pc]') : null;
        const mobileLink = row ? row.querySelector('[data-netflix-history-mobile]') : null;
        const originalHtml = trigger.innerHTML;
        trigger.disabled = true;
        trigger.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Đang xử lý...';
        if (message) {
            message.textContent = '';
            message.dataset.state = '';
        }
        if (result) result.hidden = true;

        try {
            const body = new URLSearchParams({
                action: 'regenerate_link',
                token: app.dataset.token || '',
                log_id: trigger.dataset.logId || ''
            });
            const response = await fetch(app.dataset.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Không thể tạo lại link Netflix.');
            }

            const data = payload.data || {};
            if (!data.pc_link && !data.mobile_link) {
                throw new Error('API không trả về link xem hợp lệ.');
            }
            if (pcLink) {
                pcLink.hidden = !data.pc_link;
                pcLink.href = data.pc_link || '#';
            }
            if (mobileLink) {
                mobileLink.hidden = !data.mobile_link;
                mobileLink.href = data.mobile_link || '#';
            }
            if (result) result.hidden = false;
            if (message) {
                message.dataset.state = 'success';
                message.textContent = `Đã tạo lại, link còn hiệu lực khoảng ${formatRemaining(data.time_remaining)}.`;
            }
        } catch (error) {
            if (message) {
                message.dataset.state = 'error';
                message.textContent = error.message || 'Vui lòng thử lại sau.';
            }
        } finally {
            trigger.disabled = false;
            trigger.innerHTML = originalHtml;
        }
    };

    app.querySelectorAll('[data-netflix-history-refresh]').forEach((trigger) => {
        trigger.addEventListener('click', () => requestLink(trigger));
    });
}());

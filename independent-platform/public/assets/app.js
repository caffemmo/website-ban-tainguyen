(function () {
  'use strict';

  const sidebar = document.querySelector('[data-sidebar]');
  document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!sidebar) return;
      sidebar.classList.toggle('is-open');
    });
  });

  document.querySelectorAll('.choice-card').forEach((card) => {
    card.addEventListener('click', () => {
      const group = card.closest('.choice-grid') || document;
      group.querySelectorAll('.choice-card').forEach((item) => item.classList.remove('is-selected'));
      card.classList.add('is-selected');
      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    });
  });

  const filterInput = document.querySelector('[data-filter-input]');
  const filterButtons = document.querySelectorAll('[data-filter]');
  const productCards = document.querySelectorAll('[data-product-grid] .product-card');
  let activeFilter = 'all';

  function filterProducts() {
    const term = (filterInput?.value || '').trim().toLowerCase();
    productCards.forEach((card) => {
      const matchesType = activeFilter === 'all' || card.dataset.type === activeFilter;
      const matchesTerm = !term || (card.dataset.name || '').includes(term);
      card.classList.toggle('is-hidden', !(matchesType && matchesTerm));
    });
  }

  filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      activeFilter = button.dataset.filter || 'all';
      filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
      filterProducts();
    });
  });
  filterInput?.addEventListener('input', filterProducts);

  document.querySelectorAll('[data-product-buy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const productId = Number(button.dataset.productId || 0);
      const result = document.querySelector('[data-order-result]');
      if (!productId || !window.confirm('Xác nhận mua sản phẩm này bằng số dư ví?')) return;
      button.disabled = true;
      if (result) result.textContent = 'Đang tạo đơn...';
      try {
        const data = await requestJson('/api/order/create', { items: [{ product_id: productId, quantity: 1 }] });
        if (result) {
          result.classList.remove('notice-error');
          result.textContent = `Đặt hàng thành công. Mã đơn: ${data.order_code}.`;
        }
      } catch (error) {
        if (result) {
          result.classList.add('notice-error');
          result.textContent = error.message;
        }
      } finally {
        button.disabled = false;
      }
    });
  });

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const appUrl = String(window.CAFFEMMO_APP_URL || '').replace(/\/$/, '');
  const apiUrl = (path) => `${appUrl}${path}`;
  const requestJson = async (url, payload = {}) => {
    const response = await fetch(apiUrl(url), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ ...payload, _csrf: csrf }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.ok === false) throw new Error(data.error || data.status || 'Không thể hoàn tất yêu cầu.');
    return data;
  };

  document.querySelector('[data-deposit-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const result = form.querySelector('[data-deposit-result]');
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      const data = await requestJson('/api/wallet/deposit-intent', Object.fromEntries(new FormData(form).entries()));
      result.hidden = false;
      result.classList.remove('notice-error');
      result.querySelector('span').textContent = `Mã nạp của bạn: ${data.deposit_code}. Hãy ghi đúng mã này trong nội dung chuyển khoản.`;
    } catch (error) {
      result.hidden = false;
      result.classList.add('notice-error');
      result.querySelector('span').textContent = error.message;
    } finally {
      button.disabled = false;
    }
  });

  const proxyForm = document.querySelector('[data-proxy-form]');
  if (proxyForm) {
    const total = proxyForm.querySelector('[data-proxy-total]');
    const summary = proxyForm.querySelector('dd[data-proxy-summary]') || proxyForm.querySelector('[data-proxy-summary]');
    const updateProxyQuote = () => {
      const quantity = Math.max(1, Number(proxyForm.querySelector('[name="quantity"]')?.value || 1));
      const days = Math.max(1, Number(proxyForm.querySelector('[name="rent_period_days"]')?.value || 1));
      const unit = Number(proxyForm.querySelector('[name="unit_price"]')?.value || 0);
      const estimate = unit * quantity * days;
      total.value = estimate;
      summary.textContent = estimate > 0 ? new Intl.NumberFormat('vi-VN').format(estimate) + 'đ' : 'Chọn đủ cấu hình';
    };
    proxyForm.querySelectorAll('input, select').forEach((input) => input.addEventListener('change', updateProxyQuote));
    proxyForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = proxyForm.querySelector('button[type="submit"]');
      button.disabled = true;
      try {
        const data = await requestJson('/api/proxy/purchase', Object.fromEntries(new FormData(proxyForm).entries()));
        proxyForm.querySelector('[data-proxy-result]').textContent = `Đã tạo proxy thành công. Mã provider: ${data.provider_order_id || 'đang đồng bộ'}.`;
      } catch (error) {
        proxyForm.querySelector('[data-proxy-result]').textContent = error.message;
      } finally {
        button.disabled = false;
      }
    });
    updateProxyQuote();
  }

  document.querySelectorAll('[data-social-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      const result = form.querySelector('[data-social-result]');
      button.disabled = true;
      try {
        const data = await requestJson('/api/social/request', Object.fromEntries(new FormData(form).entries()));
        result.textContent = data.status === 'completed' ? 'Yêu cầu đã được tiếp nhận.' : 'Yêu cầu đã được ghi nhận để xử lý.';
      } catch (error) {
        result.textContent = error.message;
      } finally {
        button.disabled = false;
      }
    });
  });

  document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const value = button.dataset.copy || '';
      if (!value || !navigator.clipboard) return;
      try {
        await navigator.clipboard.writeText(value);
        const original = button.textContent;
        button.textContent = 'Đã sao chép';
        window.setTimeout(() => { button.textContent = original; }, 1400);
      } catch (error) {
        button.setAttribute('title', 'Không thể sao chép tự động');
      }
    });
  });

  const renewForm = document.querySelector('[data-renew-form]');
  if (renewForm) {
    const checks = [...renewForm.querySelectorAll('[data-renew-proxy]')];
    const daysInput = renewForm.querySelector('[data-renew-days]');
    const submit = renewForm.querySelector('[data-renew-submit]');
    const summary = renewForm.querySelector('[data-renew-summary]');
    const result = renewForm.querySelector('[data-renew-result]');
    const updateRenewSummary = () => {
      const selected = checks.filter((input) => input.checked).length;
      submit.disabled = selected === 0;
      if (summary) {
        summary.querySelector('strong').textContent = selected ? `${selected} proxy được chọn` : 'Chưa chọn proxy';
        summary.querySelector('span').textContent = selected ? `Gia hạn thêm ${daysInput.value} ngày cho nhóm đã chọn.` : 'Chọn ít nhất một proxy để tiếp tục.';
      }
    };
    checks.forEach((input) => input.addEventListener('change', updateRenewSummary));
    daysInput?.addEventListener('change', updateRenewSummary);
    renewForm.querySelector('[data-renew-select-all]')?.addEventListener('click', () => {
      const selectAll = checks.some((input) => !input.checked);
      checks.forEach((input) => { input.checked = selectAll; });
      updateRenewSummary();
    });
    renewForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const selected = checks.filter((input) => input.checked);
      if (!selected.length) return;
      submit.disabled = true;
      result.classList.remove('notice-error');
      result.textContent = 'Đang xử lý gia hạn...';
      let completed = 0;
      let failed = 0;
      for (const input of selected) {
        try {
          await requestJson('/api/proxy/renew', { proxy_order_id: input.value, rent_period_days: daysInput.value });
          completed += 1;
        } catch (error) {
          failed += 1;
        }
      }
      if (failed) result.classList.add('notice-error');
      result.textContent = failed ? `Đã gia hạn ${completed} proxy; ${failed} proxy chưa xử lý được.` : `Đã gia hạn ${completed} proxy thành công.`;
      submit.disabled = false;
      updateRenewSummary();
    });
    updateRenewSummary();
  }

  if (window.lucide) {
    window.lucide.createIcons({ attrs: { 'aria-hidden': 'true' } });
  }
})();

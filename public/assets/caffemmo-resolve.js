(() => {
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];
  const backdrop = $('#modalBackdrop');
  const toast = $('#toast');
  let activeModal = null;
  let toastTimer;

  const escapeHtml = (value) => value.replace(/[&<>'"]/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;',
  }[character]));

  window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });

  function showToast(message) {
    $('span', toast).textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3400);
  }

  function openModal(modal) {
    $$('.modal', backdrop).forEach((item) => { item.hidden = item !== modal; });
    backdrop.hidden = false;
    activeModal = modal;
    setTimeout(() => $('select, textarea, button', modal)?.focus(), 0);
  }

  function closeModal() {
    backdrop.hidden = true;
    activeModal = null;
  }

  $('#openRequest').addEventListener('click', () => openModal($('#requestModal')));
  $$('[data-open-provider-registration]').forEach((button) => button.addEventListener('click', () => openModal($('#providerModal'))));
  $$('[data-open-safety]').forEach((button) => button.addEventListener('click', () => openModal($('#safetyModal'))));
  $('#openDispute').addEventListener('click', () => openModal($('#disputeModal')));
  $$('.close-modal').forEach((button) => button.addEventListener('click', closeModal));
  backdrop.addEventListener('click', (event) => { if (event.target === backdrop) closeModal(); });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && activeModal) closeModal(); });

  $('#requestForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const type = $('#serviceType').value;
    const row = document.createElement('button');
    row.className = 'request-row';
    row.dataset.status = 'case';
    row.dataset.request = 'FB-4838';
    row.innerHTML = `<span class="request-symbol blue"><i data-lucide="file-search"></i></span><span class="request-info"><strong>${type}</strong><small>#FB-4838 · Vừa tạo</small></span><span class="request-status quote"><i></i> Chưa chọn dịch vụ</span><i class="row-arrow" data-lucide="chevron-right"></i>`;
    $('#requestList').prepend(row);
    window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });
    closeModal();
    event.target.reset();
    showToast('Case đã được tạo. Bạn có thể chọn dịch vụ hoặc bổ sung thông tin sau.');
  });

  $('#disputeForm').addEventListener('submit', (event) => {
    event.preventDefault();
    closeModal();
    showToast('Đã ghi nhận sự cố. Ký quỹ được tạm giữ để đội ngũ xem xét.');
  });

  $('#providerForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const name = $('#providerName').value.trim();
    const service = $('#providerService').value.trim();
    const bio = $('#providerBio').value.trim();
    const rate = Number($('#providerRate').value.replace(/\D/g, ''));
    const initials = name.split(/\s+/).slice(-2).map((part) => part[0]).join('').toUpperCase();
    const safeName = escapeHtml(name);
    const safeService = escapeHtml(service);
    const safeInitials = escapeHtml(initials);
    const card = document.createElement('article');
    card.className = 'provider-card';
    card.dataset.rating = '0';
    card.dataset.bio = bio;
    card.dataset.service = service;
    card.dataset.rate = `Từ ${rate.toLocaleString('vi-VN')} đ`;
    card.dataset.initials = initials;
    card.innerHTML = `<div class="provider-card-head"><span class="avatar avatar-purple">${safeInitials}</span><span class="verified"><i data-lucide="clock-3"></i> Chờ xác minh</span><button class="icon-button favourite" aria-label="Lưu đối tác"><i data-lucide="heart"></i></button></div><h3>${safeName}</h3><p>${safeService}</p><div class="profile-tags"><span>Hồ sơ mới</span><span>Đang chờ duyệt</span></div><div class="provider-stats"><span><i data-lucide="file-text"></i> Chờ phản hồi đầu tiên</span></div><div class="provider-footer"><strong>Từ ${rate.toLocaleString('vi-VN')} đ</strong><button class="outline-button" data-quote="${safeName}">Xem hồ sơ</button></div>`;
    $('#providerList').prepend(card);
    window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });
    event.target.reset();
    closeModal();
    showToast('Hồ sơ đã gửi. Hồ sơ sẽ hiển thị công khai sau khi được xác minh.');
  });

  $('#completeTask').addEventListener('click', () => {
    const status = $('.task-card .request-status');
    status.className = 'request-status progress';
    status.innerHTML = '<i></i> Đang chờ giải ngân';
    $('#completeTask').disabled = true;
    $('#completeTask').innerHTML = '<i data-lucide="check"></i> Đã xác nhận';
    window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });
    showToast('Đã ghi nhận xác nhận. Ký quỹ sẽ được giải ngân theo điều khoản.');
  });

  $('#openEscrow').addEventListener('click', () => showToast('650.000 đ đang được giữ an toàn cho ticket #FB-4829.'));
  $('#showAllRequests').addEventListener('click', () => showToast('Đang hiển thị các yêu cầu trong workspace này.'));
  $('#filterProviders').addEventListener('click', () => {
    const cards = $$('.provider-card');
    const isFiltered = cards.some((card) => card.hidden);
    cards.forEach((card) => { card.hidden = !isFiltered && Number(card.dataset.rating) < 4.9; });
    $('#filterProviders').innerHTML = isFiltered ? '<i data-lucide="sliders-horizontal"></i> Bộ lọc' : '<i data-lucide="x"></i> Xóa lọc';
    window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });
  });

  $$('.request-tabs button').forEach((button) => button.addEventListener('click', () => {
    $$('.request-tabs button').forEach((item) => { item.classList.toggle('selected', item === button); item.setAttribute('aria-selected', String(item === button)); });
    const filter = button.dataset.filter;
    $$('.request-row').forEach((row) => { row.hidden = filter !== 'all' && row.dataset.status !== filter; });
  }));

  document.addEventListener('click', (event) => {
    const request = event.target.closest('.request-row');
    if (request) {
      $$('.request-row').forEach((row) => row.classList.toggle('selected', row === request));
      $('.ticket-code').textContent = `#${request.dataset.request}`;
      $('.task-card h2').textContent = $('.request-info strong', request).textContent;
      showToast(`Đã mở yêu cầu #${request.dataset.request}.`);
    }
    const quote = event.target.closest('[data-quote]');
    if (quote) {
      const card = quote.closest('.provider-card');
      const name = quote.dataset.quote;
      $('#profileTitle').textContent = name;
      $('#profileInitials').textContent = card?.dataset.initials || name.split(/\s+/).slice(-2).map((part) => part[0]).join('').toUpperCase();
      $('#profileService').textContent = card?.dataset.service || $('.provider-card p', card)?.textContent || '';
      $('#profileRate').textContent = card?.dataset.rate || $('.provider-footer strong', card)?.textContent || '';
      $('#profileBio').textContent = card?.dataset.bio || 'Hỗ trợ khách chuẩn bị hồ sơ qua các quy trình chính thức, theo dõi tiến độ trong ticket và bảo vệ giao dịch bằng ký quỹ.';
      $('#createUnlockCaseFromProfile').dataset.provider = name;
      openModal($('#profileModal'));
    }
    const favourite = event.target.closest('.favourite');
    if (favourite) {
      favourite.classList.toggle('saved');
      const icon = $('svg', favourite);
      icon.style.fill = favourite.classList.contains('saved') ? '#d65352' : 'none';
      icon.style.color = favourite.classList.contains('saved') ? '#d65352' : '';
      showToast(favourite.classList.contains('saved') ? 'Đã lưu đối tác.' : 'Đã bỏ lưu đối tác.');
    }
  });

  $('#createUnlockCaseFromProfile').addEventListener('click', (event) => {
    closeModal();
    openModal($('#requestModal'));
    showToast(`Bạn đang tạo case mở khóa để kết nối với ${event.currentTarget.dataset.provider}.`);
  });

  $('#sidebarToggle').addEventListener('click', () => $('#sidebar').classList.toggle('open'));
})();

(() => {
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];
  const backdrop = $('#modalBackdrop');
  const toast = $('#toast');
  let activeModal = null;
  let toastTimer;

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
    row.dataset.status = 'quote';
    row.dataset.request = 'FB-4838';
    row.innerHTML = `<span class="request-symbol blue"><i data-lucide="file-search"></i></span><span class="request-info"><strong>${type}</strong><small>#FB-4838 · Vừa tạo</small></span><span class="request-status quote"><i></i> Đang tìm đối tác</span><i class="row-arrow" data-lucide="chevron-right"></i>`;
    $('#requestList').prepend(row);
    window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });
    closeModal();
    event.target.reset();
    showToast('Yêu cầu đã được tạo. Đối tác phù hợp sẽ gửi báo giá trong ticket.');
  });

  $('#disputeForm').addEventListener('submit', (event) => {
    event.preventDefault();
    closeModal();
    showToast('Đã ghi nhận sự cố. Ký quỹ được tạm giữ để đội ngũ xem xét.');
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
  $('#browseProviders').addEventListener('click', () => showToast('Danh sách đối tác đầy đủ sẽ được mở khi kết nối backend.'));
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
      $('#serviceType').value = 'Không thể truy cập tài khoản';
      openModal($('#requestModal'));
      showToast(`Bạn đang tạo yêu cầu báo giá cho ${quote.dataset.quote}.`);
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

  $('#sidebarToggle').addEventListener('click', () => $('#sidebar').classList.toggle('open'));
})();

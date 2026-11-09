const timeZone = 'Asia/Ho_Chi_Minh';
const dateLabel = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'full', timeZone });
const timeLabel = new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit', timeZone });
const normalize = value => String(value ?? '').trim().toLocaleLowerCase('vi');
const categories = {
  'borrow-approved': { icon: '✓', label: 'Mượn sách', tone: 'success' },
  'borrow-rejected': { icon: '×', label: 'Mượn sách', tone: 'warn' },
  'renewal-approved': { icon: '↗', label: 'Gia hạn', tone: 'success' },
  'renewal-rejected': { icon: '×', label: 'Gia hạn', tone: 'warn' },
  'due-reminder': { icon: '◷', label: 'Nhắc hạn', tone: 'reminder' },
};

function element(tag, className, value) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (value !== undefined) node.textContent = value;
  return node;
}

function notificationCard(item) {
  const kind = categories[item.type] || { icon: '•', label: 'Thông báo', tone: 'neutral' };
  const card = element('article', `notice-item ${item.readAt ? 'is-read' : 'is-unread'}`);
  const icon = element('span', `notice-item-icon ${kind.tone}`, kind.icon);
  icon.setAttribute('aria-hidden', 'true');
  const content = element('div', 'notice-item-content');
  const meta = element('div', 'notice-item-meta');
  meta.append(element('span', `notice-type ${kind.tone}`, kind.label));
  meta.append(element('span', 'notice-time', timeLabel.format(new Date(item.createdAt))));
  if (!item.readAt) meta.append(element('span', 'notice-new', 'Mới'));
  const title = element('h3', '', item.title);
  const message = element('p', 'notice-item-message', item.message);
  const foot = element('div', 'notice-item-foot');
  foot.append(element('span', 'notice-reference', item.reference));
  if (item.readAt) {
    foot.append(element('span', 'notice-read-label', 'Đã đọc'));
  } else {
    const button = element('button', 'notice-read-button', 'Đánh dấu đã đọc');
    button.type = 'button';
    button.dataset.noticeRead = String(item.id);
    button.setAttribute('aria-label', `Đánh dấu đã đọc: ${item.title}`);
    foot.append(button);
  }
  content.append(meta, title, message, foot);
  card.append(icon, content);
  return card;
}

function emptyState(title, description) {
  const wrap = element('div', 'notice-empty');
  const symbol = element('div', 'notice-empty-icon', '✉');
  symbol.setAttribute('aria-hidden', 'true');
  wrap.append(symbol, element('h3', '', title), element('p', '', description));
  return wrap;
}

function loadingState() {
  const wrap = element('div', 'notice-loading');
  wrap.setAttribute('role', 'status');
  wrap.setAttribute('aria-label', 'Đang tải thông báo');
  for (let i = 0; i < 3; i += 1) {
    const row = element('div', 'notice-skeleton-row');
    row.append(element('span', 'notice-skeleton-icon'), element('span', 'notice-skeleton-lines'));
    wrap.append(row);
  }
  return wrap;
}

export function mountNotifications({ container, signal, source, recipientName = 'bạn' }) {
  let items = [];
  let filter = 'all';
  let loaded = false;
  let busy = false;
  let requestId = 0;

  container.innerHTML = `
    <section class="notice-hero" aria-label="Tổng quan thông báo">
      <div class="notice-hero-copy"><span class="notice-hero-kicker">HỘP THƯ CỦA BẠN</span><h2>Luôn theo kịp hoạt động thư viện</h2><p>Các quyết định mượn sách, gia hạn và nhắc hạn được tập hợp tại đây.</p></div>
      <div class="notice-hero-count"><strong data-notice-unread>—</strong><span>thông báo chưa đọc</span></div>
    </section>
    ${source.mode === 'demo' ? '<div class="notice-demo-note" role="note"><strong>Bản xem trước · dữ liệu mẫu</strong><span>Chỉ hiển thị thông báo của tài khoản đang chọn. Các thao tác trên trang không gửi dữ liệu đến máy chủ.</span></div>' : ''}
    <div class="notice-stats" aria-label="Số lượng thông báo">
      <div class="notice-stat"><span>Tất cả</span><strong data-notice-total>—</strong></div>
      <div class="notice-stat"><span>Chưa đọc</span><strong data-notice-unread-stat>—</strong></div>
      <div class="notice-stat"><span>Đã đọc</span><strong data-notice-read>—</strong></div>
    </div>
    <section class="panel notice-panel" aria-labelledby="notice-heading">
      <div class="notice-panel-head"><div><div class="eyebrow">DÒNG HOẠT ĐỘNG</div><h2 id="notice-heading">Hộp thông báo</h2><p data-notice-recipient></p></div><button class="btn outline" type="button" data-notice-all disabled>Đánh dấu tất cả đã đọc</button></div>
      <div class="notice-toolbar">
        <div class="notice-filters" role="group" aria-label="Lọc theo trạng thái">
          <button type="button" data-notice-filter="all" aria-pressed="true">Tất cả <span data-notice-filter-count="all">0</span></button>
          <button type="button" data-notice-filter="unread" aria-pressed="false">Chưa đọc <span data-notice-filter-count="unread">0</span></button>
          <button type="button" data-notice-filter="read" aria-pressed="false">Đã đọc <span data-notice-filter-count="read">0</span></button>
        </div>
        <div class="notice-search-row"><label class="notice-search-label">Tìm thông báo<input class="input" type="search" data-notice-search placeholder="Tìm theo nội dung hoặc mã phiếu"></label><label class="notice-kind-label">Loại thông báo<select class="select" data-notice-kind><option value="all">Tất cả loại</option><option value="borrow">Mượn sách</option><option value="renewal">Gia hạn</option><option value="due">Nhắc hạn</option></select></label></div>
      </div>
      <p class="notice-result-count" data-notice-result role="status"></p>
      <div data-notice-body></div>
      <p class="notice-action-status" data-notice-status role="status" aria-live="polite"></p>
    </section>`;

  const query = selector => container.querySelector(selector);
  const body = query('[data-notice-body]');
  query('[data-notice-recipient]').textContent = `Đang xem thông báo của ${recipientName}.`;

  function render() {
    const unread = items.filter(item => !item.readAt).length;
    query('[data-notice-total]').textContent = String(items.length);
    query('[data-notice-unread]').textContent = String(unread);
    query('[data-notice-unread-stat]').textContent = String(unread);
    query('[data-notice-read]').textContent = String(items.length - unread);
    for (const [key, count] of Object.entries({ all: items.length, unread, read: items.length - unread })) {
      query(`[data-notice-filter-count="${key}"]`).textContent = String(count);
    }
    query('[data-notice-all]').disabled = busy || !loaded || unread === 0;
    const term = normalize(query('[data-notice-search]').value);
    const kind = query('[data-notice-kind]').value;
    const visible = items.filter(item => {
      const statusMatch = filter === 'all' || (filter === 'unread' ? !item.readAt : !!item.readAt);
      const kindMatch = kind === 'all' || item.type.startsWith(kind === 'due' ? 'due-' : `${kind}-`);
      return statusMatch && kindMatch && normalize(`${item.title} ${item.message} ${item.reference}`).includes(term);
    }).sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    query('[data-notice-result]').textContent = `${visible.length} trong ${items.length} thông báo`;
    body.replaceChildren();
    if (!items.length) {
      body.append(emptyState('Chưa có thông báo', 'Khi thư viện cập nhật yêu cầu hoặc hạn trả, thông báo sẽ xuất hiện tại đây.'));
      return;
    }
    if (!visible.length) {
      body.append(emptyState('Không tìm thấy thông báo', 'Thử thay đổi từ khóa hoặc bộ lọc để xem kết quả khác.'));
      return;
    }
    let currentDay = '';
    for (const item of visible) {
      const day = dateLabel.format(new Date(item.createdAt));
      if (day !== currentDay) {
        currentDay = day;
        body.append(element('h3', 'notice-day', day));
      }
      body.append(notificationCard(item));
    }
  }

  async function load() {
    const ticket = ++requestId;
    loaded = false;
    query('[data-notice-all]').disabled = true;
    query('[data-notice-result]').textContent = 'Đang tải thông báo…';
    body.replaceChildren(loadingState());
    try {
      const result = await source.load({ signal });
      if (signal?.aborted || ticket !== requestId) return;
      if (!Array.isArray(result)) throw new Error('Dữ liệu thông báo không đúng định dạng.');
      items = result;
      loaded = true;
      render();
    } catch (error) {
      if (signal?.aborted || ticket !== requestId) return;
      query('[data-notice-result]').textContent = '';
      const state = element('div', 'notice-error');
      state.setAttribute('role', 'alert');
      state.append(element('h3', '', 'Không tải được thông báo'), element('p', '', error.message || 'Đã xảy ra lỗi.'));
      const retry = element('button', 'btn outline', 'Thử lại');
      retry.type = 'button';
      retry.dataset.noticeRetry = '';
      state.append(retry);
      body.replaceChildren(state);
    }
  }

  container.addEventListener('click', async event => {
    const retry = event.target.closest('[data-notice-retry]');
    if (retry) { load(); return; }
    const tab = event.target.closest('[data-notice-filter]');
    if (tab) {
      filter = tab.dataset.noticeFilter;
      container.querySelectorAll('[data-notice-filter]').forEach(button => button.setAttribute('aria-pressed', String(button === tab)));
      if (loaded) render();
      return;
    }
    const read = event.target.closest('[data-notice-read]');
    const all = event.target.closest('[data-notice-all]');
    if ((!read && !all) || busy || !loaded) return;
    busy = true;
    query('[data-notice-all]').disabled = true;
    if (read) read.disabled = true;
    try {
      if (all) {
        items = await source.markAllRead();
        query('[data-notice-status]').textContent = 'Đã đánh dấu tất cả thông báo là đã đọc.';
      } else {
        const updated = await source.markRead(Number(read.dataset.noticeRead));
        items = items.map(item => item.id === updated.id ? updated : item);
        query('[data-notice-status]').textContent = 'Đã đánh dấu thông báo là đã đọc.';
      }
    } catch (error) {
      query('[data-notice-status]').textContent = error.message || 'Không cập nhật được thông báo.';
    } finally {
      busy = false;
      if (!signal?.aborted) render();
    }
  }, { signal });
  container.addEventListener('input', event => { if (event.target.matches('[data-notice-search]') && loaded) render(); }, { signal });
  container.addEventListener('change', event => { if (event.target.matches('[data-notice-kind]') && loaded) render(); }, { signal });
  load();
}

import { demoReportSource } from './data.js';

const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
const norm = value => String(value ?? '').trim().toLocaleLowerCase('vi');
const roles = { student: 'Sinh viên', librarian: 'Thủ thư', staff: 'Nhân viên', admin: 'Quản lý' };
const status = { active: 'Hoạt động', locked: 'Đã khóa' };
const cardStatus = { active: 'Chưa khóa', locked: 'Đã khóa' };

function sourceNotice(source) {
  return source.mode === 'demo' ? '<div class="report-demo" role="note"><strong>Bản xem trước · dữ liệu B1</strong><span>Số liệu trên trang này là dữ liệu mẫu từ schema/seed B1, không phản ánh database đang chạy. Mở route sản phẩm để xem dữ liệu từ API.</span></div>' : '';
}

const stat = (label, value, detail) => `<div class="report-stat"><span>${esc(label)}</span><strong>${esc(value)}</strong><small>${esc(detail)}</small></div>`;
const table = (id, headings) => `<div class="table-wrap"><table><thead><tr>${headings.map(heading => `<th scope="col">${esc(heading)}</th>`).join('')}</tr></thead><tbody id="${id}"></tbody></table></div>`;
const emptyRow = (columns, message) => `<tr><td colspan="${columns}" class="empty">${esc(message)}</td></tr>`;
const badge = (label, tone = '') => `<span class="report-badge ${tone}">${esc(label)}</span>`;

function copyCounts(book) {
  const counts = Object.fromEntries(['available', 'borrowed', 'damaged', 'lost'].map(kind => [kind, book.copies.filter(copy => copy.tinh_trang === kind).length]));
  counts.available = book.copies.filter(copy => copy.tinh_trang === 'available' && copy.dang_luu_hanh && book.dang_luu_hanh).length;
  counts.inactive_available = book.copies.filter(copy => copy.tinh_trang === 'available' && !(copy.dang_luu_hanh && book.dang_luu_hanh)).length;
  return counts;
}

function reportShell({ books, readers, accounts }, source) {
  const copies = books.flatMap(book => book.copies.map(copy => ({ ...copy, book_active: book.dang_luu_hanh })));
  return `${sourceNotice(source)}
    ${books.length + readers.length + accounts.length === 0 ? '<div class="report-demo" role="status"><strong>Chưa có dữ liệu</strong><span>Chưa có sách, độc giả hoặc tài khoản để lập báo cáo.</span></div>' : ''}
    <div class="report-stats" aria-label="Tổng quan báo cáo">
      ${stat('Đầu sách', books.length, 'Danh mục sách giấy')}
      ${stat('Bản khả dụng', copies.filter(copy => copy.tinh_trang === 'available' && copy.dang_luu_hanh && copy.book_active).length, `Trên ${copies.length} bản sách`)}
      ${stat('Độc giả có hồ sơ', readers.length, 'Bảng độc giả và thẻ')}
      ${stat('Tài khoản', accounts.length, 'Tất cả vai trò')}
    </div>
    <section class="panel report-panel" aria-labelledby="report-heading">
      <div class="panel-head"><div><h2 id="report-heading">Báo cáo dữ liệu nền</h2><p class="report-help">Chỉ thống kê sách, độc giả và tài khoản. Phiếu mượn thuộc giai đoạn sau.</p></div></div>
      <div class="report-tabs" role="tablist" aria-label="Loại báo cáo">
        <button type="button" role="tab" id="tab-books" aria-controls="panel-books" aria-selected="true" data-report-tab="books">Sách &amp; tồn kho</button>
        <button type="button" role="tab" id="tab-readers" aria-controls="panel-readers" aria-selected="false" tabindex="-1" data-report-tab="readers">Độc giả</button>
        <button type="button" role="tab" id="tab-accounts" aria-controls="panel-accounts" aria-selected="false" tabindex="-1" data-report-tab="accounts">Tài khoản</button>
      </div>
      <div id="panel-books" role="tabpanel" aria-labelledby="tab-books" data-report-panel="books">
        <div class="report-toolbar"><label>Tìm đầu sách<input class="input" type="search" data-filter="books-search" placeholder="Mã hoặc tên sách"></label><label>Thể loại<select class="select" data-filter="books-category"><option value="">Tất cả</option>${[...new Set(books.map(book => book.the_loai))].map(category => `<option value="${esc(category)}">${esc(category)}</option>`).join('')}</select></label><label>Lưu hành<select class="select" data-filter="books-circulation"><option value="">Tất cả</option><option value="active">Có</option><option value="inactive">Không</option></select></label><label>Tình trạng bản<select class="select" data-filter="books-copy-status"><option value="">Tất cả</option><option value="available">Khả dụng</option><option value="borrowed">Đang mượn</option><option value="damaged">Hỏng</option><option value="lost">Mất</option></select></label></div>
        <p class="report-count" id="books-count" role="status"></p>
        <div class="report-breakdown" id="books-breakdown" aria-label="Tình trạng bản sách"></div>
        ${table('books-rows', ['Mã sách', 'Đầu sách', 'Thể loại', 'Lưu hành', 'Tổng bản', 'Khả dụng', 'Sẵn có, ngừng lưu hành', 'Đang mượn', 'Hỏng', 'Mất'])}
      </div>
      <div id="panel-readers" role="tabpanel" aria-labelledby="tab-readers" data-report-panel="readers" hidden>
        <div class="report-toolbar"><label>Tìm độc giả<input class="input" type="search" data-filter="readers-search" placeholder="Họ tên, email, MSSV hoặc mã thẻ"></label><label>Trạng thái thẻ<select class="select" data-filter="readers-status"><option value="">Tất cả</option><option value="active">Chưa khóa</option><option value="locked">Đã khóa</option><option value="none">Chưa có thẻ</option></select></label></div>
        <p class="report-count" id="readers-count" role="status"></p>
        <div class="report-breakdown" id="readers-breakdown" aria-label="Trạng thái thẻ"></div>
        ${table('readers-rows', ['Mã thẻ', 'Độc giả', 'Email', 'MSSV', 'Hết hạn', 'Trạng thái thẻ'])}
      </div>
      <div id="panel-accounts" role="tabpanel" aria-labelledby="tab-accounts" data-report-panel="accounts" hidden>
        <div class="report-toolbar"><label>Tìm tài khoản<input class="input" type="search" data-filter="accounts-search" placeholder="Email hoặc MSSV"></label><label>Vai trò<select class="select" data-filter="accounts-role"><option value="">Tất cả</option>${Object.entries(roles).map(([key, name]) => `<option value="${key}">${name}</option>`).join('')}</select></label><label>Trạng thái<select class="select" data-filter="accounts-status"><option value="">Tất cả</option><option value="active">Hoạt động</option><option value="locked">Đã khóa</option></select></label></div>
        <p class="report-count" id="accounts-count" role="status"></p>
        <div class="report-breakdown" id="accounts-breakdown" aria-label="Vai trò tài khoản"></div>
        ${table('accounts-rows', ['Email', 'MSSV', 'Vai trò', 'Trạng thái'])}
      </div>
    </section>`;
}

function breakdown(parts) {
  const total = parts.reduce((sum, [, count]) => sum + count, 0);
  return parts.map(([label, count]) => `<div class="report-breakdown-item"><div><span>${esc(label)}</span><strong>${count}</strong></div><div class="report-bar" aria-hidden="true"><span style="width:${total ? Math.round(count * 100 / total) : 0}%"></span></div></div>`).join('');
}

function loadingView(source, label) {
  return `${sourceNotice(source)}<section class="panel report-state" role="status" aria-live="polite"><h2>Đang tải ${esc(label)}…</h2><div class="report-skeleton"></div><div class="report-skeleton short"></div></section>`;
}

function errorView(source, label, error) {
  return `${sourceNotice(source)}<section class="panel report-state" role="alert"><h2>Không tải được ${esc(label)}</h2><p>${esc(error?.message || 'Đã xảy ra lỗi khi lấy dữ liệu.')}</p><button class="btn outline" type="button" data-report-retry>Thử lại</button></section>`;
}

export function mountReports({ container, signal, source = demoReportSource }) {
  let data = null, requestId = 0;
  const value = name => container.querySelector(`[data-filter="${name}"]`)?.value ?? '';
  const update = () => {
    if (!data) return;
    const books = data.books.filter(book => {
      const condition = value('books-copy-status');
      const circulation = value('books-circulation');
      const c = copyCounts(book);
      return (!value('books-category') || book.the_loai === value('books-category'))
        && (!circulation || book.dang_luu_hanh === (circulation === 'active'))
        && (!condition || c[condition] > 0)
        && norm(`${book.ma_sach} ${book.ten_sach}`).includes(norm(value('books-search')));
    });
    container.querySelector('#books-count').textContent = `${books.length} / ${data.books.length} đầu sách`;
    container.querySelector('#books-rows').innerHTML = books.map(book => {
      const c = copyCounts(book);
        return `<tr><td><strong>${esc(book.ma_sach)}</strong></td><td>${esc(book.ten_sach)}</td><td>${esc(book.the_loai)}</td><td>${badge(book.dang_luu_hanh ? 'Có' : 'Không', book.dang_luu_hanh ? '' : 'muted')}</td><td>${book.copies.length}</td><td><strong>${c.available}</strong></td><td>${c.inactive_available}</td><td>${c.borrowed}</td><td>${c.damaged}</td><td>${c.lost}</td></tr>`;
    }).join('') || emptyRow(10, data.books.length ? 'Không có đầu sách phù hợp bộ lọc.' : 'Chưa có dữ liệu đầu sách.');
    container.querySelector('#books-breakdown').innerHTML = breakdown([
      ['Khả dụng', books.reduce((sum, book) => sum + copyCounts(book).available, 0)],
      ['Sẵn có, ngừng lưu hành', books.reduce((sum, book) => sum + copyCounts(book).inactive_available, 0)],
      ['Đang mượn', books.reduce((sum, book) => sum + copyCounts(book).borrowed, 0)],
      ['Hỏng', books.reduce((sum, book) => sum + copyCounts(book).damaged, 0)],
      ['Mất', books.reduce((sum, book) => sum + copyCounts(book).lost, 0)],
    ]);

    const readers = data.readers.filter(reader => (!value('readers-status') || (value('readers-status') === 'none' ? reader.trang_thai === null : reader.trang_thai === value('readers-status'))) && norm(`${reader.ma_the} ${reader.ho_ten} ${reader.email} ${reader.mssv}`).includes(norm(value('readers-search'))));
    container.querySelector('#readers-count').textContent = `${readers.length} / ${data.readers.length} độc giả`;
    container.querySelector('#readers-rows').innerHTML = readers.map(reader => `<tr><td><strong>${esc(reader.ma_the || '—')}</strong></td><td>${esc(reader.ho_ten)}</td><td>${esc(reader.email)}</td><td>${esc(reader.mssv || '—')}</td><td>${esc(reader.ngay_het_han || '—')}</td><td>${badge(reader.trang_thai === null ? 'Chưa có thẻ' : cardStatus[reader.trang_thai] || reader.trang_thai, reader.trang_thai === 'active' ? '' : 'warn')}</td></tr>`).join('') || emptyRow(6, data.readers.length ? 'Không có độc giả phù hợp bộ lọc.' : 'Chưa có dữ liệu độc giả.');
    container.querySelector('#readers-breakdown').innerHTML = breakdown([['Thẻ chưa khóa', readers.filter(reader => reader.trang_thai === 'active').length], ['Thẻ đã khóa', readers.filter(reader => reader.trang_thai === 'locked').length], ['Chưa có thẻ', readers.filter(reader => reader.trang_thai === null).length]]);

    const accounts = data.accounts.filter(account => (!value('accounts-role') || account.vai_tro === value('accounts-role')) && (!value('accounts-status') || account.trang_thai === value('accounts-status')) && norm(`${account.email} ${account.mssv}`).includes(norm(value('accounts-search'))));
    container.querySelector('#accounts-count').textContent = `${accounts.length} / ${data.accounts.length} tài khoản`;
    container.querySelector('#accounts-rows').innerHTML = accounts.map(account => `<tr><td><strong>${esc(account.email)}</strong></td><td>${esc(account.mssv || '—')}</td><td>${esc(roles[account.vai_tro] || account.vai_tro)}</td><td>${badge(status[account.trang_thai] || account.trang_thai, account.trang_thai === 'active' ? '' : 'warn')}</td></tr>`).join('') || emptyRow(4, data.accounts.length ? 'Không có tài khoản phù hợp bộ lọc.' : 'Chưa có dữ liệu tài khoản.');
    container.querySelector('#accounts-breakdown').innerHTML = breakdown(Object.entries(roles).map(([key, label]) => [label, accounts.filter(account => account.vai_tro === key).length]));
  };
  const activate = name => {
    container.querySelectorAll('[data-report-tab]').forEach(tab => {
      const selected = tab.dataset.reportTab === name;
      tab.setAttribute('aria-selected', String(selected)); tab.tabIndex = selected ? 0 : -1;
    });
    container.querySelectorAll('[data-report-panel]').forEach(panel => { panel.hidden = panel.dataset.reportPanel !== name; });
  };
  const load = async () => {
    const ticket = ++requestId; data = null;
    container.innerHTML = loadingView(source, 'báo cáo');
    try {
      const next = await source.loadReports({ signal });
      if (signal.aborted || ticket !== requestId) return;
      if (!next || !['books', 'readers', 'accounts'].every(key => Array.isArray(next[key]))) throw new Error('Dữ liệu báo cáo không đúng định dạng.');
      data = next; container.innerHTML = reportShell(data, source); update();
    } catch (error) { if (!signal.aborted && ticket === requestId) container.innerHTML = errorView(source, 'báo cáo', error); }
  };
  container.addEventListener('input', event => { if (event.target.matches('[data-filter]')) update(); }, { signal });
  container.addEventListener('change', event => { if (event.target.matches('[data-filter]')) update(); }, { signal });
  container.addEventListener('click', event => {
    if (event.target.closest('[data-report-retry]')) { load(); return; }
    const tab = event.target.closest('[data-report-tab]');
    if (tab && container.contains(tab)) activate(tab.dataset.reportTab);
  }, { signal });
  container.addEventListener('keydown', event => {
    if (!event.target.matches('[data-report-tab]') || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    const tabs = [...container.querySelectorAll('[data-report-tab]')];
    const index = tabs.indexOf(event.target);
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    event.preventDefault(); activate(tabs[next].dataset.reportTab); tabs[next].focus();
  }, { signal });
  load();
}

const policyGroups = [
  { title: 'Mượn sách giấy', fields: [
    ['borrow_per_request', 'Tối đa mỗi lần mượn', 1, 3, 'cuốn'],
    ['borrow_total', 'Tối đa đang mượn', 1, 7, 'cuốn'],
    ['borrow_days', 'Thời hạn mượn', 1, 14, 'ngày'],
  ] },
  { title: 'Gia hạn', fields: [
    ['renewal_limit', 'Số lần gia hạn tối đa', 0, 2, 'lần'],
    ['renewal_days', 'Số ngày thêm mỗi lần', 1, 7, 'ngày'],
    ['renewal_overdue_days', 'Quá hạn tối đa được xét', 0, 1, 'ngày'],
  ] },
  { title: 'eBook và nhắc hạn', fields: [
    ['ebook_per_request', 'eBook mỗi yêu cầu', 1, 3, 'cuốn'],
    ['ebook_days', 'Thời hạn quyền eBook', 1, 14, 'ngày'],
    ['notice_days_before_due', 'Nhắc trước hạn', 0, 30, 'ngày'],
  ] },
];
const policyNames = Object.fromEntries(policyGroups.flatMap(group => group.fields.map(([key, label]) => [key, label])));
policyNames.notice_hour_local = 'Giờ nhắc hạn';

function settingsShell(policies, source) {
  return `${sourceNotice(source)}<section class="panel report-panel" aria-labelledby="settings-heading">
    <div class="panel-head"><div><h2 id="settings-heading">Form chính sách nghiệp vụ</h2><p class="report-help">${source.mode === 'demo' ? 'Giá trị mẫu B1.' : 'Giá trị hiện tại đọc từ database.'} Giới hạn nhập theo đề xuất TV4; xem trước không cập nhật database.</p></div></div>
    <form id="report-settings-form">
      <div class="report-policy-grid">${policyGroups.map(group => `<fieldset class="report-policy-group"><legend>${esc(group.title)}</legend>${group.fields.map(([key, label, min, max, unit]) => `<label class="report-policy-field"><span>${esc(label)} <small>(${esc(unit)})</small></span><input class="input" type="number" name="${key}" min="${min}" max="${max}" step="1" value="${esc(policies[key])}" required><small>Cho phép ${min}–${max} ${esc(unit)}</small></label>`).join('')}${group.title === 'eBook và nhắc hạn' ? `<label class="report-policy-field"><span>Giờ nhắc hạn <small>(Asia/Ho_Chi_Minh)</small></span><input class="input" type="time" name="notice_hour_local" value="${esc(policies.notice_hour_local)}" required></label>` : ''}</fieldset>`).join('')}</div>
      <div class="report-fixed"><h3>Quy tắc đã chốt trong B1</h3><p>Phạt quá hạn: <strong>${Number(policies.overdue_daily_vnd).toLocaleString('vi-VN')} VNĐ/cuốn/ngày</strong> · Thẻ: <strong>${esc(policies.card_duration === '12_months' ? '12 tháng' : policies.card_duration)}</strong> · Phụ phí mất/hỏng: <strong>${esc(policies.damage_surcharge)} VNĐ</strong> · Múi giờ: <strong>${esc(policies.business_timezone)}</strong></p></div>
      <p class="report-form-error" id="settings-error" role="alert" hidden></p>
      <div class="report-actions"><button class="btn" type="submit">Xem trước thay đổi</button><button class="btn outline" type="reset">Khôi phục giá trị hiện tại</button></div>
    </form><div id="settings-preview" class="report-preview" role="status" hidden></div>
  </section>`;
}

export function mountSettings({ container, signal, source = demoReportSource }) {
  let policies = null, pending = null, requestId = 0;
  const clear = () => {
    pending = null;
    const error = container.querySelector('#settings-error');
    const preview = container.querySelector('#settings-preview');
    if (error) { error.hidden = true; error.textContent = ''; }
    if (preview) { preview.hidden = true; preview.replaceChildren(); }
  };
  const load = async () => {
    const ticket = ++requestId; policies = null;
    container.innerHTML = loadingView(source, 'chính sách');
    try {
      const next = await source.loadSettings({ signal });
      if (signal.aborted || ticket !== requestId) return;
      if (!next || typeof next !== 'object' || Array.isArray(next)) throw new Error('Dữ liệu chính sách không đúng định dạng.');
      if (Object.keys(next).length === 0) {
        container.innerHTML = `${sourceNotice(source)}<section class="panel report-state" role="status"><h2>Chưa có chính sách</h2><p>Không có giá trị chính sách để hiển thị hoặc chỉnh sửa.</p><button class="btn outline" type="button" data-report-retry>Thử lại</button></section>`;
        return;
      }
      if (!Object.keys(policyNames).every(key => Object.hasOwn(next, key))) throw new Error('Dữ liệu chính sách thiếu trường bắt buộc.');
      policies = next; container.innerHTML = settingsShell(policies, source);
    } catch (error) { if (!signal.aborted && ticket === requestId) container.innerHTML = errorView(source, 'chính sách', error); }
  };
  container.addEventListener('click', async event => {
    if (event.target.closest('[data-report-retry]')) { load(); return; }
    const button = event.target.closest('[data-save-settings]');
    if (!button || !pending || !source.saveSettings || button.disabled) return;
    const changesToSave = { ...pending };
    button.disabled = true; button.textContent = 'Đang lưu…';
    const error = container.querySelector('#settings-error');
    try {
      await source.saveSettings({ changes: changesToSave, signal });
      if (signal.aborted) return;
      policies = { ...policies, ...changesToSave }; pending = null;
      container.innerHTML = settingsShell(policies, source);
      const success = document.createElement('p');
      success.className = 'report-save-success'; success.setAttribute('role', 'status');
      success.textContent = 'Đã lưu chính sách vào hệ thống.';
      container.querySelector('#report-settings-form').after(success);
    } catch (saveError) {
      if (signal.aborted) return;
      error.textContent = saveError?.message || 'Không lưu được chính sách. Hãy thử lại.';
      error.hidden = false; button.disabled = false; button.textContent = 'Lưu chính sách';
    }
  }, { signal });
  container.addEventListener('input', event => { if (event.target.closest('#report-settings-form')) clear(); }, { signal });
  container.addEventListener('reset', event => { if (event.target.id === 'report-settings-form') clear(); }, { signal });
  container.addEventListener('submit', event => {
    if (event.target.id !== 'report-settings-form' || !policies) return;
    event.preventDefault(); clear();
    const form = event.target;
    if (!form.reportValidity()) return;
    const raw = new FormData(form);
    const next = Object.fromEntries(Object.keys(policyNames).map(key => [key, key === 'notice_hour_local' ? raw.get(key) : Number(raw.get(key))]));
    const error = container.querySelector('#settings-error');
    if (next.borrow_total < next.borrow_per_request) {
      error.textContent = 'Tổng số cuốn đang mượn phải lớn hơn hoặc bằng số cuốn mỗi lần mượn.';
      error.hidden = false; form.elements.borrow_total.focus(); return;
    }
    const changes = Object.keys(next).filter(key => next[key] !== (key === 'notice_hour_local' ? policies[key] : Number(policies[key])));
    pending = Object.fromEntries(changes.map(key => [key, next[key]]));
    const preview = container.querySelector('#settings-preview');
    preview.innerHTML = `<strong>${changes.length ? `${changes.length} thay đổi trong bản xem trước` : 'Chưa có thay đổi'}</strong><p>${source.saveSettings ? 'Kiểm tra các thay đổi trước khi lưu vào hệ thống.' : 'Đây là bản xem trước trên trình duyệt. Chưa lưu vào database vì B2 chung chưa duyệt các khóa được phép sửa.'}</p>${changes.length ? `<ul>${changes.map(key => `<li>${esc(policyNames[key])}: <del>${esc(policies[key])}</del> → <strong>${esc(next[key])}</strong></li>`).join('')}</ul>` : ''}${changes.length && source.saveSettings ? '<button class="btn" type="button" data-save-settings>Lưu chính sách</button>' : ''}`;
    preview.hidden = false;
  }, { signal });
  load();
}

import { request } from '../shared/api.js';
import { refreshSession } from '../shared/session.js';

const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const roles = { student: 'Sinh viên', librarian: 'Thủ thư', staff: 'Nhân viên', admin: 'Quản lý' };
const field = (label, name, value = '', options = '') => `<label class="field">${label}<input class="input" name="${name}" value="${esc(value)}" ${options}></label>`;
const select = (label, name, value, options) => `<label class="field">${label}<select class="select" name="${name}">${options.map(([key, text]) => `<option value="${key}" ${value === key ? 'selected' : ''}>${text}</option>`).join('')}</select></label>`;
const roleOptions = Object.entries(roles);
const errorText = error => error?.message || 'Không thực hiện được thao tác.';

function dialog(container, title, content) {
  const modal = document.createElement('dialog');
  modal.className = 'modal modal-backdrop';
  modal.innerHTML = `<h2>${esc(title)}</h2><p class="account-error" role="alert" hidden></p>${content}<button class="btn outline" type="button" data-dialog-close>Đóng</button>`;
  container.append(modal);
  modal.addEventListener('close', () => modal.remove(), { once: true });
  modal.showModal();
  return modal;
}

function accountForm(account) {
  const isNew = !account;
  return `<form id="live-account-form" data-id="${esc(account?.tai_khoan_id ?? '')}" novalidate>${field('Họ tên', 'ho_ten', account?.ho_ten, 'required maxlength="150"')}${field('Email', 'email', account?.email, 'type="email" required maxlength="191"')}${field('MSSV (bắt buộc với sinh viên)', 'mssv', account?.mssv, 'maxlength="32"')}${field('Số điện thoại', 'dien_thoai', account?.dien_thoai, 'type="tel" maxlength="20"')}${select('Vai trò', 'vai_tro', account?.vai_tro ?? 'student', roleOptions)}${select('Trạng thái', 'trang_thai', account?.trang_thai ?? 'active', [['active', 'Hoạt động'], ['locked', 'Khóa']])}${field(isNew ? 'Mật khẩu' : 'Mật khẩu mới (để trống để giữ nguyên)', 'password', '', `type="password" autocomplete="new-password" ${isNew ? 'required' : ''} minlength="8"`)}${field('Nhập lại mật khẩu', 'confirm_password', '', 'type="password" autocomplete="new-password"')}<button class="btn" type="submit">Lưu tài khoản</button></form>`;
}

function accountTable(data) {
  const rows = data.items.map(a => `<tr><td>${a.tai_khoan_id}</td><td>${esc(a.ho_ten || '—')}</td><td>${esc(a.email)}</td><td>${esc(a.mssv || '—')}</td><td>${esc(roles[a.vai_tro])}</td><td>${a.trang_thai === 'active' ? 'Hoạt động' : 'Khóa'}</td><td><button class="btn outline" type="button" data-account-id="${a.tai_khoan_id}">Sửa</button></td></tr>`).join('');
  const pages = Math.max(1, Math.ceil(data.total / data.per_page));
  return `<section class="panel account-panel"><div class="panel-head"><h3>Tài khoản (${data.total})</h3><button class="btn" type="button" data-account-add>+ Thêm tài khoản</button></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>Họ tên</th><th>Email</th><th>MSSV</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>${rows || '<tr><td colspan="7" class="empty">Chưa có tài khoản.</td></tr>'}</tbody></table></div><p>Trang ${data.page}/${pages} <button class="btn outline" type="button" data-page="previous" ${data.page <= 1 ? 'disabled' : ''}>Trước</button> <button class="btn outline" type="button" data-page="next" ${data.page >= pages ? 'disabled' : ''}>Sau</button></p></section>`;
}

export function mountLiveAccounts({ container, signal }) {
  let page = 1, data = null;
  const load = async () => {
    container.innerHTML = '<p role="status">Đang tải tài khoản…</p>';
    try {
      const result = await request(`/accounts?page=${page}`, { signal });
      if (signal.aborted) return;
      data = { items: result.data, ...result.meta };
      container.innerHTML = accountTable(data);
    } catch (error) { if (!signal.aborted) container.innerHTML = `<p role="alert">${esc(errorText(error))}</p>`; }
  };
  container.addEventListener('click', event => {
    const add = event.target.closest('[data-account-add]');
    const edit = event.target.closest('[data-account-id]');
    const pager = event.target.closest('[data-page]');
    const close = event.target.closest('[data-dialog-close]');
    if (add) dialog(container, 'Thêm tài khoản', accountForm(null));
    if (edit) {
      const account = data?.items.find(a => a.tai_khoan_id === Number(edit.dataset.accountId));
      if (account) dialog(container, 'Cập nhật tài khoản', accountForm(account));
    }
    if (pager) { page += pager.dataset.page === 'next' ? 1 : -1; load(); }
    if (close) close.closest('dialog')?.close();
  }, { signal });
  container.addEventListener('submit', async event => {
    if (event.target.id !== 'live-account-form') return;
    event.preventDefault();
    const form = event.target, modal = form.closest('dialog');
    const values = Object.fromEntries(new FormData(form));
    const errorBox = modal.querySelector('[role="alert"]');
    const button = form.querySelector('[type="submit"]');
    errorBox.hidden = true; button.disabled = true;
    try {
      if (values.password !== values.confirm_password) throw new Error('Mật khẩu nhập lại không khớp.');
      delete values.confirm_password;
      if (form.dataset.id && !values.password) delete values.password;
      await request(form.dataset.id ? `/accounts/${form.dataset.id}` : '/accounts', { method: form.dataset.id ? 'PATCH' : 'POST', body: values, signal });
      if (signal.aborted) return;
      modal.close(); await load();
    } catch (error) { if (!signal.aborted && form.isConnected) { errorBox.textContent = errorText(error); errorBox.hidden = false; } }
    finally { if (!signal.aborted && form.isConnected) button.disabled = false; }
  }, { signal });
  load();
  return () => {};
}

function employeeForm(employee) {
  const isNew = !employee;
  return `<form id="live-employee-form" data-id="${esc(employee?.nhan_vien_id ?? '')}" novalidate>${isNew ? field('ID tài khoản có sẵn (để trống để tạo mới)', 'tai_khoan_id', '', 'inputmode="numeric"') : ''}${field('Mã nhân viên', 'ma_nhan_vien', employee?.ma_nhan_vien, 'required maxlength="32"')}${field('Họ tên', 'ho_ten', employee?.ho_ten, 'required maxlength="150"')}${field('Email', 'email', employee?.email, 'type="email" required maxlength="191"')}${field('Số điện thoại', 'dien_thoai', employee?.dien_thoai, 'type="tel" maxlength="20"')}${field('Chức vụ', 'chuc_vu', employee?.chuc_vu, 'required maxlength="80"')}${select('Vai trò', 'vai_tro', employee?.vai_tro ?? 'staff', roleOptions.filter(([key]) => key !== 'student'))}${select('Trạng thái làm việc', 'trang_thai', employee?.trang_thai ?? 'active', [['active', 'Đang làm việc'], ['inactive', 'Ngừng hoạt động']])}${isNew ? field('Mật khẩu (khi tạo tài khoản mới)', 'password', '', 'type="password" autocomplete="new-password" minlength="8"') + field('Nhập lại mật khẩu', 'confirm_password', '', 'type="password" autocomplete="new-password"') : ''}<button class="btn" type="submit">Lưu nhân viên</button></form>`;
}

function employeeTable(items) {
  const rows = items.map(e => `<tr><td>${esc(e.ma_nhan_vien)}</td><td>${esc(e.ho_ten)}</td><td>${esc(e.email)}</td><td>${esc(e.dien_thoai || '—')}</td><td>${esc(e.chuc_vu || '—')}</td><td>${e.trang_thai === 'active' ? 'Đang làm việc' : 'Ngừng hoạt động'}</td><td><button class="btn outline" type="button" data-employee-id="${e.nhan_vien_id}">Sửa</button></td></tr>`).join('');
  return `<section class="panel employee-panel"><div class="panel-head"><h3>Nhân viên (${items.length})</h3><button class="btn" type="button" data-employee-add>+ Thêm nhân viên</button></div><div class="table-wrap"><table><thead><tr><th>Mã</th><th>Họ tên</th><th>Email</th><th>Điện thoại</th><th>Chức vụ</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>${rows || '<tr><td colspan="7" class="empty">Chưa có nhân viên.</td></tr>'}</tbody></table></div></section>`;
}

export function mountLiveEmployees({ container, signal }) {
  let items = [];
  const load = async () => {
    container.innerHTML = '<p role="status">Đang tải nhân viên…</p>';
    try {
      const result = await request('/employees', { signal });
      if (signal.aborted) return;
      items = result.data;
      container.innerHTML = employeeTable(items);
    } catch (error) { if (!signal.aborted) container.innerHTML = `<p role="alert">${esc(errorText(error))}</p>`; }
  };
  container.addEventListener('click', event => {
    const add = event.target.closest('[data-employee-add]');
    const edit = event.target.closest('[data-employee-id]');
    const close = event.target.closest('[data-dialog-close]');
    if (add) dialog(container, 'Thêm nhân viên', employeeForm(null));
    if (edit) {
      const employee = items.find(item => item.nhan_vien_id === Number(edit.dataset.employeeId));
      if (employee) dialog(container, 'Cập nhật nhân viên', employeeForm(employee));
    }
    if (close) close.closest('dialog')?.close();
  }, { signal });
  container.addEventListener('submit', async event => {
    if (event.target.id !== 'live-employee-form') return;
    event.preventDefault();
    const form = event.target, modal = form.closest('dialog');
    const values = Object.fromEntries(new FormData(form));
    const errorBox = modal.querySelector('[role="alert"]');
    const button = form.querySelector('[type="submit"]');
    errorBox.hidden = true; button.disabled = true;
    try {
      if (values.password !== values.confirm_password) throw new Error('Mật khẩu nhập lại không khớp.');
      delete values.confirm_password;
      if (values.tai_khoan_id && values.password) throw new Error('Không nhập mật khẩu khi liên kết tài khoản có sẵn.');
      if (!values.tai_khoan_id) delete values.tai_khoan_id;
      await request(form.dataset.id ? `/employees/${form.dataset.id}` : '/employees', { method: form.dataset.id ? 'PATCH' : 'POST', body: values, signal });
      if (signal.aborted) return;
      modal.close(); await load();
    } catch (error) { if (!signal.aborted && form.isConnected) { errorBox.textContent = errorText(error); errorBox.hidden = false; } }
    finally { if (!signal.aborted && form.isConnected) button.disabled = false; }
  }, { signal });
  load();
  return () => {};
}

export function mountLivePassword({ container, signal }) {
  container.innerHTML = `<section class="panel"><h3>Đổi mật khẩu</h3><form id="live-password-form" novalidate><p role="alert" hidden></p><p role="status"></p>${field('Mật khẩu hiện tại', 'current_password', '', 'type="password" autocomplete="current-password" required')}${field('Mật khẩu mới', 'new_password', '', 'type="password" autocomplete="new-password" minlength="8" required')}${field('Nhập lại mật khẩu mới', 'confirm_password', '', 'type="password" autocomplete="new-password" required')}<button class="btn" type="submit">Lưu mật khẩu</button></form></section>`;
  container.addEventListener('submit', async event => {
    if (event.target.id !== 'live-password-form') return;
    event.preventDefault();
    const form = event.target, values = Object.fromEntries(new FormData(form));
    const button = form.querySelector('[type="submit"]'), errorBox = form.querySelector('[role="alert"]'), status = form.querySelector('[role="status"]');
    button.disabled = true; errorBox.hidden = true; status.textContent = '';
    try {
      if (values.new_password !== values.confirm_password) throw new Error('Mật khẩu nhập lại không khớp.');
      delete values.confirm_password;
      await request('/accounts/me/password', { method: 'PATCH', body: values, signal });
      if (signal.aborted) return;
      await refreshSession();
      form.reset(); status.textContent = 'Đã đổi mật khẩu.';
    } catch (error) { if (!signal.aborted) { errorBox.textContent = errorText(error); errorBox.hidden = false; } }
    finally { if (!signal.aborted) button.disabled = false; }
  }, { signal });
  return () => {};
}

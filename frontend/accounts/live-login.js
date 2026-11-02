import { request } from '../shared/api.js';
import { refreshSession } from '../shared/session.js';

const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

export function mountLiveLogin({ container, signal, onLogin }) {
  let view = 'login';
  let notice = '';
  const render = () => {
    if (signal.aborted) return;
    container.innerHTML = `<div class="login-wrap"><div class="login-art"><div><div class="auth-logo">UniLib<span>.</span></div><h1>Tri thức mở ra tương lai.</h1><p>Thư viện số dành cho cộng đồng đại học hiện đại.</p><div class="auth-book">📚</div></div></div><div class="login-form"><div class="login-card"><div class="eyebrow">${view === 'login' ? 'ĐĂNG NHẬP' : 'TẠO TÀI KHOẢN SINH VIÊN'}</div><h2>${view === 'login' ? 'Đăng nhập UniLib' : 'Đăng ký UniLib'}</h2><p class="sub">${view === 'login' ? 'Truy cập không gian học tập của bạn' : 'Tạo tài khoản để sử dụng thư viện điện tử.'}</p>${notice ? `<p role="status">${esc(notice)}</p>` : ''}<form id="live-auth-form" novalidate><p class="auth-error" role="alert" hidden></p>${view === 'register' ? `<label class="field">Họ tên<input class="input" name="ho_ten" autocomplete="name" maxlength="150" required></label><label class="field">MSSV<input class="input" name="mssv" maxlength="32" required></label><label class="field">Email<input class="input" type="email" name="email" autocomplete="email" maxlength="191" required></label><label class="field">Số điện thoại<input class="input" type="tel" name="dien_thoai" autocomplete="tel" placeholder="0xxxxxxxxx hoặc +84xxxxxxxxx"></label><label class="field">Mật khẩu<input class="input" type="password" name="password" autocomplete="new-password" minlength="8" required></label><label class="field">Nhập lại mật khẩu<input class="input" type="password" name="confirm_password" autocomplete="new-password" required></label>` : `<label class="field">Email hoặc MSSV<input class="input" name="identifier" autocomplete="username" required></label><label class="field">Mật khẩu<input class="input" type="password" name="password" autocomplete="current-password" required></label>`}<button class="btn auth-submit" type="submit">${view === 'login' ? 'Đăng nhập →' : 'Tạo tài khoản'}</button></form><p class="auth-switch">${view === 'login' ? 'Chưa có tài khoản sinh viên? <a href="#register" data-auth-view="register">Đăng ký</a>' : 'Đã có tài khoản? <a href="#login" data-auth-view="login">Đăng nhập</a>'}</p>${view === 'register' ? '<p class="auth-note">Sau đăng ký, hồ sơ độc giả và thẻ được bổ sung riêng trước khi mượn sách.</p>' : ''}</div></div></div>`;
  };
  container.addEventListener('click', event => {
    const link = event.target.closest('[data-auth-view]');
    if (!link) return;
    event.preventDefault(); view = link.dataset.authView; notice = ''; render();
  }, { signal });
  container.addEventListener('submit', async event => {
    if (event.target.id !== 'live-auth-form') return;
    event.preventDefault();
    const form = event.target;
    const values = Object.fromEntries(new FormData(form));
    const button = form.querySelector('[type="submit"]');
    const errorBox = form.querySelector('.auth-error');
    button.disabled = true; errorBox.hidden = true;
    try {
      if (view === 'register') {
        if (values.password !== values.confirm_password) throw new Error('Mật khẩu nhập lại không khớp.');
        delete values.confirm_password;
        await request('/accounts/register', { method: 'POST', body: values, signal });
        if (signal.aborted) return;
        view = 'login'; notice = 'Đã tạo tài khoản. Bạn có thể đăng nhập; hồ sơ và thẻ sẽ được bổ sung sau.'; render();
      } else {
        await request('/accounts/login', { method: 'POST', body: values, signal });
        if (signal.aborted) return;
        await refreshSession();
        if (!signal.aborted) onLogin();
      }
    } catch (error) {
      if (!signal.aborted && form.isConnected) { errorBox.textContent = error.message; errorBox.hidden = false; }
    } finally { if (!signal.aborted && form.isConnected) button.disabled = false; }
  }, { signal });
  render();
  return () => {};
}

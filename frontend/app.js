// TV1: router hash; mount nhận signal và có thể trả cleanup đồng bộ/bất đồng bộ.
import { mountLayout, updateMenu, setPageTitle } from './shared/layout.js';
import { refreshSession, canAccess } from './shared/session.js';
import { request } from './shared/api.js';
import { showState, toast } from './shared/ui.js';
import { mount as mountHome } from './shared/home.js';
import { routes as bookRoutes } from './books/routes.js';
import { routes as ebookRoutes } from './ebooks/routes.js';
import { routes as weekOneRoutes } from './week1/routes.js';
import { routes as accountRoutes } from './accounts/routes.js';
import { mountLiveLogin } from './accounts/live-login.js';
const routes = [{ path: '/', title: 'Tổng quan', icon: '◫', mount: mountHome }, ...bookRoutes, ...ebookRoutes, ...accountRoutes.slice(1), ...weekOneRoutes];
const root = document.getElementById('app');
let activeController, cleanup, shellMounted = false;
let navigation = 0;
async function navigate() {
  const ticket = ++navigation;
  activeController?.abort(); cleanup?.(); cleanup = null;
  const controller = new AbortController(); activeController = controller;
  let user;
  try { user = await refreshSession(); }
  catch (error) {
    if (ticket !== navigation) return;
    if (error.status !== 401) { root.textContent = error.message; return; }
    user = null;
  }
  if (ticket !== navigation || controller.signal.aborted) return;
  if (!user) {
    shellMounted = false;
    root.className = 'login-page';
    document.title = 'Đăng nhập | UniLib';
    history.replaceState(null, '', '#/login');
    cleanup = mountLiveLogin({ container: root, signal: controller.signal, onLogin: () => {
      history.replaceState(null, '', '#/'); navigate();
    } });
    return;
  }
  if (!shellMounted) {
    root.className = '';
    mountLayout(root); shellMounted = true;
    const logout = document.createElement('button');
    logout.className = 'btn outline'; logout.textContent = 'Đăng xuất';
    logout.addEventListener('click', async () => {
      logout.disabled = true;
      try { await request('/accounts/logout', { method: 'POST' }); await refreshSession(); navigate(); }
      catch (error) { toast(error.message); logout.disabled = false; }
    });
    root.querySelector('.top-actions').prepend(logout);
  }
  document.getElementById('session-label').textContent = user.email;
  const container = document.getElementById('app-content');
  let path = location.hash.slice(1) || '/';
  if (path === '/login') { path = '/'; history.replaceState(null, '', '#/'); }
  let params = {}, selected;
  for (const route of routes) {
    const expected = route.path.split('/'), actual = path.split('/');
    if (expected.length !== actual.length) continue;
    const values = {};
    if (expected.every((part, i) => { if (part.startsWith(':')) { try { values[part.slice(1)] = decodeURIComponent(actual[i]); } catch { return false; } return actual[i] !== ''; } return part === actual[i]; })) { selected = route; params = values; break; }
  }
  updateMenu(routes, canAccess, path);
  setPageTitle(selected?.title || 'Không tìm thấy trang');
  if (!selected) return showState(container, 'Trang không tồn tại. Chọn Tổng quan để quay lại.');
  if (!canAccess(selected.roles)) return showState(container, 'Vui lòng đăng nhập bằng tài khoản có quyền truy cập.', 'status-error');
  showState(container, 'Đang tải…');
  try {
    const dispose = await selected.mount({ container, params, signal: controller.signal });
    if (controller.signal.aborted) { if (typeof dispose === 'function') dispose(); }
    else cleanup = typeof dispose === 'function' ? dispose : null;
  } catch (error) { if (!controller.signal.aborted) showState(container, error.message, 'status-error'); }
}
window.addEventListener('hashchange', navigate);
window.addEventListener('session-expired', () => { toast('Phiên đã hết hạn. Vui lòng đăng nhập lại.'); navigate(); });
navigate();

// TV1: router hash; mount nhận signal và có thể trả cleanup đồng bộ/bất đồng bộ.
import { mountLayout, updateMenu, setPageTitle } from './shared/layout.js';
import { refreshSession, canAccess } from './shared/session.js';
import { showState, toast } from './shared/ui.js';
import { mount as mountHome } from './shared/home.js';
import { routes as bookRoutes } from './books/routes.js';
import { routes as ebookRoutes } from './ebooks/routes.js';
const routes = [{ path: '/', title: 'Tổng quan', icon: '◫', mount: mountHome }, ...bookRoutes, ...ebookRoutes];
mountLayout(document.getElementById('app'));
let activeController, cleanup;
async function navigate() {
  activeController?.abort(); cleanup?.(); cleanup = null;
  const controller = new AbortController(); activeController = controller;
  const container = document.getElementById('app-content');
  const path = location.hash.slice(1) || '/';
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
window.addEventListener('session-expired', () => { document.getElementById('session-label').textContent = 'Khách'; toast('Phiên đã hết hạn. Vui lòng đăng nhập lại.'); navigate(); });
const labels = { student: 'Sinh viên', librarian: 'Thủ thư', staff: 'Nhân viên', admin: 'Quản lý' };
try { const user = await refreshSession(); document.getElementById('session-label').textContent = user ? labels[user.vai_tro] : 'Khách'; }
catch { toast('Chưa kết nối được phiên máy chủ.'); }
navigate();

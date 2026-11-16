// TV1: router hash; mount nhận signal và có thể trả cleanup đồng bộ/bất đồng bộ.
import { mountLayout, updateMenu, setPageTitle } from './shared/layout.js';
import { refreshSession, currentUser } from './shared/session.js';
import { showState, toast } from './shared/ui.js';
import { mount as mountHome } from './shared/home.js';
import { routesFor } from './shared/app-routes.js';
import { mountLogin, mountEmailAction } from './accounts/live.js';
import { request, setCsrfToken } from './shared/api.js';
import {mount as publicEbooks} from './ebooks/public.js';
const sessionUser = () => { const u=currentUser(); return u ? {role:u.vai_tro,name:u.ho_ten?.trim()||u.email} : null; };
const canAccess = (roles = []) => Boolean(sessionUser() && (!roles.length || roles.includes(sessionUser().role)));
const root = document.getElementById('app');
const authQuery=new URLSearchParams(location.search);
const emailAction=['verify','reset'].includes(authQuery.get('auth'))?{action:authQuery.get('auth'),token:authQuery.get('token')||''}:null;
if(emailAction)history.replaceState(null,'',location.pathname+'#/email-action');
let activeController, cleanup, shellMounted = false;
async function navigate() {
  activeController?.abort(); cleanup?.(); cleanup = null;
  const controller = new AbortController(); activeController = controller;
  if(emailAction){shellMounted=false;root.className='login-page';mountEmailAction({container:root,signal:controller.signal,...emailAction});return;}
  if (!sessionUser()) {
    if(location.hash==='#/public-ebooks'){shellMounted=false;root.className='login-page';try{await publicEbooks({container:root,signal:controller.signal});}catch(e){if(!controller.signal.aborted)showState(root,e.message);}return;}
    shellMounted = false;
    root.className = 'login-page';
    document.title = 'Đăng nhập | UniLib';
    history.replaceState(null, '', '#/login');
    cleanup = mountLogin({ container: root, signal: controller.signal, onLogin: () => {
      history.replaceState(null, '', '#/'); navigate();
    } });
    return;
  }
  const routes = [{ path: '/', title: 'Trang chủ', icon: '◫', mount: mountHome }, ...routesFor(sessionUser().role)];
  if (!shellMounted) {
    root.className = '';
    mountLayout(root); shellMounted = true;
  }
  document.getElementById('session-label').textContent = sessionUser().name;
  document.getElementById('avatar-initial').textContent=sessionUser().name.slice(0,1).toLocaleUpperCase('vi');
  document.getElementById('profile-avatar').title='Hồ sơ của '+sessionUser().name;
  refreshNotificationCount(controller.signal);
  const container = document.getElementById('app-content');
  let path = location.hash.slice(1) || '/';
  if (path.startsWith('/week1/')) { path = path.replace('/week1/', '/'); history.replaceState(null, '', '#' + path); }
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
  if (!selected) return showState(container, 'Trang không tồn tại. Chọn Trang chủ để quay lại.');
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
// Khôi phục phiên thật trước khi chọn màn hình.
refreshSession().then(navigate).catch(error => { if(error.status===401){navigate();return;} showState(root,error.message,'status-error'); const retry=document.createElement('button'); retry.textContent='Thử lại'; retry.onclick=()=>location.reload(); root.append(retry); });

async function refreshNotificationCount(signal){try{const r=await request('/notifications/unread-count',{signal});if(signal?.aborted)return;const badge=document.getElementById('notification-count'),bell=document.getElementById('notification-bell');if(!badge||!bell)return;const count=Number(r.data.unread)||0;badge.hidden=count===0;badge.textContent=count>99?'99+':String(count);bell.setAttribute('aria-label',count?'Thông báo: '+count+' chưa đọc':'Thông báo: không có thông báo chưa đọc');}catch(e){if(signal?.aborted)return;const badge=document.getElementById('notification-count');if(badge)badge.hidden=true;}}
window.addEventListener('notifications-updated',()=>{if(sessionUser())refreshNotificationCount(activeController?.signal);});

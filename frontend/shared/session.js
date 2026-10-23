// TV1: trạng thái giao diện; backend luôn là nơi quyết định quyền.
import { request, setCsrfToken } from './api.js';
let user = null;
export function currentUser() { return user; }
export function canAccess(roles = []) { return !roles.length || Boolean(user && roles.includes(user.vai_tro)); }
export async function refreshSession() {
  const result = await request('/session'); user = result.data.user;
  setCsrfToken(result.data.csrf_token); return user;
}
window.addEventListener('session-expired', () => { user = null; });

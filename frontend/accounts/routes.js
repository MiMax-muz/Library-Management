import { mount } from './demo-ui.js';
import { mountLiveAccounts, mountLiveEmployees, mountLivePassword } from './live.js';
// Giữ route mẫu ở vị trí đầu để menu /week1/accounts dùng giao diện B1 độc lập.
export const routes = [
  { path: '/accounts-demo', title: 'Tài khoản (mẫu tuần 1)', mount },
  { path: '/accounts', title: 'Tài khoản', icon: '◉', roles: ['admin'], mount: mountLiveAccounts },
  { path: '/employees', title: 'Nhân viên', icon: '◈', roles: ['admin'], mount: mountLiveEmployees },
  { path: '/account/password', title: 'Đổi mật khẩu', icon: '◇', mount: mountLivePassword },
];

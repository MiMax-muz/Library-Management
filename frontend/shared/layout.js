// TV1: giữ cấu trúc shell/sidebar/topbar và tên lớp CSS của UniLib gốc.
export function mountLayout(root) {
  root.innerHTML = `<div class="shell"><aside class="sidebar" id="app-sidebar"><nav id="app-menu" aria-label="Điều hướng chính"></nav></aside><button class="btn outline nav-toggle" id="menu-toggle" aria-label="Mở menu" aria-controls="app-sidebar" aria-expanded="true">‹</button><main class="main"><header class="topbar"><div class="header-brand-area"><a class="header-brand" href="#/" aria-label="UniLib — về trang chủ"><svg class="brand-book" viewBox="0 0 64 64" fill="none" aria-hidden="true"><rect width="64" height="64" rx="17" fill="#17684f"/><path d="M32 19c-6-5-13-6-21-4v31c8-2 15-1 21 4 6-5 13-6 21-4V15c-8-2-15-1-21 4Z" fill="#e2f6eb"/><path d="M32 19v31M17 23c4 0 7 1 10 3m-10 5c4 0 7 1 10 3m10-8c3-2 6-3 10-3m-10 11c3-2 6-3 10-3" stroke="#17684f" stroke-width="2.5" stroke-linecap="round"/></svg><span class="brand-wordmark">Uni<span>Lib</span><i>.</i></span><span class="brand-caption">CỔNG THÔNG TIN THƯ VIỆN</span></a><span id="page-crumb" class="brand-page">Trang chủ</span></div><div class="top-actions"><div class="account-tools"><div class="account-icons"><a id="notification-bell" class="header-icon" href="#/notifications" aria-label="Xem thông báo" title="Thông báo"><svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M9 21h6"/></svg><span id="notification-count" class="notification-badge" hidden></span></a><a id="profile-avatar" class="profile-avatar" href="#/profile" aria-label="Xem hồ sơ của tôi" title="Hồ sơ của tôi"><span id="avatar-initial">U</span></a><div class="user-name-card"><strong id="session-label">Khách</strong></div></div></div></div></header><div class="content"><div class="eyebrow">DIGITAL LIBRARY PLATFORM</div><h1 class="heading" id="page-title">Trang chủ</h1><p class="sub">Tra cứu và quản lý thư viện.</p><div id="app-content"></div></div></main></div>`;
  document.getElementById('menu-toggle').addEventListener('click', event => {
    if(window.matchMedia('(max-width: 650px)').matches){
      document.getElementById('app-sidebar').classList.toggle('is-open');
    }else{
      document.querySelector('.shell').classList.toggle('nav-collapsed');
    }
    syncNavigationToggle();
  });
}
export function updateMenu(routes, canAccess, activePath) {
  const menu = document.getElementById('app-menu'); menu.replaceChildren();
  for (const route of routes.filter(route => route.title && !route.hiddenNavigation && canAccess(route.roles))) {
    const a = document.createElement('a'); a.className = 'nav'; a.href = '#' + route.path;
    a.setAttribute('aria-label', route.title); a.title = route.title;
    const icon = document.createElement('span'); icon.className = 'glyph'; icon.textContent = route.icon || '◈'; icon.setAttribute('aria-hidden', 'true');
    const label = document.createElement('span'); label.className = 'nav-label'; label.textContent = route.title;
    a.append(icon, label);
    a.addEventListener('click', () => { document.getElementById('app-sidebar').classList.remove('is-open'); syncNavigationToggle(); });
    if (route.path === activePath) { a.classList.add('active'); a.setAttribute('aria-current', 'page'); }
    menu.append(a);
  }
}
export function setPageTitle(title) {
  document.querySelector('.content').classList.toggle('dashboard-content',title==='Trang chủ');
  document.querySelector('.shell').classList.toggle('dashboard-shell',title==='Trang chủ');
  document.getElementById('page-title').textContent = title;
  document.getElementById('page-crumb').textContent = title;
  document.title = title + ' | UniLib';
  document.getElementById('app-sidebar').classList.remove('is-open');
  syncNavigationToggle();
}

function syncNavigationToggle(){const toggle=document.getElementById('menu-toggle');if(!toggle)return;const open=window.matchMedia('(max-width: 650px)').matches?document.getElementById('app-sidebar').classList.contains('is-open'):!document.querySelector('.shell').classList.contains('nav-collapsed');toggle.setAttribute('aria-expanded',String(open));toggle.textContent=open?'‹':'›';toggle.setAttribute('aria-label',open?'Thu gọn thanh điều hướng':'Mở thanh điều hướng');}
window.addEventListener('resize',syncNavigationToggle);

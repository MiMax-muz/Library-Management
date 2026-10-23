// TV1: giữ cấu trúc shell/sidebar/topbar và tên lớp CSS của UniLib gốc.
export function mountLayout(root) {
  root.innerHTML = `<div class="shell"><aside class="sidebar" id="app-sidebar"><div class="logo">Uni<span>Lib.</span></div><div class="role">KHÔNG GIAN LÀM VIỆC<strong id="session-label">Khách</strong></div><div class="nav-title">ĐIỀU HƯỚNG</div><nav id="app-menu" aria-label="Điều hướng chính"></nav><div class="nav-title">THAM KHẢO</div><a class="nav" href="./prototype/index.html"><span class="glyph">◫</span><span class="nav-label">Giao diện mẫu UniLib</span></a></aside><main class="main"><header class="topbar"><div class="crumb">UniLib / <b id="page-crumb">Trang chủ</b></div><div class="top-actions"><span class="muted">Thư viện đại học</span><div class="avatar" aria-hidden="true">U</div><button class="btn outline mobile-menu" id="menu-toggle" aria-label="Mở menu" aria-controls="app-sidebar" aria-expanded="false">☰</button></div></header><div class="content"><div class="eyebrow">DIGITAL LIBRARY PLATFORM</div><h1 class="heading" id="page-title">Trang chủ</h1><p class="sub">Quản lý tri thức, kết nối học tập</p><div id="app-content"></div></div></main></div>`;
  document.getElementById('menu-toggle').addEventListener('click', event => {
    const open = document.getElementById('app-sidebar').classList.toggle('is-open');
    event.currentTarget.setAttribute('aria-expanded', String(open));
  });
}
export function updateMenu(routes, canAccess, activePath) {
  const menu = document.getElementById('app-menu'); menu.replaceChildren();
  for (const route of routes.filter(route => route.title && canAccess(route.roles))) {
    const a = document.createElement('a'); a.className = 'nav'; a.href = '#' + route.path;
    a.setAttribute('aria-label', route.title); a.title = route.title;
    const icon = document.createElement('span'); icon.className = 'glyph'; icon.textContent = route.icon || '◈'; icon.setAttribute('aria-hidden', 'true');
    const label = document.createElement('span'); label.className = 'nav-label'; label.textContent = route.title;
    a.append(icon, label);
    a.addEventListener('click', () => { document.getElementById('app-sidebar').classList.remove('is-open'); document.getElementById('menu-toggle').setAttribute('aria-expanded', 'false'); });
    if (route.path === activePath) { a.classList.add('active'); a.setAttribute('aria-current', 'page'); }
    menu.append(a);
  }
}
export function setPageTitle(title) {
  document.getElementById('page-title').textContent = title;
  document.getElementById('page-crumb').textContent = title;
  document.title = title + ' | UniLib';
  document.getElementById('app-sidebar').classList.remove('is-open');
  document.getElementById('menu-toggle').setAttribute('aria-expanded', 'false');
}

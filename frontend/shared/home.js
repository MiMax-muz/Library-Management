// TV1: mẫu mount module, nạp fragment và hủy request/listener khi chuyển trang.
import { request } from './api.js';
export async function mount({ container, signal }) {
  const response = await fetch(new URL('./home.html', import.meta.url), { signal });
  if (!response.ok) throw new Error('Không tải được trang chủ.');
  const html = await response.text();
  if (signal.aborted) return;
  container.innerHTML = html;
  const button = container.querySelector('#check-connection');
  async function check() {
    button.disabled = true;
    await Promise.all([['/health', '#api-status', 'Máy chủ'], ['/health/db', '#db-status', 'Cơ sở dữ liệu']].map(async ([path, selector, label]) => {
      const element = container.querySelector(selector); element.className = ''; element.textContent = label + ': đang kiểm tra…';
      try { await request(path, { signal }); element.textContent = label + ': đã kết nối'; }
      catch (error) { if (!signal.aborted) { element.className = 'status-error'; element.textContent = label + ': ' + error.message; } }
    }));
    button.disabled = false;
  }
  button.addEventListener('click', check, { signal });
  await check();
}

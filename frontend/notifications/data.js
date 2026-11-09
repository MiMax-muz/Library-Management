import { demoNotifications } from './fixtures.js';

const clone = item => ({ ...item });

function pause(ms, signal) {
  return new Promise((resolve, reject) => {
    if (signal?.aborted) { reject(new DOMException('Đã hủy', 'AbortError')); return; }
    const timer = setTimeout(() => { signal?.removeEventListener('abort', abort); resolve(); }, ms);
    const abort = () => { clearTimeout(timer); reject(new DOMException('Đã hủy', 'AbortError')); };
    signal?.addEventListener('abort', abort, { once: true });
  });
}

// Hợp đồng nguồn dữ liệu demo: load, markRead, markAllRead. Nguồn API sẽ được bàn sau.
export function createDemoNotificationSource({ recipientId, scenario = 'normal', delay = 450 }) {
  const items = demoNotifications.filter(item => item.recipientId === recipientId).map(clone);
  let loadCount = 0;
  return {
    mode: 'demo',
    async load({ signal } = {}) {
      await pause(delay, signal);
      loadCount += 1;
      if (scenario === 'error' || (scenario === 'error-once' && loadCount === 1)) {
        throw new Error('Không tải được thông báo mẫu. Hãy thử lại.');
      }
      return (scenario === 'empty' ? [] : items).map(clone);
    },
    async markRead(id) {
      const item = items.find(entry => entry.id === id);
      if (!item) throw new Error('Không tìm thấy thông báo của tài khoản này.');
      item.readAt ??= new Date().toISOString();
      return clone(item);
    },
    async markAllRead() {
      const now = new Date().toISOString();
      items.forEach(item => { item.readAt ??= now; });
      return items.map(clone);
    },
  };
}

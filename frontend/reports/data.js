import { demoBooks, demoReaders, demoAccounts, demoPolicies } from './fixtures.js';
import { request } from '../shared/api.js';

// Nguồn dữ liệu mẫu cho trang xem trước độc lập với database.
export function createDemoReportSource({ scenario = 'normal', delay = 250 } = {}) {
  let attempts = 0;
  const pause = signal => new Promise((resolve, reject) => {
    if (signal?.aborted) { reject(new DOMException('Đã hủy', 'AbortError')); return; }
    const onAbort = () => { clearTimeout(timer); reject(new DOMException('Đã hủy', 'AbortError')); };
    const timer = setTimeout(() => { signal?.removeEventListener('abort', onAbort); resolve(); }, delay);
    signal?.addEventListener('abort', onAbort, { once: true });
  });
  const load = async (value, signal) => {
    await pause(signal);
    attempts += 1;
    if (scenario === 'error' || (scenario === 'error-once' && attempts === 1)) throw new Error('Không tải được dữ liệu mẫu. Hãy thử lại.');
    if (scenario === 'empty') return Array.isArray(value) ? [] : {};
    return structuredClone(value);
  };
  return {
    mode: 'demo',
    loadReports: async ({ signal } = {}) => {
      await pause(signal);
      attempts += 1;
      if (scenario === 'error' || (scenario === 'error-once' && attempts === 1)) throw new Error('Không tải được báo cáo mẫu. Hãy thử lại.');
      if (scenario === 'empty') return { books: [], readers: [], accounts: [] };
      return structuredClone({ books: demoBooks, readers: demoReaders, accounts: demoAccounts });
    },
    loadSettings: ({ signal } = {}) => load(demoPolicies, signal),
  };
}

export const demoReportSource = createDemoReportSource();

// API tuần 3 trả envelope { data: { books, readers, accounts } } và { data: policies }.
// B2 TV4 chỉ cho đọc cấu hình; chỉ truyền settingsWritable khi B2 chung cho phép ghi.
export function createApiReportSource({ reportsPath, settingsPath, settingsWritable = false }) {
  if (!reportsPath?.startsWith('/') || !settingsPath?.startsWith('/')) throw new Error('Cần đường dẫn API reports và settings.');
  return {
    mode: 'live',
    loadReports: async ({ signal } = {}) => (await request(reportsPath, { signal })).data,
    loadSettings: async ({ signal } = {}) => (await request(settingsPath, { signal })).data,
    ...(settingsWritable ? { saveSettings: async ({ changes, signal } = {}) => (await request(settingsPath, { method: 'PATCH', body: changes, signal }))?.data ?? null } : {}),
  };
}

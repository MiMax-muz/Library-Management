// TV1: client duy nhất, hỗ trợ JSON/204/tệp, CSRF và phiên hết hạn.
import { config } from '../config.js';
let csrfToken = null;
export class ApiError extends Error {
  constructor(message, status, code, details = {}) { super(message); this.status = status; this.code = code; this.details = details; }
}
export function setCsrfToken(token) { csrfToken = token; }
export async function request(path, { method = 'GET', body, signal, responseType = 'json' } = {}) {
  method = method.toUpperCase();
  const headers = { Accept: responseType === 'blob' ? '*/*' : 'application/json' };
  if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
    if (!csrfToken) {
      const session = await request('/session', { signal });
      csrfToken = session.data.csrf_token;
    }
    headers['X-CSRF-Token'] = csrfToken;
  }
  let payload = body;
  if (body !== undefined && !(body instanceof FormData)) { headers['Content-Type'] = 'application/json'; payload = JSON.stringify(body); }
  let response;
  try { response = await fetch(config.apiBaseUrl + path, { method, body: payload, headers, credentials: 'same-origin', signal }); }
  catch (error) { if (error.name === 'AbortError') throw error; throw new ApiError('Không kết nối được máy chủ.', 0, 'NETWORK_ERROR'); }
  if (response.status === 204) return null;
  const isJson = response.headers.get('content-type')?.includes('application/json');
  if (!response.ok) {
    const result = isJson ? await response.json().catch(() => null) : null;
    if (response.status === 401) { csrfToken = null; window.dispatchEvent(new Event('session-expired')); }
    if (result?.error?.code === 'CSRF_INVALID') csrfToken = null;
    throw new ApiError(result?.error?.message || 'Máy chủ không xử lý được yêu cầu.', response.status, result?.error?.code || 'HTTP_ERROR', result?.error?.details);
  }
  if (responseType === 'blob') return response.blob();
  if (!isJson) throw new ApiError('Phản hồi máy chủ không đúng định dạng JSON.', response.status, 'INVALID_RESPONSE');
  return response.json();
}

// TV1: kiểm tra hợp đồng client trước khi các module phụ thuộc vào nó.
import assert from 'node:assert/strict';
globalThis.window = new EventTarget();
const { request, setCsrfToken } = await import('../../frontend/shared/api.js');
let calls = [];
globalThis.fetch = async (url, options) => { calls.push({url, options}); return new Response(null, { status: 204 }); };
assert.equal(await request('/empty'), null);
globalThis.fetch = async () => new Response('pdf-content', { headers: { 'Content-Type': 'application/pdf' } });
assert.equal(await (await request('/file', { responseType: 'blob' })).text(), 'pdf-content');
let expired = false; window.addEventListener('session-expired', () => { expired = true; });
globalThis.fetch = async () => new Response(JSON.stringify({error:{code:'UNAUTHENTICATED',message:'Đăng nhập lại'}}), {status:401,headers:{'Content-Type':'application/json'}});
await assert.rejects(request('/private'), error => error.status === 401 && error.code === 'UNAUTHENTICATED'); assert.equal(expired, true);
globalThis.fetch = async () => new Response('<html>Error</html>', { status: 502 });
await assert.rejects(request('/bad'), error => error.status === 502);
globalThis.fetch = async () => { throw new TypeError('network'); };
await assert.rejects(request('/offline'), error => error.code === 'NETWORK_ERROR');
globalThis.fetch = async () => { throw new DOMException('Cancelled', 'AbortError'); };
await assert.rejects(request('/abort'), error => error.name === 'AbortError');
setCsrfToken(null); calls = [];
globalThis.fetch = async (url, options) => {
  calls.push({url, options});
  if (url.endsWith('/session')) return new Response(JSON.stringify({data:{csrf_token:'test-token',user:null}}), {headers:{'Content-Type':'application/json'}});
  return new Response(null, {status:204});
};
await request('/write', { method:'POST',body:{name:'demo'} });
assert.equal(calls.length,2); assert.equal(calls[1].options.headers['X-CSRF-Token'],'test-token'); assert.equal(calls[1].options.credentials,'same-origin');
assert.deepEqual(JSON.parse(calls[1].options.body),{name:'demo'});
console.log('PASS: client 204, Blob, 401, HTTP/network/abort errors and CSRF bootstrap');

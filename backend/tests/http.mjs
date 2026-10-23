// TV1: chạy với máy chủ thử, không thay đổi dữ liệu.
import assert from 'node:assert/strict';
const base = process.env.TEST_URL || 'http://127.0.0.1:18080';
for (const endpoint of ['/api/health', '/api/health/db']) {
  const r = await fetch(base + endpoint); assert.equal(r.status, 200); assert.equal((await r.json()).data.status, 'ok');
}
const session = await fetch(base + '/api/session');
const cookie = session.headers.get('set-cookie').split(';')[0];
const payload = await session.json(); assert.equal(payload.data.user, null); assert.equal(payload.data.csrf_token.length, 64);
let r = await fetch(base + '/api/health', { method: 'POST' }); assert.equal(r.status, 403);
r = await fetch(base + '/api/health', { method: 'POST', headers: { Cookie: cookie, 'X-CSRF-Token': payload.data.csrf_token } }); assert.equal(r.status, 405); assert.equal(r.headers.get('allow'), 'GET');
r = await fetch(base + '/api/missing'); assert.equal(r.status, 404); assert.equal((await r.json()).error.code, 'NOT_FOUND');
for (const privatePath of ['/backend/config.php', '/database/schema.sql', '/.env', '/backend/storage/test-mysql/my.ini']) { r = await fetch(base + privatePath); assert.equal(r.status, 404); }
r = await fetch(base + '/'); assert.equal(r.status, 200); assert.ok((await r.text()).includes('shared/styles.css'));
console.log('PASS: API, session, CSRF, 404/405, private paths and frontend');

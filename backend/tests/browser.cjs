// TV1: kiểm thử giao diện bằng Playwright có sẵn trong môi trường kiểm thử.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.BROWSER_PATH ? { executablePath: process.env.BROWSER_PATH } : {}) });
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    await page.goto(process.env.TEST_URL || 'http://127.0.0.1:18080/');
    await page.getByText('Cơ sở dữ liệu: đã kết nối', { exact: true }).waitFor();
    await page.screenshot({ path: path.join(__dirname, '../storage/week1-desktop.png'), fullPage: true });
    await page.getByRole('button', { name: 'Kiểm tra lại' }).click();
    await page.getByText('Cơ sở dữ liệu: đã kết nối', { exact: true }).waitFor();
    await page.evaluate(() => { location.hash = '/missing'; });
    await page.getByRole('heading', { name: 'Không tìm thấy trang', exact: true }).waitFor();
    await page.getByRole('link', { name: 'Tổng quan', exact: true }).click();
    await page.getByText('Cơ sở dữ liệu: đã kết nối', { exact: true }).waitFor();
    await page.route('**/api/health/db', route => route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({data:null,error:{code:'DATABASE_NOT_READY',message:'DB thử chưa sẵn sàng'}}) }));
    await page.getByRole('button', { name: 'Kiểm tra lại' }).click();
    await page.getByText('Cơ sở dữ liệu: DB thử chưa sẵn sàng', { exact: true }).waitFor();
    await page.unroute('**/api/health/db');
    await page.setViewportSize({ width: 800, height: 1000 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole('button', { name: 'Mở menu' }).click();
    assert.equal(await page.locator('#menu-toggle').getAttribute('aria-expanded'), 'true');
    await page.getByRole('link', { name: 'Tổng quan', exact: true }).click();
    assert.equal(await page.locator('#menu-toggle').getAttribute('aria-expanded'), 'false');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.screenshot({ path: path.join(__dirname, '../storage/week1-mobile.png'), fullPage: true });
    assert.deepEqual(errors, []);
    console.log('PASS: desktop/mobile, navigation, database error, no page errors');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });

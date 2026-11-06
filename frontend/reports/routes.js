import { mountReports, mountSettings } from './reports.js';
import { createApiReportSource } from './data.js';

const source = createApiReportSource({ reportsPath: '/reports/baseline', settingsPath: '/settings' });

export const routes = [
  { path: '/reports', title: 'Báo cáo nền', icon: '▥', roles: ['admin'], mount: options => mountReports({ ...options, source }) },
  { path: '/settings', title: 'Chính sách', icon: '⚙', roles: ['admin'], mount: options => mountSettings({ ...options, source }) },
];

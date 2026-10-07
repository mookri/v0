// SoD rules list — cursor read of GET /api/v1/platform/sod-rules?tenant_id=…
// (session cookie via the shared helper; blocking vs warning severity is
// what an admin scans for, so it is the most prominent column).

import {
  apiGet,
  apiMeta,
  appendRow,
  badge,
  fillWorkspaceBadge,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('sod-rows');
const alertEl = document.getElementById('sod-alert');
const moreBtn = document.getElementById('sod-load-more');

const COLS = 6;
let cursor = null;
let loaded = false;

function severityBadge(severity) {
  if (severity === 'block') return badge('block', 'bg-danger-subtle text-danger');
  if (severity === 'warn') return badge('warn', 'bg-warning-subtle text-warning');
  return badge(severity || '—', 'bg-secondary-subtle text-secondary');
}

function statusBadge(status) {
  if (status === 'active' || status === 'enabled') {
    return badge(status, 'bg-success-subtle text-success');
  }
  if (status === 'disabled') return badge('disabled', 'bg-danger-subtle text-danger');
  return badge(status || '—', 'bg-secondary-subtle text-secondary');
}

function sourceBadge(source) {
  return source === 'tenant'
    ? badge('tenant', 'bg-primary-subtle text-primary')
    : badge('platform', 'bg-secondary-subtle text-secondary');
}

function appendItems(items) {
  for (const item of items) {
    appendRow(rowsEl, [
      { text: item.code || '', cls: 'font-monospace' },
      item.name || '',
      item.kind || '',
      severityBadge(item.severity),
      statusBadge(item.status),
      sourceBadge(item.source),
    ]);
  }
}

async function loadPage() {
  if (alertEl) alertEl.classList.add('d-none');
  if (!loaded) setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/platform/sod-rules', {
      tenant_id: apiMeta('api-tenant'),
      limit: 10,
      cursor,
    });
    const items = Array.isArray(body && body.data) ? body.data : [];
    const page = (body && body.meta && body.meta.page) || {};

    if (!loaded) {
      rowsEl.innerHTML = '';
      loaded = true;
    }

    appendItems(items);

    if (items.length === 0 && rowsEl.children.length === 0) {
      setStateRow(rowsEl, 'No SoD rules for this workspace yet.', COLS);
    }

    cursor = page.has_more && page.next_cursor ? page.next_cursor : null;
    if (moreBtn) moreBtn.classList.toggle('d-none', !cursor);
  } catch (error) {
    showError(alertEl, error);
    if (!loaded) setStateRow(rowsEl, 'Unable to load SoD rules.', COLS);
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('sod-workspace'));

  if (!apiMeta('api-tenant')) {
    setStateRow(rowsEl, 'No active workspace — ask an administrator to add you to a tenant.', COLS);
    return;
  }

  if (moreBtn) {
    moreBtn.addEventListener('click', () => {
      if (cursor) loadPage();
    });
  }

  loadPage();
}

main();

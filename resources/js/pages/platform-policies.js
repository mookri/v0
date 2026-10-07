// Access policies list — cursor read of GET /api/v1/policies. Session
// cookie + X-Tenant-Id (the route is capability-gated platform:policy:view;
// a denial surfaces as authorization_denied in the alert). The controller
// rejects unknown query params, so only limit/cursor/filters are sent.

import {
  apiGet,
  apiMeta,
  appendRow,
  badge,
  fillWorkspaceBadge,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('policy-rows');
const alertEl = document.getElementById('policy-alert');
const moreBtn = document.getElementById('policy-load-more');

const COLS = 6;
let cursor = null;
let loaded = false;

function statusBadge(status) {
  if (status === 'active') return badge('active', 'bg-success-subtle text-success');
  if (status === 'disabled') return badge('disabled', 'bg-danger-subtle text-danger');
  return badge(status || 'draft', 'bg-secondary-subtle text-secondary');
}

function effectBadge(effect) {
  return effect === 'deny'
    ? badge('deny', 'bg-danger-subtle text-danger')
    : badge('allow', 'bg-success-subtle text-success');
}

function appendItems(items) {
  for (const item of items) {
    appendRow(rowsEl, [
      item.name || '',
      effectBadge(item.effect),
      { text: item.capability_selector || '', cls: 'font-monospace' },
      { text: String(item.priority ?? ''), cls: 'text-end' },
      statusBadge(item.status),
      { text: String(item.version ?? ''), cls: 'text-end' },
    ]);
  }
}

async function loadPage() {
  if (alertEl) alertEl.classList.add('d-none');
  if (!loaded) setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/policies', { limit: 10, cursor });
    const items = Array.isArray(body && body.data) ? body.data : [];
    const page = (body && body.meta && body.meta.page) || {};

    if (!loaded) {
      rowsEl.innerHTML = '';
      loaded = true;
    }

    appendItems(items);

    if (items.length === 0 && rowsEl.children.length === 0) {
      setStateRow(rowsEl, 'No policies in this workspace yet.', COLS);
    }

    cursor = page.has_more && page.next_cursor ? page.next_cursor : null;
    if (moreBtn) moreBtn.classList.toggle('d-none', !cursor);
  } catch (error) {
    showError(alertEl, error);
    if (!loaded) setStateRow(rowsEl, 'Unable to load policies.', COLS);
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('policy-workspace'));

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

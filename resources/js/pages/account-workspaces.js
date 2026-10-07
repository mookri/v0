// My workspaces — cursor-paged reads of GET /api/v1/platform/tenants (session
// cookie, no tenant scope: the payload IS the discovery surface, TEN-006
// §4.3). Read-only — the console's workspace stays the server-resolved
// default (meta api-tenant); this page exists to show which memberships are
// held and in what state, including non-active ones the tenant-scoped pages
// refuse (so a denial has a visible cause).

import { apiGet, apiMeta, appendRow, badge, setStateRow, showError } from '../lib/admin-api.js';

const rowsEl = document.getElementById('workspace-rows');
const alertEl = document.getElementById('workspace-alert');
const moreBtn = document.getElementById('workspace-load-more');

const COLS = 5;
let cursor = null;
let loaded = false;

function stateBadge(state) {
  if (state === 'active') return badge('active', 'bg-success-subtle text-success');
  if (state === 'suspended') return badge('suspended', 'bg-warning-subtle text-warning');
  return badge(String(state || 'unknown'), 'bg-secondary-subtle text-secondary');
}

function statusBadge(status) {
  return status === 'active'
    ? badge('active', 'bg-success-subtle text-success')
    : badge(String(status || 'unknown'), 'bg-warning-subtle text-warning');
}

function appendItems(items) {
  const current = apiMeta('api-tenant');

  for (const item of items) {
    const tenant = item.tenant || {};
    const isDefault = Boolean(tenant.id) && tenant.id === current;

    appendRow(rowsEl, [
      { text: tenant.name || 'Unnamed workspace', cls: isDefault ? 'fw-semibold' : '' },
      { text: tenant.slug || '—' },
      stateBadge(item.state),
      statusBadge(tenant.status),
      isDefault
        ? badge('default', 'bg-primary-subtle text-primary')
        : { text: '—' },
    ]);
  }
}

async function loadPage() {
  if (alertEl) alertEl.classList.add('d-none');
  if (!loaded) setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/platform/tenants', { limit: 10, cursor });
    const items = Array.isArray(body && body.data) ? body.data : [];
    const page = (body && body.meta && body.meta.page) || {};

    if (!loaded) {
      rowsEl.innerHTML = '';
      loaded = true;
    }

    appendItems(items);

    if (items.length === 0 && rowsEl.children.length === 0) {
      setStateRow(rowsEl, 'No workspaces — no memberships on this account.', COLS);
    }

    cursor = page.has_more && page.next_cursor ? page.next_cursor : null;
    if (moreBtn) moreBtn.classList.toggle('d-none', !cursor);
  } catch (error) {
    showError(alertEl, error);
    if (!loaded) setStateRow(rowsEl, 'Could not load workspaces.', COLS);
  }
}

function main() {
  if (moreBtn) moreBtn.addEventListener('click', loadPage);
  loadPage();
}

main();

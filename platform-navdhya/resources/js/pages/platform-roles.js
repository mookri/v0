// Roles list — one-shot read of GET /api/v1/platform/roles?tenant_id=…
// (session cookie via the shared helper; the controller 422s without the
// workspace's tenant_id, which the no-workspace guard mirrors client-side).

import {
  apiGet,
  apiMeta,
  appendRow,
  badge,
  fillWorkspaceBadge,
  fillWorkspaceFields,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('role-rows');
const alertEl = document.getElementById('role-alert');

const COLS = 4;
let loaded = false;

function stateBadge(state) {
  if (state === 'active') return badge('active', 'bg-success-subtle text-success');
  if (state === 'disabled') return badge('disabled', 'bg-danger-subtle text-danger');
  return badge(state || '—', 'bg-secondary-subtle text-secondary');
}

function appendItems(items) {
  for (const item of items) {
    appendRow(rowsEl, [
      { text: item.code || '', cls: 'font-monospace' },
      item.name || '',
      stateBadge(item.state),
      { text: item.id || '', cls: 'font-monospace text-muted' },
    ]);
  }
}

async function load() {
  if (alertEl) alertEl.classList.add('d-none');
  if (!loaded) setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/platform/roles', { tenant_id: apiMeta('api-tenant') });
    const items = Array.isArray(body && body.data) ? body.data : [];

    if (!loaded) {
      rowsEl.innerHTML = '';
      loaded = true;
    }

    appendItems(items);

    if (items.length === 0 && rowsEl.children.length === 0) {
      setStateRow(rowsEl, 'No roles defined for this workspace yet.', COLS);
    }
  } catch (error) {
    showError(alertEl, error);
    if (!loaded) setStateRow(rowsEl, 'Unable to load roles.', COLS);
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('role-workspace'));
  fillWorkspaceFields();

  if (!apiMeta('api-tenant')) {
    setStateRow(rowsEl, 'No active workspace — ask an administrator to add you to a tenant.', COLS);
    return;
  }

  load();
}

main();

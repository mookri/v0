// Users list — one-shot read of GET /api/v1/platform/users?tenant_id=…
// (session cookie + X-Tenant-Id; capability-gated platform:user:view, so a
// denial surfaces as authorization_denied in the alert). Rows are the
// workspace's memberships; the embedded user may be null for orphans.
// Lifecycle forms post the SHARED suspend/activate actions (tenant +
// platform:user:manage + stepup:2) — the shared step-up gate in
// admin-api.js holds each post until a fresh MFA proof exists (doc 06 §7.5).

import {
  apiGet,
  apiMeta,
  appendRow,
  badge,
  fillWorkspaceBadge,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('user-rows');
const alertEl = document.getElementById('user-alert');

const COLS = 5;
let loaded = false;

function statusBadge(status) {
  if (status === 'active') return badge('active', 'bg-success-subtle text-success');
  if (status === 'suspended' || status === 'disabled') {
    return badge(status, 'bg-danger-subtle text-danger');
  }
  return badge(status || '—', 'bg-secondary-subtle text-secondary');
}

function membershipBadge(state) {
  if (state === 'active') return badge('active', 'bg-success-subtle text-success');
  if (state === 'suspended') return badge('suspended', 'bg-danger-subtle text-danger');
  return badge(state || '—', 'bg-secondary-subtle text-secondary');
}

// Lifecycle forms post the shared web actions (mirroring the API guards
// exactly); `stepUp: true` attaches the freshness gate so a stale proof
// opens the verify modal before the post goes out.
function rowForms(item) {
  const user = item.user || null;
  const tenant = apiMeta('api-tenant');

  // Orphans have no user to suspend; deactivated users are frozen
  // server-side (409 state_forbidden) — no form, no dead button.
  if (!user || user.status === 'deactivated') return [];

  if (user.status === 'suspended') {
    return [{
      action: `/platform/users/${user.id}:activate`,
      label: 'Activate',
      cls: 'btn-outline-success btn-sm',
      fields: { tenant_id: tenant },
      stepUp: true,
    }];
  }

  return [{
    action: `/platform/users/${user.id}:suspend`,
    label: 'Suspend',
    cls: 'btn-outline-danger btn-sm',
    fields: { tenant_id: tenant },
    confirmMessage: `Suspend ${user.name || user.email}? Their sessions and tokens die immediately.`,
    stepUp: true,
  }];
}

function appendItems(items) {
  for (const item of items) {
    const user = item.user || null;

    appendRow(rowsEl, [
      { text: (user && user.email) || '—', cls: 'font-monospace' },
      (user && user.name) || '—',
      user ? statusBadge(user.status) : badge('orphaned', 'bg-warning-subtle text-warning'),
      membershipBadge(item.state),
      { forms: rowForms(item) },
    ]);
  }
}

async function load() {
  if (alertEl) alertEl.classList.add('d-none');
  if (!loaded) setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/platform/users', { tenant_id: apiMeta('api-tenant') });
    const items = Array.isArray(body && body.data) ? body.data : [];

    if (!loaded) {
      rowsEl.innerHTML = '';
      loaded = true;
    }

    appendItems(items);

    if (items.length === 0 && rowsEl.children.length === 0) {
      setStateRow(rowsEl, 'No users in this workspace yet.', COLS);
    }
  } catch (error) {
    showError(alertEl, error);
    if (!loaded) setStateRow(rowsEl, 'Unable to load users.', COLS);
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('user-workspace'));

  if (!apiMeta('api-tenant')) {
    setStateRow(rowsEl, 'No active workspace — ask an administrator to add you to a tenant.', COLS);
    return;
  }

  load();
}

main();

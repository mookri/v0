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
  appendHiddenInput,
  appendPostForm,
  appendRow,
  attachStepUpGate,
  badge,
  fillWorkspaceBadge,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('user-rows');
const alertEl = document.getElementById('user-alert');
const rolesCard = document.getElementById('user-roles-card');
const rolesTitle = document.getElementById('user-roles-title');
const rolesList = document.getElementById('user-roles-list');
const rolesGrant = document.getElementById('user-roles-grant');

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

// ---- Membership roles panel (doc 06 §7.5) --------------------------------
// The "Roles" button points the shared card at one membership: assignments
// and the tenant's role catalogue are fetched on open (no N+1 at load),
// then every mutation posts the shared grant/revoke actions — confirm
// first, step-up gate second, exactly like the row forms.

async function openRoles(item) {
  if (!rolesCard) return;

  const membershipId = item.id;
  const email = (item.user && item.user.email) || 'this member';

  rolesTitle.textContent = email;
  rolesList.innerHTML = '<div class="text-muted">Loading…</div>';
  rolesGrant.innerHTML = '';
  rolesCard.classList.remove('d-none');
  rolesCard.scrollIntoView({ behavior: 'smooth', block: 'start' });

  try {
    const tenant = apiMeta('api-tenant');
    const [assignmentsBody, rolesBody] = await Promise.all([
      apiGet(`/platform/memberships/${membershipId}/roles`, { tenant_id: tenant }),
      apiGet('/platform/roles', { tenant_id: tenant }),
    ]);
    renderRoles(
      membershipId,
      email,
      Array.isArray(assignmentsBody && assignmentsBody.data) ? assignmentsBody.data : [],
      Array.isArray(rolesBody && rolesBody.data) ? rolesBody.data : [],
    );
  } catch (error) {
    showError(alertEl, error);
    rolesList.innerHTML = '';
    const failed = document.createElement('div');
    failed.className = 'text-muted';
    failed.textContent = 'Unable to load roles — see the page alert.';
    rolesList.appendChild(failed);
  }
}

function renderRoles(membershipId, email, assignments, roles) {
  rolesList.innerHTML = '';

  if (assignments.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'text-muted';
    empty.textContent = 'No roles assigned.';
    rolesList.appendChild(empty);
  } else {
    for (const assignment of assignments) {
      const row = document.createElement('div');
      row.className = 'd-flex align-items-center justify-content-between border rounded px-3 py-2 mb-2';

      const label = document.createElement('span');
      label.className = 'font-monospace';
      label.textContent = assignment.role_code || '—';
      row.appendChild(label);

      appendPostForm(row, {
        action: `/platform/memberships/${membershipId}/roles/${assignment.id}`,
        label: 'Revoke',
        cls: 'btn-outline-danger btn-sm',
        method: 'DELETE',
        fields: { tenant_id: apiMeta('api-tenant') },
        confirmMessage: `Revoke the ${assignment.role_code} role from ${email}?`,
        stepUp: true,
      });

      rolesList.appendChild(row);
    }
  }

  const assigned = new Set(assignments.map((a) => a.role_code));
  const available = roles.filter((r) => r.state === 'active' && !assigned.has(r.code));
  renderGrantForm(membershipId, email, available);
}

function renderGrantForm(membershipId, email, available) {
  rolesGrant.innerHTML = '';

  if (available.length === 0) {
    const done = document.createElement('div');
    done.className = 'text-muted small';
    done.textContent = 'No further roles to grant.';
    rolesGrant.appendChild(done);
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = `/platform/memberships/${membershipId}/roles`;
  form.className = 'd-flex gap-2';

  const token = apiMeta('csrf-token');
  if (token) appendHiddenInput(form, '_token', token);
  appendHiddenInput(form, 'tenant_id', apiMeta('api-tenant'));

  const select = document.createElement('select');
  select.name = 'role_code';
  select.className = 'form-select form-select-sm';
  for (const role of available) {
    const option = document.createElement('option');
    option.value = role.code;
    option.textContent = `${role.code} — ${role.name}`;
    select.appendChild(option);
  }

  const submit = document.createElement('button');
  submit.type = 'submit';
  submit.className = 'btn btn-sm btn-primary';
  submit.textContent = 'Grant';

  form.append(select, submit);

  // Confirm first, gate second — same listener order as appendPostForm.
  form.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    if (!window.confirm(`Grant the ${select.value} role to ${email}?`)) event.preventDefault();
  });
  attachStepUpGate(form);

  rolesGrant.appendChild(form);
}

function appendItems(items) {
  for (const item of items) {
    const user = item.user || null;

    const tr = appendRow(rowsEl, [
      { text: (user && user.email) || '—', cls: 'font-monospace' },
      (user && user.name) || '—',
      user ? statusBadge(user.status) : badge('orphaned', 'bg-warning-subtle text-warning'),
      membershipBadge(item.state),
      { forms: rowForms(item) },
    ]);

    const manage = document.createElement('button');
    manage.type = 'button';
    manage.className = 'btn btn-outline-secondary btn-sm me-1';
    manage.textContent = 'Roles';
    manage.addEventListener('click', () => openRoles(item));
    tr.lastElementChild.insertBefore(manage, tr.lastElementChild.firstChild);
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

  const rolesCancel = document.getElementById('user-roles-cancel');
  if (rolesCancel) {
    rolesCancel.addEventListener('click', () => {
      document.getElementById('user-roles-card').classList.add('d-none');
    });
  }

  if (!apiMeta('api-tenant')) {
    setStateRow(rowsEl, 'No active workspace — ask an administrator to add you to a tenant.', COLS);
    return;
  }

  load();
}

main();

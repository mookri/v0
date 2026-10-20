// Invites console — one-shot read of GET /api/v1/identity/invites?tenant_id=…
// (session cookie + X-Tenant-Id; membership-checked in the action, so a
// denial surfaces as membership_inactive in the alert). The issue form and
// the row revoke forms post the SHARED /platform/invites actions — no
// step-up on either side (the API routes carry none either; the actions
// check tenant + issuer membership). The one-time acceptance token arrives
// on the coded flash after redirect; token hashes never reach this page.

import {
  apiGet,
  apiMeta,
  appendHiddenInput,
  appendPostForm,
  appendRow,
  badge,
  fillWorkspaceBadge,
  fillWorkspaceFields,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('invite-rows');
const alertEl = document.getElementById('invite-alert');

const COLS = 6;

// Stored timestamps are UTC ('… Z' ISO or 'Y-m-d H:i:s'); parse both.
function parseWhen(value) {
  if (!value) return null;
  const text = String(value).replace(' ', 'T');
  const ms = Date.parse(/[zZ]|[+-]\d\d:?\d\d$/.test(text) ? text : `${text}Z`);
  return Number.isNaN(ms) ? null : ms;
}

function isExpired(item) {
  const expires = parseWhen(item.expires_at);
  return expires !== null && expires < Date.now();
}

// ISSUED + past expiry reads as expired even before the daily sweep
// flips the stored state (doc 06 — the sweep owns the column).
function stateBadge(item) {
  if (item.state === 'ISSUED') {
    return isExpired(item)
      ? badge('expired', 'bg-warning-subtle text-warning')
      : badge('issued', 'bg-success-subtle text-success');
  }
  if (item.state === 'ACCEPTED') return badge('accepted', 'bg-info-subtle text-info');
  if (item.state === 'REVOKED') return badge('revoked', 'bg-secondary-subtle text-secondary');
  return badge('expired', 'bg-warning-subtle text-warning');
}

// Only live invites get a revoke form — dead buttons teach nothing.
function rowForms(item) {
  if (item.state !== 'ISSUED' || isExpired(item)) return [];

  return [
    {
      action: `/platform/invites/${item.id}:revoke`,
      label: 'Revoke',
      cls: 'btn-outline-danger btn-sm',
      fields: { tenant_id: apiMeta('api-tenant') },
      confirmMessage: `Revoke the invite for ${item.identifier}? The token dies immediately.`,
    },
  ];
}

function appendItems(items) {
  for (const item of items) {
    appendRow(rowsEl, [
      { text: item.identifier || '—' },
      { text: item.track || '—' },
      { text: (Array.isArray(item.roles) && item.roles.length > 0) ? item.roles.join(', ') : '—' },
      stateBadge(item),
      { text: item.expires_at || '—' },
      { forms: rowForms(item) },
    ]);
  }
}

async function load() {
  if (alertEl) alertEl.classList.add('d-none');
  setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/identity/invites', { tenant_id: apiMeta('api-tenant') });
    const items = Array.isArray(body && body.data) ? body.data : [];
    rowsEl.innerHTML = '';

    if (items.length === 0) {
      setStateRow(rowsEl, 'No invites yet — issue the first one above.', COLS);
      return;
    }

    appendItems(items);
  } catch (error) {
    showError(alertEl, error);
    setStateRow(rowsEl, 'Could not load invites.', COLS);
  }
}

// The create form posts human comma-separated roles and an optional TTL;
// the validator wants an array and a strict integer. Split into roles[]
// and drop a blank ttl_hours (name stripped, restored on input) on submit
// — presentation concern, the shared server validation never changes.
function prepareIssueForm(form) {
  form.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;

    if (!form.querySelector('input[name="roles[]"]')) {
      const roles = form.querySelector('[name="roles_text"]');
      if (roles) {
        for (const value of roles.value.split(',').map((part) => part.trim()).filter(Boolean)) {
          appendHiddenInput(form, 'roles[]', value);
        }
      }
    }

    const ttl = form.querySelector('[name="ttl_hours"]');
    if (ttl && ttl.value.trim() === '') {
      ttl.removeAttribute('name');
    }
  });

  const ttl = form.querySelector('[name="ttl_hours"]');
  if (ttl) {
    ttl.addEventListener('input', () => ttl.setAttribute('name', 'ttl_hours'));
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('invite-workspace'));
  fillWorkspaceFields();

  const issueForm = document.getElementById('invite-create-form');
  if (issueForm) prepareIssueForm(issueForm);

  if (!apiMeta('api-tenant')) {
    setStateRow(rowsEl, 'Select a workspace to list its invites.', COLS);
    return;
  }

  load();
}

main();

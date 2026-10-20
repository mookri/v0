// API keys list — one-shot read of GET /api/v1/platform/api-keys?tenant_id=…
// (session cookie + X-Tenant-Id; membership-checked in the action, so a
// denial surfaces as membership_inactive in the alert). The issue form and
// the row forms post the SHARED issue/rotate/revoke actions (stepup:2) —
// confirm, then the shared step-up gate (doc 06 §7.5). The one-time secret
// arrives on the coded flash after redirect; nothing secret is ever
// rendered into this table.

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

const rowsEl = document.getElementById('api-key-rows');
const alertEl = document.getElementById('api-key-alert');

const COLS = 8;

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

function statusBadge(item) {
  if (item.revoked_at) return badge('revoked', 'bg-secondary-subtle text-secondary');
  if (isExpired(item)) return badge('expired', 'bg-warning-subtle text-warning');
  return badge('active', 'bg-success-subtle text-success');
}

// Lifecycle forms post the shared web actions (mirroring the API guards
// exactly); `stepUp: true` wires the freshness gate inside appendPostForm.
// Revoked and expired keys get no forms — rotate would 404, revoke would
// be a no-op; dead buttons teach nothing.
function rowForms(item) {
  const tenant = apiMeta('api-tenant');
  if (item.revoked_at || isExpired(item)) return [];

  return [
    {
      action: `/platform/api-keys/${item.id}:rotate`,
      label: 'Rotate',
      cls: 'btn-outline-primary btn-sm',
      fields: { tenant_id: tenant },
      confirmMessage: `Rotate ${item.name}? The new secret is shown once.`,
      stepUp: true,
    },
    {
      action: `/platform/api-keys/${item.id}:revoke`,
      label: 'Revoke',
      cls: 'btn-outline-danger btn-sm',
      fields: { tenant_id: tenant },
      confirmMessage: `Revoke ${item.name} (${item.prefix})? It dies immediately.`,
      stepUp: true,
    },
  ];
}

function appendItems(items) {
  for (const item of items) {
    appendRow(rowsEl, [
      { text: item.name || '—' },
      { text: item.prefix || '—', cls: 'font-monospace' },
      { text: (Array.isArray(item.scopes) && item.scopes.length > 0) ? item.scopes.join(', ') : '—' },
      { text: item.rate_tier || 'standard' },
      { text: item.expires_at || 'never' },
      { text: item.last_used_at || 'never' },
      statusBadge(item),
      { forms: rowForms(item) },
    ]);
  }
}

async function load() {
  if (alertEl) alertEl.classList.add('d-none');
  setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/platform/api-keys', { tenant_id: apiMeta('api-tenant') });
    const items = Array.isArray(body && body.data) ? body.data : [];

    rowsEl.innerHTML = '';

    if (items.length === 0) {
      setStateRow(rowsEl, 'No API keys yet — issue the first one above.', COLS);
      return;
    }

    appendItems(items);
  } catch (error) {
    showError(alertEl, error);
    setStateRow(rowsEl, 'Could not load API keys.', COLS);
  }
}

// The create form posts human comma-separated scopes and an optional TTL;
// the validator wants an array and a strict integer. Split into scopes[]
// and drop a blank ttl_hours (name stripped, restored on input) on submit
// — presentation concern, the shared server validation never changes.
// Registered BEFORE the step-up gate: a held post already carries the
// converted fields, and form.submit() after verification bypasses
// listeners with them still in the DOM.
function prepareIssueForm(form) {
  form.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;

    if (!form.querySelector('input[name="scopes[]"]')) {
      const scopes = form.querySelector('[name="scopes_text"]');
      if (scopes) {
        for (const value of scopes.value.split(',').map((part) => part.trim()).filter(Boolean)) {
          appendHiddenInput(form, 'scopes[]', value);
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

  attachStepUpGate(form);
}

function main() {
  fillWorkspaceBadge(document.getElementById('api-key-workspace'));

  const issueForm = document.getElementById('api-key-create-form');
  if (issueForm) prepareIssueForm(issueForm);

  if (!apiMeta('api-tenant')) {
    setStateRow(rowsEl, 'Select a workspace to list its API keys.', COLS);
    return;
  }

  load();
}

main();

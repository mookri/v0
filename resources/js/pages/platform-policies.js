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
  fillWorkspaceFields,
  setStateRow,
  showError,
} from '../lib/admin-api.js';

const rowsEl = document.getElementById('policy-rows');
const alertEl = document.getElementById('policy-alert');
const moreBtn = document.getElementById('policy-load-more');

const COLS = 7;
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

function selectorText(selector) {
  if (selector && typeof selector === 'object') {
    return [selector.module, selector.resource, selector.action].filter(Boolean).join(':');
  }
  return String(selector || '');
}

// State transitions post to the SHARED web actions (doc 06 §7.1-7.2) —
// cookie sessions never authenticate API writes. Draft activates; active
// disables; disabled activates again.
function rowForms(item) {
  const tenant = apiMeta('api-tenant');
  const forms = [];

  if (item.status === 'draft' || item.status === 'disabled') {
    forms.push({
      action: `/platform/policies/${item.id}:activate`,
      label: 'Activate',
      cls: 'btn-outline-success btn-sm',
      fields: { tenant_id: tenant },
    });
  }
  if (item.status === 'active') {
    forms.push({
      action: `/platform/policies/${item.id}:disable`,
      label: 'Disable',
      cls: 'btn-outline-danger btn-sm',
      fields: { tenant_id: tenant },
    });
  }

  return forms;
}

// The edit card shares the create grammar; JS points its action at the
// row being edited — a form-fill, not a mutation, until Save posts to
// the shared PATCH action (doc 06 §7.1-7.2).
function setField(id, value) {
  const el = document.getElementById(id);
  if (el) el.value = value;
}

function openEdit(item) {
  const form = document.getElementById('policy-edit-form');
  const card = document.getElementById('policy-edit-card');
  if (!form || !card) return;

  form.action = `/platform/policies/${item.id}`;
  setField('policy-edit-title', item.name || item.id);
  setField('policy-edit-name', item.name || '');
  setField('policy-edit-effect', item.effect || 'allow');
  setField('policy-edit-priority', String(item.priority ?? 0));

  const selector = item.capability_selector && typeof item.capability_selector === 'object'
    ? item.capability_selector
    : {};
  setField('policy-edit-selector-module', selector.module || '');
  setField('policy-edit-selector-resource', selector.resource || '');
  setField('policy-edit-selector-action', selector.action || '');
  setField('policy-edit-description', item.description || '');

  card.classList.remove('d-none');
  card.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function appendItems(items) {
  for (const item of items) {
    const tr = appendRow(rowsEl, [
      item.name || '',
      effectBadge(item.effect),
      { text: selectorText(item.capability_selector), cls: 'font-monospace' },
      { text: String(item.priority ?? ''), cls: 'text-end' },
      statusBadge(item.status),
      { text: String(item.version ?? ''), cls: 'text-end' },
      { forms: rowForms(item) },
    ]);

    const edit = document.createElement('button');
    edit.type = 'button';
    edit.className = 'btn btn-outline-primary btn-sm me-1';
    edit.textContent = 'Edit';
    edit.addEventListener('click', () => openEdit(item));
    tr.lastElementChild.insertBefore(edit, tr.lastElementChild.firstChild);
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
  fillWorkspaceFields();

  const cancelBtn = document.getElementById('policy-edit-cancel');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => {
      document.getElementById('policy-edit-card').classList.add('d-none');
    });
  }

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

// Pandit directory — the admin-shell pattern page for live module reads:
// cursor-loop fetches of GET /api/v1/pandit/profiles through the shared
// same-origin helper (session cookie + X-Tenant-Id), coded-error rendering
// with the request id, and an explicit "Load more" cursor advance.

import { apiGet, apiMeta } from '../lib/admin-api.js';

const rowsEl = document.getElementById('pandit-rows');
const alertEl = document.getElementById('pandit-alert');
const moreBtn = document.getElementById('pandit-load-more');
const workspaceEl = document.getElementById('pandit-workspace');

let cursor = null;
let loaded = false;

function fillWorkspaceBadge() {
  if (!workspaceEl) return;

  const tenant = apiMeta('api-tenant');
  if (!tenant) {
    workspaceEl.textContent = 'No workspace';
    return;
  }

  try {
    const memberships = JSON.parse(apiMeta('api-memberships') || '[]');
    const match = memberships.find((m) => m && m.id === tenant);
    workspaceEl.textContent = (match && match.name) || tenant;
  } catch {
    workspaceEl.textContent = tenant;
  }
}

function setState(message) {
  rowsEl.innerHTML = '';
  const tr = document.createElement('tr');
  const td = document.createElement('td');
  td.colSpan = 5;
  td.className = 'text-center text-muted';
  td.textContent = message;
  tr.appendChild(td);
  rowsEl.appendChild(tr);
}

function cell(text, className) {
  const td = document.createElement('td');
  td.textContent = text;
  if (className) td.className = className;
  return td;
}

function appendItems(items) {
  for (const item of items) {
    const tr = document.createElement('tr');

    tr.appendChild(cell(item.display_name || ''));

    tr.appendChild(cell(Array.isArray(item.specializations) ? item.specializations.join(', ') : ''));

    const count = Number(item.rating_count || 0);
    tr.appendChild(cell(
      count > 0 ? `${Number(item.rating_avg || 0).toFixed(1)} / 5 (${count})` : '—',
    ));

    tr.appendChild(cell(item.market || ''));

    const minor = item.base_rate_minor;
    const rate = minor === null || minor === undefined
      ? '—'
      : `${Number(minor).toLocaleString()} ${item.currency || ''}`.trim();
    tr.appendChild(cell(rate, 'text-end'));

    rowsEl.appendChild(tr);
  }
}

function showError(error) {
  if (!alertEl) return;
  const parts = [error.code || 'request_failed', error.message || 'Something went wrong.'];
  if (error.requestId) parts.push(`request ${error.requestId}`);
  alertEl.textContent = parts.join(' — ');
  alertEl.classList.remove('d-none');
}

async function loadPage() {
  if (alertEl) alertEl.classList.add('d-none');
  if (!loaded) setState('Loading…');

  try {
    const body = await apiGet('/pandit/profiles', { limit: 10, cursor });
    const items = Array.isArray(body && body.data) ? body.data : [];
    const page = (body && body.meta && body.meta.page) || {};

    if (!loaded) {
      rowsEl.innerHTML = '';
      loaded = true;
    }

    appendItems(items);

    if (items.length === 0 && rowsEl.children.length === 0) {
      setState('No pandits in this workspace yet.');
    }

    cursor = page.has_more && page.next_cursor ? page.next_cursor : null;
    if (moreBtn) moreBtn.classList.toggle('d-none', !cursor);
  } catch (error) {
    showError(error);
    if (!loaded) setState('Unable to load the directory.');
  }
}

function main() {
  fillWorkspaceBadge();

  if (!apiMeta('api-tenant')) {
    setState('No active workspace — ask an administrator to add you to a tenant.');
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

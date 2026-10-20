// Pandit directory — the admin-shell pattern page for live module reads:
// cursor-loop fetches of GET /api/v1/pandit/profiles through the shared
// same-origin helper (session cookie + X-Tenant-Id), coded-error rendering
// with the request id, and an explicit "Load more" cursor advance.

import { apiGet, apiMeta, appendRow, badge, fillWorkspaceBadge, setStateRow, showError } from '../lib/admin-api.js';

const rowsEl = document.getElementById('pandit-rows');
const alertEl = document.getElementById('pandit-alert');
const moreBtn = document.getElementById('pandit-load-more');

const COLS = 5;
let cursor = null;
let loaded = false;

function appendItems(items) {
  for (const item of items) {
    const count = Number(item.rating_count || 0);
    const minor = item.base_rate_minor;

    appendRow(rowsEl, [
      item.display_name || '',
      Array.isArray(item.specializations) ? item.specializations.join(', ') : '',
      count > 0 ? badge(`${Number(item.rating_avg || 0).toFixed(1)} / 5 (${count})`, 'bg-primary-subtle text-primary') : badge('—'),
      item.market || '',
      {
        text: minor === null || minor === undefined ? '—' : `${Number(minor).toLocaleString()} ${item.currency || ''}`.trim(),
        cls: 'text-end',
      },
    ]);
  }
}

async function loadPage() {
  if (alertEl) alertEl.classList.add('d-none');
  if (!loaded) setStateRow(rowsEl, 'Loading…', COLS);

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
      setStateRow(rowsEl, 'No pandits in this workspace yet.', COLS);
    }

    cursor = page.has_more && page.next_cursor ? page.next_cursor : null;
    if (moreBtn) moreBtn.classList.toggle('d-none', !cursor);
  } catch (error) {
    showError(alertEl, error);
    if (!loaded) setStateRow(rowsEl, 'Unable to load the directory.', COLS);
  }
}

function main() {
  fillWorkspaceBadge(document.getElementById('pandit-workspace'));

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

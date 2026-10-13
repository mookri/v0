// Security settings page — profile, TOTP second factor, and web session
// inventory (doc 06 §7). Reads go through apiGet (session cookie, GET/HEAD
// only — the slice-1 session middleware); writes are plain CSRF forms to
// the SHARED /account/security actions — the API stays bearer/CSRF-exempt,
// so cookie-authenticated writes never post there. One-time values (setup
// key, recovery codes) arrive on the coded flash after redirect and are
// never rendered by this file.

import { apiGet, appendRow, badge, setStateRow, showError } from '../lib/admin-api.js';

const statusEl = document.getElementById('mfa-status');
const alertEl = document.getElementById('security-alert');
const rowsEl = document.getElementById('session-rows');

const COLS = 6;

function toggle(id, show) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('d-none', !show);
}

// Two states from the status read: enabled shows the manage pair
// (regenerate / disable — both need a live code), not-enabled shows
// the setup pair. Re-enrolling overwrites any pending secret, so the
// enroll button doubles as "start over".
async function loadMfa() {
  if (!statusEl) return;

  try {
    const body = await apiGet('/identity/mfa');
    const data = (body && body.data) || {};
    const enabled = Boolean(data.enabled);

    statusEl.textContent = enabled
      ? `Enabled${data.enrolled_at ? ` since ${data.enrolled_at}` : ''} — a code from your app (or a recovery code) is required for sensitive actions.`
      : 'Not enabled — sensitive actions (suspend, role grants, API keys) will refuse until you set it up.';

    toggle('mfa-stepup-form', !enabled);
    // Rendered only while an emailed step-up code is pending (server-side),
    // so reveal it whenever the setup pair is relevant.
    toggle('mfa-stepup-verify-form', !enabled);
    toggle('mfa-confirm-form', !enabled);
    toggle('mfa-regenerate-form', enabled);
    toggle('mfa-revoke-form', enabled);
  } catch (error) {
    showError(alertEl, error);
  }
}

function whenLabel(value) {
  const ms = Date.parse(String(value));
  return Number.isNaN(ms) ? String(value ?? '—') : new Date(ms).toLocaleString();
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

async function loadSessions() {
  if (!rowsEl) return;
  setStateRow(rowsEl, 'Loading…', COLS);

  try {
    const body = await apiGet('/identity/sessions');
    const items = Array.isArray(body && body.data) ? body.data : [];
    rowsEl.innerHTML = '';

    if (items.length === 0) {
      setStateRow(rowsEl, 'No sessions found.', COLS);
      return;
    }

    for (const item of items) {
      const isCurrent = Boolean(item.current);
      const revokeBtn = !isCurrent
        ? `<form method="POST" action="/account/security/sessions/${item.id}/revoke" class="d-inline" onsubmit="return window.confirm('Revoke this session?')">
             <input type="hidden" name="_token" value="{{ csrf_token() }}">
             <button class="btn btn-sm btn-outline-danger" type="submit">Revoke</button>
           </form>`
        : '<span class="text-muted">Current</span>';

      const labelHtml = item.label
        ? `<span class="badge bg-info-subtle text-info">${escapeHtml(item.label)}</span>`
        : '<span class="text-muted">—</span>';

      appendRow(rowsEl, [
        { text: item.ip_address || '—', cls: 'font-monospace' },
        { text: item.user_agent || '—' },
        { html: labelHtml },
        { text: whenLabel(item.issued_at) },
        isCurrent
          ? badge('current', 'bg-success-subtle text-success')
          : badge('other', 'bg-secondary-subtle text-secondary'),
        { html: revokeBtn, cls: 'text-end' },
      ]);
    }
  } catch (error) {
    showError(alertEl, error);
    setStateRow(rowsEl, 'Could not load sessions.', COLS);
  }
}

// Destructive forms confirm before posting: the submitting button
// carries data-confirm (skip confirms when Enter submits without one).
function attachConfirms() {
  for (const button of document.querySelectorAll('button[data-confirm]')) {
    const form = button.form;
    if (!form) continue;

    form.addEventListener('submit', (event) => {
      if (event.defaultPrevented) return;
      if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
  }
}

function main() {
  attachConfirms();
  loadMfa();
  loadSessions();
}

main();
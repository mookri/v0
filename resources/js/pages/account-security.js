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

const COLS = 4;

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

    toggle('mfa-enroll-form', !enabled);
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
      appendRow(rowsEl, [
        { text: item.ip_address || '—', cls: 'font-monospace' },
        { text: item.user_agent || '—' },
        { text: whenLabel(item.last_activity_at) },
        item.current
          ? badge('current', 'bg-success-subtle text-success')
          : badge('other', 'bg-secondary-subtle text-secondary'),
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

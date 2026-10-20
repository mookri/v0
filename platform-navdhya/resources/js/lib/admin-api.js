// Same-origin API helper for admin-shell pages (doc 06 §8.4). Reads the
// page's API context meta tags (rendered by <x-api-context />), rides the
// web session cookie on safe reads (the api group never starts sessions
// for writes, so mutations must not go through here), scopes calls via
// X-Tenant-Id, and decodes the kernel envelope into coded errors.

export function apiMeta(name) {
  const el = document.querySelector(`meta[name="${name}"]`);
  return el ? el.getAttribute('content') || '' : '';
}

export async function apiGet(path, params = {}) {
  const base = apiMeta('api-base') || '/api/v1';
  const tenant = apiMeta('api-tenant');

  const query = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && value !== '') {
      query.set(key, String(value));
    }
  }
  const qs = query.toString();

  const headers = { Accept: 'application/json' };
  if (tenant) headers['X-Tenant-Id'] = tenant;

  const response = await fetch(`${base}${path}${qs ? `?${qs}` : ''}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers,
  });

  let body = null;
  try {
    body = await response.json();
  } catch {
    body = null;
  }

  if (response.status === 401) {
    window.location.assign('/login');
    throw Object.assign(new Error('Sign in to continue.'), {
      code: 'UNAUTHENTICATED',
      requestId: '',
      status: 401,
    });
  }

  if (!response.ok) {
    const envelope = body && typeof body.error === 'object' && body.error !== null ? body.error : {};
    throw Object.assign(new Error(envelope.message || `Request failed (${response.status}).`), {
      code: envelope.code || 'request_failed',
      requestId: envelope.request_id || (body && body.meta ? body.meta.request_id : '') || '',
      status: response.status,
    });
  }

  return body;
}

// ---------------------------------------------------------------------------
// Shared list-shell helpers. Every Blade list page follows the same shape:
// a workspace badge, a table whose tbody is the only mutable mount point
// (loading / empty / error are state rows inside it), a coded-error alert,
// and an optional cursor-driven "Load more". Rows are built with
// textContent only — server data never reaches innerHTML.
// ---------------------------------------------------------------------------

/** Cell spec for appendRow: a plain string, {text, cls}, or a badge. */
export function badge(text, cls) {
  return { badge: { text: String(text ?? ''), cls: cls || 'bg-secondary-subtle text-secondary' } };
}

/** Fill the card-header workspace badge from the page's API context. */
export function fillWorkspaceBadge(el) {
  if (!el) return;

  const tenant = apiMeta('api-tenant');
  if (!tenant) {
    el.textContent = 'No workspace';
    return;
  }

  try {
    const memberships = JSON.parse(apiMeta('api-memberships') || '[]');
    const match = memberships.find((m) => m && m.id === tenant);
    el.textContent = (match && match.name) || tenant;
  } catch {
    el.textContent = tenant;
  }
}

/** Fill hidden workspace fields (web form transport for tenant_id). */
export function fillWorkspaceFields() {
  const tenant = apiMeta('api-tenant');
  document.querySelectorAll('input[data-workspace-field]').forEach((el) => {
    el.value = tenant;
  });
}

/** Append a hidden input to a form. */
export function appendHiddenInput(form, name, value) {
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = name;
  input.value = String(value ?? '');
  form.appendChild(input);
}

/**
 * Append a CSRF-carrying mutation form (doc 06 §7.4) — JS-rendered rows
 * build what Blade's @csrf would for a server-rendered one: _token from
 * the page meta, _method spoofing for PATCH/DELETE, extra hidden fields
 * (workspace id), an optional submit confirmation, and the step-up gate
 * for `stepUp: true` (sensitive) forms. These forms post to the SHARED
 * web actions, never to /api/v1 (cookie sessions never authenticate
 * API writes). Returns the form so callers can enrich it further.
 */
export function appendPostForm(parent, { action, label, cls = 'btn-outline-primary', method = 'POST', fields = {}, confirmMessage = '', stepUp = false }) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = action;
  form.className = 'd-inline';

  const token = apiMeta('csrf-token');
  if (token) appendHiddenInput(form, '_token', token);
  if (method !== 'POST') appendHiddenInput(form, '_method', method);

  for (const [name, value] of Object.entries(fields)) {
    appendHiddenInput(form, name, value);
  }

  const button = document.createElement('button');
  button.type = 'submit';
  button.className = `btn ${cls}`;
  button.textContent = label;
  form.appendChild(button);

  if (confirmMessage) {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(confirmMessage)) event.preventDefault();
    });
  }

  // Sensitive forms (stepup:2 server gate): the gate registers AFTER the
  // optional confirm, so a cancelled confirm leaves defaultPrevented set
  // and the gate stays quiet.
  if (stepUp) attachStepUpGate(form);

  parent.appendChild(form);

  return form;
}

/** Replace the table body with a single centered state row. */
export function setStateRow(rowsEl, message, colSpan) {
  rowsEl.innerHTML = '';
  const tr = document.createElement('tr');
  const td = document.createElement('td');
  td.colSpan = colSpan;
  td.className = 'text-center text-muted';
  td.textContent = message;
  tr.appendChild(td);
  rowsEl.appendChild(tr);
}

/** Append one row of cells (strings, {text, cls}, badge specs, or
 * {forms: [...]} action cells built by appendPostForm) and return the
 * row so pages can enrich the last cell (e.g. a read-only Open button). */
export function appendRow(rowsEl, cells) {
  const tr = document.createElement('tr');
  for (const spec of cells) {
    const td = document.createElement('td');
    if (typeof spec === 'string') {
      td.textContent = spec;
    } else if (spec && spec.badge) {
      const span = document.createElement('span');
      span.className = spec.badge.cls || 'badge bg-secondary-subtle text-secondary';
      span.textContent = spec.badge.text || '';
      td.appendChild(span);
    } else if (spec && Array.isArray(spec.forms)) {
      for (const options of spec.forms) {
        // appendPostForm wires csrf/_method/fields, the optional confirm
        // and the step-up gate itself.
        appendPostForm(td, options);
      }
    } else {
      td.textContent = spec && spec.text !== undefined ? String(spec.text) : '';
      if (spec && spec.cls) td.className = spec.cls;
    }
    tr.appendChild(td);
  }
  rowsEl.appendChild(tr);
  return tr;
}

/** Render a coded kernel error (code — message — request id) in the alert. */
export function showError(alertEl, error) {
  if (!alertEl) return;
  const parts = [error.code || 'request_failed', error.message || 'Something went wrong.'];
  if (error.requestId) parts.push(`request ${error.requestId}`);
  alertEl.textContent = parts.join(' — ');
  alertEl.classList.remove('d-none');
}

// ---- Step-up gate (doc 06 §7.5) -------------------------------------------
// Sensitive forms confirm first (appendPostForm's own listener), then this
// gate: probe /oauth/stepup; while the proof is fresh the form posts right
// away, otherwise a modal collects the TOTP/recovery code, posts the
// step-up endpoint, and a re-probe decides the outcome — a followed
// redirect can never masquerade as success. Fail-closed by construction:
// without MFA or a verified code the form never posts, and the server's
// `stepup:2` middleware answers 403 step_up_required regardless.

let stepUpUi = null;

function ensureStepUpModal() {
  if (stepUpUi) return stepUpUi;

  const el = document.createElement('div');
  el.className = 'modal fade';
  el.id = 'admin-stepup-modal';
  el.tabIndex = -1;
  el.innerHTML = [
    '<div class="modal-dialog modal-dialog-centered">',
    '  <div class="modal-content">',
    '    <div class="modal-header"><h5 class="modal-title">Verify your identity</h5></div>',
    '    <div class="modal-body">',
    '      <p class="text-muted mb-2" id="admin-stepup-hint">Enter the code from your authenticator app (or a recovery code).</p>',
    '      <div class="alert alert-danger d-none" id="admin-stepup-error" role="alert"></div>',
    '      <input class="form-control font-monospace" id="admin-stepup-code" inputmode="numeric" autocomplete="one-time-code" maxlength="12" placeholder="123456">',
    '    </div>',
    '    <div class="modal-footer">',
    '      <button type="button" class="btn btn-outline-secondary" id="admin-stepup-cancel">Cancel</button>',
    '      <button type="button" class="btn btn-primary" id="admin-stepup-submit">Verify</button>',
    '    </div>',
    '  </div>',
    '</div>',
  ].join('');
  document.body.appendChild(el);

  const hint = el.querySelector('#admin-stepup-hint');
  const error = el.querySelector('#admin-stepup-error');
  const code = el.querySelector('#admin-stepup-code');
  const submit = el.querySelector('#admin-stepup-submit');
  const cancel = el.querySelector('#admin-stepup-cancel');
  const modal = window.bootstrap ? new window.bootstrap.Modal(el) : null;

  stepUpUi = {
    hint,
    error,
    code,
    submit,
    show() {
      error.classList.add('d-none');
      if (modal) {
        modal.show();
      } else {
        el.classList.add('show', 'd-block');
      }
      code.focus();
    },
    hide() {
      if (modal) {
        modal.hide();
      } else {
        el.classList.remove('show', 'd-block');
      }
    },
  };

  code.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      if (submit.onclick) submit.onclick();
    }
  });
  cancel.addEventListener('click', () => stepUpUi.hide());

  return stepUpUi;
}

async function stepUpStatus() {
  const res = await fetch('/oauth/stepup', {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  });
  if (!res.ok) throw new Error(`step-up probe ${res.status}`);
  const body = await res.json();
  return body && body.data ? body.data : {};
}

async function stepUpVerify(code) {
  const data = new FormData();
  data.append('_token', apiMeta('csrf-token') || '');
  data.append('code', code);
  data.append('return_to', window.location.pathname + window.location.search);
  // redirect: manual stops the browser from following the POST's 302
  // inside the fetch — the outcome is decided by the follow-up probe, so
  // success and failure cannot be confused by a followed redirect.
  await fetch('/oauth/stepup', {
    method: 'POST',
    body: data,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
    redirect: 'manual',
  });
}

function openStepUpGate(form) {
  const ui = ensureStepUpModal();
  ui.error.classList.add('d-none');
  ui.error.textContent = '';
  ui.code.value = '';

  const holdForInput = (message) => {
    // Fixed constants only (never user input) — the enrollment nudge
    // carries a link to the security page the notice now promises.
    ui.hint.innerHTML = message;
    ui.code.classList.add('d-none');
    ui.submit.classList.add('d-none');
    ui.show();
  };

  stepUpStatus().then((status) => {
    if (status.fresh) {
      form.submit();
      return;
    }
    if (!status.mfa_enabled) {
      holdForInput(
        'Multi-factor authentication is required for this action. Enroll an authenticator app in your <a href="/account/security">security settings</a> first.',
      );
      return;
    }
    ui.hint.textContent = 'Enter the code from your authenticator app (or a recovery code).';
    ui.code.classList.remove('d-none');
    ui.submit.classList.remove('d-none');
    ui.show();
  }).catch(() => {
    // Probe failed (offline, 5xx): hold the post — fail-closed.
    ui.error.textContent = 'Cannot verify your session right now — check your connection and try again.';
    ui.error.classList.remove('d-none');
    holdForInput('Cannot verify your session right now.');
  });

  ui.submit.onclick = async () => {
    const code = ui.code.value.trim();
    if (!code) {
      ui.error.textContent = 'Enter your code.';
      ui.error.classList.remove('d-none');
      return;
    }
    ui.submit.disabled = true;
    try {
      await stepUpVerify(code);
      const after = await stepUpStatus();
      if (after.fresh) {
        ui.hide();
        form.submit();
        return;
      }
      ui.error.textContent = 'Invalid or expired code.';
      ui.error.classList.remove('d-none');
    } catch {
      ui.error.textContent = 'Verification failed — try again.';
      ui.error.classList.remove('d-none');
    } finally {
      ui.submit.disabled = false;
    }
  };
}

/**
 * Hold a sensitive form's post until a fresh MFA proof exists (doc 06
 * §7.5). Registered AFTER any confirm listener so a cancelled confirm
 * leaves defaultPrevented set and the gate stays quiet; after verification
 * form.submit() bypasses listeners entirely (no confirm re-prompt, no gate
 * loop).
 */
export function attachStepUpGate(form) {
  form.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    event.preventDefault();
    openStepUpGate(form);
  });
}

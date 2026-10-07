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
 * (workspace id), and an optional submit confirmation. These forms post
 * to the SHARED web actions, never to /api/v1 (cookie sessions never
 * authenticate API writes).
 */
export function appendPostForm(parent, { action, label, cls = 'btn-outline-primary', method = 'POST', fields = {}, confirmMessage = '' }) {
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

  parent.appendChild(form);
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
 * {forms: [...]} action cells built by appendPostForm). */
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
        appendPostForm(td, options);
      }
    } else {
      td.textContent = spec && spec.text !== undefined ? String(spec.text) : '';
      if (spec && spec.cls) td.className = spec.cls;
    }
    tr.appendChild(td);
  }
  rowsEl.appendChild(tr);
}

/** Render a coded kernel error (code — message — request id) in the alert. */
export function showError(alertEl, error) {
  if (!alertEl) return;
  const parts = [error.code || 'request_failed', error.message || 'Something went wrong.'];
  if (error.requestId) parts.push(`request ${error.requestId}`);
  alertEl.textContent = parts.join(' — ');
  alertEl.classList.remove('d-none');
}

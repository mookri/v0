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

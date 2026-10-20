import { isFederated } from '@/server/federation';
import { backendErrorResponse, dashboardFetch } from '@/server/backend';

interface LoginResponse {
  token: string;
  user: { id: string; email: string; name: string; role: 'admin' | 'manager' | 'staff' };
}

/**
 * POST /api/auth/login — the direct ceremony's proxy (ADR-0020).
 *
 *  - federated: the issuer is sole — the legacy ceremony does not exist
 *    in federated deployments; the browser hands off through
 *    /api/auth/authorize instead. 404, like customer/web.
 *  - direct: proxied verbatim to the configured dashboard backend
 *    (DASHBOARD_API_BASE), which owns the {token, user} contract the SPA
 *    client speaks. No backend configured (today's state of this repo) →
 *    503: a loud "cannot sign in", never a form that silently dead-ends.
 *
 * Error bodies are `{ message }` — the SPA client's failure contract.
 */
export async function POST(req: Request): Promise<Response> {
  if (isFederated()) {
    return Response.json(
      { message: 'This deployment authenticates at the auth subdomain.' },
      { status: 404 },
    );
  }

  let body: { email?: unknown; password?: unknown };
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json({ message: 'Invalid request body.' }, { status: 400 });
  }
  if (typeof body.email !== 'string' || typeof body.password !== 'string') {
    return Response.json({ message: 'email and password are required.' }, { status: 400 });
  }

  try {
    const result = await dashboardFetch<LoginResponse>('/auth/login', {
      method: 'POST',
      body: { email: body.email, password: body.password },
    });
    return Response.json(result);
  } catch (error) {
    return backendErrorResponse(error);
  }
}

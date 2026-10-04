import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface DocumentResponse {
  data: { id: string; object: string; kind: string; status: string };
  meta: { request_id?: string };
}

export async function POST(req: Request, { params }: { params: Promise<{ applicationId: string }> }) {
  const requestId = newRequestId();
  const { applicationId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.onboarding.documents', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('VALIDATION_ERROR', 'Invalid document upload.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const formData = await req.formData();
    const kind = formData.get('kind') as string | null;
    const file = formData.get('file') as File | null;

    if (!kind || !file) {
      return Response.json(err('VALIDATION_ERROR', 'Kind and file are required.', requestId), { status: 422 });
    }

    // For file uploads, we need to send multipart/form-data to Laravel
    // Convert to form data for the backend call
    const backendFormData = new FormData();
    backendFormData.append('kind', kind);
    backendFormData.append('file', file);

    const { laravelCall } = await import('@/adapters/backend/laravel-client');
    const { data } = await laravelCall<DocumentResponse>(
      actor.token,
      'POST',
      `/onboarding/applications/${encodeURIComponent(applicationId)}/documents`,
      {},
      backendFormData,
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
      {
        'Content-Type': 'multipart/form-data', // Will be overridden by browser with boundary
      },
    );

    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to upload document.', requestId);
  }
}
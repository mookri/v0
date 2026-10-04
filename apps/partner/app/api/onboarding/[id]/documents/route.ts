import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, headersToRecord } from '@/application/actor';
import { laravelEnabled, laravelCall, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import type { DocumentKind } from '@navdhya/partner/src/onboarding/documents';

interface DocumentResponse {
  data: { id: string; object: string; kind: string; status: string };
  meta: { request_id?: string };
}

const KINDS = ['id-proof', 'address-proof', 'certification', 'bank-proof', 'photo'] as const;

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    // Dev/mock fallback
    const { onboardingDocs, onboardingUploads, loadOwnApplication } = await import('@/server/onboarding');

    const loaded = await loadOwnApplication(req, requestId, id);
    if ('response' in loaded) return loaded.response;
    let body: { kind?: string; contentLength?: number; sha256?: string } = {};
    try {
      body = (await req.json()) as typeof body;
    } catch {
      return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
    }
    if (!body.kind || !KINDS.includes(body.kind as DocumentKind) || !body.contentLength || !body.sha256) {
      return Response.json(err('VALIDATION_ERROR', 'kind, contentLength and sha256 are required.', requestId), { status: 400 });
    }
    const nowIso = new Date().toISOString();
    try {
      const issued = await onboardingUploads.issue({
        applicationId: id,
        kind: body.kind,
        contentLength: body.contentLength,
        sha256: body.sha256,
        nowIso,
      });
      const documentId = `doc_${requestId}`;
      await onboardingDocs.save({
        id: documentId,
        applicationId: id,
        kind: body.kind as DocumentKind,
        status: 'PENDING',
        objectKey: issued.objectKey,
        sha256: body.sha256,
        createdAtIso: nowIso,
      });
      return Response.json(ok({ documentId, url: issued.url, expiresAt: issued.expiresAtIso }, requestId), { status: 201 });
    } catch (e) {
      if (e instanceof Error && e.message === 'UPLOAD_EMPTY') {
        return Response.json(err('VALIDATION_ERROR', 'Empty upload.', requestId), { status: 400 });
      }
      throw e;
    }
  }

  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    // Backend expects multipart/form-data with 'kind' and 'file'
    const formData = await req.formData();
    const kind = formData.get('kind') as string | null;
    const file = formData.get('file') as File | null;

    if (!kind || !KINDS.includes(kind as DocumentKind)) {
      return Response.json(err('VALIDATION_ERROR', 'Invalid or missing kind.', requestId), { status: 400 });
    }
    if (!file) {
      return Response.json(err('VALIDATION_ERROR', 'File is required.', requestId), { status: 400 });
    }

    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? (await import('@/application/actor')).readSessionCookie(req.headers.get('cookie'))
        : null;

    const backendFormData = new FormData();
    backendFormData.append('kind', kind);
    backendFormData.append('file', file);

    const { data } = await laravelCall<DocumentResponse>(
      token,
      'POST',
      `/onboarding/applications/${encodeURIComponent(id)}/documents`,
      {},
      backendFormData,
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
      {
        'Content-Type': 'multipart/form-data',
      },
    );

    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to upload document.', requestId);
  }
}
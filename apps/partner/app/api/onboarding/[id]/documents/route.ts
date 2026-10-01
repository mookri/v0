import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps, onboardingDocs, onboardingUploads } from '@/server/onboarding';
import { headersToRecord } from '@/application/actor';
import { DocumentKind } from '@navdhya/partner/src/onboarding/documents';

const KINDS: DocumentKind[] = ['id-proof', 'address-proof', 'certification', 'bank-proof', 'photo'];

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { id } = await params;
  const app = await onboardingApps.findById(id);
  if (!app) return Response.json(err('ONBOARDING_NOT_FOUND', 'Application not found.', requestId), { status: 404 });
  if (app.identityId !== actor.userId) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
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

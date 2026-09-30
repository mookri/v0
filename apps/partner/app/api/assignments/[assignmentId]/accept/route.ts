import { acceptOffer } from '@/application/assignments';

function err(code: string, message: string, requestId: string) {
  return { error: { code, message, requestId } };
}

export async function POST(_req: Request, { params }: { params: { assignmentId: string } }) {
  const requestId = `req_${Date.now()}`;
  const result = acceptOffer(null, 'unknown', new Date().toISOString(), `ik_${requestId}`);
  if (!result.ok && result.reason === 'NOT_FOUND') {
    return Response.json(err('ASSIGNMENT_NOT_FOUND', 'Assignment not found.', requestId), { status: 404 });
  }
  if (!result.ok && result.reason === 'NOT_OWNED') {
    return Response.json(err('FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  return Response.json(err('ASSIGNMENT_EXPIRED', 'Offer is no longer valid.', requestId), { status: 409 });
}

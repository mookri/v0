import { newRequestId, ok, err } from '@/application/api-envelope';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';
import { laravelEnabled, laravelGet, PartnerApiError } from '@/adapters/backend/laravel-client';

const actors = partnerActorResolver();

export async function GET(req: Request, { params }: { params: Promise<{ templeId: string }> }) {
  const requestId = newRequestId();
  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { templeId } = await params;

  const url = new URL(req.url);
  const state = url.searchParams.get('state') ?? 'PENDING';

  if (laravelEnabled()) {
    try {
      const token = bearerOrSessionCookie(req.headers);
      const response = await laravelGet<Array<Record<string, unknown>>>(
        token,
        `/temple/partner/temples/${encodeURIComponent(templeId)}/council/approvals`,
        { state }
      );
      return Response.json(ok(response.data, requestId));
    } catch (e) {
      if (e instanceof PartnerApiError) {
        return Response.json(err(e.code, e.message, requestId), { status: e.status });
      }
      return Response.json(err('INTERNAL_ERROR', 'Could not fetch council approvals.', requestId), { status: 500 });
    }
  }

  // Mock response for council queue
  const mockApprovals = [
    {
      id: 'appr_01',
      booking_id: 'bkg_rudra_01',
      temple_id: templeId,
      state: 'PENDING',
      devotee_name: 'Aditya Sharma',
      sankalpa: 'Maha Rudrabhishek for family health and prosperity',
      gotra: 'Kashyapa',
      nakshatra: 'Rohini',
      scheduled_at: '2026-11-12T06:00:00Z',
      sla_expires_at: '2026-11-11T18:00:00Z',
      created_at: new Date().toISOString(),
    },
  ];

  return Response.json(ok(mockApprovals, requestId));
}

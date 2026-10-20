import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Backend serializer field names (PanditProfileController::serializeFull)
// differ from the customer view model; map at the BFF boundary.
interface BackendPandit {
  id: string;
  display_name: string;
  bio?: string;
  specializations?: string[];
  market: string;
  languages?: string[];
  experience_years: number;
  rating_avg: number;
  rating_count: number;
  verification_status: string;
  status: string;
}

// The backend serializer does not expose created_at, and no customer view
// renders it, so the BFF does not fabricate a placeholder value.
interface PanditProfile {
  id: string;
  name: string;
  bio?: string;
  specializations: string[];
  market: string;
  languages: string[];
  experience_years: number;
  rating_avg: number;
  review_count: number;
  verification_status: string;
  status: string;
}

function toPanditProfile(profile: BackendPandit): PanditProfile {
  return {
    id: profile.id,
    name: profile.display_name,
    bio: profile.bio,
    specializations: profile.specializations ?? [],
    market: profile.market,
    languages: profile.languages ?? [],
    experience_years: profile.experience_years,
    rating_avg: profile.rating_avg,
    review_count: profile.rating_count,
    verification_status: profile.verification_status,
    status: profile.status,
  };
}

export async function GET(req: Request, { params }: { params: Promise<{ panditId: string }> }) {
  const requestId = newRequestId();
  const { panditId } = await params;

  if (!laravelEnabled()) {
    return Response.json(err('NOT_FOUND', 'Pandit not found.', requestId), { status: 404 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<BackendPandit>(
      actor.token,
      `/pandit/profiles/${encodeURIComponent(panditId)}`,
      {},
      {
        not_found: 'NOT_FOUND',
        PANDIT_NOT_FOUND: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(
      ok(toPanditProfile(data), requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Pandit details are unavailable.', requestId);
  }
}
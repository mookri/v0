import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { postContext } from '@/server/route-guard';
import { emptyCommandSchema } from '@/application/state-schemas';

/**
 * POST /api/notifications/read-all — mark every notification read.
 *
 * Bodyless, naturally idempotent command (contract §6): accepted empty,
 * unknown keys stripped by the schema and never forwarded (§5-style
 * explicitness). AAL1 cookie actor per §7; X-Request-Id propagated (§8).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await postContext(req, requestId, {
    schema: emptyCommandSchema,
    allowEmptyBody: true,
  });
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/customers/notifications:read-all',
      {},
      {},
      { timeout: 'fast', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to mark all notifications as read.', requestId);
  }
}

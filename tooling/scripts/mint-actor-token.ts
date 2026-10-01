// Staging drill helper: mint a bearer token with the dev secret.
// Usage: npx tsx tooling/scripts/mint-actor-token.ts <userId> <role,camma,..> [market]
// Never used in production (INSECURE_DEV_SECRET only verifies against the
// dev fallback, which production must override via SESSION_SIGNING_KEY).
import { mintActorToken, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';

const [userId, rolesCsv, market] = process.argv.slice(2);
if (!userId) {
  console.error('Usage: mint-actor-token.ts <userId> [rolesCsv] [market]');
  process.exit(1);
}
const token = mintActorToken(
  INSECURE_DEV_SECRET,
  {
    userId,
    sessionId: `drill_${userId}_${Date.now()}`,
    roles: (rolesCsv || 'partner').split(','),
    ...(market ? { market } : {}),
  },
  new Date().toISOString(),
  3600,
);
console.log(token);

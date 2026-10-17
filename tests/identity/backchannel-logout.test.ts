import { afterAll, beforeAll, beforeEach, describe, expect, test, vi } from 'vitest';
import { createSign, generateKeyPairSync } from 'node:crypto';

import * as customerOidc from '@apps/customer/server/oidc';
import * as partnerOidc from '@apps/partner/server/oidc';
import * as dashboardOidc from '@apps/dashboard/server/oidc';
import * as webOidc from '@apps/web/server/oidc';

import * as customerBcl from '@apps/customer/server/backchannel-logout';
import * as partnerBcl from '@apps/partner/server/backchannel-logout';
import * as dashboardBcl from '@apps/dashboard/server/backchannel-logout';
import * as webBcl from '@apps/web/server/backchannel-logout';

import { POST as customerReceiver } from '@apps/customer/app/api/auth/backchannel-logout/route';
import { POST as partnerReceiver } from '@apps/partner/app/api/auth/backchannel-logout/route';
import { POST as dashboardReceiver } from '@apps/dashboard/app/api/auth/backchannel-logout/route';
import { POST as webReceiver } from '@apps/web/app/api/auth/backchannel-logout/route';

import { resolveCustomerActor } from '@apps/customer/server/backend-session';
import { federatedActorFromToken } from '@apps/partner/server/session';

/**
 * A-SEC-004-6 RP side: every BFF app's back-channel logout receiver.
 *
 * The four apps are parallel copies (shared env contract, no shared
 * server modules), so the battery runs against ALL four: logout_token
 * verification fails closed on every axis (signature, typ, iss, aud,
 * events, iat window, sid/sub/jti presence), the receiver records a
 * verified token's sid and rejects replays, non-federated deployments
 * don't answer, and the denylist stops actor resolution at the two
 * real choke points (customer's decode path, partner's verified path).
 *
 * Keys are generated in-process; a fetch stub serves the JWKS.
 */

const ISSUER = 'http://issuer.test';
const CLIENT_ID = 'bff-backchannel-client';
const KID = 'test-jwks-kid-1';
const DEFAULT_SID = '01JBACKCHANNELSID00000000000001';
const BACKCHANNEL_EVENT = 'http://schemas.openid.net/event/backchannel-logout';

const { publicKey, privateKey } = generateKeyPairSync('rsa', { modulusLength: 2048 });
const { privateKey: foreignPrivateKey } = generateKeyPairSync('rsa', { modulusLength: 2048 });

const jwk = {
  ...(publicKey.export({ format: 'jwk' }) as Record<string, unknown>),
  kid: KID,
  alg: 'RS256',
  use: 'sig',
};

let jtiCounter = 0;
const nextJti = (): string => `jti-${++jtiCounter}`;
const nowSec = (): number => Math.floor(Date.now() / 1000);

function b64urlJson(value: unknown): string {
  return Buffer.from(JSON.stringify(value)).toString('base64url');
}

function signJwt(
  header: Record<string, unknown>,
  claims: Record<string, unknown>,
  key: object = privateKey,
): string {
  const signingInput = `${b64urlJson(header)}.${b64urlJson(claims)}`;
  const signer = createSign('RSA-SHA256');
  signer.update(signingInput);
  return `${signingInput}.${signer.sign(key).toString('base64url')}`;
}

function logoutClaims(over: Record<string, unknown> = {}): Record<string, unknown> {
  const now = nowSec();
  return {
    iss: ISSUER,
    aud: CLIENT_ID,
    sub: 'fed_subject_1',
    sid: DEFAULT_SID,
    iat: now,
    exp: now + 120,
    jti: nextJti(),
    events: { [BACKCHANNEL_EVENT]: {} },
    ...over,
  };
}

function signedLogout(
  claimsOver: Record<string, unknown> = {},
  headerOver: Record<string, unknown> = {},
  key: object = privateKey,
): string {
  return signJwt({ alg: 'RS256', typ: 'logout+jwt', kid: KID, ...headerOver }, logoutClaims(claimsOver), key);
}

/** A session-bound access-token shape (what the BFF cookies carry). */
function accessToken(sid: string, over: Record<string, unknown> = {}): string {
  const now = nowSec();
  return signJwt(
    { alg: 'RS256', typ: 'at+jwt', kid: KID },
    {
      sub: 'user-7',
      aud: CLIENT_ID,
      exp: now + 600,
      iat: now,
      jti: `at-${nextJti()}`,
      scopes: [],
      sid,
      ...over,
    },
  );
}

interface OidcHarness {
  verifyLogoutToken(
    token: string,
    issuer: string,
    clientId: string,
  ): Promise<{ iss: string; sub: string; sid: string; iat: number; jti: string } | null>;
  clearOidcJwksCache(): void;
}

interface BclHarness {
  rememberLogoutJti(jti: string): boolean;
  markSidTerminated(sid: string): void;
  isSidTerminated(sid: string): boolean;
  payloadSidTerminated(payload: Record<string, unknown>): boolean;
  clearBackchannelLogoutState(): void;
}

interface AppHarness {
  name: string;
  modeEnv: string;
  issuerEnv: string;
  clientIdEnv: string;
  clientSecretEnv: string;
  oidc: OidcHarness;
  bcl: BclHarness;
  post(req: Request): Promise<Response>;
}

const APPS: AppHarness[] = [
  {
    name: 'customer',
    modeEnv: 'CUSTOMER_AUTH_MODE',
    issuerEnv: 'CUSTOMER_AUTH_ISSUER',
    clientIdEnv: 'CUSTOMER_AUTH_CLIENT_ID',
    clientSecretEnv: 'CUSTOMER_AUTH_CLIENT_SECRET',
    oidc: customerOidc,
    bcl: customerBcl,
    post: customerReceiver,
  },
  {
    name: 'partner',
    modeEnv: 'PARTNER_AUTH_MODE',
    issuerEnv: 'PARTNER_AUTH_ISSUER',
    clientIdEnv: 'PARTNER_AUTH_CLIENT_ID',
    clientSecretEnv: 'PARTNER_AUTH_CLIENT_SECRET',
    oidc: partnerOidc,
    bcl: partnerBcl,
    post: partnerReceiver,
  },
  {
    name: 'dashboard',
    modeEnv: 'DASHBOARD_AUTH_MODE',
    issuerEnv: 'DASHBOARD_AUTH_ISSUER',
    clientIdEnv: 'DASHBOARD_AUTH_CLIENT_ID',
    clientSecretEnv: 'DASHBOARD_AUTH_CLIENT_SECRET',
    oidc: dashboardOidc,
    bcl: dashboardBcl,
    post: dashboardReceiver,
  },
  {
    // web reuses the customer env names (shared contract).
    name: 'web',
    modeEnv: 'CUSTOMER_AUTH_MODE',
    issuerEnv: 'CUSTOMER_AUTH_ISSUER',
    clientIdEnv: 'CUSTOMER_AUTH_CLIENT_ID',
    clientSecretEnv: 'CUSTOMER_AUTH_CLIENT_SECRET',
    oidc: webOidc,
    bcl: webBcl,
    post: webReceiver,
  },
];

const MANAGED_ENV = new Set(APPS.flatMap((a) => [a.modeEnv, a.issuerEnv, a.clientIdEnv, a.clientSecretEnv]));
const originalEnv = new Map<string, string | undefined>();

function setFederated(h: AppHarness): void {
  process.env[h.modeEnv] = 'federated';
  process.env[h.issuerEnv] = ISSUER;
  process.env[h.clientIdEnv] = CLIENT_ID;
  process.env[h.clientSecretEnv] = 'test-client-secret-1';
}

function setDirect(h: AppHarness): void {
  process.env[h.modeEnv] = 'direct';
}

function receiverRequest(body: string, contentType = 'application/x-www-form-urlencoded'): Request {
  return new Request('http://bff.test/api/auth/backchannel-logout', {
    method: 'POST',
    headers: { 'content-type': contentType },
    body,
  });
}

const formBody = (token: string): string => new URLSearchParams({ logout_token: token }).toString();

beforeAll(() => {
  for (const key of MANAGED_ENV) originalEnv.set(key, process.env[key]);

  vi.stubGlobal(
    'fetch',
    vi.fn(async (input: unknown) => {
      if (String(input) === `${ISSUER}/.well-known/jwks.json`) {
        return { ok: true, json: async () => ({ keys: [jwk] }) };
      }
      return { ok: false, json: async () => ({}) };
    }),
  );
});

afterAll(() => {
  vi.unstubAllGlobals();
  for (const [key, value] of originalEnv) {
    if (value === undefined) delete process.env[key];
    else process.env[key] = value;
  }
});

describe.each(APPS)('$name back-channel logout receiver', (h) => {
  beforeEach(() => {
    setFederated(h);
    h.oidc.clearOidcJwksCache();
    h.bcl.clearBackchannelLogoutState();
  });

  describe('verifyLogoutToken', () => {
    test('accepts a spec-shaped logout_token and returns its claims', async () => {
      const claims = await h.oidc.verifyLogoutToken(signedLogout(), ISSUER, CLIENT_ID);

      expect(claims).not.toBeNull();
      expect(claims!.sid).toBe(DEFAULT_SID);
      expect(claims!.sub).toBe('fed_subject_1');
      expect(claims!.jti).not.toBe('');
      expect(claims!.iat).toBeGreaterThan(nowSec() - 5);
    });

    test('rejects a token signed by a foreign key', async () => {
      expect(await h.oidc.verifyLogoutToken(signedLogout({}, {}, foreignPrivateKey), ISSUER, CLIENT_ID)).toBeNull();
    });

    test('rejects a tampered claims payload', async () => {
      const token = signedLogout();
      const [header, , sig] = token.split('.');
      const tampered = `${header}.${b64urlJson(logoutClaims({ sub: 'attacker' }))}.${sig}`;
      expect(await h.oidc.verifyLogoutToken(tampered, ISSUER, CLIENT_ID)).toBeNull();
    });

    test('rejects a wrong typ header', async () => {
      expect(await h.oidc.verifyLogoutToken(signedLogout({}, { typ: 'JWT' }), ISSUER, CLIENT_ID)).toBeNull();
      expect(await h.oidc.verifyLogoutToken(signedLogout({}, { typ: undefined }), ISSUER, CLIENT_ID)).toBeNull();
    });

    test('rejects a token without the back-channel events claim', async () => {
      const claims = logoutClaims();
      delete claims['events'];
      expect(await h.oidc.verifyLogoutToken(signJwt({ alg: 'RS256', typ: 'logout+jwt', kid: KID }, claims), ISSUER, CLIENT_ID)).toBeNull();

      expect(
        await h.oidc.verifyLogoutToken(signedLogout({ events: { 'http://other/event': {} } }), ISSUER, CLIENT_ID),
      ).toBeNull();
    });

    test('rejects a stale iat (each attempt re-mints — retries never widen the window)', async () => {
      expect(await h.oidc.verifyLogoutToken(signedLogout({ iat: nowSec() - 600 }), ISSUER, CLIENT_ID)).toBeNull();
      // Inside the window still passes the freshness axis.
      expect(await h.oidc.verifyLogoutToken(signedLogout({ iat: nowSec() - 240 }), ISSUER, CLIENT_ID)).not.toBeNull();
    });

    test('rejects a meaningfully future iat', async () => {
      expect(await h.oidc.verifyLogoutToken(signedLogout({ iat: nowSec() + 600 }), ISSUER, CLIENT_ID)).toBeNull();
    });

    test('rejects the wrong issuer, audience or client binding', async () => {
      expect(await h.oidc.verifyLogoutToken(signedLogout({ iss: 'http://evil.test' }), ISSUER, CLIENT_ID)).toBeNull();
      expect(await h.oidc.verifyLogoutToken(signedLogout({ aud: 'someone-else' }), ISSUER, CLIENT_ID)).toBeNull();
      expect(await h.oidc.verifyLogoutToken(signedLogout(), 'http://other-issuer.test', CLIENT_ID)).toBeNull();
    });

    test('rejects missing sid, sub or jti', async () => {
      for (const axis of ['sid', 'sub', 'jti'] as const) {
        const claims = logoutClaims();
        delete claims[axis];
        const token = signJwt({ alg: 'RS256', typ: 'logout+jwt', kid: KID }, claims);
        expect(await h.oidc.verifyLogoutToken(token, ISSUER, CLIENT_ID), axis).toBeNull();
      }
    });
  });

  describe('receiver route', () => {
    test('records the terminated sid and answers 204', async () => {
      expect(h.bcl.isSidTerminated(DEFAULT_SID)).toBe(false);

      const res = await h.post(receiverRequest(formBody(signedLogout())));

      expect(res.status).toBe(204);
      expect(h.bcl.isSidTerminated(DEFAULT_SID)).toBe(true);
      expect(res.headers.get('Cache-Control')).toBe('no-store');
    });

    test('rejects a replayed logout_token (jti already seen)', async () => {
      const token = signedLogout();

      expect((await h.post(receiverRequest(formBody(token)))).status).toBe(204);
      expect((await h.post(receiverRequest(formBody(token)))).status).toBe(400);
    });

    test('rejects an invalid token with a uniform 400 and records nothing', async () => {
      const sid = '01JSIDNEVERRECORDED000000000001';
      const token = signedLogout({ sid }, {}, foreignPrivateKey);

      expect((await h.post(receiverRequest(formBody(token)))).status).toBe(400);
      expect(h.bcl.isSidTerminated(sid)).toBe(false);
      expect(h.bcl.isSidTerminated(DEFAULT_SID)).toBe(false);
    });

    test('rejects malformed bodies', async () => {
      expect((await h.post(receiverRequest('not-a-jwt'))).status).toBe(400);
      expect((await h.post(receiverRequest(new URLSearchParams({}).toString()))).status).toBe(400);
      expect(
        (await h.post(receiverRequest(JSON.stringify({ logout_token: signedLogout() }), 'application/json'))).status,
      ).toBe(400);
    });

    test('answers 404 outside federation (direct deployments have no receiver)', async () => {
      setDirect(h);
      expect((await h.post(receiverRequest(formBody(signedLogout())))).status).toBe(404);
      expect(h.bcl.isSidTerminated(DEFAULT_SID)).toBe(false);
    });
  });

  describe('denylist', () => {
    test('only verified terminations match, and state clears', () => {
      expect(h.bcl.payloadSidTerminated({ sid: DEFAULT_SID })).toBe(false);

      h.bcl.markSidTerminated(DEFAULT_SID);
      expect(h.bcl.payloadSidTerminated({ sid: DEFAULT_SID })).toBe(true);
      expect(h.bcl.payloadSidTerminated({})).toBe(false);
      expect(h.bcl.payloadSidTerminated({ sid: '' })).toBe(false);
      expect(h.bcl.payloadSidTerminated({ sid: 42 })).toBe(false);
      expect(h.bcl.payloadSidTerminated({ sid: 'unrelated' })).toBe(false);

      h.bcl.clearBackchannelLogoutState();
      expect(h.bcl.payloadSidTerminated({ sid: DEFAULT_SID })).toBe(false);
    });

    test('rememberLogoutJti reports the first sighting only', () => {
      const jti = nextJti();
      expect(h.bcl.rememberLogoutJti(jti)).toBe(true);
      expect(h.bcl.rememberLogoutJti(jti)).toBe(false);
      expect(h.bcl.rememberLogoutJti(nextJti())).toBe(true);
    });
  });
});

describe('actor choke points stop behind a terminated sid', () => {
  beforeEach(() => {
    setFederated(APPS[0]!);
    setFederated(APPS[1]!);
    APPS[0]!.oidc.clearOidcJwksCache();
    APPS[1]!.oidc.clearOidcJwksCache();
    APPS[0]!.bcl.clearBackchannelLogoutState();
    APPS[1]!.bcl.clearBackchannelLogoutState();
  });

  test('customer: the cookie stops resolving after back-channel logout', () => {
    const token = accessToken(DEFAULT_SID);
    const cookie = `nv_customer_token=${token}`;

    // Before the OP's logout the cookie still resolves (the upstream
    // remains the validity judge on proxied calls — I18).
    const actor = resolveCustomerActor(cookie, 'unused-secret');
    expect(actor).not.toBeNull();
    expect(actor!.userId).toBe('user-7');

    // The OP ends the session over the back channel…
    customerBcl.markSidTerminated(DEFAULT_SID);

    // …and the same cookie now resolves to nothing, immediately.
    expect(resolveCustomerActor(cookie, 'unused-secret')).toBeNull();
  });

  test('partner: the verified local actor resolves to nothing after back-channel logout', async () => {
    const token = accessToken(DEFAULT_SID);

    const actor = await federatedActorFromToken(token);
    expect(actor).not.toBeNull();
    expect(actor!.userId).toBe('user-7');
    expect(actor!.roles).toEqual([]);

    partnerBcl.markSidTerminated(DEFAULT_SID);

    expect(await federatedActorFromToken(token)).toBeNull();
  });

  test('web: the raw-cookie helper refuses a terminated sid', () => {
    const token = accessToken('01JSIDWEB00000000000000000000001');

    expect(webBcl.tokenSidTerminated(token)).toBe(false);
    webBcl.markSidTerminated('01JSIDWEB00000000000000000000001');
    expect(webBcl.tokenSidTerminated(token)).toBe(true);
    expect(webBcl.tokenSidTerminated('garbage')).toBe(false);
    expect(webBcl.tokenSidTerminated('a.b.c')).toBe(false);
  });
});

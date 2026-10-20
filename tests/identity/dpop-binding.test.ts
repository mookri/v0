import { createHash, createPublicKey, verify } from 'node:crypto';
import { existsSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';

import { GET as customerCallback } from '@apps/customer/app/api/auth/callback/route';
import { laravelGet, laravelPost } from '@apps/customer/adapters/backend/laravel-client';
import { dpopProof, dpopPublicJwk } from '@apps/customer/server/dpop';

// DPoP sender-constraint, BFF side (A-SEC-004-9, RFC 9449): the
// customer BFF is the canonical client implementation — one EC P-256
// key per process, ES256 proofs on the code exchange (binding the mint
// to this key) and on every upstream call carrying a bearer token
// (ath over the presented token). Proofs must be verifiable by any
// RFC 9449 server: typ/alg/jwk inline, htm uppercased, htu without
// query or fragment, fresh iat, unique jti, IEEE P1363 signatures.

const ISSUER = 'http://op.test';

interface DecodedProof {
  header: { typ?: string; alg?: string; jwk?: Record<string, string> };
  payload: { htm?: string; htu?: string; iat?: number; jti?: string; ath?: string };
  signingInput: string;
  signature: Buffer;
}

function decodeProof(proof: string): DecodedProof {
  const [h, p, s] = proof.split('.');
  return {
    header: JSON.parse(Buffer.from(h, 'base64url').toString('utf8')),
    payload: JSON.parse(Buffer.from(p, 'base64url').toString('utf8')),
    signingInput: `${h}.${p}`,
    signature: Buffer.from(s, 'base64url'),
  };
}

function proofVerifies(proof: DecodedProof, jwk: Record<string, string>): boolean {
  const key = createPublicKey({ key: jwk as never, format: 'jwk' });
  return verify('sha256', Buffer.from(proof.signingInput), { key, dsaEncoding: 'ieee-p1363' }, proof.signature);
}

const b64url = (input: string | Buffer): string => Buffer.from(input).toString('base64url');

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
});

describe('dpopProof minting', () => {
  test('is a compact JWS with typ=DPoP, ES256 and the inline public JWK', () => {
    const proof = decodeProof(dpopProof('POST', 'http://r.test/oauth/token'));

    expect(proof.header.typ).toBe('DPoP');
    expect(proof.header.alg).toBe('ES256');
    expect(proof.header.jwk).toEqual(dpopPublicJwk());
    expect(proof.header.jwk).toMatchObject({ kty: 'EC', crv: 'P-256' });
    expect(proof.header.jwk?.x).toBeTruthy();
    expect(proof.header.jwk?.y).toBeTruthy();
  });

  test('signature verifies against the advertised key (IEEE P1363)', () => {
    const proof = decodeProof(dpopProof('GET', 'http://r.test/api/v1/me'));

    expect(proofVerifies(proof, dpopPublicJwk())).toBe(true);
  });

  test('tampering with the payload breaks the signature', () => {
    const proof = dpopProof('GET', 'http://r.test/api/v1/me');
    const [h, p, s] = proof.split('.');
    const payload = JSON.parse(Buffer.from(p, 'base64url').toString('utf8')) as { htu?: string };
    payload.htu = 'http://attacker.test/api/v1/me';
    const forged = `${h}.${b64url(JSON.stringify(payload))}.${s}`;

    expect(proofVerifies(decodeProof(forged), dpopPublicJwk())).toBe(false);
  });

  test('htm is uppercased and htu drops query and fragment', () => {
    const proof = decodeProof(dpopProof('post', 'http://r.test/oauth/token?x=1#frag'));

    expect(proof.payload.htm).toBe('POST');
    expect(proof.payload.htu).toBe('http://r.test/oauth/token');
  });

  test('ath is the base64url sha256 of the presented token, only when given', () => {
    const withToken = decodeProof(dpopProof('GET', 'http://r.test/api/v1/me', 'at_abc'));
    const withoutToken = decodeProof(dpopProof('GET', 'http://r.test/api/v1/me'));

    expect(withToken.payload.ath).toBe(b64url(createHash('sha256').update('at_abc').digest()));
    expect(withoutToken.payload.ath).toBeUndefined();
  });

  test('iat is fresh and jti is unique per proof', () => {
    const a = decodeProof(dpopProof('GET', 'http://r.test/a'));
    const b = decodeProof(dpopProof('GET', 'http://r.test/a'));

    expect(Math.abs((a.payload.iat ?? 0) - Math.floor(Date.now() / 1000))).toBeLessThanOrEqual(2);
    expect(a.payload.jti).toBeTruthy();
    expect(a.payload.jti).not.toBe(b.payload.jti);
  });

  test('every proof advertises the same instance key', () => {
    const a = decodeProof(dpopProof('GET', 'http://r.test/a'));
    const b = decodeProof(dpopProof('GET', 'http://r.test/b'));

    expect(a.header.jwk).toEqual(b.header.jwk);
    expect(proofVerifies(a, b.header.jwk as Record<string, string>)).toBe(true);
  });
});

describe('callback exchange binding', () => {
  const ENV = {
    CUSTOMER_AUTH_MODE: 'federated',
    CUSTOMER_AUTH_ISSUER: ISSUER,
    CUSTOMER_AUTH_CLIENT_ID: 'client-customer',
    CUSTOMER_AUTH_CLIENT_SECRET: 'test-client-secret-1',
    SESSION_COOKIE_SECURE: '0',
  };

  let tokenCall: { url: string; init?: RequestInit } | null = null;

  function callbackRequest(): Request {
    const state = 'test-state-base64url';
    const flow = encodeURIComponent(['test-state-base64url', 'pkce-verifier', 'test-nonce', ''].join('.'));
    const url =
      `http://customer.bff.test/api/auth/callback` +
      `?code=authcode-1&state=${state}&iss=${encodeURIComponent(ISSUER)}`;
    return new Request(url, { headers: { cookie: `nv-auth-flow=${flow}` } });
  }

  afterEach(() => {
    tokenCall = null;
  });

  test('the code exchange presents a valid proof for htu=token endpoint without ath', async () => {
    for (const [k, v] of Object.entries(ENV)) vi.stubEnv(k, v);

    vi.stubGlobal(
      'fetch',
      vi.fn(async (url: string, init?: RequestInit) => {
        tokenCall = { url, init };
        // Exchanged OK but no id_token: the route fails closed at the
        // 9a id_token presence check — the exchange itself already
        // happened with the DPoP header attached.
        return new Response(JSON.stringify({ access_token: 'at_1', expires_in: 900 }), { status: 200 });
      }),
    );

    const res = await customerCallback(callbackRequest());
    expect(res.status).toBe(502);

    expect(tokenCall?.url).toBe(`${ISSUER}/oauth/token`);
    const headers = tokenCall?.init?.headers as Record<string, string>;
    expect(headers.DPoP).toBeTruthy();

    const proof = decodeProof(headers.DPoP);
    expect(proof.payload.htm).toBe('POST');
    expect(proof.payload.htu).toBe(`${ISSUER}/oauth/token`);
    expect(proof.payload.ath).toBeUndefined();
    expect(proofVerifies(proof, dpopPublicJwk())).toBe(true);
  });

  test('a missing-flow callback never reaches the exchange (no proof minted)', async () => {
    for (const [k, v] of Object.entries(ENV)) vi.stubEnv(k, v);
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    const res = await customerCallback(
      new Request(`http://customer.bff.test/api/auth/callback?code=c&state=s&iss=${encodeURIComponent(ISSUER)}`),
    );

    expect(res.status).toBe(401);
    expect(fetchMock).not.toHaveBeenCalled();
  });
});

describe('upstream call binding', () => {
  function wiredEnv(): void {
    vi.stubEnv('CUSTOMER_BACKEND', 'laravel');
    vi.stubEnv('NAVDHYA_API_BASE', 'https://api.example.test');
    vi.stubEnv('CUSTOMER_TENANT_ID', 'tenant_1');
  }

  test('a bearer call carries a proof with ath, htm and the exact htu', async () => {
    wiredEnv();
    let call: { url: string; init?: RequestInit } | null = null;
    vi.stubGlobal(
      'fetch',
      vi.fn(async (url: string, init?: RequestInit) => {
        call = { url, init };
        return new Response(JSON.stringify({ data: { ok: true }, meta: {} }), { status: 200 });
      }),
    );

    await laravelGet('at_session_1', '/bookings', { status: 'upcoming' }).then((r) => r.data);

    expect(call?.url).toBe('https://api.example.test/api/v1/bookings?status=upcoming');
    const headers = call?.init?.headers as Record<string, string>;
    expect(headers.Authorization).toBe('Bearer at_session_1');

    const proof = decodeProof(headers.DPoP);
    expect(proof.payload.htm).toBe('GET');
    // htu matches the request target with the query dropped (RFC 9449 §4.2).
    expect(proof.payload.htu).toBe('https://api.example.test/api/v1/bookings');
    expect(proof.payload.ath).toBe(b64url(createHash('sha256').update('at_session_1').digest()));
    expect(proofVerifies(proof, dpopPublicJwk())).toBe(true);
  });

  test('POST calls prove the POST method', async () => {
    wiredEnv();
    let call: { init?: RequestInit } | null = null;
    vi.stubGlobal(
      'fetch',
      vi.fn(async (_url: string, init?: RequestInit) => {
        call = { init };
        return new Response(JSON.stringify({ data: { ok: true }, meta: {} }), { status: 200 });
      }),
    );

    await laravelPost('at_session_1', '/bookings', { templeId: 't1' }).then((r) => r.data);

    const headers = call?.init?.headers as Record<string, string>;
    expect(decodeProof(headers.DPoP).payload.htm).toBe('POST');
  });

  test('a tokenless call sends neither Authorization nor DPoP', async () => {
    wiredEnv();
    let call: { init?: RequestInit } | null = null;
    vi.stubGlobal(
      'fetch',
      vi.fn(async (_url: string, init?: RequestInit) => {
        call = { init };
        return new Response(JSON.stringify({ data: { ok: true }, meta: {} }), { status: 200 });
      }),
    );

    await laravelGet(null, '/discover').then((r) => r.data);

    const headers = call?.init?.headers as Record<string, string>;
    expect(headers.Authorization).toBeUndefined();
    expect(headers.DPoP).toBeUndefined();
  });
});

describe('per-instance key store (A-SEC-004-11)', () => {
  // Next 16 splits the proxy and the route handlers into SEPARATE
  // module graphs. A module-level key cache held TWO keys in the leg
  // 90 drill: the callback (route graph) bound refresh families to
  // the route key, the proxy proved rotations with its own copy, and
  // every rotation died 400 invalid_dpop_proof. vi.resetModules() +
  // re-import reproduces the split — two module instances that must
  // still sign with ONE key, sourced from the instance file.

  const tmpFile = path.join(os.tmpdir(), `nv-dpop-key-${process.pid}-${Math.random().toString(36).slice(2)}.json`);
  let previous: string | undefined;

  beforeEach(() => {
    previous = process.env.CUSTOMER_DPOP_KEY_FILE;
    process.env.CUSTOMER_DPOP_KEY_FILE = tmpFile;
  });

  afterEach(() => {
    if (previous === undefined) delete process.env.CUSTOMER_DPOP_KEY_FILE;
    else process.env.CUSTOMER_DPOP_KEY_FILE = previous;
    vi.resetModules();
    vi.restoreAllMocks();
    rmSync(tmpFile, { force: true });
  });

  test('a second module instance loads the same key from the instance file; its proofs verify against the first', async () => {
    vi.resetModules();
    const routeGraph = await import('@apps/customer/server/dpop');
    const boundJwk = routeGraph.dpopPublicJwk();

    // First use published the instance key...
    expect(existsSync(tmpFile)).toBe(true);

    // ...and a separate graph (the proxy, in production) with its own
    // module instance must prove with the SAME key — the leg 90
    // rotation, from the other side of the graph split.
    vi.resetModules();
    const proxyGraph = await import('@apps/customer/server/dpop');
    expect(proxyGraph.dpopPublicJwk()).toEqual(boundJwk);

    const rotationProof = proxyGraph.dpopProof('POST', 'http://op.test/oauth/token');
    expect(proofVerifies(decodeProof(rotationProof), boundJwk)).toBe(true);
  });

  test('a persisted key survives a full module restart (same file, same key)', async () => {
    vi.resetModules();
    const before = (await import('@apps/customer/server/dpop')).dpopPublicJwk();
    vi.resetModules();
    const after = (await import('@apps/customer/server/dpop')).dpopPublicJwk();
    expect(after).toEqual(before);
  });

  test('a corrupt key file is regenerated once and the replacement is a valid record', async () => {
    const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    writeFileSync(tmpFile, 'not json {');

    vi.resetModules();
    const fresh = await import('@apps/customer/server/dpop');
    expect(() => fresh.dpopPublicJwk()).not.toThrow();
    expect(() => JSON.parse(readFileSync(tmpFile, 'utf8'))).not.toThrow();
    expect(errorSpy).toHaveBeenCalledWith(expect.stringContaining('unreadable'));
  });
});

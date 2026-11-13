import { createHash, randomBytes, timingSafeEqual } from 'node:crypto';

export type AuthMode = 'direct' | 'federated';
export type ParMode = 'off' | 'required';

export interface FederationConfig {
  issuer: string;
  clientId: string;
  clientSecret: string;
}

export interface AuthorizeParams {
  redirectUri: string;
  state: string;
  challenge: string;
  nonce: string;
}

export interface OAuthFlow {
  state: string;
  verifier: string;
  nonce: string;
  redirect: string | null;
}

export const OAUTH_CALLBACK_PATH = '/api/auth/callback';

export function isSecureContext(): boolean {
  const override = process.env.SESSION_COOKIE_SECURE;
  if (override === '1') return true;
  if (override === '0') return false;
  return process.env.NODE_ENV === 'production';
}

export function callbackUrl(origin: string): string {
  return `${origin}${OAUTH_CALLBACK_PATH}`;
}

export function safeNext(raw: string | null | undefined): string | null {
  if (!raw) return null;
  if (!raw.startsWith('/') || raw.startsWith('//') || raw.startsWith('/\\')) return null;
  return raw;
}

export function randomBase64Url(bytes = 32): string {
  return randomBytes(bytes).toString('base64url');
}

export function pkceChallenge(verifier: string): string {
  return createHash('sha256').update(verifier).digest('base64url');
}

export function timingSafeCompare(a: string, b: string): boolean {
  const bufA = Buffer.from(a);
  const bufB = Buffer.from(b);
  if (bufA.length !== bufB.length) return false;
  return timingSafeEqual(bufA, bufB);
}

export function authorizeParams(
  config: FederationConfig,
  params: AuthorizeParams,
): Record<string, string> {
  return {
    response_type: 'code',
    client_id: config.clientId,
    redirect_uri: params.redirectUri,
    scope: 'openid',
    state: params.state,
    nonce: params.nonce,
    code_challenge: params.challenge,
    code_challenge_method: 'S256',
  };
}

export function buildAuthorizeUrl(
  config: FederationConfig,
  redirectUri: string,
  state: string,
  challenge: string,
  nonce: string,
): string {
  const url = new URL(`${config.issuer}/oauth/authorize`);
  for (const [key, value] of Object.entries(
    authorizeParams(config, { redirectUri, state, challenge, nonce }),
  )) {
    url.searchParams.set(key, value);
  }
  return url.toString();
}

export async function pushAuthorizationRequest(
  config: FederationConfig,
  params: Record<string, string>,
): Promise<string> {
  const body = new URLSearchParams({ ...params, client_secret: config.clientSecret });

  const res = await fetch(`${config.issuer}/api/oauth/par`, {
    method: 'POST',
    headers: {
      accept: 'application/json',
      'content-type': 'application/x-www-form-urlencoded',
    },
    body: body.toString(),
  });

  if (res.status !== 201) {
    throw new Error(`pushed authorization request failed with status ${res.status}`);
  }

  const payload = (await res.json()) as { request_uri?: unknown };
  if (typeof payload.request_uri !== 'string' || payload.request_uri === '') {
    throw new Error('pushed authorization request returned no request_uri');
  }

  return payload.request_uri;
}

export function buildRequestUriUrl(config: FederationConfig, requestUri: string): string {
  const url = new URL(`${config.issuer}/oauth/authorize`);
  url.searchParams.set('client_id', config.clientId);
  url.searchParams.set('request_uri', requestUri);
  return url.toString();
}

export function createFederationEnvHelper(
  envPrefix: 'CUSTOMER' | 'PARTNER' | 'DASHBOARD',
  flowCookieBase = 'nv-auth-flow',
) {
  const modeVar = `${envPrefix}_AUTH_MODE`;
  const issuerVar = `${envPrefix}_AUTH_ISSUER`;
  const clientIdVar = `${envPrefix}_AUTH_CLIENT_ID`;
  const clientSecretVar = `${envPrefix}_AUTH_CLIENT_SECRET`;
  const parVar = `${envPrefix}_AUTH_PAR`;
  const devFlowCookie = flowCookieBase;
  const prodFlowCookie = `__Host-${flowCookieBase}`;
  const LEGACY_FLOW_COOKIES = [devFlowCookie, prodFlowCookie];

  const authMode = (): AuthMode => {
    const raw = (process.env[modeVar] ?? 'direct').trim();
    if (raw !== 'direct' && raw !== 'federated') {
      throw new Error(`${modeVar} must be 'direct' or 'federated', got '${raw}'`);
    }
    return raw;
  };

  const isFederated = (): boolean => authMode() === 'federated';

  const federationConfig = (): FederationConfig => {
    const issuer = (process.env[issuerVar] ?? '').replace(/\/+$/, '');
    const clientId = process.env[clientIdVar] ?? '';
    const clientSecret = process.env[clientSecretVar] ?? '';

    if (authMode() === 'federated' && (issuer === '' || clientId === '' || clientSecret === '')) {
      throw new Error(
        `${modeVar}=federated requires ${issuerVar}, ${clientIdVar} and ${clientSecretVar}`,
      );
    }

    return { issuer, clientId, clientSecret };
  };

  const parMode = (): ParMode => {
    const raw = (process.env[parVar] ?? 'off').trim();
    if (raw !== 'off' && raw !== 'required') {
      throw new Error(`${parVar} must be 'off' or 'required', got '${raw}'`);
    }
    return raw;
  };

  const flowCookieName = (): string => (isSecureContext() ? prodFlowCookie : devFlowCookie);

  const buildFlowCookie = (flow: OAuthFlow, maxAgeSeconds = 600): string => {
    const value = [
      flow.state,
      flow.verifier,
      flow.nonce,
      encodeURIComponent(flow.redirect ?? ''),
    ].join('.');
    const secure = isSecureContext() ? '; Secure' : '';
    return `${flowCookieName()}=${encodeURIComponent(value)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${maxAgeSeconds}${secure}`;
  };

  const clearFlowCookie = (): string => {
    const secure = isSecureContext() ? '; Secure' : '';
    return `${flowCookieName()}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
  };

  const readFlowCookie = (cookieHeader: string | null): OAuthFlow | null => {
    if (!cookieHeader) return null;
    for (const name of LEGACY_FLOW_COOKIES) {
      const match = cookieHeader.match(
        new RegExp(`(?:^|;\\s*)${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`),
      );
      if (!match) continue;
      const decoded = decodeURIComponent(match[1]);
      const segments = decoded.split('.');
      if (segments.length < 4) return null;
      const [state, verifier, nonce] = segments;
      const decodedRedirect = decodeURIComponent(segments.slice(3).join('.'));
      return {
        state,
        verifier,
        nonce,
        redirect: decodedRedirect === '' ? null : decodedRedirect,
      };
    }
    return null;
  };

  return {
    authMode,
    isFederated,
    federationConfig,
    parMode,
    LEGACY_FLOW_COOKIES,
    flowCookieName,
    buildFlowCookie,
    clearFlowCookie,
    readFlowCookie,
  };
}

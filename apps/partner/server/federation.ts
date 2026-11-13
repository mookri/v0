import { createFederationEnvHelper } from '@navdhya/auth-client';

const env = createFederationEnvHelper('PARTNER', 'nv-partner-auth-flow');

export const authMode = env.authMode;
export const isFederated = env.isFederated;
export const federationConfig = env.federationConfig;
export const parMode = env.parMode;
export const LEGACY_FLOW_COOKIES = env.LEGACY_FLOW_COOKIES;
export const flowCookieName = env.flowCookieName;
export const buildFlowCookie = env.buildFlowCookie;
export const clearFlowCookie = env.clearFlowCookie;
export const readFlowCookie = env.readFlowCookie;

export {
  OAUTH_CALLBACK_PATH,
  callbackUrl,
  safeNext,
  randomBase64Url,
  pkceChallenge,
  timingSafeCompare,
  authorizeParams,
  buildAuthorizeUrl,
  pushAuthorizationRequest,
  buildRequestUriUrl,
  isSecureContext,
  type AuthMode,
  type ParMode,
  type FederationConfig,
  type AuthorizeParams,
  type OAuthFlow,
} from '@navdhya/auth-client';

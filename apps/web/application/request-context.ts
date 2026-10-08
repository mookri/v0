import { newRequestId } from '@/application/api-envelope';

export interface AnonymousContext {
  actor: {
    id: string;
    type: 'anonymous';
    requestId: string;
  } | null;
  requestId: string;
}

export function anonymousContext(
  scope: string,
  requestId?: string
): AnonymousContext {
  const rid = requestId ?? newRequestId();
  return {
    actor: null,
    requestId: rid,
  };
}

export interface AuthenticatedActor {
  id: string;
  type: 'customer' | 'pandit' | 'admin';
  token: string;
  tenantId?: string;
  scopes: string[];
  requestId: string;
}

export interface AuthenticatedContext {
  actor: AuthenticatedActor;
  requestId: string;
}

export type RequestContext = AnonymousContext | AuthenticatedContext;

export function isAuthenticated(ctx: RequestContext): ctx is AuthenticatedContext {
  return ctx.actor !== null;
}
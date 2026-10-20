export interface CustomerActorContext {
  userId: string;
  sessionId: string;
  roles: string[];
  authenticated: true;
}

export interface CustomerRequestContext {
  requestId: string;
  actor: CustomerActorContext | null;
  locale: string;
  product: 'customer';
  routeId: string;
}

export function anonymousContext(routeId: string, requestId: string, locale = 'en'): CustomerRequestContext {
  return { requestId, actor: null, locale, product: 'customer', routeId };
}

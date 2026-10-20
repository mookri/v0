// lib/context-resolver.ts
// Utility to resolve request context from headers or use defaults

import { headers } from 'next/headers';
import { Role, Tenant, DeviceType, RequestContext } from '@/lib/dlo-x/types';
import { getMockSession } from '@/lib/auth/mock-session';

let reqCounter = 0;

/**
 * Resolves the request context from headers or uses defaults
 * @param path - The current path
 * @returns RequestContext object with resolved or default values
 */
export async function resolveContext(path: string): Promise<RequestContext> {
  const headerStore = await headers();
  const userAgent = headerStore.get('user-agent') ?? '';
  const session = await getMockSession(path);
  const device: DeviceType = /mobile/i.test(userAgent)
    ? 'mobile'
    : /tablet|ipad/i.test(userAgent)
      ? 'tablet'
      : 'desktop';

  reqCounter++;
  return {
    requestId: `req-${reqCounter}`,
    tenantId: 'default',
    userRole: session.role,
    device,
    seed: 'dynamic-seed',
    path
  };
}

/**
 * Default context for pages that can't resolve from headers
 * @param path - The current path
 * @returns RequestContext object with default values
 */
export function getDefaultContext(path: string): RequestContext {
  reqCounter++;
  return {
    requestId: `default-req-${reqCounter}`,
    tenantId: 'default',
    userRole: 'User',
    device: 'desktop',
    seed: 'default-seed',
    path
  };
}

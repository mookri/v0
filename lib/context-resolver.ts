// lib/context-resolver.ts
// Utility to resolve request context from headers or use defaults

import { Role, Tenant, DeviceType, RequestContext } from '@/lib/dlo-x/types';

/**
 * Resolves the request context from headers or uses defaults
 * @param path - The current path
 * @returns RequestContext object with resolved or default values
 */
export async function resolveContext(path: string): Promise<RequestContext> {
  // In a server component, we would resolve from headers, but for now we'll use defaults
  return {
    requestId: `req-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
    tenantId: 'default',
    userRole: 'User',
    device: 'desktop',
    seed: Math.random().toString(36).substring(2),
    path
  };
}

/**
 * Default context for pages that can't resolve from headers
 * @param path - The current path
 * @returns RequestContext object with default values
 */
export function getDefaultContext(path: string): RequestContext {
  return {
    requestId: `default-req-${Date.now()}`,
    tenantId: 'default',
    userRole: 'User',
    device: 'desktop',
    seed: Math.random().toString(36).substring(2),
    path
  };
}
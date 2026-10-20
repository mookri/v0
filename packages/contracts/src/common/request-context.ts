export interface ActorContext {
  userId?: string;
  role?: string;
}

export interface ScopeContext {
  tenantId?: string;
  organizationId?: string;
  branchId?: string;
  departmentId?: string;
  userId?: string;
}

export interface RequestContext {
  requestId: string;
  correlationId?: string;
  tenantId?: string;
  actor?: ActorContext;
  locale?: string;
}

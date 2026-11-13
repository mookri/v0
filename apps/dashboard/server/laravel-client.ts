import { createBackendClient, StepUpRequiredError, BackendApiError } from '@navdhya/api-client';

export function dashboardLaravelBase(): string {
  return (process.env.NAVDHYA_API_BASE ?? process.env.DASHBOARD_API_BASE ?? '').replace(/\/$/, '');
}

export function dashboardLaravelEnabled(): boolean {
  return process.env.DASHBOARD_BACKEND === 'laravel' && dashboardLaravelBase() !== '';
}

export const dashboardBackendClient = createBackendClient({
  baseUrl: dashboardLaravelBase,
  tenantId: () => process.env.DASHBOARD_TENANT_ID ?? '',
  useDpop: false,
  defaultTimeoutMs: 8000,
});

export function dashboardErrorToResponse(e: unknown): Response {
  if (e instanceof StepUpRequiredError) {
    return Response.json(
      {
        error: 'step_up_required',
        requiredAcr: e.requiredAcr,
        challengeUrl: e.challengeUrl,
        message: e.detail ?? 'Step-up authentication required.',
        requestId: e.requestId,
      },
      { status: 403 },
    );
  }
  if (e instanceof BackendApiError) {
    const headers: Record<string, string> = {};
    if (e.retryAfter) headers['Retry-After'] = e.retryAfter;
    return Response.json(
      { error: e.code, rawCode: e.rawCode, message: e.detail || e.code, requestId: e.requestId },
      { status: e.status, headers },
    );
  }
  throw e;
}


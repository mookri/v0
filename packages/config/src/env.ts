export type NavdhyaAppName = 'web' | 'customer' | 'partner' | 'dashboard';

export interface EnvValidationResult {
  ok: boolean;
  app: NavdhyaAppName;
  errors: string[];
  warnings: string[];
}

const PUBLIC_SECRET_PATTERN = /^NEXT_PUBLIC_.*(?:SECRET|PRIVATE_KEY|TOKEN|PASSWORD|DPOP_KEY)/i;

/**
 * Validates server runtime environment invariants for each Next.js BFF app:
 * - Rejects wildcard parent cookie domains in production (per-host cookie rule, ADR-0023)
 * - Rejects leaking private secrets via NEXT_PUBLIC_* env vars
 * - Validates NAVDHYA_API_BASE URL syntax when set
 */
export function validateAppRuntimeEnv(
  app: NavdhyaAppName,
  env: Record<string, string | undefined> = process.env,
): EnvValidationResult {
  const errors: string[] = [];
  const warnings: string[] = [];
  const isProd = env.NODE_ENV === 'production';

  for (const key of Object.keys(env)) {
    if (PUBLIC_SECRET_PATTERN.test(key) && env[key]) {
      errors.push(`secret_exposed_publicly:${key}`);
    }
  }

  const cookieDomain = env.SESSION_COOKIE_DOMAIN ?? env.COOKIE_DOMAIN ?? '';
  if (cookieDomain && cookieDomain.startsWith('.')) {
    errors.push(`wildcard_cookie_domain_forbidden:${cookieDomain}`);
  }

  const apiBase = env.NAVDHYA_API_BASE ?? '';
  if (apiBase) {
    try {
      const parsed = new URL(apiBase);
      if (isProd && parsed.protocol !== 'https:') {
        errors.push(`insecure_api_base_in_production:${apiBase}`);
      }
    } catch {
      errors.push(`invalid_api_base_url:${apiBase}`);
    }
  } else if (isProd) {
    warnings.push(`NAVDHYA_API_BASE_unset:${app}`);
  }

  return {
    ok: errors.length === 0,
    app,
    errors,
    warnings,
  };
}

export function assertValidAppRuntimeEnv(
  app: NavdhyaAppName,
  env: Record<string, string | undefined> = process.env,
): void {
  if (typeof window !== 'undefined') return;
  const result = validateAppRuntimeEnv(app, env);
  if (!result.ok) {
    throw new Error(`[env:${app}] Invalid runtime configuration: ${result.errors.join(', ')}`);
  }
}

export function validateEnv() {
  if (typeof window !== 'undefined') return;
  assertValidAppRuntimeEnv('web');
}

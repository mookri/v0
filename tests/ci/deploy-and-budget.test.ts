import fs from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';
import { validateAppRuntimeEnv } from '@navdhya/config/src/env';
import {
  ApiVersionSkewError,
  MIN_SUPPORTED_API_VERSION,
  assertApiFloorCompatible,
  isApiVersionCompatible,
} from '@navdhya/contracts/src/api/version-skew';

const APPS = ['web', 'customer', 'partner', 'dashboard'] as const;

describe('Phase 7 deployment containers, healthz, env validator & API floor skew (E-AP-07)', () => {
  const repoRoot = path.resolve(__dirname, '../..');

  it('ships standalone multi-stage Dockerfile with non-root user and /api/healthz probe for all 4 apps', () => {
    for (const app of APPS) {
      const dockerfilePath = path.join(repoRoot, 'apps', app, 'Dockerfile');
      expect(fs.existsSync(dockerfilePath)).toBe(true);
      const dockerfile = fs.readFileSync(dockerfilePath, 'utf8');
      expect(dockerfile).toContain('USER nextjs');
      expect(dockerfile).toContain('/api/healthz');
      expect(dockerfile).toContain(`apps/${app}/server.js`);

      const healthzPath = path.join(repoRoot, 'apps', app, 'app/api/healthz/route.ts');
      expect(fs.existsSync(healthzPath)).toBe(true);
    }
  });

  it('ships canary & rollback runbook plus Lighthouse JS & CWV budget config', () => {
    expect(fs.existsSync(path.join(repoRoot, 'docs/runbooks/frontend-canary-rollback.md'))).toBe(true);
    const lhc = JSON.parse(fs.readFileSync(path.join(repoRoot, 'lighthouserc.json'), 'utf8')) as {
      ci: { assert: { assertions: Record<string, unknown> } };
    };
    expect(lhc.ci.assert.assertions['largest-contentful-paint']).toBeDefined();
    expect(lhc.ci.assert.assertions['resource-summary:script:size']).toBeDefined();
  });

  it('validates runtime env: forbids wildcard cookie domains, invalid API URLs, and NEXT_PUBLIC_* secrets', () => {
    expect(
      validateAppRuntimeEnv('customer', {
        NODE_ENV: 'production',
        NAVDHYA_API_BASE: 'https://api.navdhya.com',
      }).ok,
    ).toBe(true);

    const wildcard = validateAppRuntimeEnv('partner', {
      NODE_ENV: 'production',
      SESSION_COOKIE_DOMAIN: '.navdhya.com',
    });
    expect(wildcard.ok).toBe(false);
    expect(wildcard.errors).toContain('wildcard_cookie_domain_forbidden:.navdhya.com');

    const leaked = validateAppRuntimeEnv('dashboard', {
      NODE_ENV: 'production',
      NEXT_PUBLIC_SESSION_SECRET: 'oops',
    });
    expect(leaked.ok).toBe(false);
    expect(leaked.errors).toContain('secret_exposed_publicly:NEXT_PUBLIC_SESSION_SECRET');
  });

  it('enforces API floor skew checks cleanly', () => {
    expect(isApiVersionCompatible(MIN_SUPPORTED_API_VERSION)).toBe(true);
    expect(isApiVersionCompatible('2026-12-01')).toBe(true);
    expect(isApiVersionCompatible('2025-01-01')).toBe(false);

    expect(() =>
      assertApiFloorCompatible(new Headers({ 'X-Navdhya-Api-Version': '2025-01-01' })),
    ).toThrow(ApiVersionSkewError);

    expect(() =>
      assertApiFloorCompatible(new Headers({ 'X-Navdhya-Api-Version': MIN_SUPPORTED_API_VERSION })),
    ).not.toThrow();
  });
});

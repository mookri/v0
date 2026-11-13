import fs from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';
import { trackingBatchSchema } from '@apps/web/app/api/ads/tracking-schema';
import { containsDisallowedGreen } from '@navdhya/design-tokens';

describe('Web SEO content source, revalidation, and PII-free analytics contracts (E-AP-08)', () => {
  const repoRoot = path.resolve(__dirname, '../..');

  it('guards /api/ads/tracking-schema against raw PII keys inside customData', () => {
    const valid = trackingBatchSchema.safeParse({
      batchId: 'batch-1',
      sentAt: Date.now(),
      source: 'fetch',
      events: [
        {
          type: 'impression',
          requestId: 'req-1',
          decisionId: 'dec-1',
          auctionId: 'auc-1',
          adId: 'ad-1',
          placement: 'hero',
          layout: 'card',
          idempotencyKey: 'ik-1',
          customData: { cluster: 'panchang', surface: 'web' },
        },
      ],
    });
    expect(valid.success).toBe(true);

    const leakedPii = trackingBatchSchema.safeParse({
      batchId: 'batch-2',
      sentAt: Date.now(),
      source: 'fetch',
      events: [
        {
          type: 'impression',
          requestId: 'req-2',
          decisionId: 'dec-2',
          auctionId: 'auc-2',
          adId: 'ad-2',
          placement: 'hero',
          layout: 'card',
          idempotencyKey: 'ik-2',
          customData: { phone: '+919876543210' },
        },
      ],
    });
    expect(leakedPii.success).toBe(false);
  });

  it('wires SEO catch-all route to generatePageMetadata and /api/revalidate with path traversal & rate limits', () => {
    const seoRoute = fs.readFileSync(path.join(repoRoot, 'apps/web/app/(seo)/[...slug]/page.tsx'), 'utf8');
    expect(seoRoute).toContain('generatePageMetadata');
    expect(seoRoute).toContain('loadSeoRouteData');

    const revalidateRoute = fs.readFileSync(path.join(repoRoot, 'apps/web/app/api/revalidate/route.ts'), 'utf8');
    expect(revalidateRoute).toContain('verifySecret');
    expect(revalidateRoute).toContain('MAX_SLUGS_PER_REQUEST');
    expect(revalidateRoute).toContain('SLUG_FORMAT');
  });

  it('ensures newly refactored Sacred Ember modules contain zero disallowed green classes', () => {
    const sacredPaths = [
      'apps/dashboard/components/Layout.tsx',
      'apps/dashboard/app/(app)/page.tsx',
      'apps/dashboard/app/(app)/payouts/page.tsx',
      'apps/partner/components/EntitySwitcher.tsx',
      'apps/partner/presentation/pandit/PanditAssignmentsView.tsx',
      'apps/partner/presentation/pandit/PanditEarningsView.tsx',
      'apps/customer/app/(protected)/book/start/page.tsx',
    ];
    for (const rel of sacredPaths) {
      const src = fs.readFileSync(path.join(repoRoot, rel), 'utf8');
      expect(containsDisallowedGreen(src), `Disallowed green found in ${rel}`).toBe(false);
    }
  });
});

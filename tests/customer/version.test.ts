import { test, expect } from 'vitest';
import { GET as customerVersion } from '@apps/customer/app/api/version/route';
import { GET as partnerVersion } from '@apps/partner/app/api/version/route';

test('version endpoints expose public-safe build metadata, never secrets', async () => {
  for (const [get, product] of [
    [customerVersion, 'customer'],
    [partnerVersion, 'partner'],
  ] as const) {
    const res = await get();
    expect(res.status).toBe(200);
    expect(res.headers.get('Cache-Control')).toBe('no-store');
    const body = (await res.json()) as { data: Record<string, string>; requestId: string };
    expect(body.data.product).toBe(product);
    for (const key of ['version', 'commitSha', 'buildAt', 'environment']) {
      expect(typeof body.data[key]).toBe('string');
    }
    expect(JSON.stringify(body.data)).not.toMatch(/SECRET|KEY|TOKEN|PASSWORD/i);
  }
});

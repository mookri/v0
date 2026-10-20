/**
 * @jest-environment node
 */
import { GET } from '@/app/api/healthz/route';

describe('/api/healthz (liveness)', () => {
  it('answers 200 with the service identity — static truth, no dependencies', async () => {
    const res = await GET();

    expect(res.status).toBe(200);
    const body = (await res.json()) as { status: string; service: string; ts: string };
    expect(body.status).toBe('ok');
    expect(body.service).toBe('web-bff');
    expect(typeof body.ts).toBe('string');
  });
});

/**
 * @jest-environment node
 *
 * W2 — security + system panels.
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { SecurityPanel, SecurityCard } from '@/components/console/platform/SecurityPanel';
import { SystemPanel, SystemHealthCard } from '@/components/console/platform/SystemPanel';

const originalFetch = global.fetch;

function mockBff(body: unknown, status = 200) {
  global.fetch = jest.fn(
    async () =>
      new Response(JSON.stringify(body), {
        status,
        headers: { 'content-type': 'application/json' },
      }),
  ) as typeof fetch;
}

afterEach(() => {
  global.fetch = originalFetch;
});

async function renderPanel(panel: Promise<React.ReactElement>): Promise<string> {
  return renderToStaticMarkup(await panel);
}

const fallbackBody = { data: { data: { mfa: { enrolled: 0, total_users: 0 }, authz: { denied_today: 0, denied_total: 0 }, audit: { total: 0 } }, meta: { fallback: true } } };
const errorBody = { error: { code: 'FORBIDDEN', message: 'Capability required.', requestId: 'req_1' } };

describe('SecurityCard', () => {
  it('renders all six security stats', () => {
    const html = renderToStaticMarkup(
      <SecurityCard overview={{ mfa: { enrolled: 3, total_users: 10 }, authz: { denied_today: 2, denied_total: 5 }, audit: { total: 100 } }} />,
    );
    expect(html).toContain('data-testid="security-stat"');
    expect(html).toContain('3');
    expect(html).toContain('10');
    expect(html).toContain('30%');
    expect(html).toContain('2');
    expect(html).toContain('5');
    expect(html).toContain('100');
  });
});

describe('SystemHealthCard', () => {
  it('renders checks with OK/Failed and a ready status', () => {
    const html = renderToStaticMarkup(
      <SystemHealthCard health={{ status: 'ready', checks: { central_database: 'ok', tenant_database: 'ok', cache: 'ok', queue: 'failed' } }} />,
    );
    expect(html).toContain('data-testid="system-check"');
    expect(html).toContain('All systems operational');
    expect(html).toContain('OK');
    expect(html).toContain('Failed');
  });

  it('renders a degraded status when not ready', () => {
    const html = renderToStaticMarkup(
      <SystemHealthCard health={{ status: 'degraded', checks: { central_database: 'failed' } }} />,
    );
    expect(html).toContain('Degraded');
  });
});

describe('SecurityPanel', () => {
  it('renders the overview it fetched', async () => {
    mockBff({ data: { data: { mfa: { enrolled: 3, total_users: 10 }, authz: { denied_today: 2, denied_total: 5 }, audit: { total: 100 } }, meta: {} } });
    const html = await renderPanel(SecurityPanel());
    expect(html).toContain('data-testid="security-stat"');
    expect(html).toContain('30%');
  });

  it('renders a configuration notice when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(SecurityPanel());
    expect(html).toContain('Backend not configured');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(SecurityPanel());
    expect(html).toContain('Could not load security overview (FORBIDDEN)');
  });
});

describe('SystemPanel', () => {
  it('renders the health it fetched', async () => {
    mockBff({ data: { data: { status: 'ready', checks: { central_database: 'ok', cache: 'ok' } }, meta: {} } });
    const html = await renderPanel(SystemPanel());
    expect(html).toContain('data-testid="system-check"');
    expect(html).toContain('All systems operational');
  });

  it('renders a configuration notice when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(SystemPanel());
    expect(html).toContain('Backend not configured');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(SystemPanel());
    expect(html).toContain('Could not load system health (FORBIDDEN)');
  });
});

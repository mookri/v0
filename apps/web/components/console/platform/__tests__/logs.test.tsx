/**
 * @jest-environment node
 *
 * W2 — audit logs panel.
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { LogsPanel, LogsTable } from '@/components/console/platform/LogsPanel';

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

const fallbackBody = { data: { data: [], meta: { fallback: true } } };
const errorBody = { error: { code: 'FORBIDDEN', message: 'Capability required.', requestId: 'req_1' } };

describe('LogsTable', () => {
  it('renders audit rows with action, resource and actor', () => {
    const html = renderToStaticMarkup(
      <LogsTable
        rows={[
          {
            id: 'l1',
            action: 'authz.denied',
            resource: { module: 'platform', resource_type: 'capability', resource_id: 'platform:role:manage' },
            actor: { principal_id: 'u_42' },
            request_id: 'req_99',
            occurred_at: '2026-10-01T10:00:00Z',
          },
        ]}
      />,
    );
    expect(html).toContain('data-testid="audit-row"');
    expect(html).toContain('authz.denied');
    expect(html).toContain('platform/capability');
    expect(html).toContain('u_42');
    expect(html).toContain('req_99');
  });

  it('renders a placeholder for a missing resource/actor', () => {
    const html = renderToStaticMarkup(
      <LogsTable rows={[{ id: 'l2', action: 'x', resource: null, actor: null, request_id: null, occurred_at: null }]} />,
    );
    expect(html).toContain('data-testid="audit-row"');
    expect(html).toContain('—');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<LogsTable rows={[]} />);
    expect(html).toContain('No audit entries');
    expect(html).not.toContain('<table');
  });
});

describe('LogsPanel', () => {
  it('renders the entries it fetched', async () => {
    mockBff({
      data: {
        data: [{ id: 'l1', action: 'authz.denied', resource: { module: 'platform', resource_type: 'capability' }, actor: { principal_id: 'u_42' }, request_id: 'req_99', occurred_at: '2026-10-01T10:00:00Z' }],
        meta: {},
      },
    });
    const html = await renderPanel(LogsPanel());
    expect(html).toContain('data-testid="audit-row"');
    expect(html).toContain('authz.denied');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(LogsPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(LogsPanel());
    expect(html).toContain('Could not load audit entries (FORBIDDEN)');
    expect(html).not.toContain('<table');
  });
});

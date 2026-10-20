/**
 * @jest-environment node
 *
 * W1b slice 2 — managers + support panels.
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { ManagersPanel, ManagersTable } from '@/components/console/admin/ManagersPanel';
import { SupportPanel, SupportTable } from '@/components/console/admin/SupportPanel';

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

describe('ManagersTable', () => {
  it('renders queue items with track, status and SLA', () => {
    const html = renderToStaticMarkup(
      <ManagersTable
        rows={[
          {
            id: 'a1',
            track: 'pandit',
            status: 'SUBMITTED',
            submitted_at: '2026-10-01T10:00:00Z',
            sla_breached: false,
            sla_remaining_ms: 7200000,
          },
        ]}
      />,
    );
    expect(html).toContain('data-testid="review-row"');
    expect(html).toContain('pandit');
    expect(html).toContain('data-status="submitted"');
    expect(html).toContain('2h left');
  });

  it('flags a breached SLA', () => {
    const html = renderToStaticMarkup(
      <ManagersTable
        rows={[{ id: 'a2', track: 'vendor', status: 'IN_REVIEW', submitted_at: null, sla_breached: true, sla_remaining_ms: null }]}
      />,
    );
    expect(html).toContain('Breached');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<ManagersTable rows={[]} />);
    expect(html).toContain('No applications in review');
    expect(html).not.toContain('<table');
  });
});

describe('SupportTable', () => {
  it('renders tickets with subject, category, masked customer and status', () => {
    const html = renderToStaticMarkup(
      <SupportTable
        rows={[
          {
            id: 't1',
            subject: 'Refund request',
            category: 'billing',
            status: 'OPEN',
            customer: { id: 'u1', name: 'Asha', email: 'a***@navdhya.dev' },
            created_at: '2026-10-01T10:00:00Z',
          },
        ]}
      />,
    );
    expect(html).toContain('data-testid="support-row"');
    expect(html).toContain('Refund request');
    expect(html).toContain('billing');
    expect(html).toContain('Asha (a***@navdhya.dev)');
    expect(html).toContain('data-status="open"');
  });

  it('renders a placeholder when the customer record is missing', () => {
    const html = renderToStaticMarkup(
      <SupportTable rows={[{ id: 't2', subject: 'x', category: 'other', status: 'OPEN', customer: null, created_at: null }]} />,
    );
    expect(html).toContain('data-testid="support-row"');
    expect(html).toContain('—');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<SupportTable rows={[]} />);
    expect(html).toContain('No support tickets');
    expect(html).not.toContain('<table');
  });
});

describe('ManagersPanel', () => {
  it('renders the queue items it fetched', async () => {
    mockBff({
      data: {
        data: [{ id: 'a1', track: 'pandit', status: 'SUBMITTED', submitted_at: '2026-10-01T10:00:00Z', sla_breached: false, sla_remaining_ms: 7200000 }],
        meta: {},
      },
    });
    const html = await renderPanel(ManagersPanel());
    expect(html).toContain('data-testid="review-row"');
    expect(html).toContain('pandit');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(ManagersPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(ManagersPanel());
    expect(html).toContain('Could not load review items (FORBIDDEN)');
    expect(html).not.toContain('<table');
  });
});

describe('SupportPanel', () => {
  it('renders the tickets it fetched', async () => {
    mockBff({
      data: {
        data: [{ id: 't1', subject: 'Refund request', category: 'billing', status: 'OPEN', customer: { id: 'u1', name: 'Asha', email: 'a***@navdhya.dev' }, created_at: '2026-10-01T10:00:00Z' }],
        meta: {},
      },
    });
    const html = await renderPanel(SupportPanel());
    expect(html).toContain('data-testid="support-row"');
    expect(html).toContain('Refund request');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(SupportPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(SupportPanel());
    expect(html).toContain('Could not load support tickets (FORBIDDEN)');
    expect(html).not.toContain('<table');
  });
});

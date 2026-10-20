/**
 * @jest-environment node
 *
 * W2 — admin ops panels (bookings, content, seo, payouts).
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { BookingsPanel, BookingsTable } from '@/components/console/admin/BookingsPanel';
import { ContentPanel, ContentTable } from '@/components/console/admin/ContentPanel';
import { SeoPanel, SeoCoverageCard } from '@/components/console/admin/SeoPanel';
import { PayoutsPanel, PayoutsTable } from '@/components/console/admin/PayoutsPanel';

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

describe('BookingsTable', () => {
  it('renders rows with service, status and total', () => {
    const html = renderToStaticMarkup(
      <BookingsTable
        rows={[
          {
            id: 'b1',
            service_sku: 'puja-griha',
            status: 'CONFIRMED',
            scheduled_at: '2026-10-02T10:00:00Z',
            total_minor: 120000,
            currency: 'INR',
            created_at: '2026-10-01T09:00:00Z',
            user_id: 1,
          },
        ]}
      />,
    );
    expect(html).toContain('data-testid="booking-row"');
    expect(html).toContain('puja-griha');
    expect(html).toContain('data-status="confirmed"');
    expect(html).toContain('INR 1200.00');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<BookingsTable rows={[]} />);
    expect(html).toContain('No bookings in this tenant');
    expect(html).not.toContain('<table');
  });
});

describe('ContentTable', () => {
  it('renders rows with slug, cluster and indexability', () => {
    const html = renderToStaticMarkup(
      <ContentTable
        rows={[{ id: 'c1', slug: 'griha-pravesh', cluster: 'ceremony', title: 'Griha Pravesh', noindex: false, last_updated: '2026-10-01T10:00:00Z' }]}
      />,
    );
    expect(html).toContain('data-testid="content-row"');
    expect(html).toContain('griha-pravesh');
    expect(html).toContain('ceremony');
    expect(html).toContain('Griha Pravesh');
    expect(html).toContain('Yes');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<ContentTable rows={[]} />);
    expect(html).toContain('No content in the queue');
    expect(html).not.toContain('<table');
  });
});

describe('SeoCoverageCard', () => {
  it('renders all four coverage stats', () => {
    const html = renderToStaticMarkup(<SeoCoverageCard coverage={{ pages: 10, indexed: 8, noindex: 2, clusters: 3 }} />);
    expect(html).toContain('data-testid="seo-stat"');
    expect(html).toContain('10');
    expect(html).toContain('8');
    expect(html).toContain('2');
    expect(html).toContain('3');
  });
});

describe('PayoutsTable', () => {
  it('renders rows with vendor, period, status and amount', () => {
    const html = renderToStaticMarkup(
      <PayoutsTable
        rows={[
          {
            id: 'po1',
            vendor_id: 'v_1',
            period: '2026-09',
            status: 'PENDING',
            amount_minor: 500000,
            currency: 'INR',
            reference: 'ref_1',
            created_at: '2026-10-01T10:00:00Z',
          },
        ]}
      />,
    );
    expect(html).toContain('data-testid="payout-row"');
    expect(html).toContain('v_1');
    expect(html).toContain('2026-09');
    expect(html).toContain('data-status="pending"');
    expect(html).toContain('INR 5000.00');
    expect(html).toContain('ref_1');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<PayoutsTable rows={[]} />);
    expect(html).toContain('No payouts on file');
    expect(html).not.toContain('<table');
  });
});

describe('BookingsPanel', () => {
  it('renders the bookings it fetched', async () => {
    mockBff({ data: { data: [{ id: 'b1', service_sku: 'puja-griha', status: 'CONFIRMED', scheduled_at: '2026-10-02T10:00:00Z', total_minor: 120000, currency: 'INR', created_at: '2026-10-01T09:00:00Z', user_id: 1 }], meta: {} } });
    const html = await renderPanel(BookingsPanel());
    expect(html).toContain('data-testid="booking-row"');
    expect(html).toContain('puja-griha');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(BookingsPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(BookingsPanel());
    expect(html).toContain('Could not load bookings (FORBIDDEN)');
  });
});

describe('ContentPanel', () => {
  it('renders the content it fetched', async () => {
    mockBff({ data: { data: [{ id: 'c1', slug: 'griha-pravesh', cluster: 'ceremony', title: 'Griha Pravesh', noindex: false, last_updated: '2026-10-01T10:00:00Z' }], meta: {} } });
    const html = await renderPanel(ContentPanel());
    expect(html).toContain('data-testid="content-row"');
    expect(html).toContain('griha-pravesh');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(ContentPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(ContentPanel());
    expect(html).toContain('Could not load content pages (FORBIDDEN)');
  });
});

describe('SeoPanel', () => {
  it('renders the coverage it fetched', async () => {
    mockBff({ data: { data: { pages: 10, indexed: 8, noindex: 2, clusters: 3 }, meta: {} } });
    const html = await renderPanel(SeoPanel());
    expect(html).toContain('data-testid="seo-stat"');
    expect(html).toContain('10');
  });

  it('renders a configuration notice when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(SeoPanel());
    expect(html).toContain('Backend not configured');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(SeoPanel());
    expect(html).toContain('Could not load SEO coverage (FORBIDDEN)');
  });
});

describe('PayoutsPanel', () => {
  it('renders the payouts it fetched', async () => {
    mockBff({ data: { data: [{ id: 'po1', vendor_id: 'v_1', period: '2026-09', status: 'PENDING', amount_minor: 500000, currency: 'INR', reference: 'ref_1', created_at: '2026-10-01T10:00:00Z' }], meta: {} } });
    const html = await renderPanel(PayoutsPanel());
    expect(html).toContain('data-testid="payout-row"');
    expect(html).toContain('v_1');
    expect(html).toContain('INR 5000.00');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(PayoutsPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(PayoutsPanel());
    expect(html).toContain('Could not load payouts (FORBIDDEN)');
  });
});

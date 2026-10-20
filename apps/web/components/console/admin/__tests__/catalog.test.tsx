/**
 * @jest-environment node
 *
 * W3 — catalog item manage panel.
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { CatalogPanel, CatalogTable } from '@/components/console/admin/CatalogPanel';

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

describe('CatalogTable', () => {
  it('renders rows with sku, name, status and price', () => {
    const html = renderToStaticMarkup(
      <CatalogTable
        rows={[{ id: 'i1', sku: 'puja-griha', name: 'Griha Pravesh', status: 'DRAFT', currency: 'INR', price_minor: 120000 }]}
      />,
    );
    expect(html).toContain('data-testid="catalog-row"');
    expect(html).toContain('puja-griha');
    expect(html).toContain('Griha Pravesh');
    expect(html).toContain('data-status="draft"');
    expect(html).toContain('INR 1200.00');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<CatalogTable rows={[]} />);
    expect(html).toContain('No catalog items');
    expect(html).not.toContain('<table');
  });
});

describe('CatalogPanel', () => {
  it('renders the items it fetched', async () => {
    mockBff({ data: { data: [{ id: 'i1', sku: 'puja-griha', name: 'Griha Pravesh', status: 'DRAFT', currency: 'INR', price_minor: 120000 }], meta: {} } });
    const html = await renderPanel(CatalogPanel());
    expect(html).toContain('data-testid="catalog-row"');
    expect(html).toContain('puja-griha');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(CatalogPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(CatalogPanel());
    expect(html).toContain('Could not load catalog items (FORBIDDEN)');
  });
});

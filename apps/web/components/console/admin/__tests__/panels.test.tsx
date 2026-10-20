/**
 * @jest-environment node
 *
 * W1b admin console panels — `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`.
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { PanditsPanel, PanditsTable } from '@/components/console/admin/PanditsPanel';
import { VendorsPanel, VendorsTable } from '@/components/console/admin/VendorsPanel';

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

describe('PanditsTable', () => {
  it('renders rows with normalised status badges', () => {
    const html = renderToStaticMarkup(
      <PanditsTable
        rows={[{ id: 'p1', display_name: 'Pandit A', status: 'ACTIVE', verification_status: 'VERIFIED' }]}
      />,
    );
    expect(html).toContain('data-testid="pandit-row"');
    expect(html).toContain('Pandit A');
    expect(html).toContain('data-status="active"');
    expect(html).toContain('data-status="verified"');
    expect(html).toContain('data-variant="success"');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<PanditsTable rows={[]} />);
    expect(html).toContain('No pandits in this tenant');
    expect(html).not.toContain('<table');
  });
});

describe('VendorsTable', () => {
  it('renders rows with city and status badges', () => {
    const html = renderToStaticMarkup(
      <VendorsTable
        rows={[{ id: 'v1', name: 'Flora and Co', city: 'Pune', status: 'ACTIVE', verification_status: 'VERIFIED' }]}
      />,
    );
    expect(html).toContain('data-testid="vendor-row"');
    expect(html).toContain('Flora and Co');
    expect(html).toContain('Pune');
    expect(html).toContain('data-status="active"');
    expect(html).toContain('data-status="verified"');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<VendorsTable rows={[]} />);
    expect(html).toContain('No vendors in this tenant');
    expect(html).not.toContain('<table');
  });
});

describe('PanditsPanel', () => {
  it('renders the pandits it fetched', async () => {
    mockBff({ data: { data: [{ id: 'p1', display_name: 'Pandit A', status: 'ACTIVE', verification_status: 'VERIFIED' }], meta: {} } });
    const html = await renderPanel(PanditsPanel());
    expect(html).toContain('data-testid="pandit-row"');
    expect(html).toContain('Pandit A');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(PanditsPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(PanditsPanel());
    expect(html).toContain('Could not load pandit profiles (FORBIDDEN)');
    expect(html).not.toContain('<table');
  });
});

describe('VendorsPanel', () => {
  it('renders the vendors it fetched', async () => {
    mockBff({ data: { data: [{ id: 'v1', name: 'Flora and Co', city: 'Pune', status: 'ACTIVE', verification_status: 'VERIFIED' }], meta: {} } });
    const html = await renderPanel(VendorsPanel());
    expect(html).toContain('data-testid="vendor-row"');
    expect(html).toContain('Flora and Co');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(VendorsPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(VendorsPanel());
    expect(html).toContain('Could not load vendors (FORBIDDEN)');
    expect(html).not.toContain('<table');
  });
});

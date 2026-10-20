/**
 * @jest-environment node
 *
 * W2 — settings + billing panels.
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { SettingsPanel, SettingsTable } from '@/components/console/platform/SettingsPanel';
import { BillingPanel, BillingTable } from '@/components/console/platform/BillingPanel';

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

const fallbackBody = { data: { data: null, meta: { fallback: true } } };
const errorBody = { error: { code: 'FORBIDDEN', message: 'Capability required.', requestId: 'req_1' } };

describe('SettingsTable', () => {
  it('renders the tenant configuration rows', () => {
    const html = renderToStaticMarkup(
      <SettingsTable
        settings={{
          code: 'acme',
          name: 'Acme',
          state: 'active',
          status: 'active',
          plan_code: 'pro',
          base_currency: 'INR',
          timezone: 'Asia/Kolkata',
          locale: 'en-IN',
        }}
      />,
    );
    expect(html).toContain('data-testid="settings-row"');
    expect(html).toContain('acme');
    expect(html).toContain('Asia/Kolkata');
    expect(html).toContain('en-IN');
  });
});

describe('BillingTable', () => {
  it('renders plan fields and an empty invoice state', () => {
    const html = renderToStaticMarkup(
      <BillingTable info={{ plan_code: 'pro', status: 'active', base_currency: 'INR', invoices: [] }} />,
    );
    expect(html).toContain('data-testid="billing-row"');
    expect(html).toContain('pro');
    expect(html).toContain('No invoices yet');
    expect(html).not.toContain('data-testid="invoice-row"');
  });

  it('renders invoice rows when present', () => {
    const html = renderToStaticMarkup(
      <BillingTable
        info={{
          plan_code: 'pro',
          status: 'active',
          base_currency: 'INR',
          invoices: [{ id: 'inv_1', amount: '₹1000', status: 'paid', issued_at: '2026-10-01' }],
        }}
      />,
    );
    expect(html).toContain('data-testid="invoice-row"');
    expect(html).toContain('inv_1');
    expect(html).toContain('₹1000');
  });
});

describe('SettingsPanel', () => {
  it('renders the settings it fetched', async () => {
    mockBff({ data: { data: { code: 'acme', name: 'Acme', state: 'active', status: 'active', plan_code: 'pro', base_currency: 'INR', timezone: 'Asia/Kolkata', locale: 'en-IN' }, meta: {} } });
    const html = await renderPanel(SettingsPanel());
    expect(html).toContain('data-testid="settings-row"');
    expect(html).toContain('Asia/Kolkata');
  });

  it('renders a configuration notice when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(SettingsPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(SettingsPanel());
    expect(html).toContain('Could not load settings (FORBIDDEN)');
  });
});

describe('BillingPanel', () => {
  it('renders the billing info it fetched', async () => {
    mockBff({ data: { data: { plan_code: 'pro', status: 'active', base_currency: 'INR', invoices: [] }, meta: {} } });
    const html = await renderPanel(BillingPanel());
    expect(html).toContain('data-testid="billing-row"');
    expect(html).toContain('pro');
  });

  it('renders a configuration notice when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(BillingPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(BillingPanel());
    expect(html).toContain('Could not load billing (FORBIDDEN)');
  });
});

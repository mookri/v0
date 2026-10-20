/**
 * @jest-environment node
 *
 * W1a console panels — `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`.
 *
 * Two layers are covered:
 *   - the pure tables (row rendering, empty state, status derivation), and
 *   - the async panels end to end, with the BFF mocked, proving the three
 *     outcomes stay apart: real rows render as a table, an unconfigured
 *     backend renders a *notice* (never an empty table that reads as "no roles
 *     exist"), and a mapped failure renders its code.
 */
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

jest.mock('next/headers', () => ({
  cookies: () => Promise.resolve({ getAll: () => [{ name: 'navdhya-session', value: 'test-session-token' }] }),
}));

import { ApiKeysTable, ApiPanel, OAuthClientsTable } from '@/components/console/platform/ApiPanel';
import { RolesPanel, RolesTable } from '@/components/console/platform/RolesPanel';
import { UsersPanel, UsersTable } from '@/components/console/platform/UsersPanel';

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

describe('RolesTable', () => {
  it('renders one row per role with its state badge', () => {
    const html = renderToStaticMarkup(
      <RolesTable rows={[{ id: 'r1', code: 'admin', name: 'Administrator', state: 'active' }]} />,
    );
    expect(html).toContain('data-testid="role-row"');
    expect(html).toContain('admin');
    expect(html).toContain('Administrator');
    expect(html).toContain('data-status="active"');
    expect(html).toContain('data-variant="success"');
  });

  it('renders the empty state instead of a bare table', () => {
    const html = renderToStaticMarkup(<RolesTable rows={[]} />);
    expect(html).toContain('No roles in this tenant');
    expect(html).not.toContain('<table');
  });
});

describe('UsersTable', () => {
  it('shows membership state and account state separately', () => {
    const html = renderToStaticMarkup(
      <UsersTable
        rows={[
          {
            id: 'm1',
            state: 'active',
            user: { id: 'u1', name: 'Asha', email: 'asha@navdhya.dev', status: 'suspended' },
          },
        ]}
      />,
    );
    expect(html).toContain('data-testid="user-row"');
    expect(html).toContain('Asha');
    expect(html).toContain('asha@navdhya.dev');
    // Two badges in the same row: membership vs account.
    expect(html).toContain('data-status="active"');
    expect(html).toContain('data-status="suspended"');
  });

  it('renders a placeholder cell when the user record is missing', () => {
    const html = renderToStaticMarkup(<UsersTable rows={[{ id: 'm2', state: 'active', user: null }]} />);
    expect(html).toContain('data-testid="user-row"');
    expect(html).toContain('—');
  });

  it('renders the empty state when the tenant has no members', () => {
    const html = renderToStaticMarkup(<UsersTable rows={[]} />);
    expect(html).toContain('No members in this tenant');
    expect(html).not.toContain('<table');
  });
});

describe('ApiKeysTable / OAuthClientsTable', () => {
  it('marks a revoked key as cancelled and a live key as active', () => {
    const html = renderToStaticMarkup(
      <ApiKeysTable
        rows={[
          { id: 'k1', name: 'warehouse', prefix: 'ndk_live_ab', revoked_at: null },
          { id: 'k2', name: 'retired', prefix: 'ndk_live_cd', revoked_at: '2026-01-01' },
        ]}
      />,
    );
    expect(html).toContain('data-testid="api-key-row"');
    expect(html).toContain('data-status="active"');
    expect(html).toContain('data-status="cancelled"');
    // The secret is never in the payload; only the prefix is rendered.
    expect(html).not.toMatch(/secret/i);
  });

  it('renders PKCE requirement and revocation state for clients', () => {
    const html = renderToStaticMarkup(
      <OAuthClientsTable
        rows={[
          { id: 'c1', client_id: 'web-app', name: 'Web app', require_pkce: true, revoked: false },
          { id: 'c2', client_id: 'legacy', name: '', require_pkce: false, revoked: true },
        ]}
      />,
    );
    expect(html).toContain('data-testid="oauth-client-row"');
    expect(html).toContain('Required');
    expect(html).toContain('Optional');
    expect(html).toContain('data-status="cancelled"');
  });

  it('renders the empty state rather than an empty table', () => {
    expect(renderToStaticMarkup(<OAuthClientsTable rows={[]} />)).toContain('No OAuth clients');
    expect(renderToStaticMarkup(<ApiKeysTable rows={[]} />)).toContain('No machine API keys');
  });
});

describe('RolesPanel', () => {
  it('renders the roles it fetched', async () => {
    mockBff({ data: { data: [{ id: 'r1', code: 'admin', name: 'Administrator', state: 'active' }], meta: {} } });
    const html = await renderPanel(RolesPanel());
    expect(html).toContain('Roles &amp; Permissions');
    expect(html).toContain('data-testid="role-row"');
  });

  it('renders a configuration notice instead of an empty table when unconfigured', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(RolesPanel());
    expect(html).toContain('Backend not configured');
    expect(html).toContain('CUSTOMER_TENANT_ID');
    expect(html).not.toContain('<table');
  });

  it('renders the mapped failure code', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(RolesPanel());
    expect(html).toContain('Could not load roles (FORBIDDEN)');
    expect(html).not.toContain('<table');
  });

  it('reports a payload-free success instead of inventing rows', async () => {
    mockBff({ data: { meta: {} } });
    const html = await renderPanel(RolesPanel());
    expect(html).toContain('No roles payload');
  });
});

describe('UsersPanel', () => {
  it('renders the members it fetched', async () => {
    mockBff({
      data: {
        data: [
          {
            id: 'm1',
            state: 'active',
            user: { id: 'u1', name: 'Asha', email: 'asha@navdhya.dev', status: 'active' },
          },
        ],
        meta: {},
      },
    });
    const html = await renderPanel(UsersPanel());
    expect(html).toContain('data-testid="user-row"');
    expect(html).toContain('asha@navdhya.dev');
  });

  it('never renders an empty table for an unconfigured backend', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(UsersPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });
});

describe('ApiPanel', () => {
  it('renders machine keys and federation clients together', async () => {
    mockBff({
      data: {
        data: {
          api_keys: [{ id: 'k1', name: 'warehouse', prefix: 'ndk_live_ab', revoked_at: null }],
          oauth_clients: [{ id: 'c1', client_id: 'web-app', name: 'Web app', require_pkce: true }],
        },
        meta: {},
      },
    });
    const html = await renderPanel(ApiPanel());
    expect(html).toContain('data-testid="api-key-row"');
    expect(html).toContain('data-testid="oauth-client-row"');
    expect(html).toContain('Machine API keys');
    expect(html).toContain('OAuth clients');
  });

  it('renders a configuration notice instead of two empty tables', async () => {
    mockBff(fallbackBody);
    const html = await renderPanel(ApiPanel());
    expect(html).toContain('Backend not configured');
    expect(html).not.toContain('<table');
  });

  it('surfaces a denied upstream as an error, not as "no credentials"', async () => {
    mockBff(errorBody, 403);
    const html = await renderPanel(ApiPanel());
    expect(html).toContain('Could not load API credentials (FORBIDDEN)');
    expect(html).not.toContain('<table');
  });
});

import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { POST as acceptHandler } from '../assignments/[assignmentId]/accept/route';
import { POST as startHandler } from '../assignments/[assignmentId]/start/route';
import { GET as councilApprovalsHandler } from '../temples/[templeId]/council/approvals/route';
import { POST as decideCouncilApprovalHandler } from '../temples/[templeId]/council/approvals/[approvalId]/decide/route';
import { __issuePartnerTestToken } from '@/server/auth';

describe('Partner Actions & Temple Council Approvals (WP6)', () => {
  const originalFetch = global.fetch;

  beforeEach(() => {
    process.env.PARTNER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'https://api.navdhya.test';
  });

  afterEach(() => {
    global.fetch = originalFetch;
    delete process.env.PARTNER_BACKEND;
  });

  it('accepts assignment via live backend', async () => {
    const token = await __issuePartnerTestToken('usr_pandit_01', ['partner', 'pandit']);

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: async () => ({
        data: { id: 'asgn_01', status: 'CONFIRMED' },
        meta: { request_id: 'req_accept_1' },
      }),
    } as unknown as Response);

    const req = new Request('https://partner.navdhya.com/api/assignments/asgn_01/accept', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        'Idempotency-Key': 'ik_accept_test',
      },
    });

    const res = await acceptHandler(req, {
      params: Promise.resolve({ assignmentId: 'asgn_01' }),
    });

    expect(res.status).toBe(200);
    const json = await res.json();
    expect(json.data.status).toBe('ACCEPTED');
    expect(json.data.assignmentId).toBe('asgn_01');
  });

  it('starts assignment moving to IN_PROGRESS', async () => {
    const token = await __issuePartnerTestToken('usr_pandit_02', ['partner', 'pandit']);

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: async () => ({
        data: { id: 'asgn_02', status: 'IN_PROGRESS' },
        meta: { request_id: 'req_start_1' },
      }),
    } as unknown as Response);

    const req = new Request('https://partner.navdhya.com/api/assignments/asgn_02/start', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Idempotency-Key': 'ik_start_test',
      },
      body: JSON.stringify({ note: 'Commencing Vedic homa mantras' }),
    });

    const res = await startHandler(req, {
      params: Promise.resolve({ assignmentId: 'asgn_02' }),
    });

    expect(res.status).toBe(200);
    const json = await res.json();
    expect(json.data.status).toBe('IN_PROGRESS');
    expect(global.fetch).toHaveBeenCalledWith(
      'https://api.navdhya.test/api/v1/pandit/assignments/asgn_02:start',
      expect.objectContaining({ method: 'POST' }),
    );
  });

  it('retrieves temple council approvals queue', async () => {
    const token = await __issuePartnerTestToken('usr_headpriest_01', ['partner', 'temple_admin']);

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: async () => ({
        data: [
          {
            id: 'appr_rudra_99',
            temple_id: 'tmp_kashi_01',
            state: 'PENDING',
            devotee_name: 'Vikramaditya',
          },
        ],
        meta: { request_id: 'req_council_1' },
      }),
    } as unknown as Response);

    const req = new Request('https://partner.navdhya.com/api/temples/tmp_kashi_01/council/approvals', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const res = await councilApprovalsHandler(req, {
      params: Promise.resolve({ templeId: 'tmp_kashi_01' }),
    });

    expect(res.status).toBe(200);
    const json = await res.json();
    expect(json.data).toHaveLength(1);
    expect(json.data[0].id).toBe('appr_rudra_99');
  });

  it('records council sanction approval decision', async () => {
    const token = await __issuePartnerTestToken('usr_headpriest_01', ['partner', 'temple_admin']);

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: async () => ({
        data: {
          id: 'appr_rudra_99',
          state: 'APPROVED',
          decided_by: 'usr_headpriest_01',
        },
        meta: { request_id: 'req_decide_1' },
      }),
    } as unknown as Response);

    const req = new Request(
      'https://partner.navdhya.com/api/temples/tmp_kashi_01/council/approvals/appr_rudra_99/decide',
      {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ decision: 'APPROVED' }),
      }
    );

    const res = await decideCouncilApprovalHandler(req, {
      params: Promise.resolve({ templeId: 'tmp_kashi_01', approvalId: 'appr_rudra_99' }),
    });

    expect(res.status).toBe(200);
    const json = await res.json();
    expect(json.data.state).toBe('APPROVED');
  });
});

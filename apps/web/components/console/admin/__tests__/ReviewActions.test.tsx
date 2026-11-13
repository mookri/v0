/**
 * T-TR01-04 (T-07): seam test proving ReviewActions never submits second_reviewer: ''
 * and preserves reject payload across step-up MFA retry.
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { ReviewActions } from '@/components/console/admin/ReviewActions';

describe('ReviewActions reject countersign seam (T-07)', () => {
  const originalFetch = global.fetch;

  afterEach(() => {
    global.fetch = originalFetch;
    jest.restoreAllMocks();
  });

  it('requires non-empty second reviewer and blocks blank submission', async () => {
    const fetchMock = jest.fn();
    global.fetch = fetchMock as unknown as typeof fetch;

    render(<ReviewActions applicationId="app_123" />);
    fireEvent.click(screen.getByTestId('reject-button'));

    expect(screen.getByRole('dialog', { name: 'Reject application' })).toBeInTheDocument();

    fireEvent.click(screen.getByTestId('reject-confirm-submit'));
    expect(screen.getByTestId('reject-validation-error')).toHaveTextContent('Second reviewer identifier is required');
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('submits structured payload and preserves it after step-up challenge retry', async () => {
    const calls: Array<{ url: string; body?: string }> = [];
    let attempt = 0;

    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      const url = String(input);
      calls.push({ url, body: typeof init?.body === 'string' ? init.body : undefined });

      if (url.endsWith('/reject')) {
        attempt += 1;
        if (attempt === 1) {
          return {
            ok: false,
            status: 403,
            json: async () => ({ error: { code: 'FORBIDDEN' } }),
          } as unknown as Response;
        }
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: { id: 'app_123', status: 'REJECTED' } }),
        } as unknown as Response;
      }

      if (url === '/api/mfa/verify') {
        return {
          ok: true,
          status: 200,
          json: async () => ({ ok: true }),
        } as unknown as Response;
      }

      return { ok: false, status: 500, json: async () => ({}) } as unknown as Response;
    }) as unknown as typeof fetch;

    render(<ReviewActions applicationId="app_123" />);
    fireEvent.click(screen.getByTestId('reject-button'));
    fireEvent.change(screen.getByTestId('reject-reason-select'), { target: { value: 'authority_conflict' } });
    fireEvent.change(screen.getByTestId('reject-second-reviewer'), { target: { value: 'reviewer_42' } });
    fireEvent.change(screen.getByTestId('reject-note'), { target: { value: 'Trustee signature missing' } });
    fireEvent.click(screen.getByTestId('reject-confirm-submit'));

    await waitFor(() => {
      expect(screen.getByTestId('stepup-code')).toBeInTheDocument();
    });

    fireEvent.change(screen.getByTestId('stepup-code'), { target: { value: '654321' } });
    fireEvent.click(screen.getByTestId('stepup-submit'));

    await waitFor(() => {
      expect(screen.getByTestId('reject-button')).toHaveTextContent('✓ Rejected');
    });

    const rejectCalls = calls.filter((c) => c.url === '/api/admin/managers/app_123/reject');
    expect(rejectCalls).toHaveLength(2);
    expect(JSON.parse(rejectCalls[0].body!)).toEqual({
      reason_code: 'authority_conflict',
      note: 'Trustee signature missing',
      second_reviewer: 'reviewer_42',
    });
    expect(JSON.parse(rejectCalls[1].body!)).toEqual({
      reason_code: 'authority_conflict',
      note: 'Trustee signature missing',
      second_reviewer: 'reviewer_42',
    });
  });
});

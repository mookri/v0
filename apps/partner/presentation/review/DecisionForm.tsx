'use client';

import { useState } from 'react';

const REASONS = [
  'DOCUMENT_INCOMPLETE',
  'SKILL_REQUIREMENT_NOT_MET',
  'SERVICE_AREA_UNSUPPORTED',
  'VERIFICATION_FAILED',
  'PROFILE_INCOMPLETE',
  'OTHER',
] as const;

export function DecisionForm({ applicationId }: { applicationId: string }) {
  const [result, setResult] = useState<string | null>(null);

  async function submit(decision: 'APPROVED' | 'REJECTED' | 'REQUEST_INFO') {
    setResult(null);
    const form = document.getElementById(`decision-${applicationId}`) as HTMLFormElement | null;
    const data = new FormData(form ?? undefined);
    const res = await fetch(`/api/review/${applicationId}/decision`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        decision,
        reasonCode: (data?.get('reasonCode') as string) || undefined,
        note: (data?.get('note') as string) || undefined,
        secondReviewer: (data?.get('secondReviewer') as string) || undefined,
      }),
    });
    const body = (await res.json()) as { data?: { status: string }; error?: { code: string; message: string } };
    setResult(res.ok ? `Recorded: ${body.data?.status}` : `Failed: ${body.error?.code} — ${body.error?.message}`);
  }

  return (
    <form id={`decision-${applicationId}`}>
      <label>
        Reason
        <select name="reasonCode" defaultValue="">
          <option value="">—</option>
          {REASONS.map((r) => (
            <option key={r} value={r}>
              {r}
            </option>
          ))}
        </select>
      </label>
      <label>
        Note
        <input name="note" type="text" />
      </label>
      <label>
        Second reviewer (required for rejections)
        <input name="secondReviewer" type="text" />
      </label>
      <div>
        <button type="button" onClick={() => void submit('APPROVED')}>
          Approve
        </button>
        <button type="button" onClick={() => void submit('REJECTED')}>
          Reject
        </button>
        <button type="button" onClick={() => void submit('REQUEST_INFO')}>
          Request info
        </button>
      </div>
      {result ? <p role="status">{result}</p> : null}
    </form>
  );
}

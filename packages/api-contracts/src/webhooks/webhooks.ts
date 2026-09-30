export interface WebhookEnvelope {
  eventId: string;
  type: string;
  occurredAt: string;
  payload: unknown;
}

export interface WebhookVerifyResult {
  ok: boolean;
  reason?: 'BAD_SIGNATURE' | 'STALE_TIMESTAMP' | 'DUPLICATE';
}

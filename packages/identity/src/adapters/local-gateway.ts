import { SessionAudience } from '@navdhya/identity/src/domain/session';
import { validateHandoff } from '@navdhya/identity/src/domain/handoff';
import { HandoffStore } from '@navdhya/identity/src/ports/stores';
import { hashCode } from '@navdhya/identity/src/application/sessions';

export interface IdentityGateway {
  getSession(sessionId: string): Promise<{ sessionId: string; userId: string; audience: SessionAudience } | null>;
  createHandoff(input: {
    subject: string;
    source: SessionAudience;
    audience: SessionAudience;
    ttlSeconds: number;
    nowIso: string;
    returnPath?: string;
  }): Promise<{ code: string }>;
  exchangeHandoff(input: { code: string; audience: SessionAudience; nowIso: string }): Promise<{ subject: string }>;
}

export class LocalIdentityGateway implements IdentityGateway {
  private readonly handoffs = new Map<string, { codeHash: string; record: Record<string, unknown> }>();

  constructor(private readonly store: HandoffStore) {}

  async getSession(): Promise<null> {
    return null;
  }

  async createHandoff(input: {
    subject: string;
    source: SessionAudience;
    audience: SessionAudience;
    ttlSeconds: number;
    nowIso: string;
    returnPath?: string;
  }): Promise<{ code: string }> {
    const code = `ho_${Math.random().toString(36).slice(2)}`;
    await this.store.save({
      id: `h_${Date.now()}`,
      subject: input.subject,
      source: input.source,
      audience: input.audience,
      codeHash: hashCode(code),
      expiresAt: new Date(Date.parse(input.nowIso) + input.ttlSeconds * 1000).toISOString(),
      returnPath: input.returnPath,
      nonce: Math.random().toString(36).slice(2),
    });
    return { code };
  }

  async exchangeHandoff(input: { code: string; audience: SessionAudience; nowIso: string }): Promise<{ subject: string }> {
    const record = await this.store.consume(hashCode(input.code), input.nowIso);
    if (!record) throw new Error('INVALID_HANDOFF');
    const v = validateHandoff(record, { codeHash: hashCode(input.code), audience: input.audience, nowIso: input.nowIso });
    if (!v.ok) throw new Error(v.error);
    return { subject: record.subject };
  }
}

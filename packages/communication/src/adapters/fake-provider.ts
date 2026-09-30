import { CommunicationChannel } from '../domain/message';
import { ChannelProvider } from '../ports/providers';

// Deterministic fake for CI: no network, scripted outcomes per channel.
export class FakeChannelProvider implements ChannelProvider {
  readonly providerId = 'fake';
  constructor(
    readonly channel: CommunicationChannel,
    private readonly outcome: 'SUCCESS' | 'TIMEOUT' | 'INVALID' = 'SUCCESS',
  ) {}

  capabilities() {
    return { templates: true, deliveryReceipts: true, readReceipts: false, richMedia: false, scheduling: false };
  }

  async send(): Promise<{ accepted: boolean; providerMessageId?: string; errorCode?: string }> {
    if (this.outcome === 'SUCCESS') return { accepted: true, providerMessageId: 'fake_1' };
    if (this.outcome === 'TIMEOUT') return { accepted: false, errorCode: 'TIMEOUT' };
    return { accepted: false, errorCode: 'INVALID' };
  }

  normalizeError(error: unknown): string {
    return typeof error === 'string' ? error : 'UNKNOWN';
  }
}

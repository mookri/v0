import { CommunicationChannel } from '@navdhya/communication/src/domain/message';
import { ChannelProvider } from '@navdhya/communication/src/ports/providers';

export interface OrchestratedDispatch {
  channel: CommunicationChannel;
  providerId: string;
  dedupKey: string;
}

// Policy: transactional/security bypass quiet hours; marketing requires consent upstream.
export function selectChannel(input: {
  preferred: CommunicationChannel[];
  category: 'transactional' | 'security' | 'service' | 'marketing';
  quietHoursActive: boolean;
}): CommunicationChannel | null {
  if (input.category === 'security') return input.preferred[0] ?? null;
  if (input.quietHoursActive && (input.category === 'marketing' || input.category === 'service')) return null;
  return input.preferred[0] ?? null;
}

export function fallbackChannel(failed: CommunicationChannel): CommunicationChannel | null {
  if (failed === 'whatsapp') return 'sms';
  if (failed === 'push') return 'sms';
  if (failed === 'sms') return 'email';
  return null;
}

export function routeProvider(providers: ChannelProvider[], channel: CommunicationChannel): ChannelProvider | null {
  return providers.find((p) => p.channel === channel) ?? null;
}

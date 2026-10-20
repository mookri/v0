import { CommunicationChannel } from '@navdhya/communication/src/domain/message';

export interface ProviderCapabilities {
  templates: boolean;
  deliveryReceipts: boolean;
  readReceipts: boolean;
  richMedia: boolean;
  scheduling: boolean;
}

export interface ProviderSendResult {
  accepted: boolean;
  providerMessageId?: string;
  errorCode?: string;
}

export interface ChannelProvider {
  readonly providerId: string;
  readonly channel: CommunicationChannel;
  capabilities(): ProviderCapabilities;
  send(message: { to: string; body: string }): Promise<ProviderSendResult>;
  normalizeError(error: unknown): string;
}

export interface RecipientDirectory {
  resolveUser(userId: string): Promise<{ userId: string; locale: string; phone?: string; email?: string } | null>;
}

export interface CommunicationGateway {
  send(input: { userId: string; channel: CommunicationChannel; templateId: string; variables: Record<string, string> }): Promise<{ messageId: string }>;
}

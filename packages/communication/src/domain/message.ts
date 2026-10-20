export type CommunicationChannel = 'email' | 'sms' | 'whatsapp' | 'push' | 'in_app';
export type CommunicationCategory = 'transactional' | 'security' | 'service' | 'marketing';
export type DeliveryStatus = 'PENDING' | 'QUEUED' | 'SENT' | 'DELIVERED' | 'FAILED' | 'RETRYING' | 'CANCELLED' | 'EXPIRED';

export interface Message {
  id: string;
  notificationId: string;
  category: CommunicationCategory;
  channel: CommunicationChannel;
  templateId: string;
  templateVersion: string;
  recipientUserId: string;
  locale: string;
  scheduledAt?: string;
  status: DeliveryStatus;
  createdAt: string;
}

export interface DeliveryAttempt {
  id: string;
  messageId: string;
  provider: string;
  attemptNumber: number;
  startedAt: string;
  completedAt?: string;
  status: 'SUCCESS' | 'RETRYABLE_FAILURE' | 'PERMANENT_FAILURE';
  providerMessageId?: string;
  normalizedErrorCode?: string;
}

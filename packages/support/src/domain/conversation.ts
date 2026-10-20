export type MessageVisibility = 'CUSTOMER' | 'INTERNAL' | 'PARTNER';
export type MessageAuthorType = 'CUSTOMER' | 'AGENT' | 'PARTNER' | 'SYSTEM';

export interface SupportMessage {
  id: string;
  caseId: string;
  author: { type: MessageAuthorType; id?: string };
  visibility: MessageVisibility;
  channel: 'APP' | 'WEB' | 'EMAIL' | 'WHATSAPP' | 'SYSTEM';
  body: string;
  sequence: number;
  createdAt: string;
}

export function visibleToCustomer(message: SupportMessage): boolean {
  return message.visibility === 'CUSTOMER';
}

export function visibleToPartner(message: SupportMessage): boolean {
  return message.visibility === 'PARTNER';
}

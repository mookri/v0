export type NotificationCategory = 'transactional' | 'security' | 'service' | 'marketing';
export type NotificationCriticality = 'critical' | 'high' | 'normal' | 'low';
export type NotificationStatus = 'CREATED' | 'PLANNED' | 'DISPATCHED' | 'PARTIALLY_DELIVERED' | 'DELIVERED' | 'FAILED' | 'CANCELLED';
export type NotificationAudience = 'customer' | 'partner' | 'admin' | 'operations' | 'system';

export interface RouteReference {
  routeId: string;
  params?: Record<string, string>;
}

export interface Notification {
  id: string;
  type: string;
  recipient: { userId: string };
  audience: NotificationAudience;
  category: NotificationCategory;
  criticality: NotificationCriticality;
  sourceEventId?: string;
  route?: RouteReference;
  title: string;
  body: string;
  createdAt: string;
  readAt?: string;
  status: NotificationStatus;
}

export function isRead(n: Notification): boolean {
  return n.readAt !== undefined;
}

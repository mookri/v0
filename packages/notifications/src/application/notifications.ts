import { Notification } from '@navdhya/notifications/src/domain/notification';

export interface NotificationRepository {
  save(n: Notification): Promise<void>;
  findById(id: string): Promise<Notification | null>;
  listByRecipient(userId: string): Promise<Notification[]>;
  markRead(id: string, readAt: string): Promise<Notification | null>;
}

export function unreadCount(notifications: Notification[]): number {
  return notifications.filter((n) => !n.readAt).length;
}

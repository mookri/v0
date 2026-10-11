import { NotificationsPageView } from '@/presentation/notifications/NotificationsPage';
import { customerApi } from '@/adapters/backend/customer-api-client';
import type { CustomerNotification } from '@/application/notifications/notifications';

export const dynamic = 'force-dynamic';

export default async function NotificationsPage() {
  try {
    const data = await customerApi.getNotifications();
    const items = (data as { items: CustomerNotification[] }).items || [];
    return <NotificationsPageView items={items} />;
  } catch (e: any) {
    if (e.status === 401) {
      return <NotificationsPageView items={[]} />;
    }
    return <NotificationsPageView items={[]} />;
  }
}
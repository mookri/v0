import { NotificationsPageView } from '@/presentation/notifications/NotificationsPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';
import type { CustomerNotification } from '@/application/notifications/notifications';

export const dynamic = 'force-dynamic';

export default async function NotificationsPage() {
  try {
    const api = await getServerCustomerApi();
    const data = await api.getNotifications();
    const items = (data as { items: CustomerNotification[] }).items || [];
    return <NotificationsPageView items={items} />;
  } catch (e: any) {
    if (e.status === 401) {
      return <NotificationsPageView items={[]} />;
    }
    return <NotificationsPageView items={[]} error="Failed to load notifications" />;
  }
}
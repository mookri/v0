import { CustomerProfilePage } from '@/presentation/profile/CustomerProfilePage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';
import { toCustomerProfile } from '@/application/profile/customer-profile';
import { notFound } from 'next/navigation';

export const dynamic = 'force-dynamic';

export default async function ProfilePage() {
  try {
    const api = await getServerCustomerApi();
    // The BFF wraps the backend payload as `{profile}`; the page must
    // unwrap it before mapping (previously `id`/`name`/`email` vanished).
    const data = await api.getProfile();
    const rawProfile = (data as { profile?: Record<string, unknown> | null }).profile;
    const profile = rawProfile ? toCustomerProfile(rawProfile) : null;
    return <CustomerProfilePage profile={profile} />;
  } catch (e: any) {
    if (e.status === 401 || e.status === 404) {
      notFound();
    }
    return <CustomerProfilePage profile={null} error="Failed to load profile" />;
  }
}
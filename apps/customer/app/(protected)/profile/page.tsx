import { CustomerProfilePage } from '@/presentation/profile/CustomerProfilePage';
import { customerApi } from '@/adapters/backend/customer-api-client';
import { toCustomerProfile } from '@/application/profile/customer-profile';
import { notFound } from 'next/navigation';

export default async function ProfilePage() {
  try {
    const data = await customerApi.getProfile();
    const profile = toCustomerProfile(data as Record<string, unknown>);
    return <CustomerProfilePage profile={profile} />;
  } catch (e: any) {
    if (e.status === 401 || e.status === 404) {
      notFound();
    }
    return <CustomerProfilePage profile={null} error="Failed to load profile" />;
  }
}
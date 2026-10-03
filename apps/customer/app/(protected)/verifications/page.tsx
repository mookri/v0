import { VerificationsPageView } from '@/presentation/verifications/VerificationsPage';
import { customerApi } from '@/adapters/backend/customer-api-client';
import type { CustomerVerification } from '@/application/verifications/verifications';

export default async function VerificationsPage() {
  try {
    const data = await customerApi.getVerifications();
    const verifications = data as CustomerVerification[];
    return <VerificationsPageView verifications={verifications} />;
  } catch (e: any) {
    if (e.status === 401) {
      return <VerificationsPageView verifications={[]} error="Please sign in to view verifications" />;
    }
    return <VerificationsPageView verifications={[]} error="Failed to load verifications" />;
  }
}
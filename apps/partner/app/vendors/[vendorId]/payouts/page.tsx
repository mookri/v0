import { VendorPayoutsView } from '@/presentation/vendors/VendorPayoutsView';
import { VendorMissing } from '@/presentation/vendors/VendorMissing';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorListPayload, VendorPayout } from '@/application/vendor';

interface VendorPayoutsPageProps {
  params: Promise<{ vendorId: string }>;
}

export default async function VendorPayoutsPage({ params }: VendorPayoutsPageProps) {
  const { vendorId } = await params;

  try {
    const [, payload] = await Promise.all([
      partnerApi.getVendor(vendorId),
      partnerApi.getVendorPayouts(vendorId, { limit: 50 }),
    ]);
    const payouts = (payload as VendorListPayload<VendorPayout> | undefined)?.data ?? [];
    return <VendorPayoutsView vendorId={vendorId} payouts={payouts} />;
  } catch {
    return <VendorMissing />;
  }
}

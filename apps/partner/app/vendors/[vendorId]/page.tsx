import { VendorDetailView } from '@/presentation/vendors/VendorDetailView';
import { VendorMissing } from '@/presentation/vendors/VendorMissing';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorProfile } from '@/application/vendor';

interface VendorDetailPageProps {
  params: Promise<{ vendorId: string }>;
}

export default async function VendorDetailPage({ params }: VendorDetailPageProps) {
  const { vendorId } = await params;

  try {
    const vendor = await partnerApi.getVendor(vendorId);
    return <VendorDetailView vendor={vendor as VendorProfile} />;
  } catch {
    return <VendorMissing />;
  }
}

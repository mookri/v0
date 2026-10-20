import { VendorSettingsView } from '@/presentation/vendors/VendorSettingsView';
import { VendorMissing } from '@/presentation/vendors/VendorMissing';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorProfile } from '@/application/vendor';

interface VendorSettingsPageProps {
  params: Promise<{ vendorId: string }>;
}

export default async function VendorSettingsPage({ params }: VendorSettingsPageProps) {
  const { vendorId } = await params;

  try {
    const vendor = await partnerApi.getVendor(vendorId);
    return <VendorSettingsView vendor={vendor as VendorProfile} />;
  } catch {
    return <VendorMissing />;
  }
}

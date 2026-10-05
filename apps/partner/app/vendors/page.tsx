import { VendorsListView } from '@/presentation/vendors/VendorsListView';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorListPayload, VendorProfile } from '@/application/vendor';

export default async function VendorsPage() {
  try {
    const data = await partnerApi.getVendors({ limit: 20 });
    const vendors = (data as VendorListPayload<VendorProfile> | undefined)?.data ?? [];
    return <VendorsListView vendors={vendors} />;
  } catch {
    return <VendorsListView vendors={[]} />;
  }
}

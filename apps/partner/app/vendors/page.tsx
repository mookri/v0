import { VendorsListView } from '@/presentation/vendors/VendorsListView';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorListPayload, VendorProfile } from '@/application/vendor';

// Per-request data: the vendor list is tenant-scoped and must never be baked
// into a build artefact (see /pandit for the build-blocking symptom).
export const dynamic = 'force-dynamic';

export default async function VendorsPage() {
  try {
    const data = await partnerApi.getVendors({ limit: 20 });
    const vendors = (data as VendorListPayload<VendorProfile> | undefined)?.data ?? [];
    return <VendorsListView vendors={vendors} />;
  } catch {
    return <VendorsListView vendors={[]} />;
  }
}

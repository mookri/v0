import { VendorInventoryView } from '@/presentation/vendors/VendorInventoryView';
import { VendorMissing } from '@/presentation/vendors/VendorMissing';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorInventoryRow, VendorListPayload } from '@/application/vendor';

interface VendorInventoryPageProps {
  params: Promise<{ vendorId: string }>;
}

export default async function VendorInventoryPage({ params }: VendorInventoryPageProps) {
  const { vendorId } = await params;

  try {
    const [, payload] = await Promise.all([
      partnerApi.getVendor(vendorId),
      partnerApi.getVendorInventory(vendorId, { limit: 50 }),
    ]);
    const rows = (payload as VendorListPayload<VendorInventoryRow> | undefined)?.data ?? [];
    return <VendorInventoryView vendorId={vendorId} rows={rows} />;
  } catch {
    return <VendorMissing />;
  }
}

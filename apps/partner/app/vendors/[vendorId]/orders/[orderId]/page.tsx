import { VendorOrderDetailView } from '@/presentation/vendors/VendorOrderDetailView';
import { VendorMissing } from '@/presentation/vendors/VendorMissing';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorOrder } from '@/application/vendor';

interface VendorOrderDetailPageProps {
  params: Promise<{ vendorId: string; orderId: string }>;
}

export default async function VendorOrderDetailPage({ params }: VendorOrderDetailPageProps) {
  const { vendorId, orderId } = await params;

  try {
    const order = await partnerApi.getVendorOrder(vendorId, orderId);
    return <VendorOrderDetailView vendorId={vendorId} order={order as VendorOrder} />;
  } catch {
    return <VendorMissing />;
  }
}

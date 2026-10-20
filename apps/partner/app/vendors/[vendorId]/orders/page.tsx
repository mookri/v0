import { VendorOrdersView } from '@/presentation/vendors/VendorOrdersView';
import { VendorMissing } from '@/presentation/vendors/VendorMissing';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorListPayload, VendorOrder } from '@/application/vendor';

interface VendorOrdersPageProps {
  params: Promise<{ vendorId: string }>;
}

type OrderListRow = Omit<VendorOrder, 'items'>;

export default async function VendorOrdersPage({ params }: VendorOrdersPageProps) {
  const { vendorId } = await params;

  try {
    const [, payload] = await Promise.all([
      partnerApi.getVendor(vendorId),
      partnerApi.getVendorOrders(vendorId, { limit: 50 }),
    ]);
    const orders = (payload as VendorListPayload<OrderListRow> | undefined)?.data ?? [];
    return <VendorOrdersView vendorId={vendorId} orders={orders} />;
  } catch {
    return <VendorMissing />;
  }
}

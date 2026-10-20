import { VendorProductsView } from '@/presentation/vendors/VendorProductsView';
import { VendorMissing } from '@/presentation/vendors/VendorMissing';
import { partnerApi } from '@/adapters/backend/partner-api-client';
import type { VendorProduct, VendorProductsPayload } from '@/application/vendor';

interface VendorProductsPageProps {
  params: Promise<{ vendorId: string }>;
}

export default async function VendorProductsPage({ params }: VendorProductsPageProps) {
  const { vendorId } = await params;

  try {
    const [, payload] = await Promise.all([
      partnerApi.getVendor(vendorId),
      partnerApi.getVendorProducts(vendorId, { limit: 50 }),
    ]);
    const products = (payload as VendorProductsPayload | undefined)?.data ?? [];
    return <VendorProductsView vendorId={vendorId} products={products as VendorProduct[]} />;
  } catch {
    return <VendorMissing />;
  }
}

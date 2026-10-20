import { PujaDetailPageView } from '@/presentation/pujas/PujaDetailPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';

interface PujaDetail {
  id: string;
  title: string;
  kind: 'puja';
  sku: string;
  currency: string;
  price_minor: number;
  description?: string;
}

interface RouteParams {
  params: Promise<{ pujaId: string }>;
}

export const dynamic = 'force-dynamic';

export default async function PujaDetailPage({ params }: RouteParams) {
  const { pujaId } = await params;

  try {
    const api = await getServerCustomerApi();
    const puja = await api.getPuja(encodeURIComponent(pujaId));
    return <PujaDetailPageView puja={puja as PujaDetail} />;
  } catch (e: any) {
    if (e.status === 404) {
      return <PujaDetailPageView puja={{ id: '', title: '', kind: 'puja', sku: '', currency: 'INR', price_minor: 0 }} error="Puja not found" />;
    }
    return <PujaDetailPageView puja={{ id: '', title: '', kind: 'puja', sku: '', currency: 'INR', price_minor: 0 }} error="Failed to load puja details" />;
  }
}
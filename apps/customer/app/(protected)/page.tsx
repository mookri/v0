import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';
import { getCustomerHome, type CustomerHomeViewModel } from '@/application/home/get-customer-home';
import { CustomerHomePage } from '@/presentation/home/CustomerHomePage';

export const dynamic = 'force-dynamic';

export default async function CustomerHome() {
  try {
    const api = await getServerCustomerApi();
    const home = (await api.getHome()) as CustomerHomeViewModel;
    return <CustomerHomePage home={home} />;
  } catch {
    // The BFF owns the greeting/booking/recommendation composition; this
    // fallback only covers a transport failure, not a second data source.
    return <CustomerHomePage home={getCustomerHome({ displayName: 'Friend', recommendations: [], recentActivity: [] })} />;
  }
}

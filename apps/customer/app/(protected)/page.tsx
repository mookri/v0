import { getCustomerHome } from '../../application/home/get-customer-home';
import { CustomerHomePage } from '../../presentation/home/CustomerHomePage';

export default function CustomerHome() {
  const home = getCustomerHome({ displayName: 'Friend', recommendations: [], recentActivity: [] });
  return <CustomerHomePage home={home} />;
}

import { SupportPageView } from '@/presentation/support/SupportPage';
import { helpArticles } from '@/application/support/support';

export const dynamic = 'force-dynamic';

export default function SupportPage() {
  return <SupportPageView articles={helpArticles} />;
}

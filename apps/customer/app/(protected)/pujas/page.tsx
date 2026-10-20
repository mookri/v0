import Link from 'next/link';

import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';

export const dynamic = 'force-dynamic';

interface PujaListItem {
  id: string;
  title: string;
  sku: string;
  currency: string;
  price_minor: number;
  description?: string;
}

function formatPrice(minor: number, currency: string): string {
  const amount = minor / 100;
  try {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency,
      maximumFractionDigits: 0,
    }).format(amount);
  } catch {
    return `${currency} ${amount.toFixed(0)}`;
  }
}

export default async function PujasPage() {
  let items: PujaListItem[] = [];
  try {
    const api = await getServerCustomerApi();
    const data = await api.getPujas();
    items = (data as { items?: PujaListItem[] }).items ?? [];
  } catch {
    items = [];
  }

  return (
    <main className="p-6 max-w-5xl mx-auto">
      <h1 className="text-2xl font-bold mb-1">Pujas &amp; rituals</h1>
      <p className="text-gray-600 mb-6">Choose a ritual to book with a verified pandit.</p>

      {items.length === 0 ? (
        <p className="text-gray-500">No pujas are available right now.</p>
      ) : (
        <ul className="grid gap-4 sm:grid-cols-2">
          {items.map((puja) => (
            <li key={puja.id} className="rounded-xl border border-gray-200 p-4 hover:shadow-sm">
              <Link href={`/pujas/${encodeURIComponent(puja.id)}`} className="block">
                <h2 className="text-lg font-semibold">{puja.title}</h2>
                {puja.description ? (
                  <p className="mt-1 text-sm text-gray-600 line-clamp-2">{puja.description}</p>
                ) : null}
                <p className="mt-3 font-medium">{formatPrice(puja.price_minor, puja.currency)}</p>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}

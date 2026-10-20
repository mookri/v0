import Link from 'next/link';
import { DiscoverFeed } from '@/application/discover/discover-feed';

export function DiscoverPageView({ feed }: { feed: DiscoverFeed }) {
  const renderList = (items: DiscoverFeed['recommended']) => (
    <ul className="space-y-3">
      {items.map((item) => {
        const content = (
          <>
            <h3 className="font-medium text-gray-900">{item.title}</h3>
            <p className="text-sm text-gray-500 mt-1">Kind: {item.kind}</p>
          </>
        );
        const className =
          'block p-4 bg-white border border-gray-200 rounded-lg hover:border-indigo-300 hover:shadow-sm transition-all';
        return (
          <li key={item.id}>
            {item.kind === 'puja' ? (
              <Link href={`/pujas/${encodeURIComponent(item.id)}`} className={className}>
                {content}
              </Link>
            ) : (
              // Content cards have no puja id; a /pujas link would 404.
              <div className={className}>{content}</div>
            )}
          </li>
        );
      })}
    </ul>
  );

  return (
    <main className="p-6 max-w-3xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">Discover</h1>

      <section className="mb-8">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Recommended for you</h2>
        {feed.recommended.length > 0 ? (
          renderList(feed.recommended)
        ) : (
          <p className="text-gray-500 text-center py-8">No recommendations yet — start exploring!</p>
        )}
      </section>

      <section className="mb-8">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Popular rituals</h2>
        {feed.popular.length > 0 ? (
          renderList(feed.popular)
        ) : (
          <p className="text-gray-500 text-center py-8">No popular rituals yet.</p>
        )}
      </section>

      <section>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Recently added</h2>
        {feed.recent.length > 0 ? (
          renderList(feed.recent)
        ) : (
          <p className="text-gray-500 text-center py-8">No recent additions.</p>
        )}
      </section>
    </main>
  );
}
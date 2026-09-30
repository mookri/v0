import { DiscoverFeed } from '@/application/discover/discover-feed';

export function DiscoverPageView({ feed }: { feed: DiscoverFeed }) {
  return (
    <main>
      <h1>Discover</h1>
      <section>
        <h2>Recommended</h2>
        <ul>
          {feed.recommended.map((r) => (
            <li key={r.id}>{r.title}</li>
          ))}
        </ul>
      </section>
      <section>
        <h2>Popular rituals</h2>
        <ul>
          {feed.popular.map((r) => (
            <li key={r.id}>{r.title}</li>
          ))}
        </ul>
      </section>
    </main>
  );
}

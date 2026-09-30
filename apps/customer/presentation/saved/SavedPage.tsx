import { SavedItemCard } from '../../application/saved/saved-items';

export function SavedPageView({ items }: { items: SavedItemCard[] }) {
  if (items.length === 0) {
    return (
      <main>
        <h1>Saved</h1>
        <p>Nothing saved yet. Discover rituals to save them here.</p>
      </main>
    );
  }
  return (
    <main>
      <h1>Saved</h1>
      <ul>
        {items.map((i) => (
          <li key={i.id}>
            <a href={i.href}>{i.title}</a>
          </li>
        ))}
      </ul>
    </main>
  );
}

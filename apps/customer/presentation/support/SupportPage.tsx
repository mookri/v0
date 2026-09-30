import { HelpArticle } from '../../application/support/support';

export function SupportPageView({ articles }: { articles: HelpArticle[] }) {
  return (
    <main>
      <h1>Support</h1>
      <ul>
        {articles.map((a) => (
          <li key={a.id}>
            <a href={a.href}>{a.title}</a>
          </li>
        ))}
      </ul>
    </main>
  );
}

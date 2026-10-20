import {
  ConsoleEmptyState,
  ConsolePanel,
  ConsoleResultBody,
  ConsoleTable,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/admin/content` — W2. Backed by Laravel GET /api/v1/platform/ops/content
 * (`platform:ops:view`): the editorial queue (most recently updated SEO pages).
 */

export interface ContentRow {
  id: string;
  object?: string;
  slug: string;
  cluster: string;
  title: string | null;
  noindex: boolean;
  last_updated: string | null;
}

export function ContentTable({ rows }: { rows: ContentRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No content in the queue"
        description="Editorial pages appear here as they are created or updated."
      />
    );
  }

  return (
    <ConsoleTable headers={['Slug', 'Cluster', 'Title', 'Indexable', 'Updated']}>
      {rows.map((page) => (
        <tr key={page.id} data-testid="content-row">
          <td className="px-4 py-3 font-mono text-xs">{page.slug}</td>
          <td className="px-4 py-3 text-muted-foreground">{page.cluster}</td>
          <td className="px-4 py-3">{page.title ?? '—'}</td>
          <td className="px-4 py-3 text-muted-foreground">{page.noindex ? 'No' : 'Yes'}</td>
          <td className="px-4 py-3 text-muted-foreground">
            {page.last_updated ? new Date(page.last_updated).toLocaleString() : '—'}
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function ContentPanel() {
  const result = readConsole<ContentRow[]>(await bffJSON<ContentRow[]>('/api/admin/content'));

  return (
    <ConsolePanel
      title="Content"
      description="Editorial queue, refreshes, and publication status."
    >
      <ConsoleResultBody result={result} resource="content pages">
        {(rows) => <ContentTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}

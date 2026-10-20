import {
  ConsolePanel,
  ConsoleResultBody,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/admin/seo` — W2. Backed by Laravel GET /api/v1/platform/ops/seo
 * (`platform:ops:view`): an aggregate of SEO coverage (pages, noindex,
 * indexed, clusters).
 */

export interface SeoCoverage {
  pages: number;
  noindex: number;
  indexed: number;
  clusters: number;
}

export function SeoCoverageCard({ coverage }: { coverage: SeoCoverage }) {
  const rows: { label: string; value: number }[] = [
    { label: 'Total pages', value: coverage.pages },
    { label: 'Indexed', value: coverage.indexed },
    { label: 'Noindex', value: coverage.noindex },
    { label: 'Clusters', value: coverage.clusters },
  ];

  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
      {rows.map((row) => (
        <div
          key={row.label}
          data-testid="seo-stat"
          className="rounded-2xl border border-border bg-card p-4 text-center"
        >
          <p className="text-2xl font-bold">{row.value}</p>
          <p className="text-xs text-muted-foreground">{row.label}</p>
        </div>
      ))}
    </div>
  );
}

export async function SeoPanel() {
  const result = readConsole<SeoCoverage>(await bffJSON<SeoCoverage>('/api/admin/seo'));

  return (
    <ConsolePanel
      title="SEO"
      description="Coverage rollout, QA, structured data, and market expansion status."
    >
      <ConsoleResultBody result={result} resource="SEO coverage">
        {(coverage) => <SeoCoverageCard coverage={coverage} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}

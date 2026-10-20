import { StatusBadge } from '@/components/dashboard/StatusBadge';
import {
  ConsoleEmptyState,
  ConsolePanel,
  ConsoleResultBody,
  ConsoleTable,
} from '@/components/console/ConsolePanel';
import { bffJSON } from '@/lib/console/bff';
import { readConsole } from '@/lib/console/envelope';

/**
 * `/admin/catalog` — W3 capability surfacing. Backed by Laravel
 * GET /api/v1/catalog/items (`scope:catalog:read` for the list;
 * `capability:catalog:item:manage` gates publish/archive/update). The panel
 * shows the item's status and offers publish (draft → active) and archive
 * (active → archived) actions.
 */

export interface CatalogItemRow {
  id: string;
  object?: string;
  sku: string;
  name: string;
  status: string;
  currency: string;
  price_minor: number;
}

export function CatalogTable({ rows }: { rows: CatalogItemRow[] }) {
  if (rows.length === 0) {
    return (
      <ConsoleEmptyState
        title="No catalog items"
        description="Catalog items appear here once they are created."
      />
    );
  }

  return (
    <ConsoleTable headers={['SKU', 'Name', 'Status', 'Price']}>
      {rows.map((item) => (
        <tr key={item.id} data-testid="catalog-row">
          <td className="px-4 py-3 font-mono text-xs">{item.sku}</td>
          <td className="px-4 py-3">{item.name}</td>
          <td className="px-4 py-3">
            <StatusBadge status={item.status.toLowerCase()} />
          </td>
          <td className="px-4 py-3 text-muted-foreground">
            {item.currency} {(item.price_minor / 100).toFixed(2)}
          </td>
        </tr>
      ))}
    </ConsoleTable>
  );
}

export async function CatalogPanel() {
  const result = readConsole<CatalogItemRow[]>(await bffJSON<CatalogItemRow[]>('/api/admin/catalog'));

  return (
    <ConsolePanel
      title="Catalog"
      description="Catalog items with publish and archive controls."
    >
      <ConsoleResultBody result={result} resource="catalog items">
        {(rows) => <CatalogTable rows={rows} />}
      </ConsoleResultBody>
    </ConsolePanel>
  );
}

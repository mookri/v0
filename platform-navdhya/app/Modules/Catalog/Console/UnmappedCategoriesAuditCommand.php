<?php

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Models\CatalogCategory;
use App\Modules\Catalog\Models\CatalogItem;
use Illuminate\Console\Command;

/**
 * Command to audit legacy unmapped free-text categories and backfill category_id (F1).
 */
final class UnmappedCategoriesAuditCommand extends Command
{
    protected $signature = 'catalog:unmapped-categories {--backfill : Automatically update items matching known patterns}';

    protected $description = 'Audit legacy free-text category strings on catalog items and report unmapped residue';

    public function handle(): int
    {
        $this->info('Starting catalog category residue audit...');

        $items = CatalogItem::query()
            ->withoutGlobalScopes()
            ->whereNull('category_id')
            ->get();

        if ($items->isEmpty()) {
            $this->info('Zero items missing category_id! All catalog items are categorized.');
            return 0;
        }

        $categories = CatalogCategory::query()->withoutGlobalScopes()->get();
        $unmappedResidue = [];
        $backfilled = 0;

        foreach ($items as $item) {
            $rawCat = $item->attributes['category'] ?? null;
            $matchedCategory = null;

            if ($rawCat !== null) {
                $matchedCategory = $categories->first(function (CatalogCategory $c) use ($rawCat): bool {
                    return strcasecmp($c->name, (string) $rawCat) === 0
                        || strcasecmp($c->slug, (string) $rawCat) === 0;
                });
            }

            // Fallback match based on SKU or Name keyword
            if ($matchedCategory === null) {
                if (stripos($item->name, 'Havan') !== false || stripos($item->name, 'Homa') !== false) {
                    $matchedCategory = $categories->firstWhere('slug', 'havans');
                } elseif (stripos($item->name, 'Puja') !== false) {
                    $matchedCategory = $categories->firstWhere('slug', 'rituals');
                } elseif (stripos($item->name, 'Darshan') !== false) {
                    $matchedCategory = $categories->firstWhere('slug', 'temple-pujas');
                }
            }

            if ($matchedCategory !== null) {
                if ($this->option('backfill')) {
                    $item->category_id = $matchedCategory->id;
                    $item->saveQuietly();
                    $backfilled++;
                }
            } else {
                $residueKey = $rawCat ?? '(none)';
                $unmappedResidue[$residueKey][] = $item->sku;
            }
        }

        if ($this->option('backfill')) {
            $this->info("Successfully backfilled {$backfilled} catalog items with category relationships.");
        }

        if (! empty($unmappedResidue)) {
            $this->warn('Unmapped residue strings found:');
            foreach ($unmappedResidue as $residue => $skus) {
                $this->line(" - '{$residue}': " . count($skus) . ' items (' . implode(', ', array_slice($skus, 0, 5)) . ')');
            }
            return 1;
        }

        $this->info('Audit complete: Zero unmapped categories residue.');
        return 0;
    }
}


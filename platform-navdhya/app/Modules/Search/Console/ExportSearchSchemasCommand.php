<?php

namespace App\Modules\Search\Console;

use App\Modules\Search\Support\TransliterationSchemes;
use Illuminate\Console\Command;

class ExportSearchSchemasCommand extends Command
{
    protected $signature = 'search:export-schemas {--out= : Target path for exported JSON artifact}';
    protected $description = 'Export transliteration schema and mapping table to packages/search artifact';

    public function handle(): int
    {
        $table = TransliterationSchemes::exportTable();
        $json = json_encode($table, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $target = $this->option('out') ?: base_path('../packages/search/gen/transliteration.v1.json');

        $dir = dirname($target);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($target, $json . "\n");

        $this->info("Exported transliteration schema v{$table['schema_version']} to {$target}");

        return 0;
    }
}

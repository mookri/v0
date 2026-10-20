<?php

namespace App\Platform\Modules\Console;

use App\Platform\Modules\ModuleRegistry;
use Illuminate\Console\Command;

/**
 * `php artisan module:list` — prints code, version, phase, status and
 * dependencies for operations (doc 01 §2, doc 15 M0).
 */
final class ModuleListCommand extends Command
{
    protected $signature = 'module:list {--json : Emit machine-readable JSON}';

    protected $description = 'List registered modules (code, version, phase, status, dependencies)';

    public function handle(ModuleRegistry $registry): int
    {
        $modules = $registry->modules();

        if ($this->option('json')) {
            $this->line((string) json_encode(array_map(
                static fn ($module): array => [
                    'code' => $module->code,
                    'name' => $module->name,
                    'version' => $module->version,
                    'phase' => $module->phase,
                    'priority' => $module->priority,
                    'status' => $registry->enabled($module) ? 'enabled' : 'disabled',
                    'feature_flag' => $module->featureFlag,
                    'dependencies' => $module->dependencies,
                ],
                $modules,
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($modules as $module) {
            $rows[] = [
                $module->code,
                $module->version,
                (string) $module->phase,
                $registry->enabled($module) ? 'enabled' : 'disabled',
                $module->dependencies === [] ? '-' : implode(', ', $module->dependencies),
            ];
        }

        $this->table(['Code', 'Version', 'Phase', 'Status', 'Dependencies'], $rows);
        $this->newLine();
        $this->info(sprintf(
            '%d module(s) registered (%d permissions, %d events).',
            count($modules),
            count($registry->permissions()),
            count($registry->events()),
        ));

        return self::SUCCESS;
    }
}

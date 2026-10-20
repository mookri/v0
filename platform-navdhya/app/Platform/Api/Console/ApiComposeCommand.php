<?php

namespace App\Platform\Api\Console;

use App\Platform\Api\OpenApiComposer;
use App\Platform\Modules\ModuleRegistry;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * `php artisan api:compose [--check]` — merge kernel components + module
 * fragments into docs/api/openapi.v1.yaml (api-platform §9.1). Fails on
 * path collisions, duplicate operationIds, and undeclared x-permission
 * codes. --check validates without writing (CI gate).
 */
final class ApiComposeCommand extends Command
{
    protected $signature = 'api:compose {--check : Validate without writing}';

    protected $description = 'Compose module OpenAPI fragments into docs/api/openapi.v1.yaml';

    public function handle(ModuleRegistry $registry): int
    {
        $root = base_path();
        $kernelPath = $root.'/docs/api/components/kernel.yaml';
        if (! is_file($kernelPath)) {
            $this->error('missing kernel components: docs/api/components/kernel.yaml');

            return self::FAILURE;
        }

        $fragments = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root.'/app', \FilesystemIterator::SKIP_DOTS)
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/^openapi.*\.yaml$/', $file->getFilename()) && str_contains($file->getPath(), DIRECTORY_SEPARATOR.'Http')) {
                $fragments[] = ['source' => $file->getPathname(), 'document' => OpenApiComposer::parseFile($file->getPathname())];
            }
        }

        $declared = array_keys($registry->permissions());
        $result = OpenApiComposer::compose(OpenApiComposer::parseFile($kernelPath), $fragments, $declared);

        if ($result['errors'] !== []) {
            foreach ($result['errors'] as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $out = $root.'/docs/api/openapi.v1.yaml';
        if ($this->option('check')) {
            $this->info('compose OK: '.count($fragments).' fragments, '.count($result['document']['paths']).' paths');
            if (is_file($out)) {
                $current = OpenApiComposer::parseFile($out);
                if ($current !== $result['document']) {
                    $this->error('composed artifact is stale — run api:compose without --check');

                    return self::FAILURE;
                }
            }

            return self::SUCCESS;
        }

        file_put_contents($out, Yaml::dump($result['document'], 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $this->info('wrote '.$out.' ('.count($fragments).' fragments, '.count($result['document']['paths']).' paths)');

        return self::SUCCESS;
    }
}

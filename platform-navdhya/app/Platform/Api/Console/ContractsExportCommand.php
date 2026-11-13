<?php

namespace App\Platform\Api\Console;

use App\Platform\Api\OpenApiComposer;
use Illuminate\Console\Command;

/**
 * `php artisan contracts:export [--check]` — exports authoritative backend
 * domain contracts (BookingLifecycle state machine + OpenAPI v1 operation index)
 * to `packages/contracts/src/generated/` so TypeScript apps and packages never
 * maintain parallel hand-coded copies (E-AP-01 / T-AP01-02 / A-14).
 */
final class ContractsExportCommand extends Command
{
    protected $signature = 'contracts:export {--out= : Target generated directory (defaults to ../packages/contracts/src/generated)} {--check : Fail if checked-in generated files are stale}';

    protected $description = 'Export backend BookingLifecycle and OpenAPI schemas into packages/contracts/src/generated';

    public function handle(): int
    {
        $root = base_path();
        $outDir = rtrim((string) ($this->option('out') ?: $root.'/../packages/contracts/src/generated'), '/\\');

        if (! app()->bound('navdhya.contracts.lifecycle_snapshot')) {
            $this->error('Lifecycle snapshot binding [navdhya.contracts.lifecycle_snapshot] is not registered.');

            return self::FAILURE;
        }

        /** @var array{source: string, statuses: list<string>, transitions: array<string, list<string>>, cancellable: list<string>, reschedulable: list<string>, sections: array<string, list<string>>, sectionsList: list<string>, terminal: list<string>} $lifecyclePayload */
        $lifecyclePayload = app('navdhya.contracts.lifecycle_snapshot');

        $lifecycleJson = json_encode($lifecyclePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
        $lifecycleTs = $this->renderBookingLifecycleTs($lifecyclePayload);
        $openApiTs = $this->renderOpenApiOperationsTs($root);

        $targets = [
            $outDir.'/booking-lifecycle.json' => $lifecycleJson,
            $outDir.'/booking-lifecycle.ts' => $lifecycleTs,
            $outDir.'/openapi-operations.ts' => $openApiTs,
        ];

        if ($this->option('check')) {
            $stale = [];
            foreach ($targets as $path => $expected) {
                $actual = is_file($path) ? file_get_contents($path) : null;
                if ($actual !== $expected) {
                    $stale[] = $path;
                }
            }
            if ($stale !== []) {
                foreach ($stale as $path) {
                    $this->error('Stale generated contract: '.$path);
                }

                return self::FAILURE;
            }
            $this->info('Generated contracts are up to date.');

            return self::SUCCESS;
        }

        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        foreach ($targets as $path => $contents) {
            file_put_contents($path, $contents);
        }

        $this->info('Exported contracts to '.$outDir);

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderBookingLifecycleTs(array $payload): string
    {
        $statusesLiteral = implode(",\n  ", array_map(static fn (string $s): string => "'{$s}'", $payload['statuses']));
        $transitionsJson = json_encode($payload['transitions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $cancellableLiteral = implode(', ', array_map(static fn (string $s): string => "'{$s}'", $payload['cancellable']));
        $reschedulableLiteral = implode(', ', array_map(static fn (string $s): string => "'{$s}'", $payload['reschedulable']));
        $sectionsJson = json_encode($payload['sections'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $sectionsListLiteral = implode(', ', array_map(static fn (string $s): string => "'{$s}'", $payload['sectionsList']));
        $terminalLiteral = implode(', ', array_map(static fn (string $s): string => "'{$s}'", $payload['terminal']));

        $sectionByStatus = [];
        foreach ($payload['sections'] as $section => $list) {
            foreach ($list as $status) {
                $sectionByStatus[$status] = $section;
            }
        }
        $sectionByStatusJson = json_encode($sectionByStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<TS
// AUTO-GENERATED from {$payload['source']} via `php artisan contracts:export`.
// Do NOT hand-edit. Run `php artisan contracts:export` after editing BookingLifecycle.php.

export const BOOKING_STATUSES = [
  {$statusesLiteral},
] as const;

export type BookingStatus = (typeof BOOKING_STATUSES)[number];

export const BOOKING_SECTIONS_LIST = [{$sectionsListLiteral}] as const;

export type BookingSection = (typeof BOOKING_SECTIONS_LIST)[number];

export const BOOKING_LEGAL_TRANSITIONS: Record<BookingStatus, readonly BookingStatus[]> = {$transitionsJson} as const;

export const BOOKING_CANCELLABLE_STATUSES: readonly BookingStatus[] = [{$cancellableLiteral}] as const;

export const BOOKING_RESCHEDULABLE_STATUSES: readonly BookingStatus[] = [{$reschedulableLiteral}] as const;

export const BOOKING_SECTIONS: Record<BookingSection, readonly BookingStatus[]> = {$sectionsJson} as const;

export const BOOKING_TERMINAL_STATUSES: readonly BookingStatus[] = [{$terminalLiteral}] as const;

export const BOOKING_STATUS_SECTION_MAP: Record<BookingStatus, BookingSection> = {$sectionByStatusJson} as const;
TS."\n";
    }

    private function renderOpenApiOperationsTs(string $root): string
    {
        $specPath = $root.'/docs/api/openapi.v1.yaml';
        $operations = [];
        if (is_file($specPath)) {
            $doc = OpenApiComposer::parseFile($specPath);
            $paths = is_array($doc['paths'] ?? null) ? $doc['paths'] : [];
            foreach ($paths as $path => $methods) {
                if (! is_array($methods)) {
                    continue;
                }
                foreach ($methods as $method => $op) {
                    if (! is_array($op) || ! isset($op['operationId'])) {
                        continue;
                    }
                    $operations[(string) $op['operationId']] = [
                        'method' => strtoupper((string) $method),
                        'path' => (string) $path,
                        'permission' => isset($op['x-permission']) ? (string) $op['x-permission'] : null,
                        'gate' => isset($op['x-gate']) ? (string) $op['x-gate'] : null,
                    ];
                }
            }
            ksort($operations);
        }

        $encoded = json_encode($operations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<TS
// AUTO-GENERATED from platform-navdhya/docs/api/openapi.v1.yaml via `php artisan contracts:export`.
// Do NOT hand-edit.

export interface GeneratedOpenApiOperation {
  readonly method: string;
  readonly path: string;
  readonly permission: string | null;
  readonly gate: string | null;
}

export const OPENAPI_OPERATIONS = {$encoded} as const satisfies Record<string, GeneratedOpenApiOperation>;

export type OpenApiOperationId = keyof typeof OPENAPI_OPERATIONS;
TS."\n";
    }
}


<?php

namespace App\Domain\Verification\Console;

use App\Domain\Verification\Evidence\EvidenceIntegrityVerifier;
use App\Platform\Audit\AuditLog;
use App\Platform\Audit\AuditWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verifies per-tenant platform audit_logs prev_hash -> sha256 chains AND
 * stored onboarding document SHA-256 integrity (SPEC §5, §18 T-TR01-02 / Closes T-04).
 */
final class VerifyAuditAndEvidenceChainCommand extends Command
{
    protected $signature = 'verification:integrity
        {--tenant= : Optional single tenant ULID to inspect}
        {--skip-documents : Only verify audit_logs hash chains}';

    protected $description = 'Verify platform audit_logs per-tenant hash chains and application_documents SHA-256 digests';

    public function __construct(private readonly EvidenceIntegrityVerifier $evidenceVerifier)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');
        $auditChecked = 0;

        $tenantIdsQuery = AuditLog::query()->withoutGlobalScopes()->select('tenant_id')->distinct();
        if (is_string($tenantFilter) && $tenantFilter !== '') {
            $tenantIdsQuery->where('tenant_id', $tenantFilter);
        }

        foreach ($tenantIdsQuery->pluck('tenant_id') as $tenantId) {
            $prevHash = null;

            $rows = AuditLog::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', (string) $tenantId)
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $storedPrev = $row->prev_hash;
                if ($storedPrev !== $prevHash) {
                    $this->error("Audit chain broken for tenant {$tenantId} at {$row->id} ({$row->action}): prev_hash discontinuity.");

                    return self::FAILURE;
                }

                $recomputed = AuditWriter::computeHash([
                    'tenant_id' => (string) $row->tenant_id,
                    'actor' => $row->actor,
                    'action' => (string) $row->action,
                    'resource' => $row->resource,
                    'before' => $row->before,
                    'after' => $row->after,
                    'request_id' => $row->request_id,
                    'prev_hash' => $row->prev_hash,
                    'occurred_at' => $row->occurred_at?->utc()->startOfSecond()->toISOString(),
                ]);

                if (! hash_equals($recomputed, (string) $row->hash)) {
                    $this->error("Audit chain broken for tenant {$tenantId} at {$row->id} ({$row->action}): seal mismatch.");

                    return self::FAILURE;
                }

                $prevHash = (string) $row->hash;
                $auditChecked++;
            }
        }

        $docsChecked = 0;
        if (! $this->option('skip-documents')) {
            $docQuery = DB::connection('tenant_shared')
                ->table('application_documents')
                ->orderBy('id');
            if (is_string($tenantFilter) && $tenantFilter !== '') {
                $docQuery->where('tenant_id', $tenantFilter);
            }

            foreach ($docQuery->get() as $doc) {
                $result = $this->evidenceVerifier->verifyRaw((string) $doc->object_key, (string) ($doc->sha256 ?? ''));
                DB::connection('tenant_shared')
                    ->table('application_documents')
                    ->where('id', $doc->id)
                    ->update([
                        'integrity_state' => $result['state'],
                        'integrity_last_checked_at' => now(),
                    ]);

                if (! $result['ok']) {
                    $this->error("Document integrity failure for tenant {$doc->tenant_id} at {$doc->id}: {$result['reason']}.");

                    return self::FAILURE;
                }
                $docsChecked++;
            }
        }

        $this->info("Integrity verified: {$auditChecked} audit rows, {$docsChecked} documents.");

        return self::SUCCESS;
    }
}

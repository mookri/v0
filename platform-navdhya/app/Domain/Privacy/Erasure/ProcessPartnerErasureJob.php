<?php

namespace App\Domain\Privacy\Erasure;

use App\Domain\Privacy\Models\PrivacySubjectRequest;
use App\Platform\Audit\AuditWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes partner PII blobs and anonymizes personal draft fields while
 * preserving immutable statutory audit hashes and claim ledger integrity (T-TR06-03, Closes T-17).
 */
final class ProcessPartnerErasureJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $subjectIdentityId,
        public readonly string $requestId,
        public readonly string $actorId = 'system:privacy-erasure',
    ) {
    }

    public function handle(AuditWriter $audit): void
    {
        $disk = Storage::disk((string) config('filesystems.default', 'local'));

        $appIds = DB::connection('tenant_shared')
            ->table('partner_applications')
            ->where('tenant_id', $this->tenantId)
            ->where('identity_id', $this->subjectIdentityId)
            ->pluck('id')
            ->all();

        $blobsDeleted = 0;

        if ($appIds !== []) {
            $docs = DB::connection('tenant_shared')
                ->table('application_documents')
                ->where('tenant_id', $this->tenantId)
                ->whereIn('application_id', $appIds)
                ->get(['id', 'object_key']);

            foreach ($docs as $doc) {
                $key = (string) ($doc->object_key ?? '');
                if ($key !== '' && $key !== '[ERASED]' && $disk->exists($key)) {
                    $disk->delete($key);
                    $blobsDeleted++;
                }

                DB::connection('tenant_shared')
                    ->table('application_documents')
                    ->where('id', $doc->id)
                    ->update([
                        'object_key' => '[ERASED]',
                        'quarantine_status' => 'erased',
                    ]);
            }

            DB::connection('tenant_shared')
                ->table('partner_applications')
                ->where('tenant_id', $this->tenantId)
                ->whereIn('id', $appIds)
                ->update([
                    'evidence' => json_encode(['_erased' => true, 'erased_at' => now()->utc()->toISOString()]),
                ]);
        }

        PrivacySubjectRequest::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->where('id', $this->requestId)
            ->update([
                'status' => 'completed',
                'completed_at' => now()->utc(),
                'handled_by' => $this->actorId,
            ]);

        $audit->record(
            'privacy.erasure.completed',
            ['module' => 'privacy', 'resource_type' => 'privacy_subject_request', 'resource_id' => $this->requestId],
            null,
            [
                'subject_identity_id' => $this->subjectIdentityId,
                'applications_anonymized' => count($appIds),
                'blobs_deleted' => $blobsDeleted,
            ],
        );
    }
}


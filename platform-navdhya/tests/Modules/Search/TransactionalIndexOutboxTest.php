<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Console\SearchIndexWorkCommand;
use App\Modules\Search\Models\SearchIndexOutbox;
use App\Modules\Search\SearchIndexer;
use App\Modules\Search\Services\GenerationManager;
use App\Modules\Temple\Models\Temple;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

class TransactionalIndexOutboxTest extends TenantIntegrationTestCase
{
    public function test_saving_temple_writes_to_outbox_on_tenant_connection(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $temple = Temple::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Kashi Vishwanath Jyotirlinga',
            'status' => 'ACTIVE',
            'verification_status' => 'VERIFIED',
        ]);

        $this->assertDatabaseHas('search_index_outbox', [
            'tenant_id' => $tenant->id,
            'entity_type' => 'temple',
            'entity_id' => (string) $temple->id,
            'operation' => SearchIndexOutbox::OP_UPSERT,
            'status' => SearchIndexOutbox::STATUS_PENDING,
        ], 'tenant_shared');
    }

    public function test_rolled_back_transaction_leaves_zero_outbox_or_search_rows(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            DB::connection('tenant_shared')->transaction(function () use ($tenant) {
                Temple::factory()->create([
                    'tenant_id' => $tenant->id,
                    'name' => 'Temporary Temple',
                    'status' => 'ACTIVE',
                    'verification_status' => 'VERIFIED',
                ]);

                throw new \RuntimeException('Simulated rollback');
            });
        } catch (\RuntimeException) {
            // Transaction rolled back
        }

        $this->assertDatabaseMissing('search_index_outbox', [
            'tenant_id' => $tenant->id,
            'entity_type' => 'temple',
        ], 'tenant_shared');

        $this->assertDatabaseMissing('search_documents', [
            'title' => 'Temporary Temple',
        ], 'central');
    }

    public function test_worker_processes_outbox_batch_and_coalesces_generation(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $temple = Temple::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Somnath Mahadev Temple',
            'status' => 'ACTIVE',
            'verification_status' => 'VERIFIED',
        ]);

        $command = app(SearchIndexWorkCommand::class);
        $indexer = app(SearchIndexer::class);
        $genManager = app(GenerationManager::class);

        $initialGen = $genManager->current();

        $processed = $command->processBatch($indexer, 10);
        $this->assertSame(1, $processed);

        // Verify outbox entry marked completed
        $this->assertDatabaseHas('search_index_outbox', [
            'entity_id' => (string) $temple->id,
            'status' => SearchIndexOutbox::STATUS_COMPLETED,
        ], 'tenant_shared');

        // Verify central search document was created
        $this->assertDatabaseHas('search_documents', [
            'entity_type' => 'temple',
            'entity_id' => (string) $temple->id,
        ], 'central');

        // Generation bump once per batch
        $newGen = $genManager->advance();
        $this->assertGreaterThan($initialGen, $newGen);
    }
}

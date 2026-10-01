<?php

namespace Tests\Architecture;

use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * Hottest reads must not full-scan (process gate): EXPLAIN each canonical
 * query shape per driver (SQLite QUERY PLAN / MySQL type) and fail on
 * table scans. New hot queries get added here, not reviewed by hope.
 */
final class QueryPlanTest extends TenantIntegrationTestCase
{
    /**
     * @return list<string> plan lines for the query.
     */
    private function plan(string $connection, string $sql, array $bindings = []): array
    {
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'sqlite') {
            $rows = DB::connection($connection)->select("EXPLAIN QUERY PLAN {$sql}", $bindings);

            return array_map(static fn ($row): string => (string) $row->detail, $rows);
        }

        $rows = DB::connection($connection)->select("EXPLAIN {$sql}", $bindings);

        return array_map(static fn ($row): string => (string) ($row->type ?? ''), $rows);
    }

    private function assertIndexed(array $plan, string $query): void
    {
        foreach ($plan as $line) {
            $this->assertDoesNotMatchRegularExpression(
                '/^(SCAN\s|ALL$)/i',
                trim($line),
                "Full scan detected for {$query}: {$line}"
            );
        }
    }

    public function test_hottest_reads_use_indexes(): void
    {
        $conn = 'tenant_shared';
        $tenant = '01J0000000000000000000001';

        $this->assertIndexed(
            $this->plan($conn, 'select * from partner_applications where tenant_id = ? and status = ?', [$tenant, 'UNDER_REVIEW']),
            'review queue scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from partner_applications where tenant_id = ? and identity_id = ?', [$tenant, 'u1']),
            'applicant applications scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from application_documents where tenant_id = ? and application_id = ?', [$tenant, 'app_1']),
            'documents by application scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from catalog_items where tenant_id = ? and status = ?', [$tenant, 'ACTIVE']),
            'catalog index scan'
        );
        $this->assertIndexed(
            $this->plan('central', 'select * from membership_role_assignments where membership_id = ?', ['m1']),
            'assignment lookup scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from customer_addresses where tenant_id = ? and user_id = ?', [$tenant, '1']),
            'address book scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from saved_items where tenant_id = ? and user_id = ?', [$tenant, '1']),
            'saved items scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from customer_notifications where tenant_id = ? and user_id = ?', [$tenant, '1']),
            'notifications scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from customer_settings where tenant_id = ? and user_id = ?', [$tenant, '1']),
            'settings scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from bookings where tenant_id = ? and user_id = ?', [$tenant, '1']),
            'reservations by customer scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from booking_transitions where tenant_id = ? and booking_id = ?', [$tenant, 'b1']),
            'transitions by booking scan'
        );
        $this->assertIndexed(
            $this->plan($conn, 'select * from booking_reviews where tenant_id = ? and user_id = ?', [$tenant, '1']),
            'reviews by customer scan'
        );
    }
}

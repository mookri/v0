<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-warehouse inventory, kit BOM, append-only ledger, and shipment lifecycle (D1).
 */
return new class () extends Migration {
    public function up(): void
    {
        // 1. Vendor Warehouses
        Schema::connection('tenant_shared')->create('vendor_warehouses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('vendor_id');
            $table->string('code', 32);
            $table->string('name', 128);
            $table->string('city', 100);
            $table->string('state', 100);
            $table->json('address_json')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->unique(['tenant_id', 'vendor_id', 'code']);
            $table->index(['tenant_id', 'vendor_id']);
        });

        // 2. Expand Vendor Inventories with location, reserved counter and optimistic version
        Schema::connection('tenant_shared')->table('vendor_inventories', function (Blueprint $table): void {
            $table->ulid('location_id')->nullable()->after('sku');
            $table->unsignedInteger('reserved')->default(0)->after('on_hand');
            $table->unsignedInteger('version')->default(1)->after('reserved');

            $table->dropUnique(['tenant_id', 'vendor_id', 'sku']);
            $table->unique(['tenant_id', 'vendor_id', 'sku', 'location_id'], 'vendor_inventories_location_unique');
        });

        // 3. Append-only Inventory Movements Ledger
        Schema::connection('tenant_shared')->create('inventory_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('sku', 64);
            $table->ulid('location_id')->nullable();
            $table->string('movement_type', 32); // ADJUST, HOLD, RELEASE, COMMIT, CONSUME, TRANSFER_IN, TRANSFER_OUT
            $table->bigInteger('delta_signed');
            $table->bigInteger('balance_after');
            $table->string('reference_type', 64)->nullable(); // e.g. booking, order, manual
            $table->string('reference_id', 64)->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sku', 'location_id']);
            $table->index(['tenant_id', 'reference_type', 'reference_id']);
        });

        // 4. Kit Components (Bill of Materials)
        Schema::connection('tenant_shared')->create('vendor_kit_components', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('kit_sku', 64);
            $table->string('component_sku', 64);
            $table->ulid('vendor_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->unique(['tenant_id', 'kit_sku', 'component_sku'], 'vendor_kit_components_unique');
            $table->index(['tenant_id', 'vendor_id', 'kit_sku']);
        });

        // 5. Shipments and Shipment Events
        Schema::connection('tenant_shared')->create('shipments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('order_id');
            $table->string('status', 32); // PACKED, HANDOFF, IN_TRANSIT, DELIVERED, RETURNED
            $table->string('carrier_name', 64)->nullable();
            $table->string('tracking_code', 100)->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('pod_note', 500)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('vendor_orders')->onDelete('cascade');
            $table->index(['tenant_id', 'order_id']);
        });

        Schema::connection('tenant_shared')->create('shipment_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('shipment_id');
            $table->string('status', 32);
            $table->string('note', 500)->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->timestamps();

            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
            $table->index(['tenant_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant_shared')->dropIfExists('shipment_events');
        Schema::connection('tenant_shared')->dropIfExists('shipments');
        Schema::connection('tenant_shared')->dropIfExists('vendor_kit_components');
        Schema::connection('tenant_shared')->dropIfExists('inventory_movements');

        Schema::connection('tenant_shared')->table('vendor_inventories', function (Blueprint $table): void {
            $table->dropUnique('vendor_inventories_location_unique');
            $table->unique(['tenant_id', 'vendor_id', 'sku']);

            $table->dropColumn(['location_id', 'reserved', 'version']);
        });

        Schema::connection('tenant_shared')->dropIfExists('vendor_warehouses');
    }
};

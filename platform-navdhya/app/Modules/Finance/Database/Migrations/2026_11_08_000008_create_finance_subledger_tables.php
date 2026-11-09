<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $conn = 'tenant_shared';

        // 1. Finance Sequences (Gapless Sequential Numbering)
        Schema::connection($conn)->create('finance_sequences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('sequence_key', 64);
            $table->string('prefix', 16);
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['tenant_id', 'sequence_key'], 'uq_finance_sequences_key');
        });

        // 2. Finance Documents (Invoices, Credit Notes, Receipts)
        Schema::connection($conn)->create('finance_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('document_number', 64);
            $table->string('document_type', 32); // INVOICE, CREDIT_NOTE, DEBIT_NOTE, RECEIPT
            $table->string('booking_id', 26)->nullable();
            $table->ulid('payment_operation_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->char('currency', 3);
            $table->bigInteger('subtotal_minor');
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor');
            $table->string('status', 32)->default('ISSUED'); // ISSUED, PAID, CANCELLED, VOID
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'document_number'], 'uq_finance_docs_num');
            $table->index(['tenant_id', 'booking_id'], 'idx_finance_docs_booking');
            $table->index(['tenant_id', 'status'], 'idx_finance_docs_status');
        });

        // 3. Finance Document Lines
        Schema::connection($conn)->create('finance_document_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('finance_document_id');
            $table->unsignedInteger('line_number')->default(1);
            $table->string('sku', 64)->nullable();
            $table->string('description', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_price_minor');
            $table->unsignedInteger('tax_rate_bps')->default(0);
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor');
            $table->timestamps();

            $table->index(['tenant_id', 'finance_document_id'], 'idx_finance_doc_lines_doc');
        });

        // 4. Ledger Journals (Double-Entry Subledger)
        Schema::connection($conn)->create('ledger_journals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('journal_number', 64);
            $table->string('reference_type', 64); // BOOKING, PAYMENT, SETTLEMENT, ADJUSTMENT
            $table->string('reference_id', 64);
            $table->string('description', 255);
            $table->timestamp('posted_at');
            $table->string('status', 32)->default('POSTED');
            $table->timestamps();

            $table->unique(['tenant_id', 'journal_number'], 'uq_ledger_journals_num');
            $table->index(['tenant_id', 'reference_type', 'reference_id'], 'idx_ledger_journals_ref');
        });

        // 5. Ledger Entries
        Schema::connection($conn)->create('ledger_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->ulid('journal_id');
            $table->string('account_code', 64); // 1000 Cash, 2000 AP, 2100 Tax, 4000 Revenue, etc.
            $table->string('entry_direction', 8); // DEBIT, CREDIT
            $table->bigInteger('amount_minor'); // positive unsigned value
            $table->char('currency', 3);
            $table->timestamps();

            $table->index(['tenant_id', 'journal_id'], 'idx_ledger_entries_journal');
            $table->index(['tenant_id', 'account_code'], 'idx_ledger_entries_account');
        });

        // 6. Settlement Obligations
        Schema::connection($conn)->create('settlement_obligations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('beneficiary_type', 32); // PANDIT, TEMPLE, VENDOR, PLATFORM
            $table->string('beneficiary_id', 64);
            $table->string('booking_id', 26)->nullable();
            $table->bigInteger('gross_amount_minor');
            $table->bigInteger('commission_minor')->default(0);
            $table->bigInteger('tax_withheld_minor')->default(0);
            $table->bigInteger('net_payable_minor');
            $table->char('currency', 3);
            $table->string('status', 32)->default('PENDING'); // PENDING, APPROVED, PAID, CANCELLED
            $table->timestamps();

            $table->index(['tenant_id', 'beneficiary_type', 'beneficiary_id', 'status'], 'idx_settle_obligations_beneficiary');
            $table->index(['tenant_id', 'booking_id'], 'idx_settle_obligations_booking');
        });

        // 7. Payouts (Maker/Checker Dual Custody)
        Schema::connection($conn)->create('payouts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('payout_batch_id', 64)->nullable();
            $table->string('beneficiary_id', 64);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 32)->default('PENDING_APPROVAL'); // PENDING_APPROVAL, APPROVED, PROCESSING, SUCCEEDED, FAILED
            $table->string('maker_id', 64);
            $table->string('checker_id', 64)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('payout_provider', 32)->nullable();
            $table->string('payout_reference', 64)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_payouts_status');
            $table->index(['tenant_id', 'beneficiary_id'], 'idx_payouts_beneficiary');
        });
    }

    public function down(): void
    {
        $conn = 'tenant_shared';
        Schema::connection($conn)->dropIfExists('payouts');
        Schema::connection($conn)->dropIfExists('settlement_obligations');
        Schema::connection($conn)->dropIfExists('ledger_entries');
        Schema::connection($conn)->dropIfExists('ledger_journals');
        Schema::connection($conn)->dropIfExists('finance_document_lines');
        Schema::connection($conn)->dropIfExists('finance_documents');
        Schema::connection($conn)->dropIfExists('finance_sequences');
    }
};

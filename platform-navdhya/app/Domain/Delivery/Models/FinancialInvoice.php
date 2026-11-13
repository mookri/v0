<?php

namespace App\Domain\Delivery\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable financial tax invoice model (E-CM-07).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $invoice_number
 * @property string $booking_id
 * @property int $user_id
 * @property string $fy_year
 * @property int $sequence_number
 * @property string $supplier_model
 * @property string $supplier_gstin
 * @property string|null $recipient_gstin
 * @property string $place_of_supply
 * @property string $sac_code
 * @property int $taxable_amount_cents
 * @property int $cgst_cents
 * @property int $sgst_cents
 * @property int $igst_cents
 * @property int $total_amount_cents
 * @property array<string, mixed> $snapshot_json
 * @property string $status
 * @property \Carbon\CarbonInterface $issued_at
 */
final class FinancialInvoice extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const MODEL_FACILITATOR = 'facilitator';
    public const MODEL_DIRECT_TEMPLE = 'direct_temple';
    public const MODEL_DIRECT_PANDIT = 'direct_pandit';

    protected $connection = 'tenant_shared';

    protected $table = 'financial_invoices';

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'booking_id',
        'user_id',
        'fy_year',
        'sequence_number',
        'supplier_model',
        'supplier_gstin',
        'recipient_gstin',
        'place_of_supply',
        'sac_code',
        'taxable_amount_cents',
        'cgst_cents',
        'sgst_cents',
        'igst_cents',
        'total_amount_cents',
        'snapshot_json',
        'status',
        'issued_at',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'taxable_amount_cents' => 'integer',
        'cgst_cents' => 'integer',
        'sgst_cents' => 'integer',
        'igst_cents' => 'integer',
        'total_amount_cents' => 'integer',
        'sequence_number' => 'integer',
        'issued_at' => 'datetime',
    ];
}


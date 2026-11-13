<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypted & masked bank instrument record (§8.2 / T-TR05-05, Closes T-10, T-11).
 */
final class VerificationBankInstrument extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_bank_instruments';

    protected $guarded = [];

    protected $casts = [
        'name_match_score' => 'float',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'cooling_off_until' => 'datetime',
        'discrepancy_frozen_at' => 'datetime',
    ];

    protected $hidden = [
        'account_number_encrypted',
        'ifsc_encrypted',
        'account_fingerprint',
    ];

    /**
     * Masked API serializer: never reveals full account number or IFSC suffix (§8.2).
     *
     * @return array<string, mixed>
     */
    public function toMaskedArray(): array
    {
        return [
            'id' => (string) $this->getKey(),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'account_masked' => '••••' . $this->account_last4,
            'ifsc_masked' => $this->ifsc_prefix_masked,
            'account_holder_name' => $this->account_holder_name,
            'account_type' => $this->account_type,
            'ownership_type' => $this->ownership_type,
            'verifier_mode' => $this->verifier_mode,
            'verification_status' => $this->verification_status,
            'name_match_score' => $this->name_match_score,
            'cooling_off_until' => $this->cooling_off_until?->toIso8601String(),
            'discrepancy_frozen' => $this->discrepancy_frozen_at !== null,
            'claim_id' => $this->claim_id,
        ];
    }
}


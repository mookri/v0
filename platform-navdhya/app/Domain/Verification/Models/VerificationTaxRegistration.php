<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationTaxRegistration extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_tax_registrations';

    protected $guarded = [];

    protected $casts = [
        'trade_names_json' => 'array',
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'gstin_encrypted',
        'gstin_hmac',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toMaskedArray(): array
    {
        return [
            'id' => (string) $this->getKey(),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'gst_status' => $this->gst_status,
            'gstin_masked' => $this->gstin_masked,
            'state_code' => $this->state_code,
            'legal_name' => $this->legal_name,
            'registration_status' => $this->registration_status,
            'verifier_mode' => $this->verifier_mode,
            'verification_claim_id' => $this->verification_claim_id,
        ];
    }
}


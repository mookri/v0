<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\BookingReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Booking review row (tenant_shared): one per booking, author-only.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $booking_id
 * @property int $user_id
 * @property int $rating
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class BookingReview extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<BookingReviewFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'booking_id', 'user_id', 'rating', 'comment',
    ];

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }

    /**
     * @return Factory<BookingReview>
     */
    protected static function newFactory(): Factory
    {
        return BookingReviewFactory::new();
    }
}

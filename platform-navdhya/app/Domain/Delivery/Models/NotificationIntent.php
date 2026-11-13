<?php

namespace App\Domain\Delivery\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Pre-channel notification intent model (E-CM-03).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $recipient_ref
 * @property string $subject_type
 * @property string $subject_id
 * @property string $category
 * @property string $priority
 * @property string $locale
 * @property string $dedup_key
 * @property string $policy_version
 * @property string $status
 */
final class NotificationIntent extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'notification_intents';

    protected $fillable = [
        'tenant_id',
        'recipient_ref',
        'subject_type',
        'subject_id',
        'category',
        'priority',
        'locale',
        'dedup_key',
        'policy_version',
        'status',
    ];
}


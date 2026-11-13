<?php

namespace App\Domain\Delivery\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Communication template model (E-CM-04).
 *
 * @property string $id
 * @property string $code
 * @property string $category
 * @property string|null $meta_template_id
 * @property string $approval_status
 * @property string $language
 * @property string $content
 * @property array<string, mixed>|null $variables
 */
final class CommunicationTemplate extends Model
{
    use HasUlids;

    public const CATEGORY_UTILITY = 'UTILITY';
    public const CATEGORY_AUTHENTICATION = 'AUTHENTICATION';
    public const CATEGORY_MARKETING = 'MARKETING';
    public const CATEGORY_SERVICE = 'SERVICE';

    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_PAUSED = 'PAUSED';

    protected $connection = 'tenant_shared';

    protected $table = 'communication_templates';

    protected $fillable = [
        'code',
        'category',
        'meta_template_id',
        'approval_status',
        'language',
        'content',
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
    ];
}


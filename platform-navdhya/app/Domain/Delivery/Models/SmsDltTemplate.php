<?php

namespace App\Domain\Delivery\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * TRAI DLT SMS template model (E-CM-05).
 *
 * @property string $id
 * @property string $template_code
 * @property string $pe_id
 * @property string $header
 * @property string $dlt_template_id
 * @property bool $unicode_flag
 * @property string $content_template
 * @property string $approval_status
 */
final class SmsDltTemplate extends Model
{
    use HasUlids;

    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';

    protected $connection = 'tenant_shared';

    protected $table = 'sms_dlt_templates';

    protected $fillable = [
        'template_code',
        'pe_id',
        'header',
        'dlt_template_id',
        'unicode_flag',
        'content_template',
        'approval_status',
    ];

    protected $casts = [
        'unicode_flag' => 'boolean',
    ];
}


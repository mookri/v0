<?php

namespace App\Domain\Verification\Concerns;

use App\Domain\Verification\Exceptions\DirectVerificationStatusWriteException;
use App\Domain\Verification\Projector\VerificationProjector;
use Illuminate\Database\Eloquent\Model;

/**
 * Runtime write-block protecting projected partner `verification_status` columns.
 * Mutations outside VerificationProjector::withoutWriteBlock() fail loudly.
 */
trait BlocksDirectVerificationStatusWrite
{
    public static function bootBlocksDirectVerificationStatusWrite(): void
    {
        static::updating(static function (Model $model): void {
            if ($model->isDirty('verification_status') && ! VerificationProjector::isProjecting()) {
                $model->setAttribute('verification_status', $model->getRawOriginal('verification_status'));

                throw DirectVerificationStatusWriteException::forModel($model::class);
            }
        });
    }
}

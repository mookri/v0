<?php

namespace App\Modules\Customers;

use App\Modules\Customers\Models\CustomerActivity;

/**
 * Customer activity writer (module root, like the notifier): one-line
 * recording from customers-module mutations, persisted under the ambient
 * tenant context. Kinds are closed (CustomerActivity::KINDS); labels are
 * fixed templates from callers — never user input verbatim.
 */
final class CustomerActivityRecorder
{
    public function record(string $userId, string $kind, string $label): void
    {
        if (! in_array($kind, CustomerActivity::KINDS, true)) {
            throw new \InvalidArgumentException("Unknown activity kind {$kind}.");
        }

        $activity = new CustomerActivity();
        $activity->forceFill([
            'user_id' => $userId,
            'kind' => $kind,
            'label' => mb_substr($label, 0, 280),
        ]);
        $activity->save();
    }
}

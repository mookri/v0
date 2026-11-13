<?php

namespace App\Domain\Delivery\State;

use App\Domain\Delivery\Exceptions\IllegalStateTransitionException;
use App\Modules\Customers\Models\NotificationDelivery;

/**
 * Delivery State Machine (E-CM-02 / §6.1 / §6.2).
 * Enforces legal state transitions, terminal state immutability,
 * and automatic lifecycle timestamp tracking across all channels.
 */
final class DeliveryStateMachine
{
    /**
     * Map of legal source states to allowed destination states.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED = [
        NotificationDelivery::STATUS_CREATED => [
            NotificationDelivery::STATUS_POLICY_CHECK,
            NotificationDelivery::STATUS_CANCELLED,
        ],
        NotificationDelivery::STATUS_POLICY_CHECK => [
            NotificationDelivery::STATUS_QUEUED,
            NotificationDelivery::STATUS_SUPPRESSED,
            NotificationDelivery::STATUS_POLICY_BLOCKED,
            NotificationDelivery::STATUS_CANCELLED,
        ],
        NotificationDelivery::STATUS_QUEUED => [
            NotificationDelivery::STATUS_SUBMITTED,
            NotificationDelivery::STATUS_CANCELLED,
            NotificationDelivery::STATUS_FAILED,
        ],
        NotificationDelivery::STATUS_SUBMITTED => [
            NotificationDelivery::STATUS_PROVIDER_ACCEPTED,
            NotificationDelivery::STATUS_SENT,
            NotificationDelivery::STATUS_FAILED,
            NotificationDelivery::STATUS_DELIVERY_UNKNOWN,
        ],
        NotificationDelivery::STATUS_PROVIDER_ACCEPTED => [
            NotificationDelivery::STATUS_SENT,
            NotificationDelivery::STATUS_DELIVERED,
            NotificationDelivery::STATUS_READ,
            NotificationDelivery::STATUS_FAILED,
            NotificationDelivery::STATUS_DELIVERY_UNKNOWN,
        ],
        NotificationDelivery::STATUS_SENT => [
            NotificationDelivery::STATUS_DELIVERED,
            NotificationDelivery::STATUS_READ,
            NotificationDelivery::STATUS_FAILED,
            NotificationDelivery::STATUS_DELIVERY_UNKNOWN,
            NotificationDelivery::STATUS_EXPIRED,
        ],
        NotificationDelivery::STATUS_DELIVERED => [
            NotificationDelivery::STATUS_READ,
        ],
        NotificationDelivery::STATUS_DELIVERY_UNKNOWN => [
            NotificationDelivery::STATUS_DELIVERED,
            NotificationDelivery::STATUS_READ,
            NotificationDelivery::STATUS_FAILED,
            NotificationDelivery::STATUS_EXPIRED,
        ],

        // Legacy states
        'pending' => [
            NotificationDelivery::STATUS_SENT,
            NotificationDelivery::STATUS_DELIVERED,
            NotificationDelivery::STATUS_READ,
            NotificationDelivery::STATUS_FAILED,
            NotificationDelivery::STATUS_SUPPRESSED,
            'sent',
            'delivered',
            'read',
            'failed',
            'suppressed',
        ],
        'sent' => [
            NotificationDelivery::STATUS_DELIVERED,
            NotificationDelivery::STATUS_READ,
            NotificationDelivery::STATUS_FAILED,
            NotificationDelivery::STATUS_DELIVERY_UNKNOWN,
            NotificationDelivery::STATUS_EXPIRED,
            'delivered',
            'read',
            'failed',
        ],
        'delivered' => [
            NotificationDelivery::STATUS_READ,
            'read',
        ],
    ];

    public static function normalize(string $state): string
    {
        $upper = strtoupper(trim($state));

        return match ($upper) {
            'PENDING' => 'pending',
            'QUEUED' => NotificationDelivery::STATUS_QUEUED,
            'SENT' => NotificationDelivery::STATUS_SENT,
            'DELIVERED' => NotificationDelivery::STATUS_DELIVERED,
            'READ' => NotificationDelivery::STATUS_READ,
            'FAILED', 'REJECTED' => NotificationDelivery::STATUS_FAILED,
            'SUPPRESSED' => NotificationDelivery::STATUS_SUPPRESSED,
            'PROVIDER_ACCEPTED' => NotificationDelivery::STATUS_PROVIDER_ACCEPTED,
            'DELIVERY_UNKNOWN' => NotificationDelivery::STATUS_DELIVERY_UNKNOWN,
            'EXPIRED' => NotificationDelivery::STATUS_EXPIRED,
            'CANCELLED' => NotificationDelivery::STATUS_CANCELLED,
            'POLICY_BLOCKED' => NotificationDelivery::STATUS_POLICY_BLOCKED,
            'POLICY_CHECK' => NotificationDelivery::STATUS_POLICY_CHECK,
            'CREATED' => NotificationDelivery::STATUS_CREATED,
            'SUBMITTED' => NotificationDelivery::STATUS_SUBMITTED,
            default => $state,
        };
    }

    public function canTransition(string $fromState, string $toState): bool
    {
        $normalizedTo = self::normalize($toState);
        $allowed = self::ALLOWED[$fromState] ?? [];

        return in_array($toState, $allowed, true) || in_array($normalizedTo, $allowed, true);
    }

    public function apply(
        NotificationDelivery $delivery,
        string $targetState,
        ?string $errorCode = null,
        ?int $unitCostCents = null
    ): NotificationDelivery {
        $currentState = $delivery->status;
        $normalizedTarget = self::normalize($targetState);

        // Idempotent transition to the same state
        if ($currentState === $normalizedTarget || $currentState === $targetState) {
            return $delivery;
        }

        if (! $this->canTransition($currentState, $targetState)) {
            throw new IllegalStateTransitionException(
                $currentState,
                $targetState,
                "State [{$currentState}] cannot transition to [{$targetState}]. Terminal or regressive edge rejected."
            );
        }

        $now = now();

        if ($normalizedTarget === NotificationDelivery::STATUS_PROVIDER_ACCEPTED) {
            $delivery->accepted_at ??= $now;
        } elseif ($normalizedTarget === NotificationDelivery::STATUS_SENT) {
            $delivery->sent_at ??= $now;
        } elseif ($normalizedTarget === NotificationDelivery::STATUS_DELIVERED) {
            $delivery->delivered_at ??= $now;
        } elseif ($normalizedTarget === NotificationDelivery::STATUS_READ) {
            $delivery->read_at ??= $now;
            $delivery->delivered_at ??= $now; // READ implies DELIVERED
        } elseif ($normalizedTarget === NotificationDelivery::STATUS_FAILED) {
            $delivery->failed_at ??= $now;
        }

        if ($errorCode !== null) {
            $delivery->error_code = $errorCode;
        }

        if ($currentState === NotificationDelivery::STATUS_DELIVERY_UNKNOWN) {
            $delivery->reconciled_at = $now;
        }

        if ($unitCostCents !== null) {
            $delivery->unit_cost_cents = $unitCostCents;
        }

        // Prefer canonical status unless legacy was requested
        $delivery->status = in_array($targetState, ['sent', 'delivered', 'read', 'failed', 'pending'], true)
            ? $targetState
            : $normalizedTarget;

        $delivery->save();

        return $delivery;
    }
}

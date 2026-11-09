<?php

namespace App\Modules\Customers\Providers;

/**
 * Normalized outbound notification message payload (E4).
 */
final class NotificationMessage
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public readonly int|string $userId,
        public readonly string $recipient, // Phone E.164 or Email address
        public readonly string $category,  // ACCOUNT | BOOKING | REMINDER | MARKETING
        public readonly string $channel,   // SMS | WHATSAPP | EMAIL | IN_APP
        public readonly string $template,  // Template key/name
        public readonly array $variables = [],
        public readonly ?string $subject = null,
        public readonly ?string $body = null,
    ) {
    }
}

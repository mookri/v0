<?php

namespace App\Domain\Delivery\Routing;

/**
 * Authoritative channel routing service based on versioned config (E-CM-03).
 */
final class ChannelRoutingService
{
    /**
     * @param list<string> $availableChannels
     */
    public function selectChannel(string $category, array $availableChannels, bool $quietHoursActive = false): ?string
    {
        $category = strtolower(trim($category));
        $config = config("communication.categories.{$category}");

        if (! is_array($config)) {
            $config = config('communication.categories.transactional');
        }

        if ($quietHoursActive && ! ($config['bypasses_quiet_hours'] ?? false)) {
            // During quiet hours, only in-app is allowed for non-bypass categories
            return in_array('IN_APP', $availableChannels, true) ? 'IN_APP' : null;
        }

        $priority = $config['priority'] ?? ['IN_APP'];

        foreach ($priority as $channel) {
            if (in_array($channel, $availableChannels, true)) {
                return $channel;
            }
        }

        return null;
    }

    public function fallbackChannel(string $category, string $failedChannel): ?string
    {
        $category = strtolower(trim($category));
        $fallbacks = config("communication.categories.{$category}.fallback", []);

        return $fallbacks[$failedChannel] ?? null;
    }
}


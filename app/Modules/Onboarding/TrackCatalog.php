<?php

namespace App\Modules\Onboarding;

/**
 * Track catalog (mirrors the Next.js track definitions v1; the control
 * plane owns these records when track-studio lands — this class is the
 * interim source of truth and the shape it must keep).
 */
final class TrackCatalog
{
    /**
     * @return array<string, array{version: string, sla_hours: int, steps: list<array{id: string, required: bool}>}>
     */
    public static function tracks(): array
    {
        $review = ['id' => 'review', 'required' => false];

        return [
            'pandit' => [
                'version' => 'pandit-v1',
                'sla_hours' => 48,
                'steps' => [
                    ['id' => 'identity', 'required' => true],
                    ['id' => 'credentials', 'required' => true],
                    ['id' => 'services', 'required' => true],
                    ['id' => 'areas', 'required' => true],
                    ['id' => 'availability', 'required' => true],
                    ['id' => 'documents', 'required' => true],
                    ['id' => 'agreement', 'required' => true],
                    $review,
                ],
            ],
            'partner-vendor' => [
                'version' => 'partner-vendor-v1',
                'sla_hours' => 72,
                'steps' => [
                    ['id' => 'identity', 'required' => true],
                    ['id' => 'business', 'required' => true],
                    ['id' => 'services', 'required' => true],
                    ['id' => 'areas', 'required' => true],
                    ['id' => 'capacity', 'required' => true],
                    ['id' => 'documents', 'required' => true],
                    ['id' => 'agreement', 'required' => true],
                    $review,
                ],
            ],
            'partner-individual' => [
                'version' => 'partner-individual-v1',
                'sla_hours' => 72,
                'steps' => [
                    ['id' => 'identity', 'required' => true],
                    ['id' => 'services', 'required' => true],
                    ['id' => 'areas', 'required' => true],
                    ['id' => 'availability', 'required' => false],
                    ['id' => 'documents', 'required' => true],
                    ['id' => 'agreement', 'required' => true],
                    $review,
                ],
            ],
        ];
    }

    /**
     * @return array{version: string, sla_hours: int, steps: list<array{id: string, required: bool}>}|null
     */
    public static function find(string $track, ?string $version = null): ?array
    {
        $def = self::tracks()[$track] ?? null;
        if ($def === null) {
            return null;
        }
        if ($version !== null && $def['version'] !== $version) {
            return null;
        }

        return $def;
    }
}

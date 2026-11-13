<?php

namespace App\Domain\Calendar\Astronomy;

use ArrayAccess;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Value object representing astronomical horizon event calculation (sunrise/sunset).
 *
 * @implements ArrayAccess<string, mixed>
 */
final class HorizonResult implements ArrayAccess
{
    public function __construct(
        public readonly bool $is_risen,
        public readonly ?CarbonImmutable $instant_utc,
        public readonly HorizonStatusEnum $status,
        public readonly ?int $hour = null,
        public readonly ?int $minute = null,
        public readonly ?int $second = null,
        public readonly ?float $fractional_hour = null
    ) {
    }

    public static function normal(
        CarbonImmutable $instantUtc,
        int $hour,
        int $minute,
        int $second,
        float $fractionalHour
    ): self {
        return new self(
            is_risen: true,
            instant_utc: $instantUtc,
            status: HorizonStatusEnum::NORMAL,
            hour: $hour,
            minute: $minute,
            second: $second,
            fractional_hour: $fractionalHour
        );
    }

    public static function midnightSun(): self
    {
        return new self(
            is_risen: false,
            instant_utc: null,
            status: HorizonStatusEnum::MIDNIGHT_SUN
        );
    }

    public static function polarNight(): self
    {
        return new self(
            is_risen: false,
            instant_utc: null,
            status: HorizonStatusEnum::POLAR_NIGHT
        );
    }

    public static function unsupportedLatitude(): self
    {
        return new self(
            is_risen: false,
            instant_utc: null,
            status: HorizonStatusEnum::UNSUPPORTED_LATITUDE
        );
    }

    public function isSupported(): bool
    {
        return $this->status === HorizonStatusEnum::NORMAL;
    }

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, ['hour', 'minute', 'second', 'fractional_hour', 'status', 'is_risen', 'instant_utc'], true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'hour' => $this->hour,
            'minute' => $this->minute,
            'second' => $this->second,
            'fractional_hour' => $this->fractional_hour,
            'status' => $this->status,
            'is_risen' => $this->is_risen,
            'instant_utc' => $this->instant_utc,
            default => throw new InvalidArgumentException("Unknown property: {$offset}"),
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new InvalidArgumentException('HorizonResult is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new InvalidArgumentException('HorizonResult is immutable');
    }
}


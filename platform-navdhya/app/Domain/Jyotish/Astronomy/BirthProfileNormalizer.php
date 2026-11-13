<?php

namespace App\Domain\Jyotish\Astronomy;

use App\Domain\Jyotish\Astronomy\Time\AstronomicalTime;
use App\Domain\Jyotish\Contracts\GeocodingProvider;
use App\Domain\Jyotish\Exceptions\AmbiguousLocalTimeException;
use App\Domain\Jyotish\Exceptions\AmbiguousPlaceException;
use App\Domain\Jyotish\Exceptions\InvalidBirthTimeException;
use App\Domain\Jyotish\Exceptions\InvalidCoordinatesException;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Birth Profile Normalization Engine (T-JY01-01).
 *
 * Normalizes birthplace strings and local civil birth times into validated WGS84 coordinates,
 * historical IANA time offsets, DST gap/fold resolution, and UTC/TT astronomical times.
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class BirthProfileNormalizer
{
    public function __construct(
        private GeocodingProvider $geocoding,
    ) {
    }

    /**
     * @param array{
     *     birth_place?: string,
     *     place_id?: string,
     *     latitude?: float,
     *     longitude?: float,
     *     elevation_m?: int,
     *     iana_timezone?: string,
     *     year: int,
     *     month: int,
     *     day: int,
     *     hour?: int,
     *     minute?: int,
     *     second?: float,
     *     fold_selection?: int|null
     * } $input
     * @return array{
     *     place_id: string|null,
     *     raw_birth_place_text: string,
     *     latitude: float,
     *     longitude: float,
     *     elevation_m: int,
     *     iana_timezone: string,
     *     local_datetime: string,
     *     utc_datetime: string,
     *     astronomical_time: AstronomicalTime,
     *     fold_resolved: int|null
     * }
     */
    public function normalize(array $input): array
    {
        $rawPlaceText = trim((string) ($input['birth_place'] ?? ''));

        // 1. Resolve Location Coordinates
        if (isset($input['latitude'], $input['longitude'])) {
            $lat = (float) $input['latitude'];
            $lon = (float) $input['longitude'];
            $elevation = (int) ($input['elevation_m'] ?? 0);
            $tzName = (string) ($input['iana_timezone'] ?? 'UTC');
            $placeId = $input['place_id'] ?? null;
        } elseif ($rawPlaceText !== '') {
            $candidates = $this->geocoding->resolve($rawPlaceText);

            if (count($candidates) === 0) {
                throw new InvalidArgumentException("Unrecognized birthplace: '{$rawPlaceText}'.");
            }

            if (count($candidates) > 1 && empty($input['place_id'])) {
                throw new AmbiguousPlaceException($rawPlaceText, $candidates);
            }

            $matched = $candidates[0];
            if (! empty($input['place_id'])) {
                foreach ($candidates as $c) {
                    if ($c['place_id'] === $input['place_id']) {
                        $matched = $c;
                        break;
                    }
                }
            }

            $lat = $matched['latitude'];
            $lon = $matched['longitude'];
            $elevation = $matched['elevation_m'];
            $tzName = $matched['iana_timezone'];
            $placeId = $matched['place_id'];
        } else {
            throw new InvalidArgumentException('Either birth_place or latitude/longitude must be provided.');
        }

        // Validate Coordinates
        if ($lat < -90.0 || $lat > 90.0 || $lon < -180.0 || $lon > 180.0) {
            throw new InvalidCoordinatesException($lat, $lon);
        }

        // 2. Validate & Resolve Timezone Transitions (DST)
        $year = (int) $input['year'];
        $month = (int) $input['month'];
        $day = (int) $input['day'];
        $hour = (int) ($input['hour'] ?? 0);
        $minute = (int) ($input['minute'] ?? 0);
        $second = (float) ($input['second'] ?? 0.0);
        $fold = $input['fold_selection'] ?? null;

        $tz = new DateTimeZone($tzName);
        $timeStr = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, (int) $second);

        // Check for DST gaps or folds around the requested date
        $startWindow = (int) (new DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00 UTC', $year, $month, $day)))->format('U') - 86400;
        $endWindow = $startWindow + (3 * 86400);
        $transitions = $tz->getTransitions($startWindow, $endWindow);

        $utcDateTime = null;
        $foldResolved = null;

        if (is_array($transitions)) {
            foreach ($transitions as $i => $tr) {
                if ($i === 0) {
                    continue;
                }
                $prev = $transitions[$i - 1];
                $transTime = $tr['ts'];
                $prevOffset = $prev['offset'];
                $nextOffset = $tr['offset'];

                // Spring forward gap (offset increases)
                if ($nextOffset > $prevOffset) {
                    $gapStart = $transTime + $prevOffset;
                    $gapEnd = $transTime + $nextOffset;
                    $localSec = (new DateTimeImmutable($timeStr . ' UTC'))->getTimestamp();

                    if ($localSec >= $gapStart && $localSec < $gapEnd) {
                        throw new InvalidBirthTimeException($timeStr, $tzName);
                    }
                }

                // Fallback fold (offset decreases: ambiguous local time)
                if ($nextOffset < $prevOffset) {
                    $foldStart = $transTime + $nextOffset;
                    $foldEnd = $transTime + $prevOffset;
                    $localSec = (new DateTimeImmutable($timeStr . ' UTC'))->getTimestamp();

                    if ($localSec >= $foldStart && $localSec < $foldEnd) {
                        if ($fold === null) {
                            throw new AmbiguousLocalTimeException($timeStr, $tzName);
                        }

                        $foldResolved = $fold;
                        $chosenOffset = ($fold === 0) ? $prevOffset : $nextOffset;
                        $utcEpoch = $localSec - $chosenOffset;
                        $utcDateTime = (new DateTimeImmutable('@' . $utcEpoch))->setTimezone(new DateTimeZone('UTC'));
                        break;
                    }
                }
            }
        }

        if ($utcDateTime === null) {
            $localDt = new DateTimeImmutable($timeStr, $tz);
            $utcDateTime = $localDt->setTimezone(new DateTimeZone('UTC'));
        }

        // 3. Compute High-Precision Astronomical Time
        $astroTime = AstronomicalTime::fromUtcDateTime($utcDateTime);

        return [
            'place_id' => $placeId,
            'raw_birth_place_text' => $rawPlaceText,
            'latitude' => $lat,
            'longitude' => $lon,
            'elevation_m' => $elevation,
            'iana_timezone' => $tzName,
            'local_datetime' => $timeStr,
            'utc_datetime' => $utcDateTime->format('Y-m-d\TH:i:s.u\Z'),
            'astronomical_time' => $astroTime,
            'fold_resolved' => $foldResolved,
        ];
    }
}


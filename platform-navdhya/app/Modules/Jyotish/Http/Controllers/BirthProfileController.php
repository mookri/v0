<?php

namespace App\Modules\Jyotish\Http\Controllers;

use App\Domain\Jyotish\Astronomy\BirthProfileNormalizer;
use App\Domain\Jyotish\Exceptions\AmbiguousLocalTimeException;
use App\Domain\Jyotish\Exceptions\AmbiguousPlaceException;
use App\Domain\Jyotish\Exceptions\InvalidBirthTimeException;
use App\Domain\Jyotish\Exceptions\InvalidCoordinatesException;
use App\Domain\Jyotish\Geography\PlaceResolver;
use App\Modules\Jyotish\Http\JyotishProblemResponder;
use App\Modules\Jyotish\Http\Presenters\JyotishResultEnvelope;
use App\Modules\Jyotish\Models\BirthProfile;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;

/**
 * Controller for Devotee Birth Profile CRUD (T-JY01-02).
 * Integrates BirthProfileNormalizer with fail-closed RFC 7807 problem responses.
 */
final class BirthProfileController extends Controller
{
    private BirthProfileNormalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new BirthProfileNormalizer(new PlaceResolver());
    }

    public function index(Request $request): JsonResponse
    {
        $profiles = BirthProfile::query()
            ->latest()
            ->paginate(20);

        return response()->json(
            JyotishResultEnvelope::wrap($profiles->items(), 'birth_profiles_list_v1')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'gender' => ['nullable', 'string', 'in:male,female,other,unspecified'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'place_id' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'elevation_m' => ['nullable', 'integer'],
            'iana_timezone' => ['nullable', 'string', 'max:64'],
            'year' => ['required', 'integer', 'between:1800,2200'],
            'month' => ['required', 'integer', 'between:1,12'],
            'day' => ['required', 'integer', 'between:1,31'],
            'hour' => ['nullable', 'integer', 'between:0,23'],
            'minute' => ['nullable', 'integer', 'between:0,59'],
            'second' => ['nullable', 'numeric', 'between:0,59.999'],
            'fold_selection' => ['nullable', 'integer', 'in:0,1'],
            'accuracy_class' => ['nullable', 'string', 'in:exact,approximate_time,approximate_place'],
            'tradition' => ['nullable', 'string', 'in:AMANTA,PURNIMANTA'],
        ]);

        try {
            $normalized = $this->normalizer->normalize($validated);
        } catch (AmbiguousPlaceException $e) {
            return JyotishProblemResponder::validationFailed('Ambiguous birthplace matches multiple locations', [
                'field' => 'birth_place',
                'code' => 'ambiguous_place',
                'candidates' => $e->candidates,
            ]);
        } catch (AmbiguousLocalTimeException $e) {
            return JyotishProblemResponder::validationFailed('Local birth time falls within ambiguous DST fold', [
                'field' => 'fold_selection',
                'code' => 'ambiguous_local_time',
                'local_time' => $e->localTime,
                'timezone' => $e->timezone,
            ]);
        } catch (InvalidBirthTimeException $e) {
            return JyotishProblemResponder::validationFailed('Non-existent local birth time in DST gap', [
                'field' => 'hour',
                'code' => 'invalid_birth_time',
                'local_time' => $e->localTime,
                'timezone' => $e->timezone,
            ]);
        } catch (InvalidCoordinatesException $e) {
            return JyotishProblemResponder::validationFailed('Coordinates outside valid range', [
                'field' => 'latitude',
                'code' => 'invalid_coordinates',
            ]);
        } catch (InvalidArgumentException $e) {
            return JyotishProblemResponder::validationFailed($e->getMessage());
        }

        $utcAtUs = (int) round(((new DateTimeImmutable($normalized['utc_datetime']))->format('U.u')) * 1_000_000);

        $profile = BirthProfile::create([
            'user_id' => $request->user()?->id,
            'name' => $validated['name'],
            'gender' => $validated['gender'] ?? 'unspecified',
            'birth_place_text' => $normalized['raw_birth_place_text'],
            'place_id' => $normalized['place_id'],
            'latitude' => $normalized['latitude'],
            'longitude' => $normalized['longitude'],
            'elevation_m' => $normalized['elevation_m'],
            'iana_timezone' => $normalized['iana_timezone'],
            'local_datetime' => $normalized['local_datetime'],
            'utc_at_us' => $utcAtUs,
            'jd_utc' => $normalized['astronomical_time']->utc->value,
            'jd_tt' => $normalized['astronomical_time']->tt->value,
            'delta_t_s' => $normalized['astronomical_time']->deltaTSeconds,
            'accuracy_class' => $validated['accuracy_class'] ?? 'exact',
            'tradition' => $validated['tradition'] ?? 'AMANTA',
        ]);

        return response()->json(
            JyotishResultEnvelope::wrap($profile->toArray(), 'birth_profile_detail_v1'),
            201
        );
    }

    public function show(string $id): JsonResponse
    {
        $profile = BirthProfile::findOrFail($id);

        return response()->json(
            JyotishResultEnvelope::wrap($profile->toArray(), 'birth_profile_detail_v1')
        );
    }
}


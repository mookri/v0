<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Models\ReferralAttribution;
use App\Modules\Customers\Models\ReferralCode;
use App\Platform\Api\CursorPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Referrals, customer side (attribution only — reward rules are out of
 * scope): the caller's code (issued at signup, backfilled on first read),
 * referred count, and a cursor list of referred accounts (masked phones,
 * no names — the referrer gets reachability, not profiles).
 */
final class CustomerReferralController extends CustomerSelfServiceController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        // Backfill: pre-module accounts never went through code issuance.
        $code = ReferralCode::query()->where('user_id', $userId)->first();
        if ($code === null) {
            $code = new ReferralCode();
            $code->forceFill(['user_id' => $userId, 'code' => self::uniqueCode()]);
            $code->save();
        }

        $base = ReferralAttribution::query()->where('referrer_user_id', $userId);
        [$attributions, $page] = CursorPagination::paginate(clone $base, $request);

        $phones = User::query()
            ->whereIn('id', collect($attributions)->map(static fn (ReferralAttribution $a): string => (string) $a->referred_user_id)->all())
            ->pluck('phone', 'id');

        return new JsonResponse([
            'data' => [
                'object' => 'referral_summary',
                'code' => $code->code,
                'referred_count' => (clone $base)->count(),
                'referrals' => array_map(
                    static function (ReferralAttribution $attribution) use ($phones): array {
                        $phone = $phones->get($attribution->referred_user_id);

                        return [
                            'id' => (string) $attribution->getKey(),
                            'phone_masked' => is_string($phone) ? self::maskPhone($phone) : '****',
                            'at' => $attribution->created_at,
                        ];
                    },
                    $attributions
                ),
            ],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    private static function uniqueCode(): string
    {
        for ($attempts = 0; $attempts < 5; $attempts++) {
            $code = ReferralCode::generate();
            if (! ReferralCode::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        return ReferralCode::PREFIX.substr(strtoupper((string) Str::ulid()), 0, 8);
    }
}

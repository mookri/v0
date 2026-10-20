<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Models\User;
use App\Platform\Authz\Http\Middleware\RequiresAcr;
use App\Platform\Identity\Models\MfaStepUp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Step-up surface (SEC-004 §6, doc 06 §7.5): GET renders the TOTP form —
 * or, fail-closed, the enrollment-required notice — and answers the
 * console's freshness probe as JSON so the sensitive-action gate can skip
 * the form while the proof is still fresh. POST shares
 * MfaController::verify: the MfaStepUp row it writes is exactly what the
 * `stepup:2` middleware reads, so form and API verify through one code
 * path. return_to is clamped to same-site paths (open redirects are
 * authorization bugs too).
 */
final class StepUpController
{
    public function show(Request $request): JsonResponse|View|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            // 'auth' middleware guarantees a session user; fail closed anyway.
            return redirect()->route('login');
        }

        $fresh = MfaStepUp::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('verified_at', '>=', gmdate('Y-m-d H:i:s', time() - RequiresAcr::FRESH_MINUTES * 60))
            ->exists();

        if ($request->expectsJson()) {
            return new JsonResponse([
                'data' => [
                    'object' => 'stepup_status',
                    'mfa_enabled' => (bool) $user->mfa_enabled,
                    'fresh' => $fresh,
                ],
                'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
            ]);
        }

        return view('oauth.stepup', [
            'mfaEnabled' => (bool) $user->mfa_enabled,
            'fresh' => $fresh,
            'returnTo' => self::safeReturnTo($request->query('return_to')),
        ]);
    }

    /**
     * Clamp a post-verification return target to a plain site-relative
     * path: anything else (off-site, protocol-relative, backslash
     * smuggling, oversized) collapses to `/`.
     */
    public static function safeReturnTo(mixed $value): string
    {
        if (is_string($value)
            && $value !== ''
            && strlen($value) <= 2048
            && str_starts_with($value, '/')
            && ! str_starts_with($value, '//')
            && ! str_contains($value, '\\')) {
            return $value;
        }

        return '/';
    }
}

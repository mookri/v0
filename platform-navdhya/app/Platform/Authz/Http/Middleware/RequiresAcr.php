<?php

namespace App\Platform\Authz\Http\Middleware;

use App\Models\User;
use App\Platform\Api\CodedHttpException;
use App\Platform\Identity\MachinePrincipal;
use App\Platform\Identity\Models\MfaStepUp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Step-up gate (`requires_acr:2`, SEC-004): sensitive operations demand
 * a fresh MFA proof — a step-up row younger than 15 minutes. Machine
 * principals can never satisfy it (spec); users without enrolled MFA
 * are told to enroll first (v1 has no password re-auth alternative).
 * Denials are 403 `step_up_required` with actionable details, never
 * the generic denial code.
 */
final class RequiresAcr
{
    public const FRESH_MINUTES = 15;

    public function handle(Request $request, Closure $next, string $level = '2'): Response
    {
        if ($level !== '2') {
            throw new CodedHttpException(403, 'step_up_required', 'Unsupported assurance level.');
        }

        $principal = $request->user();

        if ($principal instanceof MachinePrincipal) {
            throw new CodedHttpException(403, 'step_up_required', 'Machine credentials cannot satisfy step-up.');
        }

        if (! $principal instanceof User) {
            throw new CodedHttpException(401, 'unauthenticated', 'Unauthenticated.');
        }

        if (! (bool) $principal->mfa_enabled) {
            throw new CodedHttpException(403, 'step_up_required', 'Multi-factor authentication is required for this action.');
        }

        $fresh = MfaStepUp::query()
            ->where('user_id', $principal->getAuthIdentifier())
            ->where('verified_at', '>=', gmdate('Y-m-d H:i:s', time() - self::FRESH_MINUTES * 60))
            ->exists();

        if (! $fresh) {
            throw new CodedHttpException(403, 'step_up_required', 'Fresh multi-factor verification is required for this action.');
        }

        return $next($request);
    }
}

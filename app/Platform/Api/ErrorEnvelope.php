<?php

namespace App\Platform\Api;

use App\Platform\Authz\Exceptions\AuthorizationDeniedException;
use App\Platform\Authz\Exceptions\SodConflictException;
use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;
use App\Platform\Identity\Otp\OtpProviderRateLimitedException;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Tenancy\Exceptions\MembershipInactiveException;
use App\Platform\Tenancy\Exceptions\TenantHeaderMissingException;
use App\Platform\Tenancy\Exceptions\TenantNotInTokenException;
use App\Platform\Tenancy\Exceptions\TenantSuspendedException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * JSON error envelope renderer (doc 06 §4).
 *
 * All non-2xx API responses use exactly:
 *   { "error": { "code", "message", "details", "request_id" } }
 *
 * `code` comes from the kernel-owned registry (doc 06 §4.2); clients branch
 * on `code`, never on `message`. Blade/admin requests keep the framework's
 * default HTML error rendering — only JSON-expecting or /api requests are
 * enveloped.
 */
final class ErrorEnvelope
{
    public static function render(Request $request, Throwable $e): ?JsonResponse
    {
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return null;
        }

        [$status, $code, $message, $details] = self::classify($request, $e);

        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
                'request_id' => (string) ($request->attributes->get('request_id') ?? ''),
            ],
        ];

        $headers = [];

        if ($e instanceof ThrottleRequestsException) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

            if ($retryAfter !== null) {
                $headers['Retry-After'] = $retryAfter;
            }
        }

        if ($e instanceof HttpExceptionInterface) {
            $headers = array_merge($headers, $e->getHeaders());
        }

        return new JsonResponse($payload, $status, $headers);
    }

    /**
     * @return array{0: int, 1: string, 2: string, 3: array<string, mixed>}
     */
    private static function classify(Request $request, Throwable $e): array
    {
        // Laravel's handler normalizes AuthorizationException to an
        // AccessDeniedHttpException before render callbacks run, keeping
        // the original exception as `previous`; unwrap it so kernel
        // authorization denials keep the stable `authorization_denied`
        // envelope code (doc 06 §4.2).
        if ($e instanceof AccessDeniedHttpException && $e->getPrevious() instanceof Throwable) {
            return self::classify($request, $e->getPrevious());
        }

        if ($e instanceof SodConflictException) {
            return [
                403,
                'sod_conflict',
                $e->getMessage() ?: 'This action violates separation of duties.',
                ['capability' => $e->capability, 'rules' => $e->rules],
            ];
        }

        if ($e instanceof AuthorizationDeniedException) {
            return [
                403,
                'authorization_denied',
                $e->getMessage() ?: 'This action is unauthorized.',
                ['capability' => $e->capability, 'reason' => $e->reason],
            ];
        }

        if ($e instanceof TenantHeaderMissingException) {
            return [400, 'tenant_header_missing', $e->getMessage(), []];
        }

        if ($e instanceof TenantNotInTokenException) {
            return [401, 'tenant_not_in_token', $e->getMessage(), []];
        }

        if ($e instanceof TenantSuspendedException) {
            return [403, 'tenant_suspended', $e->getMessage(), []];
        }

        if ($e instanceof MembershipInactiveException) {
            return [403, 'membership_inactive', $e->getMessage(), []];
        }

        if ($e instanceof InvalidPolicyPayloadException) {
            return [
                422,
                'validation_failed',
                $e->getMessage(),
                ['fields' => [$e->field => [$e->getMessage()]]],
            ];
        }

        if ($e instanceof CodedHttpException) {
            return [
                $e->getStatusCode(),
                $e->envelopeCode,
                $e->getMessage() !== '' ? $e->getMessage() : ResponseMessages::forStatus($e->getStatusCode()),
                [],
            ];
        }

        if ($e instanceof ValidationException) {
            return [
                422,
                'validation_failed',
                'The given data was invalid.',
                ['fields' => $e->errors()],
            ];
        }

        if ($e instanceof AuthenticationException) {
            return [401, 'unauthenticated', $e->getMessage() ?: 'Unauthenticated.', []];
        }

        if ($e instanceof AuthorizationException) {
            return [403, 'permission_missing', $e->getMessage() ?: 'Forbidden.', []];
        }

        if ($e instanceof ModelNotFoundException) {
            // 404 for both "absent" and "outside tenant scope" —
            // no existence leakage (doc 06 §4.2).
            return [404, 'not_found', 'Resource not found.', []];
        }

        if ($e instanceof OtpProviderRateLimitedException) {
            // Provider-side back-off (Google's TOO_MANY_ATTEMPTS family):
            // retryable, so a 429 rather than a dependency outage. The
            // symbolic Google head belongs in the log, not the envelope.
            return [429, 'rate_limited', 'Too many attempts.', []];
        }

        if ($e instanceof OtpProviderUnavailableException) {
            // Fail-closed provider fault. Without this mapping a
            // verify-side outage fell through to a bare 500, contradicting
            // the typed-503 contract recorded in
            // docs/04-security/firebase-phone-otp-spike.md; the send-side
            // controllers keep their own catch for the explicit report().
            return [503, 'dependency_unavailable', 'Verification is temporarily unavailable. Try again shortly.', []];
        }

        if ($e instanceof ThrottleRequestsException) {
            return [429, 'rate_limited', 'Too many requests.', []];
        }

        if ($e instanceof HttpExceptionInterface) {
            return [
                $e->getStatusCode(),
                self::codeForStatus($e->getStatusCode()),
                $e->getMessage() !== '' ? $e->getMessage() : ResponseMessages::forStatus($e->getStatusCode()),
                [],
            ];
        }

        // Unexpected failure: request id is logged server-side; nothing
        // sensitive is exposed (doc 06 §4.2).
        report($e);

        return [500, 'internal_error', 'Server error.', []];
    }

    private static function codeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'permission_missing',
            404 => 'not_found',
            409 => 'conflict',
            412 => 'precondition_failed',
            422 => 'validation_failed',
            429 => 'rate_limited',
            503 => 'service_unavailable',
            default => Str::snake(ResponseMessages::reasonPhrase($status) ?: 'error'),
        };
    }
}

<?php

namespace App\Platform\Api;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Response negotiation for shared admin actions (doc 06 §7.1-7.2): one
 * action method serves both routes. /api/v1 always answers its JSON
 * envelope untouched — status parity across surfaces does not move — while
 * web console form posts answer a redirect back to the referring page with
 * a coded flash message. Thrown exceptions keep the framework's default
 * negotiation (validation redirects carrying $errors, denial pages keeping
 * their real status), because ErrorEnvelope only renders JSON for API
 * requests.
 */
final class WebMutations
{
    /**
     * Run the action (or take its already-built envelope) and negotiate
     * the response: JSON for /api/v1, redirect + flash for web form posts.
     * The closure form lets multi-return actions negotiate once at the
     * seam instead of at every return. Success redirects go back to the
     * referring page unless the caller passes an explicit same-site
     * target — the step-up form returns the user to where they started
     * ($redirectTo). Failures always go back so the form can retry in
     * place with the coded flash_error.
     */
    public static function negotiate(
        Request $request,
        JsonResponse|Closure $json,
        string $webFlash,
        ?string $redirectTo = null,
    ): JsonResponse|RedirectResponse {
        $result = $json instanceof Closure ? $json() : $json;

        if ($request->is('api/*')) {
            return $result;
        }

        if ($result->getStatusCode() >= 400) {
            $payload = $result->getData(true);
            $error = is_array($payload) && isset($payload['error']) && is_array($payload['error'])
                ? $payload['error']
                : [];
            $code = is_string($error['code'] ?? null) ? $error['code'] : 'error';
            $message = is_string($error['message'] ?? null) ? $error['message'] : 'The action failed.';

            return redirect()->back()->with('flash_error', $code.': '.$message);
        }

        $redirect = $redirectTo !== null ? redirect()->to($redirectTo) : redirect()->back();

        return $redirect->with('flash', $webFlash);
    }
}

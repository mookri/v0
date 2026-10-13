<?php

namespace App\Modules\Customers\Auth;

/**
 * Infrastructure, not credentials: the service account is unset/broken or
 * Firebase could not be reached during verification. Surfaces as the
 * typed 503 (dependency_unavailable) envelope — never as a 401 — because
 * a Firebase outage must not look like a wrong token (S48/S50 taxonomy).
 *
 * The message is symbolic-only by construction (exception class + a
 * connectivity/claims category, never the underlying free-text message,
 * never a token, phone, or identity), so report() on this exception
 * cannot leak a credential into the log.
 */
final class FirebaseIdTokenUnavailableException extends \RuntimeException
{
}

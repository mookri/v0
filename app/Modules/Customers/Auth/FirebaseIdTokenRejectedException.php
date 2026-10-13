<?php

namespace App\Modules\Customers\Auth;

/**
 * Fail-closed: Firebase would not vouch for this token (bad signature,
 * expired, revoked or disabled user, missing phone claim, foreign
 * project). The exchange collapses every one of these into the single
 * uniform 401 — the reason never reaches the client, so there is no
 * oracle on token state or on whether an identity exists.
 */
final class FirebaseIdTokenRejectedException extends \RuntimeException
{
}

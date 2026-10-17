<?php

namespace App\Platform\Identity\Federation;

use RuntimeException;

/**
 * A broken federation deployment (ADR-0020): an AUTH_MODE that is
 * neither `direct` nor `federated`, or a `federated` mode asserted
 * without the domain and issuer every app redirect depends on. Rejected
 * at boot — the auth surface must never come up in a state applications
 * cannot reason about.
 */
final class FederationConfigurationException extends RuntimeException
{
}

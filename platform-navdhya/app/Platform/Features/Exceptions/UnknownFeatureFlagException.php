<?php

namespace App\Platform\Features\Exceptions;

use RuntimeException;

/**
 * Thrown when a global default is changed for a code with no
 * `feature_flags` row: defaults are seeded by migration (kind +
 * description are catalogued), never invented by a console typo
 * (doc 07 §10.1). Tenant overrides may target any code — including
 * `module.*` flags declared in module manifests — so they do not
 * raise this.
 */
final class UnknownFeatureFlagException extends RuntimeException
{
    public function __construct(string $code)
    {
        parent::__construct(sprintf(
            'Unknown feature flag "%s"; seed it with a migration before setting a global default.',
            $code,
        ));
    }
}

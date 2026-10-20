<?php

namespace App\Platform\Authz;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Resolves a principal (Authenticatable) from a stored user identifier
 * without the kernel naming a concrete app model (doc 01 §7): the auth
 * user provider is reached through the framework contract only. Used by
 * the explain surface and CLI, which evaluate principals other than the
 * calling user.
 */
final class PrincipalResolver
{
    public function resolveById(int|string $id): ?Authenticatable
    {
        $manager = app('auth');

        if (! $manager instanceof AuthManager) {
            return null;
        }

        return $manager->createUserProvider($this->providerName())?->retrieveById($id);
    }

    /**
     * The user provider behind the default guard. Laravel 11+ skeleton
     * configs no longer declare `auth.defaults.provider`, so the provider
     * name is read from the guard definition itself; when it cannot be
     * resolved the lookup fails closed (null provider -> no principal).
     */
    private function providerName(): ?string
    {
        $guard = config('auth.defaults.guard');
        $provider = is_string($guard) ? config('auth.guards.'.$guard.'.provider') : null;

        if (! is_string($provider)) {
            $provider = config('auth.defaults.provider');
        }

        return is_string($provider) ? $provider : null;
    }
}

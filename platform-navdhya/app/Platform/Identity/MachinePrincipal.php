<?php

namespace App\Platform\Identity;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Machine principal for API-key authentication: key-bound tenant and
 * scopes without impersonating a User row. Code MUST distinguish via
 * instanceof — machine actors have no memberships, no password, no
 * sessions; they carry grants, not identity.
 */
final class MachinePrincipal implements Authenticatable
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private readonly string $keyId,
        private readonly string $tenantId,
        private readonly array $scopes,
        private readonly string $rateTier,
    ) {
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function rateTier(): string
    {
        return $this->rateTier;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return 'key:'.$this->keyId;
    }

    public function getAuthPasswordName(): string
    {
        return 'secret_hash';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }
}

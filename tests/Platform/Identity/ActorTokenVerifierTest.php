<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\ActorTokenVerifier;
use Tests\TestCase;

/**
 * HMAC actor-token verification (frontend token bridge, first half):
 * Laravel verifies tokens minted by the Next.js BFFs, including real
 * frontend-minted vectors. Tampered, expired, and malformed tokens fail
 * closed with null (no oracle between failure modes).
 */
final class ActorTokenVerifierTest extends TestCase
{
    private const SECRET = 'vector-secret';

    private const NOW = '2026-10-01T00:00:00.000Z';

    // Minted by packages/identity mintActorToken (frontend) — cross-
    // implementation fixtures. Regenerate with:
    // npx tsx -e "import {mintActorToken} from '@navdhya/identity/src/application/token-actor'; ..."
    private const SCOPED_VECTOR = 'eyJ2IjoxLCJ1IjoidTkiLCJzIjoic2Vzc185IiwiciI6InBhcnRuZXIiLCJtIjoiaW4iLCJvIjpudWxsLCJlIjoiMjAyNi0xMC0wMVQwMTowMDowMC4wMDBaIn0.rI9iCIInqMLdc6RHKWP5UsIJ7qkanz-eruJJPC5ExT8';

    private const BARE_VECTOR = 'eyJ2IjoxLCJ1IjoidTkiLCJzIjoic2Vzc185IiwiciI6IiIsIm0iOm51bGwsIm8iOm51bGwsImUiOiIyMDI2LTEwLTAxVDAxOjAwOjAwLjAwMFoifQ.gSognpJuD5Kgl5xLiCTzYjFgnZk88YYt5Jgl5ENsRvY';

    public function test_frontend_vectors_verify_with_claims(): void
    {
        $this->assertSame(
            ['user_id' => 'u9', 'session_id' => 'sess_9', 'roles' => ['partner'], 'market' => 'in', 'org_id' => null],
            ActorTokenVerifier::verify(self::SECRET, self::SCOPED_VECTOR, self::NOW)
        );
        $this->assertSame(
            ['user_id' => 'u9', 'session_id' => 'sess_9', 'roles' => [], 'market' => null, 'org_id' => null],
            ActorTokenVerifier::verify(self::SECRET, self::BARE_VECTOR, self::NOW)
        );
    }

    public function test_failures_are_indistinguishable_nulls(): void
    {
        // Tampered payload.
        $this->assertNull(ActorTokenVerifier::verify(self::SECRET, substr_replace(self::SCOPED_VECTOR, 'x', 10, 1), self::NOW));
        // Wrong secret.
        $this->assertNull(ActorTokenVerifier::verify('other-secret', self::SCOPED_VECTOR, self::NOW));
        // Expired.
        $this->assertNull(ActorTokenVerifier::verify(self::SECRET, self::SCOPED_VECTOR, '2026-10-01T02:00:00.000Z'));
        // Malformed shapes.
        $this->assertNull(ActorTokenVerifier::verify(self::SECRET, 'garbage', self::NOW));
        $this->assertNull(ActorTokenVerifier::verify(self::SECRET, 'a.b.c', self::NOW));
        $this->assertNull(ActorTokenVerifier::verify('', self::SCOPED_VECTOR, self::NOW));
        $this->assertNull(ActorTokenVerifier::verify(self::SECRET, '', self::NOW));
    }
}

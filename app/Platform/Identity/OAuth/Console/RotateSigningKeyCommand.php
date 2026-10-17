<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use Illuminate\Console\Command;
use Laravel\Passport\Passport;

/**
 * Rotate the authorization server's token-signing key:
 * `auth:rotate-signing-key [--key-dir=]` (ADR-0020 D3).
 *
 * File-mode only (config/env-managed PEMs are refused — those rotate
 * through deployment, which owns their custody). The drill:
 *
 *  1. archive the CURRENT public half to
 *     `federation.retired_signing_keys_dir/<kid>.pub` BEFORE touching
 *     anything — JWKS keeps serving it, so already-issued tokens keep
 *     verifying during the overlap window;
 *  2. generate a fresh RSA-4096 pair and write it over the key files
 *     (the old private half is destroyed, never archived);
 *  3. record `signing_key.retired` + `signing_key.issued` on the
 *     hash-chained audit chain — rotation is attributable (D3/D5).
 *
 * Keys are read per process: restart queue workers / PHP-FPM after
 * rotating so every signer picks up the new private half. If the audit
 * write fails after the files rotated, the command fails loudly with
 * the exact state — verify the chain, then re-verify the files.
 */
final class RotateSigningKeyCommand extends Command
{
    protected $signature = 'auth:rotate-signing-key
        {--key-dir= : Directory holding oauth-private.key/oauth-public.key (defaults to Passport key path)}';

    protected $description = 'Rotate the token-signing key pair with JWKS overlap (public half archived, rotation audited)';

    public function handle(): int
    {
        if ((string) config('passport.private_key', '') !== ''
            || (string) config('passport.public_key', '') !== ''
        ) {
            $this->error('Keys are config/env-managed (passport.private_key / passport.public_key set): rotate them through deployment, not this command.');

            return self::FAILURE;
        }

        $retiredDir = rtrim((string) config('federation.retired_signing_keys_dir', ''), '/\\');

        if ($retiredDir === '') {
            $this->error('federation.retired_signing_keys_dir is required: rotation without a JWKS overlap window breaks already-issued tokens.');

            return self::FAILURE;
        }

        $keyDir = $this->option('key-dir') !== null && $this->option('key-dir') !== ''
            ? rtrim((string) $this->option('key-dir'), '/\\')
            : dirname(Passport::keyPath('oauth-private.key'));

        $privatePath = $keyDir.DIRECTORY_SEPARATOR.'oauth-private.key';
        $publicPath = $keyDir.DIRECTORY_SEPARATOR.'oauth-public.key';

        if (! is_file($privatePath) || ! is_file($publicPath)) {
            $this->error('No key files in '.$keyDir.' (expected oauth-private.key + oauth-public.key).');

            return self::FAILURE;
        }

        $oldPublic = file_get_contents($publicPath);

        if (! is_string($oldPublic) || $oldPublic === '') {
            $this->error('Current public key is unreadable; aborting before touching anything.');

            return self::FAILURE;
        }

        $oldKid = Jwk::thumbprintFromPem($oldPublic);

        if ($oldKid === null) {
            $this->error('Current public key is not a readable RSA key; aborting before touching anything.');

            return self::FAILURE;
        }

        $pair = RsaKeyGenerator::generate(4096);

        if ($pair === null) {
            $this->error('Failed to generate a new key pair (current keys untouched).');

            return self::FAILURE;
        }

        $newPrivate = $pair['private'];
        $newKid = Jwk::thumbprintFromPem($pair['public']);

        if ($newKid === null) {
            $this->error('Generated key pair failed validation (current keys untouched).');

            return self::FAILURE;
        }

        $newPublic = $pair['public'];

        // Archive the OLD public half first: if this fails, nothing has
        // changed and the overlap story stays intact.
        if (! is_dir($retiredDir) && ! @mkdir($retiredDir, 0755, true) && ! is_dir($retiredDir)) {
            $this->error('Cannot create retired-key directory: '.$retiredDir);

            return self::FAILURE;
        }

        $archivePath = $retiredDir.DIRECTORY_SEPARATOR.$oldKid.'.pub';

        if (file_put_contents($archivePath, $oldPublic) === false) {
            $this->error('Cannot archive the current public key to '.$archivePath.' (current keys untouched).');

            return self::FAILURE;
        }

        // Write the new pair. Private first: no window exists where a
        // verifier sees the new key but the signer still holds the old
        // one (JWKS overlap covers the inverse direction anyway).
        if (file_put_contents($privatePath, $newPrivate) === false) {
            $this->error('Cannot write '.$privatePath.' — public half archived but key files unchanged; resolve and re-run.');

            return self::FAILURE;
        }

        @chmod($privatePath, 0600);

        if (file_put_contents($publicPath, $newPublic) === false) {
            $this->error('Cannot write '.$publicPath.' — private half is NEW while public is stale; re-run the rotation now.');

            return self::FAILURE;
        }

        try {
            $recorder = app(AuthEventRecorder::class);
            $recorder->record([
                'event_type' => 'signing_key.retired',
                'decision' => 'allow',
                'signing_key_id' => $oldKid,
                'reason_code' => 'scheduled_rotation',
            ]);
            $recorder->record([
                'event_type' => 'signing_key.issued',
                'decision' => 'allow',
                'signing_key_id' => $newKid,
                'reason_code' => 'scheduled_rotation',
            ]);
        } catch (\Throwable $e) {
            $this->error('Keys rotated, but the audit write FAILED: '.$e->getMessage());
            $this->line('Verify auth:verify-chain and the key files — rotation is only complete once recorded.');

            return self::FAILURE;
        }

        $this->info('Signing key rotated.');
        $this->line('Retired kid: '.$oldKid.' (public half archived at '.$archivePath.')');
        $this->line('New kid:     '.$newKid);
        $this->line('Restart queue workers / PHP-FPM: signing keys are read per process.');

        return self::SUCCESS;
    }
}

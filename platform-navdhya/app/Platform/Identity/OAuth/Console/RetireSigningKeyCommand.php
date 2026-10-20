<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use Illuminate\Console\Command;

/**
 * `auth:retire-signing-key` (ADR-0020 D3): archive the CURRENT signing
 * public half into the JWKS overlap window — the custody-agnostic half
 * of rotation, for every key source whose swap happens OUTSIDE this
 * repository (AUTH_SIGNING_CUSTODY=kms key versions, deployment-managed
 * inline PEMs). File-mode rotation stays on
 * `auth:rotate-signing-key`, which archives as step one of the swap.
 *
 * Run it BEFORE swapping the custody's key:
 *
 *  1. run this command — the current (old) public half is archived to
 *     `federation.retired_signing_keys_dir/<kid>.pub`; JWKS keeps
 *     serving it, so already-issued tokens keep verifying;
 *  2. swap the custody's key (KMS key version + the AUTH_SIGNING_KMS_*
 *     env, or the deployment-managed PEMs) and restart — signing keys
 *     are read per process;
 *  3. after the overlap window ends, delete the archived `<kid>.pub`
 *     and restart again.
 *
 * Idempotent: a key already archived is reported, never rewritten —
 * the audit row (`signing_key.retired`, attributable per D5) is
 * written only when this command actually archives something.
 */
final class RetireSigningKeyCommand extends Command
{
    protected $signature = 'auth:retire-signing-key';

    protected $description = 'Archive the current signing public key into the JWKS overlap window (external-custody rotation)';

    public function handle(): int
    {
        $retiredDir = rtrim((string) config('federation.retired_signing_keys_dir', ''), '/\\');

        if ($retiredDir === '') {
            $this->error('federation.retired_signing_keys_dir is required: retiring without a JWKS overlap window breaks already-issued tokens.');

            return self::FAILURE;
        }

        try {
            $pem = SigningCustody::resolve()->publicKeyPem();
        } catch (FederationConfigurationException $e) {
            $this->error('Current signing public half is unreadable ('.$e->getMessage().'); aborting before anything changes.');

            return self::FAILURE;
        }

        $kid = Jwk::thumbprintFromPem($pem);

        if ($kid === null) {
            $this->error('Current signing public half is not a readable RSA key; aborting before anything changes.');

            return self::FAILURE;
        }

        if (! is_dir($retiredDir) && ! @mkdir($retiredDir, 0755, true) && ! is_dir($retiredDir)) {
            $this->error('Cannot create retired-key directory: '.$retiredDir);

            return self::FAILURE;
        }

        $archivePath = $retiredDir.DIRECTORY_SEPARATOR.$kid.'.pub';

        if (is_file($archivePath)) {
            $this->info('Current signing key (kid '.$kid.') is already archived at '.$archivePath.' — nothing to do.');
            $this->line('Next: swap the custody to the new key and restart workers.');

            return self::SUCCESS;
        }

        if (file_put_contents($archivePath, $pem) === false) {
            $this->error('Cannot archive the current public key to '.$archivePath.' (nothing else changed).');

            return self::FAILURE;
        }

        try {
            app(AuthEventRecorder::class)->record([
                'event_type' => 'signing_key.retired',
                'decision' => 'allow',
                'signing_key_id' => $kid,
                'reason_code' => 'external_custody_rotation',
            ]);
        } catch (\Throwable $e) {
            $this->error('Public key archived, but the audit write FAILED: '.$e->getMessage());
            $this->line('Verify auth:verify-chain and the archive file — retirement is only complete once recorded.');

            return self::FAILURE;
        }

        $this->info('Signing key retired into the overlap window.');
        $this->line('Archived kid: '.$kid.' ('.$archivePath.')');
        $this->line('Next: swap the custody to the new key (KMS version + AUTH_SIGNING_KMS_* env, or the managed PEMs), restart workers, and delete this file once the overlap window ends.');

        return self::SUCCESS;
    }
}

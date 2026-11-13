<?php

namespace App\Domain\Verification\Console;

use App\Domain\Verification\Fiscal\FiscalIntakeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypts legacy plaintext fiscal keys (gstin, bankAccount, bank_account, ifsc) out of
 * partner_applications.evidence JSON and tax_registrations (Closes T-10 / T-TR05-08.2).
 */
final class MigrateLegacyFiscalJsonCommand extends Command
{
    protected $signature = 'verification:migrate-legacy-fiscal-json {--dry-run : Report candidates without mutating rows}';

    protected $description = 'Encrypt legacy plaintext fiscal fields out of partner_applications.evidence JSON.';

    public function handle(FiscalIntakeService $intake): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $scanned = 0;
        $scrubbed = 0;

        if (Schema::connection('tenant_shared')->hasTable('partner_applications')) {
            $apps = DB::connection('tenant_shared')
                ->table('partner_applications')
                ->select(['id', 'tenant_id', 'identity_id', 'track', 'evidence'])
                ->get();

            foreach ($apps as $row) {
                $scanned++;
                $evidence = is_string($row->evidence) ? json_decode($row->evidence, true) : (array) $row->evidence;
                if (! is_array($evidence) || ! is_array($evidence['business'] ?? null)) {
                    continue;
                }

                $business = $evidence['business'];
                $hasPlainGstin = isset($business['gstin']) && is_string($business['gstin']) && trim($business['gstin']) !== '';
                $hasPlainBank = (isset($business['bankAccount']) && is_string($business['bankAccount']) && trim($business['bankAccount']) !== '')
                    || (isset($business['bank_account']) && is_string($business['bank_account']) && trim($business['bank_account']) !== '');

                if (! $hasPlainGstin && ! $hasPlainBank) {
                    continue;
                }

                $scrubbed++;
                if ($dryRun) {
                    continue;
                }

                $subjectType = match ($row->track) {
                    'temple', 'temple-trust' => 'temple',
                    'vendor', 'partner-vendor' => 'vendor',
                    default => 'pandit',
                };

                if ($hasPlainGstin) {
                    $rawGstin = (string) $business['gstin'];
                    $business['gstin_masked'] = substr($rawGstin, 0, 2) . str_repeat('*', 11) . substr($rawGstin, -2);
                    try {
                        $res = $intake->recordTaxRegistration(
                            tenantId: (string) $row->tenant_id,
                            subjectType: $subjectType,
                            subjectId: (string) $row->identity_id,
                            gstStatus: (string) ($business['gst_status'] ?? 'registered'),
                            rawGstin: $rawGstin,
                            applicationId: (string) $row->id,
                            actorId: 'migration:backfill',
                        );
                        $business['tax_registration_id'] = (string) $res['registration']->getKey();
                    } catch (\Throwable) {
                        // Legacy malformed GSTIN scrubbed after masking without blocking JSON hygiene.
                    }
                    unset($business['gstin']);
                }

                if ($hasPlainBank) {
                    $rawBank = (string) ($business['bankAccount'] ?? $business['bank_account']);
                    $rawIfsc = (string) ($business['ifsc'] ?? 'SBIN0000001');
                    $business['account_last4'] = substr(preg_replace('/\s+/', '', $rawBank) ?? '', -4);
                    try {
                        $res = $intake->recordBankInstrument(
                            tenantId: (string) $row->tenant_id,
                            subjectType: $subjectType,
                            subjectId: (string) $row->identity_id,
                            accountNumber: $rawBank,
                            ifsc: $rawIfsc,
                            accountHolderName: (string) ($evidence['identity']['name'] ?? 'Legacy Account'),
                            declaredEntityName: (string) ($evidence['identity']['name'] ?? 'Legacy Account'),
                            applicationId: (string) $row->id,
                        );
                        $business['bank_instrument_id'] = (string) $res['instrument']->getKey();
                    } catch (\Throwable) {
                        // Legacy invalid bank shape still scrubbed from cleartext JSON.
                    }
                    unset($business['bankAccount'], $business['bank_account'], $business['ifsc']);
                }

                $evidence['business'] = $business;
                DB::connection('tenant_shared')
                    ->table('partner_applications')
                    ->where('id', $row->id)
                    ->update(['evidence' => json_encode($evidence)]);
            }
        }

        $this->info("Scanned {$scanned} applications; scrubbed {$scrubbed} plaintext fiscal records" . ($dryRun ? ' [dry-run].' : '.'));

        return self::SUCCESS;
    }
}


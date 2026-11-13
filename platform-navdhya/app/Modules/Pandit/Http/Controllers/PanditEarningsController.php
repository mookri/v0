<?php

namespace App\Modules\Pandit\Http\Controllers;

use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pandit earnings controller (self-serve, read-only v2 — subledger & assignment accrual).
 */
final class PanditEarningsController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // SELF: Get own earnings summary backed by subledger settlement obligations + payouts
    public function own(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit profile not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $panditId = (string) $profile->getKey();

        $completed = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->where('status', PanditAssignment::STATUS_COMPLETED)
            ->get();

        $pending = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', $panditId)
            ->whereIn('status', [PanditAssignment::STATUS_PENDING, PanditAssignment::STATUS_CONFIRMED])
            ->get();

        $totalEarnings = (int) $completed->sum('compensation_minor');
        $pendingEarnings = (int) $pending->sum('compensation_minor');

        $obligations = [];
        $payouts = [];
        $grossMinor = 0;
        $commissionMinor = 0;
        $taxWithheldMinor = 0;
        $netPayableMinor = 0;
        $paidOutMinor = 0;
        $pendingPayoutMinor = 0;

        $conn = Schema::connection('tenant_shared');
        if ($conn->hasTable('settlement_obligations')) {
            $obligationRows = DB::connection('tenant_shared')
                ->table('settlement_obligations')
                ->where('tenant_id', $tenantId)
                ->where('beneficiary_type', 'PANDIT')
                ->where('beneficiary_id', $panditId)
                ->where('status', '!=', 'CANCELLED')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            foreach ($obligationRows as $row) {
                $grossMinor += (int) $row->gross_amount_minor;
                $commissionMinor += (int) $row->commission_minor;
                $taxWithheldMinor += (int) $row->tax_withheld_minor;
                $netPayableMinor += (int) $row->net_payable_minor;

                $obligations[] = [
                    'id' => (string) $row->id,
                    'booking_id' => $row->booking_id !== null ? (string) $row->booking_id : null,
                    'gross_amount_minor' => (int) $row->gross_amount_minor,
                    'commission_minor' => (int) $row->commission_minor,
                    'tax_withheld_minor' => (int) $row->tax_withheld_minor,
                    'net_payable_minor' => (int) $row->net_payable_minor,
                    'currency' => (string) $row->currency,
                    'status' => (string) $row->status,
                    'created_at' => $row->created_at !== null ? (string) $row->created_at : null,
                ];
            }
        }

        if ($conn->hasTable('payouts')) {
            $payoutRows = DB::connection('tenant_shared')
                ->table('payouts')
                ->where('tenant_id', $tenantId)
                ->where('beneficiary_id', $panditId)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            foreach ($payoutRows as $row) {
                $amount = (int) $row->amount_minor;
                $status = (string) $row->status;
                if ($status === 'SUCCEEDED') {
                    $paidOutMinor += $amount;
                } elseif (in_array($status, ['PENDING_APPROVAL', 'APPROVED', 'PROCESSING'], true)) {
                    $pendingPayoutMinor += $amount;
                }

                $payouts[] = [
                    'id' => (string) $row->id,
                    'amount_minor' => $amount,
                    'currency' => (string) $row->currency,
                    'status' => $status,
                    'payout_reference' => $row->payout_reference !== null ? (string) $row->payout_reference : null,
                    'approved_at' => $row->approved_at !== null ? (string) $row->approved_at : null,
                    'created_at' => $row->created_at !== null ? (string) $row->created_at : null,
                ];
            }
        }

        $hasSubledger = $obligations !== [];
        $headlineNetMinor = $hasSubledger ? $netPayableMinor : $totalEarnings;

        return new JsonResponse([
            'data' => [
                'pandit_profile_id' => $panditId,
                'source' => $hasSubledger ? 'subledger' : 'assignment_accrual',
                'currency' => 'INR',
                'total_earnings_minor' => $totalEarnings,
                'total_earnings_formatted' => $this->formatCurrency($totalEarnings),
                'completed_assignments_count' => $completed->count(),
                'pending_earnings_minor' => $pendingEarnings,
                'pending_earnings_formatted' => $this->formatCurrency($pendingEarnings),
                'pending_assignments_count' => $pending->count(),
                'ledger' => [
                    'gross_amount_minor' => $grossMinor,
                    'commission_minor' => $commissionMinor,
                    'tax_withheld_minor' => $taxWithheldMinor,
                    'net_payable_minor' => $netPayableMinor,
                    'paid_out_minor' => $paidOutMinor,
                    'pending_payout_minor' => $pendingPayoutMinor,
                    'headline_net_minor' => $headlineNetMinor,
                    'headline_net_formatted' => $this->formatCurrency($headlineNetMinor),
                ],
                'obligations' => $obligations,
                'payouts' => $payouts,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function formatCurrency(int $minor): string
    {
        return number_format($minor / 100, 2, '.', ',');
    }
}

<?php

use App\Modules\Onboarding\Http\Controllers\ApplicationController;
use App\Modules\Onboarding\Http\Controllers\ComplianceLifecycleController;
use App\Modules\Onboarding\Http\Controllers\DocumentContentController;
use App\Modules\Onboarding\Http\Controllers\DocumentReviewController;
use App\Modules\Onboarding\Http\Controllers\DraftController;
use App\Modules\Onboarding\Http\Controllers\FiscalVerificationController;
use App\Modules\Onboarding\Http\Controllers\InstitutionVerificationController;
use App\Modules\Onboarding\Http\Controllers\PartnerStatusController;
use App\Modules\Onboarding\Http\Controllers\PrivacyRightsController;
use App\Modules\Onboarding\Http\Controllers\QualificationController;
use App\Modules\Onboarding\Http\Controllers\ReviewController;
use App\Modules\Onboarding\Http\Controllers\SubmitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onboarding Module API Routes (mounted by the registry at
| /api/v1/onboarding/*). Tenant resolution + auth apply; step/submit/
| review/decision surfaces land next with the capability middleware.
*/

Route::post('applications', [ApplicationController::class, 'start'])->name('applications.start');
Route::get('applications/{id}', [ApplicationController::class, 'show'])->name('applications.show');
Route::post('applications/{id}/submit', [SubmitController::class, 'begin'])->name('applications.submit');
Route::post('applications/{id}/submit/resend', [SubmitController::class, 'resend'])->name('applications.submit.resend');
Route::post('applications/{id}/submit/confirm', [SubmitController::class, 'confirm'])->name('applications.confirm');
Route::get('review/queue', [ReviewController::class, 'queue'])
    ->middleware('capability:onboarding:review:view')
    ->name('review.queue');
Route::post('review/{id}:approve', [ReviewController::class, 'approve'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('review.approve');
Route::post('review/{id}:reject', [ReviewController::class, 'reject'])
    ->middleware(['capability:onboarding:review:reject', 'stepup:2'])
    ->name('review.reject');
// request-info is a WRITE (it moves the application back to DRAFT,
// overwrites reviewed_at/reviewer_id and writes a review record), so it
// is gated on a write capability and the same step-up as its sibling
// decision verbs. It used to be gated on the READ capability
// `onboarding:review:view`, which let any queue viewer mutate every
// application in the tenant.
Route::post('review/{id}:request-info', [ReviewController::class, 'requestInfo'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('review.request-info');
Route::post('review/{id}:initiate', [ReviewController::class, 'initiateDecision'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('review.initiate');
Route::post('review/{id}:countersign', [ReviewController::class, 'countersign'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('review.countersign');
Route::post('review/{id}/documents/{docId}:verify', [DocumentReviewController::class, 'verify'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('review.documents.verify');
Route::post('review/{id}/documents/{docId}:reject', [DocumentReviewController::class, 'reject'])
    ->middleware(['capability:onboarding:review:reject', 'stepup:2'])
    ->name('review.documents.reject');
Route::put('applications/{id}/step/{step}', [DraftController::class, 'putStep'])->name('applications.step');
Route::get('applications/{id}/status', [DraftController::class, 'status'])->name('applications.status');
Route::post('applications/{id}/documents', [DraftController::class, 'uploadDocument'])->name('applications.documents');

// Evidence retrieval (SPEC §5 / T-TR01-01): short-lived signed, capability-checked & audited.
Route::get('documents/{id}/access-link', [DocumentContentController::class, 'accessLink'])
    ->middleware(['capability:onboarding:document:access', 'capability:onboarding:review:view'])
    ->name('documents.access-link');
Route::get('documents/{id}/content', [DocumentContentController::class, 'content'])
    ->middleware(['capability:onboarding:document:access', 'capability:onboarding:review:view'])
    ->name('documents.content');

// Partner Data Subject Rights & Consent Management (SPEC §10 / E-TR-06 / Closes T-17).
Route::post('me/privacy/consent', [PrivacyRightsController::class, 'grantConsent'])
    ->middleware('capability:onboarding:consent:manage')
    ->name('privacy.consent.grant');
Route::delete('me/privacy/consent', [PrivacyRightsController::class, 'withdrawConsent'])
    ->middleware('capability:onboarding:consent:manage')
    ->name('privacy.consent.withdraw');
Route::get('me/privacy/access', [PrivacyRightsController::class, 'access'])
    ->middleware('capability:onboarding:subject:request')
    ->name('privacy.dsr.access');
Route::post('me/privacy/correction', [PrivacyRightsController::class, 'correction'])
    ->middleware('capability:onboarding:subject:request')
    ->name('privacy.dsr.correction');
Route::post('me/privacy/erasure', [PrivacyRightsController::class, 'erasure'])
    ->middleware('capability:onboarding:subject:request')
    ->name('privacy.dsr.erasure');

// Qualification & Competency Registry (E-TR-03 / Phase 4)
Route::get('issuers', [QualificationController::class, 'issuers'])
    ->name('issuers.index');

Route::post('credentials', [QualificationController::class, 'registerCredential'])
    ->name('credentials.register');

Route::post('credentials/verify', [QualificationController::class, 'verifyOutreach'])
    ->middleware('capability:onboarding:review:approve')
    ->name('credentials.verify');

Route::post('credentials/{credentialId}/outcome', [QualificationController::class, 'recordOutcome'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('credentials.outcome');

Route::post('competency/assess', [QualificationController::class, 'assessCompetency'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('competency.assess');

Route::post('credentials/{credentialId}/revoke', [QualificationController::class, 'revokeCredential'])
    ->middleware(['capability:onboarding:claim:revoke', 'stepup:2'])
    ->name('credentials.revoke');

// Institutional Existence vs. Representative Authority Graph (E-TR-04 / Phase 5)
Route::get('jurisdictions', [InstitutionVerificationController::class, 'jurisdictions'])
    ->name('jurisdictions.index');

Route::post('institutions/verify', [InstitutionVerificationController::class, 'verify'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('institutions.verify');

Route::post('institutions/{entityId}/approve-check', [InstitutionVerificationController::class, 'checkApprove'])
    ->middleware('capability:onboarding:review:approve')
    ->name('institutions.approve_check');

Route::post('institutions/{entityId}/payout-instrument-check', [InstitutionVerificationController::class, 'checkAttachPayout'])
    ->middleware('capability:onboarding:review:approve')
    ->name('institutions.payout_check');

Route::post('institutions/{entityId}/bank-mandate', [InstitutionVerificationController::class, 'authorizeBankMandate'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('institutions.bank_mandate');

Route::get('institutions/{entityId}/graph', [InstitutionVerificationController::class, 'graph'])
    ->middleware('capability:onboarding:review:view')
    ->name('institutions.graph');

// Fiscal Verification, Indic Name Matching & Bank Instruments (E-TR-05 / Phase 6)
Route::get('fiscal/bank-instruments/{instrumentId}', [FiscalVerificationController::class, 'showInstrument'])
    ->middleware('capability:onboarding:review:view')
    ->name('fiscal.bank.show');

Route::get('fiscal/tax-registrations/{taxId}', [FiscalVerificationController::class, 'showTax'])
    ->middleware('capability:onboarding:review:view')
    ->name('fiscal.tax.show');

Route::post('fiscal/bank-instruments/{instrumentId}:verify', [FiscalVerificationController::class, 'verifyBankInstrument'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('fiscal.bank.verify');

Route::post('fiscal/name-match', [FiscalVerificationController::class, 'matchNames'])
    ->middleware('capability:onboarding:review:view')
    ->name('fiscal.name_match');

Route::post('fiscal/discrepancy-freeze', [FiscalVerificationController::class, 'freezeDiscrepancy'])
    ->middleware(['capability:onboarding:claim:revoke', 'stepup:2'])
    ->name('fiscal.discrepancy_freeze');

// Compliance Lifecycle: Background Checks, AML Gate (G-LEGAL-0), Appeals, Revocations & Metrics (E-TR-07 / Phase 7)
Route::post('appeals', [ComplianceLifecycleController::class, 'submitAppeal'])
    ->middleware('capability:onboarding:appeal:submit')
    ->name('appeals.submit');

Route::get('admin/onboarding/appeals', [ComplianceLifecycleController::class, 'listAppeals'])
    ->middleware('capability:onboarding:review:view')
    ->name('appeals.index');

Route::post('admin/onboarding/appeals/{appealId}/decide', [ComplianceLifecycleController::class, 'decideAppeal'])
    ->middleware(['capability:onboarding:appeal:decide', 'stepup:2'])
    ->name('appeals.decide');

Route::post('admin/onboarding/revocations', [ComplianceLifecycleController::class, 'revokeClaim'])
    ->middleware(['capability:onboarding:claim:revoke', 'stepup:2'])
    ->name('claims.revoke');

Route::post('admin/onboarding/background-checks', [ComplianceLifecycleController::class, 'recordBackgroundCheck'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('background_checks.record');

Route::post('admin/onboarding/background-checks/{checkId}/clarify', [ComplianceLifecycleController::class, 'clarifyBackgroundCheck'])
    ->middleware('capability:onboarding:review:approve')
    ->name('background_checks.clarify');

Route::post('admin/onboarding/sanctions/screen', [ComplianceLifecycleController::class, 'screenSanctions'])
    ->middleware('capability:onboarding:review:view')
    ->name('sanctions.screen');

Route::get('admin/onboarding/reviewer-metrics/{reviewerId}', [ComplianceLifecycleController::class, 'reviewerMetrics'])
    ->middleware('capability:onboarding:review:view')
    ->name('reviewer_metrics.show');

// Partner Transparency, Logged Queue Prioritization & Data-Derived Trust Badges (E-TR-08 / Phase 8)
Route::get('me/onboarding-status', [PartnerStatusController::class, 'show'])
    ->name('me.onboarding_status');

Route::get('admin/onboarding/queue', [PartnerStatusController::class, 'prioritizedQueue'])
    ->middleware('capability:onboarding:review:view')
    ->name('admin.onboarding.queue');

Route::get('partners/{subjectType}/{subjectId}/trust-badge', [PartnerStatusController::class, 'trustBadge'])
    ->name('partners.trust_badge');

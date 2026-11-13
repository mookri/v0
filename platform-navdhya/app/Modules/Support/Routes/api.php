<?php

use App\Modules\Support\Http\Controllers\GrievanceController;
use App\Modules\Support\Http\Controllers\RefundAndGovernanceController;
use App\Modules\Support\Http\Controllers\SafetyCaseController;
use App\Modules\Support\Http\Controllers\SupportCaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Support Module API Routes (/api/v1/support/*)
|--------------------------------------------------------------------------
*/

Route::get('grievance-officer', [GrievanceController::class, 'publicOfficerInfo'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->name('grievance_officer.show');

Route::post('grievances/{id}', [GrievanceController::class, 'promoteToGrievance'])
    ->name('grievances.promote');
Route::get('grievances/{id}/complaint-copy', [GrievanceController::class, 'complaintCopy'])
    ->name('grievances.complaint_copy');

Route::post('admin/grievances/{id}/record-decision', [GrievanceController::class, 'recordDecision'])
    ->middleware('capability:support:grievance:manage')
    ->name('admin.grievances.record_decision');
Route::post('admin/grievances/{id}/nch-referral', [GrievanceController::class, 'nchReferral'])
    ->middleware('capability:support:grievance:manage')
    ->name('admin.grievances.nch_referral');

Route::post('cases', [SupportCaseController::class, 'store'])
    ->name('cases.store');
Route::get('cases', [SupportCaseController::class, 'index'])
    ->name('cases.index');

Route::get('admin/cases', [SupportCaseController::class, 'staffIndex'])
    ->middleware('capability:support:case:view')
    ->name('admin.cases.index');
Route::get('admin/cases/{id}', [SupportCaseController::class, 'staffShow'])
    ->middleware('capability:support:case:view')
    ->name('admin.cases.show');
Route::post('admin/cases/{id}/transition', [SupportCaseController::class, 'transition'])
    ->middleware('capability:support:case:manage')
    ->name('admin.cases.transition');
Route::post('admin/cases/{id}/resolve', [SupportCaseController::class, 'resolve'])
    ->middleware('capability:support:case:resolve')
    ->name('admin.cases.resolve');

Route::get('safety-signposting', [SafetyCaseController::class, 'signposting'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->name('safety.signposting');
Route::post('safety-cases/{id}/messages', [SafetyCaseController::class, 'addConfidentialMessage'])
    ->name('safety.messages.store');
Route::post('safety-cases/{id}/evidence', [SafetyCaseController::class, 'uploadEvidence'])
    ->name('safety.evidence.store');

Route::get('admin/safety-cases', [SafetyCaseController::class, 'index'])
    ->middleware('capability:support:safety_case:access')
    ->name('admin.safety.index');
Route::get('admin/safety-cases/metrics', [SafetyCaseController::class, 'metrics'])
    ->middleware('capability:support:safety_case:access')
    ->name('admin.safety.metrics');
Route::get('admin/safety-cases/{id}', [SafetyCaseController::class, 'show'])
    ->middleware('capability:support:safety_case:access')
    ->name('admin.safety.show');

Route::post('cases/{id}/appeal', [RefundAndGovernanceController::class, 'fileAppeal'])
    ->name('cases.appeal');
Route::post('admin/cases/{id}/refunds', [RefundAndGovernanceController::class, 'requestRefund'])
    ->middleware('capability:support:refund:request')
    ->name('admin.refunds.request');
Route::post('admin/refunds/{refundId}/approve', [RefundAndGovernanceController::class, 'approveRefund'])
    ->middleware('capability:support:refund:request')
    ->name('admin.refunds.approve');
Route::post('admin/appeals/{appealId}/decide', [RefundAndGovernanceController::class, 'decideAppeal'])
    ->middleware('capability:support:appeal:decide')
    ->name('admin.appeals.decide');
Route::get('admin/cases/{id}/dossier', [RefundAndGovernanceController::class, 'dossier'])
    ->middleware('capability:support:case:view')
    ->name('admin.cases.dossier');
Route::post('admin/improvement-plans', [RefundAndGovernanceController::class, 'proposePlan'])
    ->middleware('capability:support:improvement:manage')
    ->name('admin.plans.propose');
Route::post('improvement-plans/{planId}/acknowledge', [RefundAndGovernanceController::class, 'acknowledgePlan'])
    ->name('plans.acknowledge');


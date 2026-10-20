@extends('layouts.vertical', ['title' => 'Explain access', 'subTitle' => 'Administration'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">Explain an authorization decision</h5>
                <div class="align-items-center d-flex font-monospace small text-muted" id="explain-workspace"></div>
            </div>
            <div class="card-body">
                {{-- Read-only consumer of GET /api/v1/authz/explain (SEC-022,
                     T-0K01-02.3): replays the precedence chain for one
                     member + capability, bypassing the decision cache. The
                     form never posts — page JS builds the query through
                     apiGet (session cookie + X-Tenant-Id). Capability
                     platform:authz:explain required; member suggestions
                     additionally need platform:user:view, but a raw
                     membership id always works. --}}
                <div class="alert alert-danger d-none" id="explain-alert" role="alert"></div>
                <p class="text-muted mb-3">
                    Replays the SEC-022 precedence chain for one member and capability. The trace is always current —
                    evaluation bypasses the decision cache. Requires <code>platform:authz:explain</code>.
                </p>

                <form id="explain-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="explain-membership">Member</label>
                            <input class="form-control font-monospace" id="explain-membership" list="explain-members"
                                   placeholder="Membership id" autocomplete="off" required>
                            <datalist id="explain-members"></datalist>
                            <div class="form-text" id="explain-members-hint">Loading member suggestions…</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="explain-capability">Capability</label>
                            <input class="form-control font-monospace" id="explain-capability"
                                   placeholder="sales:order:approve" autocomplete="off" required>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" type="submit">Explain</button>
                        </div>
                    </div>

                    <details class="mt-3">
                        <summary class="text-muted small">Condition attributes (optional)</summary>
                        <div class="row g-3 mt-1">
                            <div class="col-md-2">
                                <label class="form-label" for="explain-amount">amount</label>
                                <input class="form-control" id="explain-amount" inputmode="decimal" autocomplete="off">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="explain-currency">currency</label>
                                <input class="form-control" id="explain-currency" maxlength="3" placeholder="INR"
                                       autocomplete="off">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="explain-org">org</label>
                                <input class="form-control" id="explain-org" maxlength="64" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="explain-time">time</label>
                                <input class="form-control" id="explain-time" placeholder="2026-10-08T12:00:00Z"
                                       autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="explain-status">status</label>
                                <input class="form-control" id="explain-status" maxlength="64" autocomplete="off">
                            </div>
                        </div>
                    </details>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">Decision trace</h5>
            </div>
            <div class="card-body">
                <div class="alert d-none mb-3" id="explain-decision" role="alert"></div>
                <p class="text-muted mb-3" id="explain-meta">Run an explanation to see its chain trace.</p>
                <p class="mb-3 d-none" id="explain-policies"></p>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Stage</th>
                            <th>Outcome</th>
                            <th>Reason</th>
                            <th>Details</th>
                        </tr>
                        </thead>
                        <tbody id="explain-stage-rows">
                        <tr>
                            <td colspan="4" class="text-center text-muted">No trace yet.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <noscript>
                    <div class="alert alert-warning mt-3 mb-0">
                        This tool calls the platform API and needs JavaScript.
                    </div>
                </noscript>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-bottom')
    @vite('resources/js/pages/platform-explain.js')
@endsection

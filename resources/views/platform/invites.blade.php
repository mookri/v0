@extends('layouts.vertical', ['title' => 'Invites', 'subTitle' => 'Administration'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">Issue an invite</h5>
                <div class="align-items-center d-flex font-monospace small text-muted" id="invite-workspace"></div>
            </div>
            <div class="card-body">
                {{-- Coded flash for the shared issue/revoke actions — the
                     one-time acceptance token rides it, escaped by the
                     component (doc 06 §7.2). --}}
                <x-flash-alerts />

                {{-- Issuance shares the API action (doc 06 §7.1): posts
                     /platform/invites — no step-up on either side (the
                     action checks tenant + issuer membership). Page JS
                     splits the roles input to roles[] and drops a blank
                     TTL before submit (presentation concern — server
                     validation never changes). --}}
                <form method="POST" action="{{ url('/platform/invites') }}" id="invite-create-form">
                    @csrf
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label" for="invite-identifier">Identifier (email or phone)</label>
                            <input class="form-control" id="invite-identifier" name="identifier" maxlength="190" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="invite-track">Track</label>
                            <select class="form-select" id="invite-track" name="track">
                                <option value="customer">customer</option>
                                <option value="pandit">pandit</option>
                                <option value="partner-vendor">partner-vendor</option>
                                <option value="partner-individual">partner-individual</option>
                                <option value="staff">staff</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="invite-roles">Roles (comma-separated, blank = none)</label>
                            <input class="form-control" id="invite-roles" name="roles_text" placeholder="support:read, billing:write">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="invite-ttl">TTL hours (blank = 72)</label>
                            <input class="form-control" id="invite-ttl" name="ttl_hours" inputmode="numeric" placeholder="72">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">Issue</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">Invites</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger d-none" id="invite-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Identifier</th>
                            <th>Track</th>
                            <th>Roles</th>
                            <th>State</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="invite-rows"></tbody>
                    </table>
                </div>

                <noscript>
                    <div class="alert alert-warning mt-3 mb-0">
                        This list loads live data from the platform API and needs JavaScript.
                    </div>
                </noscript>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-bottom')
    @vite('resources/js/pages/platform-invites.js')
@endsection

@extends('layouts.vertical', ['title' => 'API Keys', 'subTitle' => 'Administration'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">Issue a machine API key</h5>
                <div class="align-items-center d-flex font-monospace small text-muted" id="api-key-workspace"></div>
            </div>
            <div class="card-body">
                {{-- Coded flash for the shared issue/rotate/revoke actions —
                     the one-time secret rides it, escaped by the component
                     (doc 06 §7.2, §7.5). --}}
                <x-flash-alerts />

                {{-- Issuance shares the API action (doc 06 §7.1): posts
                     /platform/api-keys behind stepup:2. Page JS splits the
                     scopes input into scopes[] and drops a blank TTL before
                     the step-up gate lets the post out (presentation
                     concern — server validation never changes). --}}
                <form method="POST" action="{{ url('/platform/api-keys') }}" id="api-key-create-form">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label" for="api-key-name">Name</label>
                            <input class="form-control" id="api-key-name" name="name" maxlength="120" placeholder="billing-sync" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="api-key-scopes">Scopes (comma-separated, blank = none)</label>
                            <input class="form-control" id="api-key-scopes" name="scopes_text" placeholder="bookings:read, pricing:read">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="api-key-tier">Rate tier</label>
                            <select class="form-select" id="api-key-tier" name="rate_tier">
                                <option value="standard">standard</option>
                                <option value="professional">professional</option>
                                <option value="enterprise">enterprise</option>
                                <option value="partner">partner</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="api-key-ttl">TTL hours (blank = none)</label>
                            <input class="form-control" id="api-key-ttl" name="ttl_hours" inputmode="numeric" placeholder="720">
                        </div>
                        <div class="col-md-1">
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
                <h5 class="card-title mb-0">Keys</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger d-none" id="api-key-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Prefix</th>
                            <th>Scopes</th>
                            <th>Rate tier</th>
                            <th>Expires</th>
                            <th>Last used</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="api-key-rows"></tbody>
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
    @vite('resources/js/pages/platform-api-keys.js')
@endsection

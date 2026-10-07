@extends('layouts.vertical', ['title' => 'Users', 'subTitle' => 'Administration'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="card-title mb-0">Users</h4>
                <span class="badge bg-primary-subtle text-primary" id="user-workspace">&mdash;</span>
            </div>
            <div class="card-body">
                {{-- Coded flash for the shared suspend/activate actions (doc 06 §7.2). --}}
                <x-flash-alerts />
                <div class="alert alert-danger d-none" id="user-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>User status</th>
                            <th>Membership</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="user-rows"></tbody>
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

<div class="row mt-3">
    <div class="col-12">
        <div class="card d-none" id="user-roles-card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">Roles for <span class="text-muted font-monospace" id="user-roles-title"></span></h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="user-roles-cancel">Cancel</button>
            </div>
            <div class="card-body">
                {{-- Assignments read through the shared endpoint; every
                     mutation posts the shared grant/revoke actions behind
                     stepup:2 (doc 06 §7.1, §7.5). JS fills both regions. --}}
                <div id="user-roles-list" class="mb-3">
                    <div class="text-muted">Loading…</div>
                </div>
                <div id="user-roles-grant"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script-bottom')
    @vite('resources/js/pages/platform-users.js')
@endsection

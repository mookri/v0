@extends('layouts.vertical', ['title' => 'Workspaces', 'subTitle' => 'Account'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">My workspaces</h5>
            </div>
            <div class="card-body">
                {{-- Read-only consumer of GET /api/v1/platform/tenants —
                     the discovery surface (TEN-006 §4.3): every membership
                     state is listed here, including non-active ones the
                     tenant-scoped pages refuse, so denials are
                     explainable at a glance. --}}

                <div class="alert alert-danger d-none" id="workspace-alert" role="alert"></div>
                <p class="text-muted">
                    Every tenant you belong to, with membership and tenant state. The console scopes its calls to your
                    default workspace (the <strong>Console default</strong> column).
                </p>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Workspace</th>
                            <th>Slug</th>
                            <th>Membership</th>
                            <th>Tenant status</th>
                            <th>Console default</th>
                        </tr>
                        </thead>
                        <tbody id="workspace-rows"></tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-primary d-none mt-3" id="workspace-load-more">Load more</button>

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
    @vite('resources/js/pages/account-workspaces.js')
@endsection

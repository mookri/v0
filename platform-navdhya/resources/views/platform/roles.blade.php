@extends('layouts.vertical', ['title' => 'Roles', 'subTitle' => 'Administration'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="card-title mb-0">Roles</h4>
                <span class="badge bg-primary-subtle text-primary" id="role-workspace">&mdash;</span>
            </div>
            <div class="card-body">
                <x-flash-alerts />
                <div class="alert alert-danger d-none" id="role-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>State</th>
                            <th>ID</th>
                        </tr>
                        </thead>
                        <tbody id="role-rows"></tbody>
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
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">New role</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ url('/platform/roles') }}">
                    @csrf
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="role-code">Code</label>
                            <input class="form-control font-monospace" id="role-code" name="code" maxlength="64"
                                   pattern="[a-z][a-z0-9_]*" placeholder="ops_manager" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="role-name">Name</label>
                            <input class="form-control" id="role-name" name="name" maxlength="190" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Create role</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script-bottom')
    @vite('resources/js/pages/platform-roles.js')
@endsection

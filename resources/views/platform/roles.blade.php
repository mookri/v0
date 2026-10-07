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

@endsection

@section('script-bottom')
    @vite('resources/js/pages/platform-roles.js')
@endsection

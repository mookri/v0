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

@endsection

@section('script-bottom')
    @vite('resources/js/pages/platform-users.js')
@endsection

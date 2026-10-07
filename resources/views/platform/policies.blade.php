@extends('layouts.vertical', ['title' => 'Policies', 'subTitle' => 'Administration'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="card-title mb-0">Access policies</h4>
                <span class="badge bg-primary-subtle text-primary" id="policy-workspace">&mdash;</span>
            </div>
            <div class="card-body">
                <div class="alert alert-danger d-none" id="policy-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Policy</th>
                            <th>Effect</th>
                            <th>Capability</th>
                            <th class="text-end">Priority</th>
                            <th>Status</th>
                            <th class="text-end">Version</th>
                        </tr>
                        </thead>
                        <tbody id="policy-rows"></tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-primary d-none" id="policy-load-more">Load more</button>
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
    @vite('resources/js/pages/platform-policies.js')
@endsection

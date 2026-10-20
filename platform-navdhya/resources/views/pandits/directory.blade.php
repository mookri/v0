@extends('layouts.vertical', ['title' => 'Pandit Directory', 'subTitle' => 'Pandits'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="card-title mb-0">Pandit directory</h4>
                <span class="badge bg-primary-subtle text-primary" id="pandit-workspace">&mdash;</span>
            </div>
            <div class="card-body">
                <div class="alert alert-danger d-none" id="pandit-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Pandit</th>
                            <th>Specializations</th>
                            <th>Rating</th>
                            <th>Market</th>
                            <th class="text-end">Base rate</th>
                        </tr>
                        </thead>
                        <tbody id="pandit-rows"></tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-primary d-none" id="pandit-load-more">Load more</button>
                </div>

                <noscript>
                    <div class="alert alert-warning mt-3 mb-0">
                        This directory loads live data from the platform API and needs JavaScript.
                    </div>
                </noscript>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script-bottom')
    @vite('resources/js/pages/pandit-directory.js')
@endsection

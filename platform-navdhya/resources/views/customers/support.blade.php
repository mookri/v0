@extends('layouts.vertical', ['title' => 'Support', 'subTitle' => 'Customers'])

@section('content')

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="card-title mb-0">Support queue</h4>
                <span class="badge bg-primary-subtle text-primary" id="support-workspace">&mdash;</span>
            </div>
            <div class="card-body">
                <x-flash-alerts />
                <div class="alert alert-danger d-none" id="support-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Opened</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="support-rows"></tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-primary d-none" id="support-load-more">Load more</button>
                </div>

                <noscript>
                    <div class="alert alert-warning mt-3 mb-0">
                        This queue loads live data from the platform API and needs JavaScript.
                    </div>
                </noscript>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card d-none" id="support-thread-card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0" id="support-thread-title">&mdash;</h5>
                <span class="badge bg-secondary-subtle text-secondary" id="support-thread-status">&mdash;</span>
            </div>
            <div class="card-body">
                <div class="list-group mb-3" id="support-messages"></div>

                {{-- Shared web actions (doc 06 §7.1-7.2): session + CSRF,
                     JS points both forms at the selected ticket. --}}
                <form method="POST" action="" id="support-reply-form">
                    @csrf
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <label class="form-label" for="support-reply-body">Reply</label>
                    <textarea class="form-control" id="support-reply-body" name="body" rows="3" maxlength="5000" required></textarea>
                    <button type="submit" class="btn btn-primary mt-3">Send reply</button>
                </form>

                <form method="POST" action="" id="support-close-form" class="mt-2">
                    @csrf
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <button type="submit" class="btn btn-outline-danger">Close ticket</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script-bottom')
    @vite('resources/js/pages/support-console.js')
@endsection

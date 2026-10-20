@extends('layouts.vertical', ['title' => 'SoD Rules', 'subTitle' => 'Administration'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="card-title mb-0">Separation-of-duty rules</h4>
                <span class="badge bg-primary-subtle text-primary" id="sod-workspace">&mdash;</span>
            </div>
            <div class="card-body">
                <x-flash-alerts />
                <div class="alert alert-danger d-none" id="sod-alert" role="alert"></div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Kind</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="sod-rows"></tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-primary d-none" id="sod-load-more">Load more</button>
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
                <h5 class="card-title mb-0">New SoD rule</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ url('/platform/sod-rules') }}" id="sod-create-form">
                    @csrf
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="sod-code">Code</label>
                            <input class="form-control font-monospace" id="sod-code" name="code" maxlength="64"
                                   pattern="sod\.[a-z][a-z0-9_.]*" placeholder="sod.approver.payer" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="sod-name">Name</label>
                            <input class="form-control" id="sod-name" name="name" maxlength="190" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sod-severity">Severity</label>
                            <select class="form-select" id="sod-severity" name="severity">
                                <option value="block">block</option>
                                <option value="warn">warn</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sod-status">Status</label>
                            <select class="form-select" id="sod-status" name="status">
                                <option value="active">active</option>
                                <option value="disabled">disabled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sod-kind">Kind</label>
                            <select class="form-select" id="sod-kind" name="kind">
                                <option value="conflict">conflict</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sod-role-set">Role set (comma-separated)</label>
                            <input class="form-control font-monospace" id="sod-role-set" name="role_set_text"
                                   placeholder="approver, payer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sod-permission-set">Permission set (comma-separated)</label>
                            <input class="form-control font-monospace" id="sod-permission-set" name="permission_set_text"
                                   placeholder="sales:order:approve">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Create rule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card d-none" id="sod-edit-card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">Edit SoD rule <span class="text-muted font-monospace" id="sod-edit-title"></span></h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="sod-edit-cancel">Cancel</button>
            </div>
            <div class="card-body">
                {{-- Shared PATCH action (plan slice 5, doc 06 §7): code and
                     kind are immutable on update, so they are not editable;
                     a blank set input keeps that set unchanged. --}}
                <form method="POST" action="" id="sod-edit-form">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sod-edit-name">Name</label>
                            <input class="form-control" id="sod-edit-name" name="name" maxlength="190" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="sod-edit-severity">Severity</label>
                            <select class="form-select" id="sod-edit-severity" name="severity">
                                <option value="block">block</option>
                                <option value="warn">warn</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="sod-edit-status">Status</label>
                            <select class="form-select" id="sod-edit-status" name="status">
                                <option value="active">active</option>
                                <option value="disabled">disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sod-edit-role-set">Role set (comma-separated, blank = keep)</label>
                            <input class="form-control font-monospace" id="sod-edit-role-set" name="role_set_text"
                                   placeholder="approver, payer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sod-edit-permission-set">Permission set (comma-separated, blank = keep)</label>
                            <input class="form-control font-monospace" id="sod-edit-permission-set" name="permission_set_text"
                                   placeholder="sales:order:approve">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script-bottom')
    @vite('resources/js/pages/platform-sod-rules.js')
@endsection

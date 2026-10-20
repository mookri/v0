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
                <x-flash-alerts />
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
                            <th>Actions</th>
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

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">New policy</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ url('/platform/policies') }}">
                    @csrf
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="policy-name">Name</label>
                            <input class="form-control" id="policy-name" name="name" maxlength="120" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="policy-effect">Effect</label>
                            <select class="form-select" id="policy-effect" name="effect">
                                <option value="allow">allow</option>
                                <option value="deny">deny</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="policy-priority">Priority</label>
                            <input class="form-control" id="policy-priority" name="priority" type="number" min="-1000" max="1000" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="policy-selector-module">Capability module</label>
                            <input class="form-control font-monospace" id="policy-selector-module" name="capability_selector[module]" placeholder="pandit or *" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="policy-selector-resource">Resource</label>
                            <input class="form-control font-monospace" id="policy-selector-resource" name="capability_selector[resource]" placeholder="profile or *" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="policy-selector-action">Action</label>
                            <input class="form-control font-monospace" id="policy-selector-action" name="capability_selector[action]" placeholder="view or *" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="policy-description">Description</label>
                            <input class="form-control" id="policy-description" name="description">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Create policy</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card d-none" id="policy-edit-card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">Edit policy <span class="text-muted font-monospace" id="policy-edit-title"></span></h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="policy-edit-cancel">Cancel</button>
            </div>
            <div class="card-body">
                {{-- Shared PATCH action (plan slice 5, doc 06 §7): same
                     grammar as create, posted to /platform/policies/{id};
                     JS points the action at the row being edited. --}}
                <form method="POST" action="" id="policy-edit-form">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="tenant_id" data-workspace-field value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="policy-edit-name">Name</label>
                            <input class="form-control" id="policy-edit-name" name="name" maxlength="120" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="policy-edit-effect">Effect</label>
                            <select class="form-select" id="policy-edit-effect" name="effect">
                                <option value="allow">allow</option>
                                <option value="deny">deny</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="policy-edit-priority">Priority</label>
                            <input class="form-control" id="policy-edit-priority" name="priority" type="number" min="-1000" max="1000" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="policy-edit-selector-module">Capability module</label>
                            <input class="form-control font-monospace" id="policy-edit-selector-module" name="capability_selector[module]" placeholder="pandit or *" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="policy-edit-selector-resource">Resource</label>
                            <input class="form-control font-monospace" id="policy-edit-selector-resource" name="capability_selector[resource]" placeholder="profile or *" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="policy-edit-selector-action">Action</label>
                            <input class="form-control font-monospace" id="policy-edit-selector-action" name="capability_selector[action]" placeholder="view or *" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="policy-edit-description">Description</label>
                            <input class="form-control" id="policy-edit-description" name="description">
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
    @vite('resources/js/pages/platform-policies.js')
@endsection
